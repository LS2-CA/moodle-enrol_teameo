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

namespace enrol_teameo;

use core_external\external_api;
use enrol_teameo\external\enrol_users;
use enrol_teameo\external\get_enrolled_users;
use enrol_teameo\external\meta_get_instances;
use enrol_teameo\external\unenrol_users;
use enrol_teameo\external\unenrol_users_teameo;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests for the Teameo enrolment external functions.
 *
 * @package   enrol_teameo
 * @category  test
 * @copyright 2026 Teameo.io
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers    \enrol_teameo\external\enrol_users
 * @covers    \enrol_teameo\external\get_enrolled_users
 * @covers    \enrol_teameo\external\meta_get_instances
 * @covers    \enrol_teameo\external\unenrol_users
 * @covers    \enrol_teameo\external\unenrol_users_teameo
 */
#[CoversClass(enrol_users::class)]
#[CoversClass(get_enrolled_users::class)]
#[CoversClass(meta_get_instances::class)]
#[CoversClass(unenrol_users::class)]
#[CoversClass(unenrol_users_teameo::class)]
final class external_test extends \advanced_testcase {
    /** @var int The id of the student role. */
    private int $studentroleid;

    /** @var int The id of the editing teacher role. */
    private int $teacherroleid;

    /**
     * Enable the plugin and act as an administrator.
     */
    protected function setUp(): void {
        global $DB;

        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();

        \core\plugininfo\enrol::enable_plugin('teameo', 1);

        $this->studentroleid = (int)$DB->get_field('role', 'id', ['shortname' => 'student'], MUST_EXIST);
        $this->teacherroleid = (int)$DB->get_field('role', 'id', ['shortname' => 'editingteacher'], MUST_EXIST);
    }

    /**
     * Return the Teameo enrol instances of a course, indexed by role id.
     *
     * @param int $courseid
     * @return \stdClass[]
     */
    private function get_teameo_instances(int $courseid): array {
        global $DB;

        $instances = [];
        foreach ($DB->get_records('enrol', ['courseid' => $courseid, 'enrol' => 'teameo'], 'id') as $instance) {
            $instances[$instance->roleid] = $instance;
        }
        return $instances;
    }

    /**
     * Enrolling users creates one enrol instance per role and assigns the role with the plugin component.
     */
    public function test_enrol_users_creates_one_instance_per_role(): void {
        global $DB;

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_user();
        $teacher = $generator->create_user();
        $context = \context_course::instance($course->id);

        $result = enrol_users::execute([
            ['roleid' => $this->studentroleid, 'userid' => $student->id, 'courseid' => $course->id],
            ['roleid' => $this->teacherroleid, 'userid' => $teacher->id, 'courseid' => $course->id],
        ]);
        $this->assertNull(external_api::clean_returnvalue(enrol_users::execute_returns(), $result));

        $instances = $this->get_teameo_instances($course->id);
        $this->assertCount(2, $instances);
        $this->assertArrayHasKey($this->studentroleid, $instances);
        $this->assertArrayHasKey($this->teacherroleid, $instances);

        $this->assertTrue(is_enrolled($context, $student->id, '', true));
        $this->assertTrue(is_enrolled($context, $teacher->id, '', true));
        $this->assertTrue($DB->record_exists('role_assignments', [
            'contextid' => $context->id,
            'userid' => $student->id,
            'roleid' => $this->studentroleid,
            'component' => 'enrol_teameo',
            'itemid' => $instances[$this->studentroleid]->id,
        ]));
        $this->assertTrue($DB->record_exists('role_assignments', [
            'contextid' => $context->id,
            'userid' => $teacher->id,
            'roleid' => $this->teacherroleid,
            'component' => 'enrol_teameo',
            'itemid' => $instances[$this->teacherroleid]->id,
        ]));

        // Enrolling again reuses the existing instances.
        enrol_users::execute([
            ['roleid' => $this->studentroleid, 'userid' => $student->id, 'courseid' => $course->id],
            ['roleid' => $this->studentroleid, 'userid' => $teacher->id, 'courseid' => $course->id],
        ]);
        $this->assertCount(2, $this->get_teameo_instances($course->id));
        $this->assertEquals(1, $DB->count_records('user_enrolments', [
            'enrolid' => $instances[$this->studentroleid]->id,
            'userid' => $student->id,
        ]));
    }

