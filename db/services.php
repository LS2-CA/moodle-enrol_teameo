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
 * Teameo enrolment plugin external functions and service definitions.
 *
 * @package   enrol_teameo
 * @copyright 2024 Teameo.io
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @link      https://teameo.io
 */

defined('MOODLE_INTERNAL') || die();

// Teameo enrol related functions.
$functions = [
    'enrol_teameo_enrol_users' => [
        'classname'   => 'enrol_teameo\external\enrol_users',
        'methodname'  => 'execute',
        'description'  => 'Enrol users with Teameo method',
        'capabilities' => 'enrol/teameo:enrol',
        'type'         => 'write',
        'services'     => ['teameo_enrol_ws', 'teameo_ws'],
    ],

    'enrol_teameo_unenrol_users_teameo' => [
        'classname'   => 'enrol_teameo\external\unenrol_users_teameo',
        'methodname'  => 'execute',
        'description'  => 'Unenrol users by Teameo method',
        'capabilities' => 'enrol/teameo:unenrol',
        'type'         => 'write',
        'services'     => ['teameo_enrol_ws', 'teameo_ws'],
    ],

    'enrol_teameo_unenrol_users' => [
        'classname'   => 'enrol_teameo\external\unenrol_users',
        'methodname'  => 'execute',
        'description'  => 'Unenrol users by Teameo and manual methods',
        'capabilities' => 'enrol/teameo:unenrol',
        'type'         => 'write',
        'services'     => ['teameo_ws'],
    ],

    'enrol_teameo_get_enrolled_users' => [
        'classname'   => 'enrol_teameo\external\get_enrolled_users',
        'methodname'  => 'execute',
        'description'  => 'Get course enrolled users with Teameo method',
        'capabilities' => 'enrol/teameo:config',
        'type'         => 'read',
        'services'     => ['teameo_enrol_ws', 'teameo_ws'],
    ],

    'enrol_teameo_meta_get_instances' => [
        'classname'   => 'enrol_teameo\external\meta_get_instances',
        'methodname'  => 'execute',
        'description'  => 'Get course enrolled users with Teameo method',
        'capabilities' => 'enrol/teameo:config',
        'type'         => 'read',
        'services'     => ['teameo_enrol_ws', 'teameo_ws'],
    ],
];

$services = [
    'Teameo Enrol Integration'  => [
        'functions' => [
            'core_course_create_courses',
            'core_course_delete_courses',
            'core_course_get_categories',
            'core_course_get_courses_by_field',
            'core_course_get_courses',
            'core_course_update_courses',
            'core_enrol_get_users_courses',
            'core_user_create_users',
            'core_user_get_users',
            'core_user_get_users_by_field',
            'core_group_add_group_members',
            'core_group_create_groups',
            'core_group_get_group_members',
            'core_group_delete_group_members',
            'core_group_get_course_groups',
            'core_group_delete_groups',
            'core_course_import_course',
            'core_course_create_categories',
            'core_enrol_get_course_enrolment_methods',
            'mod_assign_get_assignments',
            'mod_assign_list_participants',
            'core_course_search_courses',
            'core_course_get_course_module',
            'core_enrol_get_enrolled_users',
            'core_group_update_groups',
            'core_enrol_get_enrolled_users_with_capability',
            'core_webservice_get_site_info',

            // Course meta link functions.
            'enrol_meta_add_instances',
            'enrol_meta_delete_instances',

            // Teameo custom functions.
            'enrol_teameo_enrol_users',
            'enrol_teameo_unenrol_users_teameo',
            'enrol_teameo_get_enrolled_users',
            'enrol_teameo_meta_get_instances',
        ],
        'enabled' => 1,
        'restrictedusers' => 1,
        'downloadfiles' => 1,
        'uploadfiles' => 1,
        'shortname' => 'teameo_enrol_ws',
    ],
];
