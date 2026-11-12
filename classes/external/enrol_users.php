<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Teameo enrolment external functions.
 *
 * @package   enrol_teameo
 * @copyright 2024 Teameo.io
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @link      https://teameo.io
 */
namespace enrol_teameo\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * External function for enrolling users to courses.
 *
 * @package   enrol_teameo
 * @copyright 2024 Teameo.io
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @link      https://teameo.io
 */
class enrol_users extends external_api {
    /** @var int Seconds to wait for the lock of a course before giving up. */
    private const LOCK_TIMEOUT = 30;

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters(
            [
                'enrolments' => new external_multiple_structure(
                    new external_single_structure(
                        [
                            'roleid' => new external_value(PARAM_INT, 'Role to assign to the user'),
                            'userid' => new external_value(PARAM_INT, 'The user that is going to be enrolled'),
                            'courseid' => new external_value(PARAM_INT, 'The course to enrol the user role in'),
                            'timestart' => new external_value(
                                PARAM_INT,
                                'Timestamp when the enrolment start',
                                VALUE_OPTIONAL
                            ),
                            'timeend' => new external_value(
                                PARAM_INT,
                                'Timestamp when the enrolment end',
                                VALUE_OPTIONAL
                            ),
                            'suspend' => new external_value(
                                PARAM_INT,
                                'set to 1 to suspend the enrolment',
                                VALUE_OPTIONAL
                            ),
                        ]
                    )
                ),
            ]
        );
    }

    /**
     * Enrolment of users.
     *
     * Function throw an exception at the first error encountered.
     * @param array $enrolments  An array of user enrolment
     */
    public static function execute($enrolments) {
        global $DB, $CFG;

        require_once($CFG->libdir . '/enrollib.php');

        $params = self::validate_parameters(
            self::execute_parameters(),
            ['enrolments' => $enrolments]
        );

        // Retrieve the teameo enrolment plugin.
        $enrol = enrol_get_plugin('teameo');
        if (empty($enrol)) {
            throw new \moodle_exception('teameopluginnotinstalled', 'enrol_teameo');
        }

        // Serialise concurrent requests per course, so two of them cannot both create the enrol instance of a role.
        // Locks are taken in a stable order, before the transaction starts, and released once it is over.
        $courseids = array_unique(array_column($params['enrolments'], 'courseid'));
        sort($courseids);
        $lockfactory = \core\lock\lock_config::get_lock_factory('enrol_teameo');
        $locks = [];
        $transaction = null;

        try {
            foreach ($courseids as $courseid) {
                $lock = $lockfactory->get_lock('course_' . $courseid, self::LOCK_TIMEOUT);
                if (!$lock) {
                    throw new \moodle_exception('wslocktimeout', 'enrol_teameo', '', (object)['courseid' => $courseid]);
                }
                $locks[] = $lock;
            }

            // Rollback all enrolments if an error occurs (except if the DB doesn't support it).
            $transaction = $DB->start_delegated_transaction();

            foreach ($params['enrolments'] as $enrolment) {
                // Ensure the current user is allowed to run this function in the enrolment context.
                $context = \context_course::instance($enrolment['courseid']);
                self::validate_context($context);

                // Check that the user has the permission to enrol via Teameo.
                require_capability('enrol/teameo:enrol', $context);

                // Throw an exception if user is not able to assign the role.
                $userroles = get_assignable_roles($context);
                if (!array_key_exists($enrolment['roleid'], $userroles)) {
                    $errorparams = new \stdClass();
                    $errorparams->roleid = $enrolment['roleid'];
                    $errorparams->courseid = $enrolment['courseid'];
                    $errorparams->userid = $enrolment['userid'];
                    throw new \moodle_exception('wsusercannotassign', 'enrol_teameo', '', $errorparams);
                }

                // Check teameo enrolment plugin instance is enabled/exist.
                $instances = $DB->get_records(
                    'enrol',
                    ['courseid' => $enrolment['courseid'], 'enrol' => 'teameo', 'status' => ENROL_INSTANCE_ENABLED],
                    'sortorder, id'
                );
                $instance = null;

                if (!empty($instances)) {
                    $roles = get_user_roles($context, $enrolment['userid'], false);
                    $roles = array_filter($roles, function ($role) use ($enrolment) {
                        return $role->component === 'enrol_teameo' && $role->roleid == $enrolment['roleid'];
                    });

                    // Try to find the existing instance for the role.
                    foreach ($roles as $role) {
                        if (isset($instances[$role->itemid])) {
                            $instance = $instances[$role->itemid];
                            break;
                        }
                    }

                    if (empty($instance)) {
                        // Try to find instance with the same roleid.
                        foreach ($instances as $inst) {
                            if ($inst->roleid == $enrolment['roleid']) {
                                $instance = $inst;
                                break;
                            }
                        }
                    }
                }

                // No instance found. Check permissions and add automatically if we can.
                if (empty($instance)) {
                    if (
                        $enrol->can_add_instance($enrolment['courseid']) &&
                        !$DB->record_exists('enrol', [
                            'courseid' => $enrolment['courseid'], 'enrol' => 'teameo', 'roleid' => $enrolment['roleid'],
                        ])
                    ) {
                        $course = $DB->get_record('course', ['id' => $enrolment['courseid']], '*', MUST_EXIST);
                        $instancefields = ['roleid' => $enrolment['roleid']];
                        $instanceid = $enrol->add_instance($course, $instancefields);
                        $instance = $DB->get_record('enrol', ['id' => $instanceid], '*', MUST_EXIST);
                    } else {
                        $errorparams = new \stdClass();
                        $errorparams->courseid = $enrolment['courseid'];
                        throw new \moodle_exception('wsnoinstance', 'enrol_teameo', '', $errorparams);
                    }
                }

                // Finally proceed the enrolment.
                $enrol->enrol_user(
                    $instance,
                    $enrolment['userid'],
                    $enrolment['roleid'],
                    $enrolment['timestart'] ?? 0,
                    $enrolment['timeend'] ?? 0,
                    empty($enrolment['suspend']) ? ENROL_USER_ACTIVE : ENROL_USER_SUSPENDED
                );
            }

            $transaction->allow_commit();
        } catch (\Throwable $e) {
            if ($transaction && !$transaction->is_disposed()) {
                // Undo everything done so far, then throw the error again.
                $transaction->rollback($e);
            }
            throw $e;
        } finally {
            foreach ($locks as $lock) {
                $lock->release();
            }
        }
    }

    /**
     * Returns description of method result value.
     *
     * @return null
     */
    public static function execute_returns() {
        return null;
    }
}
