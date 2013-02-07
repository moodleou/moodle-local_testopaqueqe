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
 * Unit tests for the test Opaque engine.
 *
 * @package    local_testopaqueqe
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/local/testopaqueqe/engine.php');


/**
 * Unit tests for the test Opaque engine.
 *
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class test_local_testopaqueqe_engine extends basic_testcase {
    protected $engine;

    public function setUp() {
        parent::setUp();
        $this->engine = new local_testopaqueqe_engine();
    }

    public function tearDown() {
        $this->engine = null;
        parent::tearDown();
    }

    public function test_get_question_metadata_normal() {
        $this->assertEquals('<questionmetadata>
                     <scoring><marks>3</marks></scoring>
                     <plainmode>no</plainmode>
                 </questionmetadata>',
                $this->engine->getQuestionMetadata('test', '1.0', ''));
    }

    public function test_get_question_metadata_fail() {
        $this->setExpectedException('SoapFault');
        $this->engine->getQuestionMetadata('metadata.fail', '1.0', '');
    }

    public function test_get_question_metadata_slow() {
        $start = microtime(true);
        $this->assertEquals('<questionmetadata>
                     <scoring><marks>3</marks></scoring>
                     <plainmode>no</plainmode>
                 </questionmetadata>',
                $this->engine->getQuestionMetadata('metadata.slow', '0.05', ''));
        $this->assertTrue(microtime(true) - $start > 0.05);
    }

    public function test_start() {
        $startreturn = $this->engine->start('test', '1.0', '', array('randomseed'), array('0'), array());
        $this->assertEquals('test-1.0', $startreturn->questionSession);
    }

    public function test_process() {
        $processreturn = $this->engine->process('test-1.0', array('try'), array('3'));
        $this->assertEquals('Try 3', $processreturn->progressInfo);
    }

    public function test_process_finish_right() {
        $processreturn = $this->engine->process('test-1.0', array('try', 'finish', 'mark'), array('2', 'Finish', '3.00'));
        $this->assertEquals(1, count($processreturn->results->scores));
        $this->assertEquals(3, $processreturn->results->scores[0]->marks);
        $this->assertEquals('', $processreturn->results->scores[0]->axis);
    }

    public function test_stop() {
        // Just verify there are no errors.
        $this->engine->stop('test-1.0');

        // Now do it with an expected failure.
        $this->setExpectedException('SoapFault');
        $this->engine->stop('stop.fail-1.0');
    }
}
