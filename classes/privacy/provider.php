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
 * Privacy provider.
 *
 * @package    enrol_teameo
 * @copyright  2023 Teameo.io
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_privacy\local\metadata\collection;
use core_privacy\local\metadata\external_location;

/**
 * Data provider for enrol_Teameo.
 *
 * @copyright  2023 Teameo.io
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\data_provider {

     /**
      * Returns meta data about this system.
      *
      * @param collection $collection The initialised collection to add items to.
      * @return collection A listing of user data stored through this system.
      */
    public static function get_metadata(collection $collection) : collection {
        // Details about reading user profiles.
        $collection->add_data_source('user_profile_data', [
            'userid' => 'privacy:metadata:user_profile_data:userid',
            'email' => 'privacy:metadata:user_profile_data:email',
            'firstname' => 'privacy:metadata:user_profile_data:firstname',
            'lastname' => 'privacy:metadata:user_profile_data:lastname',
            // Include other relevant user profile fields your plugin accesses.
        ])->set_subsystem_link('core_user', 'privacy:metadata:core_user');

        // External system where user records are created.
        $collection->add_external_location_link('external_system_user_creation', [
            'external_userid' => 'privacy:metadata:external_system_user_creation:external_userid',
            'external_email' => 'privacy:metadata:external_system_user_creation:external_email',
            // Include additional fields as per the data sent to the external system.
        ], 'privacy:metadata:external_system_user_creation');

        return $collection;
    }
}


