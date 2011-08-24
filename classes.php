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
 * Classes that are used in the Opaque protocol.
 *
 * @package    local
 * @subpackage testopaqueqe
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


/**
 * Implementes the Resource class from the Opaque API.
 *
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_testopaqueqe_resource {
    /** @var mixed the file contents. So, acutally, random bytes, not necessarily a string. */
    public $content;
    /** @var string the file contents encoding. */
    public $encoding = '';
    /** @var string the file name. */
    public $filename;
    /** @var string the file mime type. */
    public $mimeType;

    public static function make_from_file($path, $name, $mimetype, $encoding = '') {
        $resource = new self();
        $resource->content = file_get_contents($path);
        $resource->encoding = $encoding;
        $resource->filename = $name;
        $resource->mimeType = $mimetype;
        return $resource;
    }
}


/**
 * Implementes the Resource class from the Opaque API.
 *
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_testopaqueqe_start_return {
    /** @var string identifies this question session. */
    public $questionSession;
    /** @var string the HTML of the rendered question. */
    public $XHTML;
    /** @var string CSS to include in the page header. */
    public $CSS;
    /** @var string information about the current state of the question. */
    public $progressInfo;
    /** @var array of local_testopaqueqe_resource. */
    public $resources = array();

    /**
     * Constructor.
     * @param string $remoteid the question id
     * @param string $remoteversion the question version
     */
    public function __construct($questionid, $version, $readonly) {
        $this->questionSession = $questionid . '-' . $version;
        if ($readonly) {
            $this->questionSession = 'ro-' . $this->questionSession;
        }
    }

    /**
     * Add a resource.
     * @param local_testopaqueqe_resource $resource the resource to add.
     */
    public function addResource(local_testopaqueqe_resource $resource) {
        $this->resources[] = $resource;
    }
}


/**
 * Implementes the CustomResult class from the Opaque API.
 *
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_testopaqueqe_custom_result {
    /** @var string the result name. */
    public $name;
    /** @var string the result value. */
    public $value;
}


/**
 * Implementes the Resource class from the Opaque API.
 *
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_testopaqueqe_score {
    /** @var string the axis name. Defaults to '', the default axis. */
    public $axis;
    /** @var int the marks on this axis. */
    public $marks;

    public function __construct($marks, $axis = '') {
        $this->marks = $marks;
        $this->axis = $axis;
    }
}


/**
 * Implementes the Resource class from the Opaque API.
 *
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_testopaqueqe_results {
    /** @var string summary of the question that was posed to the student. */
    public $questionLine;
    /** @var string summary of the final answer the student gave. */
    public $answerLine;
    /** @var string summary of the actions the student took to get this far. */
    public $actionSummary;
    /** @var int number of attempts taken to get the answer right. -1 = wrong, -2 = partially right, 0 = pass. */
    public $attempts;
    /** @var array of local_testopaqueqe_score. */
    public $scores = array();
    /** @var array of local_testopaqueqe_custom_result. */
    public $customResults = array();
}


/**
 * Implementes the Resource class from the Opaque API.
 *
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_testopaqueqe_process_return {
    /** @var string the HTML of the rendered question. */
    public $XHTML;
    /** @var string CSS to include in the page header. */
    public $CSS = null;
    /** @var string information about the current state of the question. */
    public $progressInfo;
    /** @var bool whether the question is now ended. */
    public $questionEnd = false;
    /** @var array of local_testopaqueqe_resource. */
    public $resources = array();
    /** @var local_testopaqueqe_results the results, should be null unless questionEnd is true. */
    public $results = null;

    public function addResource(local_testopaqueqe_resource $resource) {
        $this->resources[] = $resource;
    }
}
