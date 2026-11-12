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
 * Teameo enrolment plugin version specification.
 *
 * @package   enrol_teameo
 * @copyright 2024 Teameo.io
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @link      https://teameo.io
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'enrol_teameo';    // Full name of the plugin (used for diagnostics).
$plugin->version   = 2026111000;        // The current plugin version (Date: YYYYMMDDXX).
$plugin->release   = 'v1.1.0';
$plugin->maturity  = MATURITY_STABLE;
$plugin->requires  = 2024100700;        // Requires Moodle 4.5 (LTS) or later.
$plugin->supported = [405, 503];        // Tested from Moodle 4.5 to 5.3.
