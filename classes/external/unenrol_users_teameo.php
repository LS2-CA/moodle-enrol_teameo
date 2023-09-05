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
 * External function for unenrolling users from courses.
 *
 * @package    enrol_teameo
 * @copyright  2023 Teameo.io
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class unenrol_users_teameo extends \external_api {

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
                            'userid' => new \external_value(PARAM_INT, 'The user that is going to be unenrolled'),
                            'courseid' => new \external_value(PARAM_INT, 'The course to unenrol the user from'),
                            'roleid' => new \external_value(PARAM_INT, 'The user role', VALUE_OPTIONAL),
                        )
                    )
                )
            )
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
        $params = self::validate_parameters(self::execute_parameters(), array('enrolments' => $enrolments));
        require_once($CFG->libdir . '/enrollib.php');
        $transaction = $DB->start_delegated_transaction(); // Rollback all enrolment if an error occurs.

        $enrol = enrol_get_plugin('teameo');
        if (empty($enrol)) {
            throw new \moodle_exception('teameopluginnotinstalled', 'enrol_teameo');
        }

        foreach ($params['enrolments'] as $enrolment) {
            $context = \context_course::instance($enrolment['courseid']);
            self::validate_context($context);

            $instance = $DB->get_record('enrol', array('courseid' => $enrolment['courseid'], 'enrol' => 'teameo'));
            if ($instance) {
                require_capability('enrol/teameo:unenrol', $context);
            }

            if (!$instance) {
                throw new \moodle_exception('wsnoinstance', 'enrol_teameo', $enrolment);
            }
            $user = $DB->get_record('user', array('id' => $enrolment['userid']));
            if (!$user) {
                throw new \invalid_parameter_exception('User id not exist: ' . $enrolment['userid']);
            }

            if ($instance) {
                $enrol->unenrol_user($instance, $enrolment['userid']);
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
