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
 * Strings for component 'enrol_teameo', language 'fr'.
 *
 * @package    enrol_teameo
 * @copyright  2023 Teameo.io
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Inscriptions Teameo';
$string['pluginname_desc'] = 'Le plugin d\'inscriptions Teameo permet aux utilisateurs de s\'inscrire via la solution tierce, Teameo, en appelant les services web appropriés.';
$string['privacy:metadata'] = 'Le plugin d\'inscriptions Teameo ne stocke aucune donnée personnelle.';
$string['teameopluginnotinstalled'] = 'Le plugin d\'inscriptions Teameo n\'a pas été installé.';
$string['wscannotenrol'] = 'L\'instance de plug-in ne peut pas inscrire manuellement un utilisateur dans l\'id de cours = {$a->courseid}';
$string['wscannotunenrol'] = 'L\'instance de plug-in ne peut pas désinscrire manuellement un utilisateur dans l\'id de cours = {$a->courseid}';
$string['wsnoinstance'] = 'L\'instance du plug-in d\'inscriptions Teameo n\'existe pas et ne peut pas être ajoutée pour le cours (id = {$a->courseid})';
$string['wsusercannotassign'] = 'Vous n\'êtes pas autorisé à attribuer ce rôle ({$a->roleid}) à cet utilisateur ({$a->userid}) dans ce cours ({$a->courseid}).';

$string['teameo:config'] = 'Configurer les instances d\'inscriptions Teameo';
$string['teameo:enrol'] = 'Inscrire des utilisateurs';
$string['teameo:unenrol'] = 'Désinscrire des utilisateurs du cours';

$string['privacy:metadata:user_profile_data'] = 'Ce plugin lit les profils utilisateur de Moodle.';
$string['privacy:metadata:user_profile_data:userid'] = 'L\'ID de l\'utilisateur en cours de lecture.';
$string['privacy:metadata:user_profile_data:email'] = 'L\'email de l\'utilisateur en cours de lecture.';
$string['privacy:metadata:user_profile_data:firstname'] = 'Le prénom de l\'utilisateur.';
$string['privacy:metadata:user_profile_data:lastname'] = 'Le nom de famille de l\'utilisateur.';
$string['privacy:metadata:core_user'] = 'S\'intègre au système utilisateur principal pour lire les profils utilisateur.';

$string['privacy:metadata:external_system_user_creation'] = 'Ce plugin envoie les données utilisateur à un système externe pour la création d\'enregistrements utilisateur.';
$string['privacy:metadata:external_system_user_creation:external_userid'] = 'L\'ID utilisateur dans le système externe.';
$string['privacy:metadata:external_system_user_creation:external_email'] = 'L\'e-mail de l\'utilisateur dans le système externe.';

