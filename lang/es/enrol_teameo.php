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
 * Strings for component 'enrol_teameo', language 'es'.
 *
 * @package    enrol_teameo
 * @copyright  2023 Teameo.io
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Inscripciones Teameo';
$string['pluginname_desc'] = 'El complemento de inscripción de Teameo permite que los usuarios se inscriban a través de la solución de terceros, Teameo, llamando a los servicios web correspondientes.';
$string['privacy:metadata'] = 'El complemento de registro de Teameo no almacena ningún dato personal.';
$string['teameopluginnotinstalled'] = 'El complemento de registro de Teameo no se ha instalado.';
$string['wscannotenrol'] = 'La instancia del complemento no puede inscribir manualmente a un usuario en el course id = {$a->courseid}';
$string['wscannotunenrol'] = 'La instancia del complemento no puede anular manualmente la inscripción de un usuario en el course id = {$a->courseid}';
$string['wsnoinstance'] = 'La instancia del complemento de inscripción de Teameo no existe y no se puede agregar para el curso (id = {$a->courseid})';
$string['wsusercannotassign'] = 'No tienes permiso para asignar este rol. ({$a->roleid}) A esta usuaria ({$a->userid}) en este curso ({$a->courseid}).';

$string['teameo:config'] = 'Configurar instancias de inscripción de Teameo';
$string['teameo:enrol'] = 'Inscribir usuarias';
$string['teameo:unenrol'] = 'Dar de baja a los usuarios del curso';

$string['privacy:metadata:user_profile_data'] = 'Este complemento lee perfiles de usuario de Moodle.';
$string['privacy:metadata:user_profile_data:userid'] = 'El ID del usuario que se está leyendo.';
$string['privacy:metadata:user_profile_data:email'] = 'El correo electrónico del usuario siendo leído.';
$string['privacy:metadata:user_profile_data:firstname'] = 'El primer nombre del usuario.';
$string['privacy:metadata:user_profile_data:lastname'] = 'El apellido del usuario.';
$string['privacy:metadata:core_user'] = 'Se integra con el sistema de usuario principal para leer perfiles de usuario.';

$string['privacy:metadata:external_system_user_creation'] = 'Este complemento envía datos del usuario a un sistema externo para la creación de registros de usuario.';
$string['privacy:metadata:external_system_user_creation:external_userid'] = 'El ID de usuario en el sistema externo.';
$string['privacy:metadata:external_system_user_creation:external_email'] = 'El correo electrónico del usuario en el sistema externo.';


