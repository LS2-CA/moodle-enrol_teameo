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
 * Teameo enrolment plugin main library file.
 *
 * @package   enrol_teameo
 * @copyright 2024 Teameo.io
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @link      https://teameo.io
 */


/**
 * Teameo enrolment plugin class implementation.
 *
 * @package   enrol_teameo
 * @copyright 2024 Teameo.io
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @link      https://teameo.io
 */
class enrol_teameo_plugin extends enrol_plugin {
    /**
     * Is it possible to hide/show enrol instance via standard UI?
     *
     * @param stdClass $instance
     * @return bool
     */
    public function can_hide_show_instance($instance) {
        $context = context_course::instance($instance->courseid);
        return has_capability('enrol/teameo:config', $context);
    }

    /**
     * Is it possible to delete enrol instance via standard UI?
     *
     * @param stdClass  $instance
     * @return bool
     */
    public function can_delete_instance($instance) {
        $context = context_course::instance($instance->courseid);
        return has_capability('enrol/teameo:config', $context);
    }

    /**
     *
     * Returns true if the current user can add a new instance of enrolment plugin in course.
     *
     * @param int $courseid
     * @return boolean
     */
    public function can_add_instance($courseid) {
        global $DB;

        $context = context_course::instance($courseid, MUST_EXIST);

        if (!has_capability('moodle/course:enrolconfig', $context)) {
            return false;
        }

        if (!has_capability('enrol/teameo:config', $context)) {
            return false;
        }

        // Multiple instances supported - instance per role.

        return true;
    }

    /**
     * Returns localised name of enrol instance
     *
     * @param object $instance (null is accepted too)
     * @return string
     */
    public function get_instance_name($instance) {
        if (empty($instance->name)) {
            $enrol = $this->get_name();
            $name = get_string('pluginname', 'enrol_' . $enrol);
            if ($instance->roleid) {
                // Add role name to the instance name.

                global $DB;
                $rolename = '';
                $role = $DB->get_record('role', ['id' => $instance->roleid]);
                if ($role) {
                    $rolename = role_get_name($role);
                } else {
                    $rolename = 'Role #' . $instance->roleid;
                }

                $name .= ' (' . $rolename . ')';
            }

            return $name;
        } else {
            $context = context_course::instance($instance->courseid);
            return format_string($instance->name, true, ['context' => $context]);
        }
    }
}