    /**
     * The optional dates and the suspend flag are applied to the user enrolment.
     */
    public function test_enrol_users_with_dates_and_suspension(): void {
        global $DB;

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $user = $generator->create_user();
        $timestart = time() - DAYSECS;
        $timeend = time() + DAYSECS;

        enrol_users::execute([[
            'roleid' => $this->studentroleid,
            'userid' => $user->id,
            'courseid' => $course->id,
            'timestart' => $timestart,
            'timeend' => $timeend,
            'suspend' => 1,
        ]]);

        $instance = $this->get_teameo_instances($course->id)[$this->studentroleid];
        $userenrolment = $DB->get_record('user_enrolments', ['enrolid' => $instance->id, 'userid' => $user->id], '*', MUST_EXIST);
        $this->assertEquals(ENROL_USER_SUSPENDED, $userenrolment->status);
        $this->assertEquals($timestart, $userenrolment->timestart);
        $this->assertEquals($timeend, $userenrolment->timeend);
    }

    /**
     * A user without the capability cannot enrol anybody, and nothing is created.
     */
    public function test_enrol_users_requires_capability(): void {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $user = $generator->create_user();
        $caller = $generator->create_user();
        $generator->enrol_user($caller->id, $course->id, 'student');
        $this->setUser($caller);

        try {
            enrol_users::execute([
                ['roleid' => $this->studentroleid, 'userid' => $user->id, 'courseid' => $course->id],
            ]);
            $this->fail('A required_capability_exception was expected.');
        } catch (\required_capability_exception $e) {
            $this->assertEmpty($this->get_teameo_instances($course->id));
            $this->assertFalse(is_enrolled(\context_course::instance($course->id), $user->id));
        }
    }

    /**
     * Unenrolling with a role only removes the enrolment of the instance of that role.
     */
    public function test_unenrol_users_teameo_by_role(): void {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $user = $generator->create_user();
        $context = \context_course::instance($course->id);

        enrol_users::execute([
            ['roleid' => $this->studentroleid, 'userid' => $user->id, 'courseid' => $course->id],
            ['roleid' => $this->teacherroleid, 'userid' => $user->id, 'courseid' => $course->id],
        ]);
        $this->assertCount(2, get_user_roles($context, $user->id, false));

        $result = unenrol_users_teameo::execute([
            ['userid' => $user->id, 'courseid' => $course->id, 'roleid' => $this->teacherroleid],
        ]);
        $this->assertNull(external_api::clean_returnvalue(unenrol_users_teameo::execute_returns(), $result));

        $roles = get_user_roles($context, $user->id, false);
        $this->assertCount(1, $roles);
        $this->assertEquals($this->studentroleid, reset($roles)->roleid);
        $this->assertTrue(is_enrolled($context, $user->id));
    }

    /**
     * Unenrolling without a role removes the user from every Teameo instance of the course.
     */
    public function test_unenrol_users_teameo_without_role(): void {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $user = $generator->create_user();
        $context = \context_course::instance($course->id);

        enrol_users::execute([
            ['roleid' => $this->studentroleid, 'userid' => $user->id, 'courseid' => $course->id],
            ['roleid' => $this->teacherroleid, 'userid' => $user->id, 'courseid' => $course->id],
        ]);

        unenrol_users_teameo::execute([['userid' => $user->id, 'courseid' => $course->id]]);

        $this->assertFalse(is_enrolled($context, $user->id));
        $this->assertEmpty(get_user_roles($context, $user->id, false));
    }

    /**
     * Unenrolling from a course without any Teameo instance is an error.
     */
    public function test_unenrol_users_teameo_without_instance(): void {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $user = $generator->create_user();

        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage(get_string('wsnoinstance', 'enrol_teameo', ['courseid' => $course->id]));
        unenrol_users_teameo::execute([['userid' => $user->id, 'courseid' => $course->id]]);
    }

