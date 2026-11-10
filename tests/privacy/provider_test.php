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

namespace enrol_teameo\privacy;

use core_privacy\local\metadata\collection;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests for the Teameo enrolment privacy provider.
 *
 * @package   enrol_teameo
 * @category  test
 * @copyright 2026 Teameo.io
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers    \enrol_teameo\privacy\provider
 */
#[CoversClass(provider::class)]
final class provider_test extends \advanced_testcase {
    /**
     * The provider is found by the privacy subsystem and every string it refers to exists.
     */
    public function test_get_metadata(): void {
        $manager = new \core_privacy\manager();
        $this->assertTrue($manager->component_is_compliant('enrol_teameo'));

        $collection = provider::get_metadata(new collection('enrol_teameo'));
        $items = $collection->get_collection();
        $this->assertCount(3, $items);

        $stringmanager = get_string_manager();
        foreach ($items as $item) {
            $this->assertTrue($stringmanager->string_exists($item->get_summary(), 'enrol_teameo'), $item->get_summary());
            foreach ($item->get_privacy_fields() as $identifier) {
                $this->assertTrue($stringmanager->string_exists($identifier, 'enrol_teameo'), $identifier);
            }
        }
    }
}
