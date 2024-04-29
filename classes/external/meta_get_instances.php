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
 * External function to get course meta enrolment instances
 *
 * @package   enrol_teameo
 * @copyright 2024 Teameo.io
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @link      https://teameo.io
 */
class meta_get_instances extends \external_api {

    /**
     * Returns description of method parameters.
     *
     * @return \external_function_parameters
     */
    public static function execute_parameters() {
        return new \external_function_parameters(
            [
                'courseid' => new \external_value(PARAM_INT, 'ID of the course with meta enrolment.'),
            ]
        );
    }

     /**
     * Get course meta enrolment instances
     *
     * @param int $courseid  course id
     * @return array An array of child ids
     */
    public static function execute($courseid, $options = []) {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/course/lib.php');

        $params = self::validate_parameters(
            self::execute_parameters(),
            array('courseid' => $courseid)
        );

        $course = $DB->get_record('course', array('id' => $params['courseid']), '*', MUST_EXIST);
        
        // Ensure the current user is allowed to access metacourse.
        $coursecontext = \context_course::instance($courseid, IGNORE_MISSING);
        try {
            self::validate_context($coursecontext);
            require_all_capabilities(['moodle/course:enrolconfig', 'enrol/meta:config'], $coursecontext);
        } catch (\moodle_exception $e) {
            throw new \invalid_parameter_exception('Unauthorized access to course');
        }

        $result = array();
        $enrolinstances = enrol_get_instances($params['courseid'], false);
        foreach ($enrolinstances as $enrolinstance) {
            if ($enrolinstance->enrol == 'meta') {
                $result[] = $enrolinstance->customint1;
            }
        }
        return $result;
    }

    /**
     * Returns description of method result value.
     *
     * @return \external_multiple_structure
     */
    public static function execute_returns() {
        return new \external_multiple_structure(
            new \external_value(PARAM_INT, 'IDs of the courses where meta enrolment is linked to.')
        );
    }
}
