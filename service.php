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
 * SOAP server for the test Opaque question engine.
 *
 * @package    local
 * @subpackage testopaqueqe
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


define('ABORT_AFTER_CONFIG', true);
require_once('../../config.php');
require_once($CFG->dirroot . '/local/testopaqueqe/engine.php');

$server = new SoapServer(dirname(__FILE__) . '/opaque.wsdl', array(
    'actor'        => $CFG->wwwroot . '/local/testopaqueqe/service.php',
    'soap_version' => SOAP_1_1,
    'cache_wsdl'   => WSDL_CACHE_NONE,
    'classmap'     => array(
        'Resource'      => 'local_testopaqueqe_resource',
        'StartReturn'   => 'local_testopaqueqe_start_return',
        'CustomResult'  => 'local_testopaqueqe_custom_result',
        'Score'         => 'local_testopaqueqe_score',
        'Results'       => 'local_testopaqueqe_results',
        'ProcessReturn' => 'local_testopaqueqe_process_return',
    ),
));
$server->setClass('local_testopaqueqe_engine');
$server->handle();
