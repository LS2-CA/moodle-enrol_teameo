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
 * Special Thanks to contributor : Jason Maur <maur.jason@uqam.ca> (Université du Québec à Montréal)
 *
 * @package    enrol_teameo
 * @copyright  2023 Teameo.io
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_teameo\external;

defined('MOODLE_INTERNAL') || die;

global $CFG;

require_once("{$CFG->libdir}/externallib.php");

/**
 * External function for enrolling users to courses.
 *
 * @package    enrol_teameo
 * @copyright  2023 Teameo.io
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrol_users extends \external_api {

    /**
     * Returns description of method parameters.
     *
     * @return \external_function_parameters
     */
    public static function execute_parameters() {
        return new \external_function_parameters(
            array(
                'enrolments' => new \external_multiple_structure(
                    new \external_single_structure(
                        array(
                            'roleid' => new \external_value(PARAM_INT, 'Role to assign to the user'),
                            'userid' => new \external_value(PARAM_INT, 'The user that is going to be enrolled'),
                            'courseid' => new \external_value(PARAM_INT, 'The course to enrol the user role in'),
                            'timestart' => new \external_value(
                                PARAM_INT,
                                'Timestamp when the enrolment start',
                                VALUE_OPTIONAL
                            ),
                            'timeend' => new \external_value(
                                PARAM_INT,
                                'Timestamp when the enrolment end',
                                VALUE_OPTIONAL
                            ),
                            'suspend' => new \external_value(
                                PARAM_INT,
                                'set to 1 to suspend the enrolment',
                                VALUE_OPTIONAL
                            )
                        )
                    )
                )
            )
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
            array('enrolments' => $enrolments)
        );

        $transaction = $DB->start_delegated_transaction(); // Rollback all enrolment if an error occurs
        // (except if the DB doesn't support it).

        // Retrieve the teameo enrolment plugin.
        $enrol = enrol_get_plugin('teameo');
        if (empty($enrol)) {
            throw new \moodle_exception('teameopluginnotinstalled', 'enrol_teameo');
        }

        foreach ($params['enrolments'] as $enrolment) {
            // Ensure the current user is allowed to run this function in the enrolment context.
            $context = \context_course::instance($enrolment['courseid'], IGNORE_MISSING);
            self::validate_context($context);

            // Check that the user has the permission to enrol via Teameo.
            require_capability('enrol/teameo:enrol', $context);

            // Throw an exception if user is not able to assign the role.
            $roles = get_assignable_roles($context);
            if (!array_key_exists($enrolment['roleid'], $roles)) {
                $errorparams = new \stdClass();
                $errorparams->roleid = $enrolment['roleid'];
                $errorparams->courseid = $enrolment['courseid'];
                $errorparams->userid = $enrolment['userid'];
                throw new \moodle_exception('wsusercannotassign', 'enrol_teameo', '', $errorparams);
            }

            // Check manual enrolment plugin instance is enabled/exist.
            $instance = null;
            $enrolinstances = enrol_get_instances($enrolment['courseid'], true);
            foreach ($enrolinstances as $courseenrolinstance) {
                if ($courseenrolinstance->enrol == "teameo") {
                    $instance = $courseenrolinstance;
                    break;
                }
            }

            // No instance found. Check permissions and add automatically if we can.
            if (empty($instance)) {
                if ($enrol->can_add_instance($enrolment['courseid'])) {
                    $course = $DB->get_record('course', array('id' => $enrolment['courseid']));
                    $instanceid = $enrol->add_instance($course);
                    $instance = $DB->get_record('enrol', array('id' => $instanceid));
                } else {
                    $errorparams = new \stdClass();
                    $errorparams->courseid = $enrolment['courseid'];
                    throw new \moodle_exception('wsnoinstance', 'enrol_teameo', '', $errorparams);
                }
            }

            // Finally proceed the enrolment.
            $enrolment['timestart'] = isset($enrolment['timestart']) ? $enrolment['timestart'] : 0;
            $enrolment['timeend'] = isset($enrolment['timeend']) ? $enrolment['timeend'] : 0;
            $enrolment['status'] = (isset($enrolment['suspend']) && !empty($enrolment['suspend'])) ?
                ENROL_USER_SUSPENDED : ENROL_USER_ACTIVE;

            $enrol->enrol_user(
                $instance, $enrolment['userid'], $enrolment['roleid'],
                $enrolment['timestart'], $enrolment['timeend'], $enrolment['status']
            );

        }

        $transaction->allow_commit();
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
