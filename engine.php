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
 * Test Opaque question engine implementation.
 *
 * @package    local
 * @subpackage testopaqueqe
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once($CFG->dirroot . '/local/testopaqueqe/classes.php');


/**
 * Implementes Opaque question engine API.
 *
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_testopaqueqe_engine {
    const MAX_MARK = 3;

    /**
     * Handles actions at the low level.
     * @param string $code currently 'fail' and 'slow' are recognised
     * @param string $delay treated as a number of seconds.
     */
    protected function handle_special($code, $delay) {
        switch ($code) {
            case 'fail':
                throw new SoapFault('1', 'Test opaque engine failing on demand.');

            case 'slow':
                // Make sure PHP does not time-out while we sleep.
                set_time_limit($delay + 10);
                usleep($delay * 1000000);

            default:
                // Do nothing special.
        }
    }

    /**
     * Handle any special actions, as determined by the question id.
     * @param string $questionid questionid. If it start with $method., triggers special actions.
     * @param string $version question verion. In some cases used as a delay in seconds.
     * @param string $method identifies the calling method.
     */
    protected function handle_special_from_questionid($questionid, $version, $method) {
        $len = strlen($method) + 1;

        if (substr($questionid, 0, $len) !== $method . '.') {
            return; // Nother special for this method.
        }

        $this->handle_special(substr($questionid, $len), $version);
    }

    /**
     * Handle any special actions, as determined by the question session id.
     * @param string $sessionid which will be of the form "$questionid-$version".
     * @param string $method identifies the calling method.
     */
    protected function handle_special_from_sessionid($sessionid, $method) {
        if (substr($sessionid, 0, 3) === 'ro-') {
            $sessionid = substr($sessionid, 3);
        }
        list($questionid, $version) = explode('-', $sessionid, 2);
        $this->handle_special_from_questionid($questionid, $version, $method);
    }

    /**
     * Handle any special actions, as determined by the data sumbitted with a process call.
     * @param array $params the POST data for this question.
     */
    protected function handle_special_from_process($params) {
        if (isset($params['fail'])) {
            $this->handle_special('fail', 0);
        } else if (isset($params['slow']) && (float) $params['slow'] > 0) {
            $this->handle_special('slow', (float) $params['slow']);
        }
    }

    /**
     * A dummy implementation of the getEngineInfo method.
     * @return string of XML.
     */
    public function getEngineInfo() {
        return '<engineinfo>
                     <Name>Test Opaque engine</Name>
                     <PHPVersion>' . phpversion() . '</PHPVersion>
                     <MemoryUsage>' . memory_get_usage(true) . '</MemoryUsage>
                     <ActiveSessions>' . 0 . '</ActiveSessions>
                     <working>Yes</working>
                 </engineinfo>';
    }

    /**
     * A dummy implementation of the getQuestionMetadata method.
     * @param string $remoteid the question id
     * @param string $remoteversion the question version
     * @param string $questionbaseurl not used
     * @return string in xml format
     */
    public function getQuestionMetadata($remoteid, $remoteversion, $questionbaseurl) {
        $this->handle_special_from_questionid($remoteid, $remoteversion, 'metadata');

        return '<questionmetadata>
                     <scoring><marks>' . self::MAX_MARK . '</marks></scoring>
                     <plainmode>no</plainmode>
                 </questionmetadata>';
    }

    /**
     * A dummy implementation of the start method.
     *
     * @param string $questionid question id.
     * @param string $questionversion question version.
     * @param string $url not used.
     * @param array $paramNames initialParams names.
     * @param array $paramValues initialParams values.
     * @param array $cachedResources not used.
     * @return local_testopaqueqe_start_return see class documentation.
     */
    function start($questionid, $questionversion, $url, $paramNames, $paramValues, $cachedResources) {
        global $CFG;

        $this->handle_special_from_questionid($questionid, $questionversion, 'start');

        $initparams = array_combine($paramNames, $paramValues);

        $return = new local_testopaqueqe_start_return($questionid, $questionversion,
                !empty($initparams['display_readonly']));

        $return->XHTML = $this->get_html($return->questionSession, 1, $initparams);
        $return->CSS = $this->get_css();
        $return->progressInfo = "Try 1";
        $return->addResource(local_testopaqueqe_resource::make_from_file(
                $CFG->dirroot . '/local/testopaqueqe/pix/world.gif', 'world.gif', 'image/gif'));

        return $return;
    }

    /**
     * returns an object (the structure of the object is taken from an OM question)
     *
     * @param $startresultquestionSession
     * @param $keys
     * @param $values
     * @return object
     */
    function process($questionSession, $names, $values) {
        global $CFG;

        $params = array_combine($names, $values);

        $this->handle_special_from_process($params);

        if (isset($params['try'])) {
            $try = $params['try'];
        } else {
            $try = -666;
        }

        if (isset($params['submit'])) {
            $try += 1;
        }

        $return = new local_testopaqueqe_process_return();
        $return->XHTML = $this->get_html($questionSession, $try, $params);
        // $return->CSS = $this->get_css(); Note that the opaque behaviour can't cope with this.
        $return->progressInfo = 'Try ' . $try;
        $return->addResource(local_testopaqueqe_resource::make_from_file(
                $CFG->dirroot . '/local/testopaqueqe/pix/world.gif', 'world.gif', 'image/gif'));

        if (isset($params['finish'])) {
            $return->questionEnd = true;
            $return->results = new local_testopaqueqe_results();
            $return->results->questionLine = 'Test Opaque question.';
            $return->results->answerLine = 'Finished on demand.';
            $return->results->actionSummary = 'Finished on demand after ' . ($params['try'] - 1) . ' submits.';

            $mark = (float) $params['mark'];
            if ($mark >= self::MAX_MARK) {
                $return->results->attempts = $params['try'];
                $return->results->scores[] = new local_testopaqueqe_score(self::MAX_MARK);
            } else if ($mark <= 0) {
                $return->results->attempts = -1;
                $return->results->scores[] = new local_testopaqueqe_score(0);
            } else {
                $return->results->attempts = -2;
                $return->results->scores[] = new local_testopaqueqe_score($mark);
            }
        }

        if (isset($params['-finish'])) {
            $return->questionEnd = true;
            $return->results = new local_testopaqueqe_results();
            $return->results->questionLine = 'Test Opaque question.';
            $return->results->answerLine = 'Finished by Submit all and finish.';
            $return->results->actionSummary = 'Finished by Submit all and finish. Treating as a pass.';
            $return->results->attempts = 0;
        }

        return $return;
    }

    /**
     * A dummy implementation of the stop method.
     * @param $questionsession the question session id.
     */
    public function stop($questionsession) {
        $this->handle_special_from_sessionid($questionsession, 'stop');
    }

    /**
    * Get the CSS that we use in our return values.
     * @return string CSS code.
    */
    protected function get_css() {
    return '
.que.opaque .formulation .local_testopaqueqe {
    border-radius: 5px 5px 5px 5px;
    background: #E4F1FA;
    padding: 0.5em;

}
.local_testopaqueqe h2 {
    margin: 0 0 10px;
}
.local_testopaqueqe h2 span {
    background: black;
    border-radius: 5px 5px 5px 5px;
    padding: 0 10px;
    line-height: 60px;
    font-size: 50px;
    font-weight: bold;
    color: #CCBB88;
}
.local_testopaqueqe h2 span img {
    vertical-align: bottom;
}
.local_testopaqueqe table th {
    text-align: left;
    padding: 0 0.5em 0 0;
}
.local_testopaqueqe table td {
    padding: 0 0.5em 0 0;
}';
    }

    /**
     * Generate the HTML we will send back in reply to start/process calls.
     * @param array $params to display, and add as hidden form fields.
     * @return string HTML code.
     */
    protected function get_html($sessionid, $try, $submitteddata) {
        global $CFG;

        $disabled = '';
        if (substr($sessionid, 0, 3) === 'ro-') {
            $disabled = 'disabled="disabled" ';
        }

        $hiddendata = array(
            'try' => $try,
        );

        $output = '
<div class="local_testopaqueqe">
<h2><span>Hello <img src="%%RESOURCES%%/world.gif" alt="world" />!</span></h2>
<p>This is the test Opaque engine at ' . $CFG->wwwroot .
    '/local/testopaqueqe/service.php processing question ' .
    $sessionid . ' on try ' . $try . '</p>';

        foreach ($hiddendata as $name => $value) {
            $output .= '<input type="hidden" name="%%IDPREFIX%%' . $name .
                    '" value="' . htmlspecialchars($value) . '" />' . "\n";
        }

        $output .= '
        <h3>Actions</h3>
<p><input type="submit" name="%%IDPREFIX%%submit" value="Submit" ' . $disabled . '/> or
    <input type="submit" name="%%IDPREFIX%%finish" value="Finish" ' . $disabled . '/>
    (with a delay of <input type="text" name="%%IDPREFIX%%slow" value="0.0" size="3" ' .
            $disabled . '/> seconds during processing).
    If finishing assign a mark of <input type="text" name="%%IDPREFIX%%mark" value="' .
            self::MAX_MARK . '.00" size="3" ' . $disabled . '/>.</p>
<p><input type="submit" name="%%IDPREFIX%%fail" value="Throw a SOAP fault" ' . $disabled . '/></p>
<h3>Submitted data</h3>
<table>
<thead>
<tr><th>Name</th><th>Value</th></tr>
</thead>
<tbody>';

        foreach ($submitteddata as $name => $value) {
            $output .= '<tr><th>' . $name . '</td><td>' . htmlspecialchars($value) . "</th></tr>\n";
        }

        $output .= '
</tbody>
</table>
</div>';

        return $output;
    }
}
