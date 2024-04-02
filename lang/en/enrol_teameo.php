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
 * Strings for component 'enrol_teameo', language 'en'.
 *
 * @package    enrol_teameo
 * @copyright  2023 Teameo.io
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Teameo enrolments';
$string['pluginname_desc'] = 'The Teameo enrolments plugin allows users to be enrolled via the third party solution, Teameo, by calling the appropriate web services.';
$string['privacy:metadata'] = 'The Teameo enrolments plugin does not store any personal data.';
$string['teameopluginnotinstalled'] = 'The Teameo enrolments plugin has not been installed.';
$string['wscannotenrol'] = 'Plugin instance cannot manually enrol a user in the course id = {$a->courseid}';
$string['wscannotunenrol'] = 'Plugin instance cannot manually unenrol a user in the course id = {$a->courseid}';
$string['wsnoinstance'] = 'Teameo enrolments plugin instance doesn\'t exist and cannot be added for the course (id = {$a->courseid})';
$string['wsusercannotassign'] = 'You don\'t have the permission to assign this role ({$a->roleid}) to this user ({$a->userid}) in this course ({$a->courseid}).';

$string['teameo:config'] = 'Configure Teameo enrol instances';
$string['teameo:enrol'] = 'Enrol users';
$string['teameo:unenrol'] = 'Unenrol users from the course';

$string['privacy:metadata:user_profile_data'] = 'This plugin reads user profiles from Moodle.';
$string['privacy:metadata:user_profile_data:userid'] = 'The ID of the user being read.';
$string['privacy:metadata:user_profile_data:email'] = 'The email of the user being read.';
$string['privacy:metadata:user_profile_data:firstname'] = 'The first name of the user.';
$string['privacy:metadata:user_profile_data:lastname'] = 'The last name of the user.';
$string['privacy:metadata:core_user'] = 'Integrates with the core user system to read user profiles.';

$string['privacy:metadata:external_system_user_creation'] = 'This plugin sends user data to an external system for user record creation.';
$string['privacy:metadata:external_system_user_creation:external_userid'] = 'The user ID in the external system.';
$string['privacy:metadata:external_system_user_creation:external_email'] = 'The email of the user in the external system.';



