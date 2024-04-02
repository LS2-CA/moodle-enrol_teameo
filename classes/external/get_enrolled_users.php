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
class get_enrolled_users extends \external_api {

    /**
     * Returns description of method parameters.
     *
     * @return \external_function_parameters
     */
    public static function execute_parameters() {
        return new \external_function_parameters(
            [
                'courseid' => new \external_value(PARAM_INT, 'course id'),
                'options'  => new \external_multiple_structure(
                    new \external_single_structure(
                        [
                            'name'  => new \external_value(PARAM_ALPHANUMEXT, 'option name'),
                            'value' => new \external_value(PARAM_RAW, 'option value')
                        ]
                    ), 'Option names:
                            * withcapability (string) return only users with this capability. This option
                                                requires \'moodle/role:review\' on the course context.
                            * groupid (integer) return only users in this group id. If the course has groups enabled and this param
                                                isn\'t defined, returns all the viewable users.
                                                This option requires \'moodle/site:accessallgroups\' on the course context if the
                                                user doesn\'t belong to the group.
                            * onlyactive (integer) return only users with active enrolments and matching time restrictions.
                                                This option requires \'moodle/course:enrolreview\' on the course context.
                                                Please note that this option can\'t
                                                be used together with onlysuspended (only one can be active).
                            * onlysuspended (integer) return only suspended users. This option requires
                                            \'moodle/course:enrolreview\' on the course context. Please note that this option can\'t
                                                be used together with onlyactive (only one can be active).
                            * userfields (\'string, string, ...\') return only the values of these user fields.
                            * limitfrom (integer) sql limit from.
                            * limitnumber (integer) maximum number of returned users.
                            * sortby (string) sort by id, firstname or lastname. For ordering like the site does, use siteorder.
                            * sortdirection (string) ASC or DESC',
                            VALUE_DEFAULT, [])
            ]
        );
    }

     /**
      * Get course participants details
      *
      * @param int $courseid  course id
      * @param array $options options {
      *                                'name' => option name
      *                                'value' => option value
      *                               }
      * @return array An array of users
      */
    public static function execute($courseid, $options = []) {
        global $CFG, $USER, $DB;

        require_once($CFG->dirroot . '/course/lib.php');
        require_once($CFG->dirroot . "/user/lib.php");

        $params = self::validate_parameters(
            self::execute_parameters(),
            array('courseid' => $courseid, 'options' => $options)
        );

        $withcapability = '';
        $groupid        = 0;
        $onlyactive     = false;
        $onlysuspended  = false;
        $userfields     = [];
        $limitfrom = 0;
        $limitnumber = 0;
        $sortby = 'us.id';
        $sortparams = [];
        $sortdirection = 'ASC';
        foreach ($options as $option) {
            switch ($option['name']) {
                case 'withcapability':
                    $withcapability = $option['value'];
                    break;
                case 'groupid':
                    $groupid = (int)$option['value'];
                    break;
                case 'onlyactive':
                    $onlyactive = !empty($option['value']);
                    break;
                case 'onlysuspended':
                    $onlysuspended = !empty($option['value']);
                    break;
                case 'userfields':
                    $thefields = explode(',', $option['value']);
                    foreach ($thefields as $f) {
                        $userfields[] = clean_param($f, PARAM_ALPHANUMEXT);
                    }
                    break;
                case 'limitfrom' :
                    $limitfrom = clean_param($option['value'], PARAM_INT);
                    break;
                case 'limitnumber' :
                    $limitnumber = clean_param($option['value'], PARAM_INT);
                    break;
                case 'sortby':
                    $sortallowedvalues = ['id', 'firstname', 'lastname', 'siteorder'];
                    if (!in_array($option['value'], $sortallowedvalues)) {
                        throw new \invalid_parameter_exception('Invalid value for sortby parameter (value: ' .
                            $option['value'] . '), allowed values are: ' . implode(',', $sortallowedvalues));
                    }
                    if ($option['value'] == 'siteorder') {
                        list($sortby, $sortparams) = users_order_by_sql('us');
                    } else {
                        $sortby = 'us.' . $option['value'];
                    }
                    break;
                case 'sortdirection':
                    $sortdirection = strtoupper($option['value']);
                    $directionallowedvalues = ['ASC', 'DESC'];
                    if (!in_array($sortdirection, $directionallowedvalues)) {
                        throw new \invalid_parameter_exception('Invalid value for sortdirection parameter
                        (value: ' . $sortdirection . '),' . 'allowed values are: ' . implode(',', $directionallowedvalues));
                    }
                    break;
            }
        }

        $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
        $coursecontext = \context_course::instance($courseid, IGNORE_MISSING);
        if ($courseid == SITEID) {
            $context = \context_system::instance();
        } else {
            $context = $coursecontext;
        }
        try {
            self::validate_context($context);
        } catch (\Exception $e) {
            $exceptionparam = new \stdClass();
            $exceptionparam->message = $e->getMessage();
            $exceptionparam->courseid = $params['courseid'];
            throw new \moodle_exception('errorcoursecontextnotvalid' , 'webservice', '', $exceptionparam);
        }

        course_require_view_participants($context);

        // To overwrite this parameter, you need role:review capability.
        if ($withcapability) {
            require_capability('moodle/role:review', $coursecontext);
        }
        // Need accessallgroups capability if you want to overwrite this option.
        if (!empty($groupid) && !groups_is_member($groupid)) {
            require_capability('moodle/site:accessallgroups', $coursecontext);
        }
        // To overwrite this option, you need course:enrolereview permission.
        if ($onlyactive || $onlysuspended) {
            require_capability('moodle/course:enrolreview', $coursecontext);
        }

        list($enrolledsql, $enrolledparams) = get_enrolled_sql($coursecontext, $withcapability, $groupid, $onlyactive,
        $onlysuspended);
        $ctxselect = ', ' . \context_helper::get_preload_record_columns_sql('ctx');
        $ctxjoin = "LEFT JOIN {context} ctx ON (ctx.instanceid = u.id AND ctx.contextlevel = :contextlevel)";
        $enrolledparams['contextlevel'] = CONTEXT_USER;

        $groupjoin = '';
        if (empty($groupid) && groups_get_course_groupmode($course) == SEPARATEGROUPS &&
                !has_capability('moodle/site:accessallgroups', $coursecontext)) {
            // Filter by groups the user can view.
            $usergroups = groups_get_user_groups($course->id);
            if (!empty($usergroups['0'])) {
                list($groupsql, $groupparams) = $DB->get_in_or_equal($usergroups['0'], SQL_PARAMS_NAMED);
                $groupjoin = "JOIN {groups_members} gm ON (u.id = gm.userid AND gm.groupid $groupsql)";
                $enrolledparams = array_merge($enrolledparams, $groupparams);
            } else {
                // User doesn't belong to any group, so he can't see any user. Return an empty array.
                return [];
            }
        }

        $instance = $DB->get_record('enrol', array('courseid' => $course->id, 'enrol' => 'teameo'));

        $sql = "SELECT us.*, COALESCE(ul.timeaccess, 0) AS lastcourseaccess
                  FROM {user} us
                  JOIN (
                      SELECT DISTINCT u.id $ctxselect
                        FROM {user} u $ctxjoin $groupjoin
                       WHERE u.id IN ($enrolledsql)
                  ) q ON q.id = us.id
                  JOIN {role_assignments} ra ON (ra.userid = us.id AND ra.component = 'enrol_teameo' AND ra.itemid = :enrolid)
                  LEFT JOIN {user_lastaccess} ul ON (ul.userid = us.id AND ul.courseid = :courseid)
                     ORDER BY $sortby $sortdirection";
        $enrolledparams = array_merge($enrolledparams, $sortparams);
        $enrolledparams['courseid'] = $courseid;
        $enrolledparams['enrolid'] = $instance->id;

        $enrolledusers = $DB->get_recordset_sql($sql, $enrolledparams, $limitfrom, $limitnumber);
        $users = [];
        foreach ($enrolledusers as $user) {
            \context_helper::preload_from_record($user);
            if ($userdetails = user_get_user_details($user, $course, $userfields)) {
                $users[] = $userdetails;
            }
        }
        $enrolledusers->close();

        return $users;
    }

    /**
     * Returns description of method result value.
     *
     * @return \external_multiple_structure
     */
    public static function execute_returns() {
        return new \external_multiple_structure(
            new \external_single_structure(
                [
                    'id'    => new \external_value(PARAM_INT, 'ID of the user'),
                    'username'    => new \external_value(PARAM_RAW, 'Username policy is defined in Moodle security config',
                            VALUE_OPTIONAL),
                    'firstname'   => new \external_value(PARAM_NOTAGS, 'The first name(s) of the user', VALUE_OPTIONAL),
                    'lastname'    => new \external_value(PARAM_NOTAGS, 'The family name of the user', VALUE_OPTIONAL),
                    'fullname'    => new \external_value(PARAM_NOTAGS, 'The fullname of the user'),
                    'email'       => new \external_value(PARAM_TEXT, 'An email address - allow email as root@localhost',
                            VALUE_OPTIONAL),
                    'address'     => new \external_value(PARAM_TEXT, 'Postal address', VALUE_OPTIONAL),
                    'phone1'      => new \external_value(PARAM_NOTAGS, 'Phone 1', VALUE_OPTIONAL),
                    'phone2'      => new \external_value(PARAM_NOTAGS, 'Phone 2', VALUE_OPTIONAL),
                    'department'  => new \external_value(PARAM_TEXT, 'department', VALUE_OPTIONAL),
                    'institution' => new \external_value(PARAM_TEXT, 'institution', VALUE_OPTIONAL),
                    'idnumber'    => new \external_value(PARAM_RAW, 'An arbitrary ID code number perhaps from the institution',
                            VALUE_OPTIONAL),
                    'interests'   => new \external_value(PARAM_TEXT, 'user interests (separated by commas)', VALUE_OPTIONAL),
                    'firstaccess' => new \external_value(PARAM_INT, 'first access to the site (0 if never)', VALUE_OPTIONAL),
                    'lastaccess'  => new \external_value(PARAM_INT, 'last access to the site (0 if never)', VALUE_OPTIONAL),
                    'lastcourseaccess'  => new \external_value(PARAM_INT, 'last access to the course (0 if never)', VALUE_OPTIONAL),
                    'description' => new \external_value(PARAM_RAW, 'User profile description', VALUE_OPTIONAL),
                    'descriptionformat' => new \external_format_value('description', VALUE_OPTIONAL),
                    'city'        => new \external_value(PARAM_NOTAGS, 'Home city of the user', VALUE_OPTIONAL),
                    'country'     => new \external_value(PARAM_ALPHA, 'Home country code of the user, such as AU or CZ',
                            VALUE_OPTIONAL),
                    'profileimageurlsmall' => new \external_value(PARAM_URL, 'User image profile URL - small version',
                            VALUE_OPTIONAL),
                    'profileimageurl' => new \external_value(PARAM_URL, 'User image profile URL - big version', VALUE_OPTIONAL),
                    'customfields' => new \external_multiple_structure(
                        new \external_single_structure(
                            [
                                'type'  => new \external_value(PARAM_ALPHANUMEXT,
                                        'The type of the custom field - text field, checkbox...'),
                                'value' => new \external_value(PARAM_RAW, 'The value of the custom field'),
                                'name' => new \external_value(PARAM_RAW, 'The name of the custom field'),
                                'shortname' => new \external_value(PARAM_RAW,
                                        'The shortname of the custom field - to be able to build the field class in the code'),
                            ]
                        ), 'User custom fields (also known as user profil fields)', VALUE_OPTIONAL),
                    'groups' => new \external_multiple_structure(
                        new \external_single_structure(
                            [
                                'id'  => new \external_value(PARAM_INT, 'group id'),
                                'name' => new \external_value(PARAM_RAW, 'group name'),
                                'description' => new \external_value(PARAM_RAW, 'group description'),
                                'descriptionformat' => new \external_format_value('description'),
                            ]
                        ), 'user groups', VALUE_OPTIONAL),
                    'roles' => new \external_multiple_structure(
                        new \external_single_structure(
                            [
                                'roleid'       => new \external_value(PARAM_INT, 'role id'),
                                'name'         => new \external_value(PARAM_RAW, 'role name'),
                                'shortname'    => new \external_value(PARAM_ALPHANUMEXT, 'role shortname'),
                                'sortorder'    => new \external_value(PARAM_INT, 'role sortorder')
                            ]
                        ), 'user roles', VALUE_OPTIONAL),
                    'preferences' => new \external_multiple_structure(
                        new \external_single_structure(
                            [
                                'name'  => new \external_value(PARAM_RAW, 'The name of the preferences'),
                                'value' => new \external_value(PARAM_RAW, 'The value of the custom field'),
                            ]
                    ), 'User preferences', VALUE_OPTIONAL),
                    'enrolledcourses' => new \external_multiple_structure(
                        new \external_single_structure(
                            [
                                'id'  => new \external_value(PARAM_INT, 'Id of the course'),
                                'fullname' => new \external_value(PARAM_RAW, 'Fullname of the course'),
                                'shortname' => new \external_value(PARAM_RAW, 'Shortname of the course')
                            ]
                    ), 'Courses where the user is enrolled - limited by which courses the user is able to see', VALUE_OPTIONAL)
                ]
            )
        );
    }
}
