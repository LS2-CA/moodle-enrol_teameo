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

defined('MOODLE_INTERNAL') || die;

global $CFG;

require_once("{$CFG->libdir}/externallib.php");

/**
 * External function for unenrolling users from courses.
 *
 * @package   enrol_teameo
 * @copyright 2024 Teameo.io
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @link      https://teameo.io
 */
class unenrol_users extends \external_api {
    /**
     * Returns description of method parameters.
     *
     * @return \external_function_parameters
     */
    public static function execute_parameters() {
        return new \external_function_parameters(
            [
                'enrolments' => new \external_multiple_structure(
                    new \external_single_structure(
                        [
                            'userid' => new \external_value(PARAM_INT, 'The user that is going to be unenrolled'),
                            'courseid' => new \external_value(PARAM_INT, 'The course to unenrol the user from'),
                            'roleid' => new \external_value(PARAM_INT, 'The user role', VALUE_OPTIONAL),
                        ]
                    )
                ),
            ]
        );
    }

    /**
     * Unenrolment of users.
     *
     * @param array $enrolments an array of course user and role ids
     * @throws \coding_exception
     * @throws \dml_transaction_exception
     * @throws \invalid_parameter_exception
     * @throws \moodle_exception
     * @throws \required_capability_exception
     * @throws \restricted_context_exception
     */
    public static function execute($enrolments) {
        global $CFG, $DB;
        $params = self::validate_parameters(self::execute_parameters(), ['enrolments' => $enrolments]);
        require_once($CFG->libdir . '/enrollib.php');
        $transaction = $DB->start_delegated_transaction(); // Rollback all enrolment if an error occurs.

        $enrolteameo = enrol_get_plugin('teameo');
        if (empty($enrolteameo)) {
            throw new \moodle_exception('teameopluginnotinstalled', 'enrol_teameo');
        }

        $enrolmanual = enrol_get_plugin('manual');

        foreach ($params['enrolments'] as $enrolment) {
            $context = \context_course::instance($enrolment['courseid']);
            self::validate_context($context);

            $instanceteameos = $DB->get_records('enrol', ['courseid' => $enrolment['courseid'], 'enrol' => 'teameo']);
            if (!empty($instanceteameos) && $enrolment['roleid']) {
                // Filter instances to only those with the roleid.

                $roles = get_user_roles($context, $enrolment['userid'], false);
                $roles = array_filter($roles, function ($role) use ($enrolment) {
                    return $role->component === 'enrol_teameo' && $role->roleid == $enrolment['roleid'];
                });

                $roleinstanceids = array_map(function ($role) {
                    return $role->itemid;
                }, $roles);

                $instanceteameos = array_filter($instanceteameos, function ($instance) use ($roleinstanceids) {
                    return in_array($instance->id, $roleinstanceids);
                });
            }

            if (!empty($instanceteameos)) {
                require_capability('enrol/teameo:unenrol', $context);
            }

            $instancemanual = $DB->get_record('enrol', ['courseid' => $enrolment['courseid'], 'enrol' => 'manual']);
            if ($instancemanual) {
                require_capability('enrol/manual:unenrol', $context);
            }

            if (empty($instanceteameos) && !$instancemanual) {
                throw new \moodle_exception('wsnoinstance', 'enrol_teameo', '', $enrolment);
            }
            $user = $DB->get_record('user', ['id' => $enrolment['userid']]);
            if (!$user) {
                throw new \invalid_parameter_exception('User id not exist: ' . $enrolment['userid']);
            }

            if (!empty($instanceteameos)) {
                foreach ($instanceteameos as $instanceteameo) {
                    $enrolteameo->unenrol_user($instanceteameo, $enrolment['userid']);
                }
            }

            if ($enrolmanual && $instancemanual) {
                $enrolmanual->unenrol_user($instancemanual, $enrolment['userid']);
            }
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