    /**
     * The combined function removes both the Teameo and the manual enrolments.
     */
    public function test_unenrol_users_removes_teameo_and_manual_enrolments(): void {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $teameouser = $generator->create_user();
        $manualuser = $generator->create_user();
        $context = \context_course::instance($course->id);

        enrol_users::execute([
            ['roleid' => $this->studentroleid, 'userid' => $teameouser->id, 'courseid' => $course->id],
        ]);
        $generator->enrol_user($manualuser->id, $course->id, 'student', 'manual');
        $this->assertTrue(is_enrolled($context, $teameouser->id));
        $this->assertTrue(is_enrolled($context, $manualuser->id));

        $result = unenrol_users::execute([
            ['userid' => $teameouser->id, 'courseid' => $course->id],
            ['userid' => $manualuser->id, 'courseid' => $course->id],
        ]);
        $this->assertNull(external_api::clean_returnvalue(unenrol_users::execute_returns(), $result));

        $this->assertFalse(is_enrolled($context, $teameouser->id));
        $this->assertFalse(is_enrolled($context, $manualuser->id));
    }

    /**
     * Only the users enrolled with the Teameo method are returned, with the role of their instance.
     */
    public function test_get_enrolled_users(): void {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_user();
        $teacher = $generator->create_user();
        $manualuser = $generator->create_user();

        enrol_users::execute([
            ['roleid' => $this->studentroleid, 'userid' => $student->id, 'courseid' => $course->id],
            ['roleid' => $this->teacherroleid, 'userid' => $teacher->id, 'courseid' => $course->id],
        ]);
        $generator->enrol_user($manualuser->id, $course->id, 'student', 'manual');

        $result = get_enrolled_users::execute($course->id);
        $result = external_api::clean_returnvalue(get_enrolled_users::execute_returns(), $result);

        $rolesbyuser = [];
        foreach ($result as $user) {
            $rolesbyuser[$user['id']] = array_column($user['roles'], 'roleid');
        }
        ksort($rolesbyuser);
        $expected = [
            $student->id => [$this->studentroleid],
            $teacher->id => [$this->teacherroleid],
        ];
        ksort($expected);
        $this->assertEquals($expected, $rolesbyuser);

        // Options are validated and applied.
        $result = get_enrolled_users::execute($course->id, [
            ['name' => 'userfields', 'value' => 'id,email'],
            ['name' => 'sortby', 'value' => 'id'],
            ['name' => 'sortdirection', 'value' => 'desc'],
        ]);
        $result = external_api::clean_returnvalue(get_enrolled_users::execute_returns(), $result);
        $this->assertCount(2, $result);
        foreach ($result as $user) {
            $this->assertArrayHasKey('email', $user);
            $this->assertArrayNotHasKey('roles', $user);
        }

        $this->expectException(\invalid_parameter_exception::class);
        get_enrolled_users::execute($course->id, [['name' => 'sortby', 'value' => 'password']]);
    }

    /**
     * The courses linked with a course meta link are returned.
     */
    public function test_meta_get_instances(): void {
        $generator = $this->getDataGenerator();
        $metacourse = $generator->create_course();
        $child1 = $generator->create_course();
        $child2 = $generator->create_course();

        $result = meta_get_instances::execute($metacourse->id);
        $this->assertSame([], external_api::clean_returnvalue(meta_get_instances::execute_returns(), $result));

        $metaplugin = enrol_get_plugin('meta');
        $metaplugin->add_instance($metacourse, ['customint1' => $child1->id]);
        $metaplugin->add_instance($metacourse, ['customint1' => $child2->id]);

        $result = meta_get_instances::execute($metacourse->id);
        $result = external_api::clean_returnvalue(meta_get_instances::execute_returns(), $result);
        sort($result);
        $this->assertEquals([$child1->id, $child2->id], $result);
    }

    /**
     * A user who cannot configure meta links gets an error.
     */
    public function test_meta_get_instances_requires_capability(): void {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $caller = $generator->create_user();
        $generator->enrol_user($caller->id, $course->id, 'student');
        $this->setUser($caller);

        $this->expectException(\invalid_parameter_exception::class);
        meta_get_instances::execute($course->id);
    }
}
