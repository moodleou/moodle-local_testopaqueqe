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
 * Classes that implement the test Opaque question engine.
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
    public function __construct($questionid, $version) {
        $this->questionSession = $questionid . '|' . $version;
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
    public $axis = '';
    /** @var int the marks on this axis. */
    public $marks;
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
    public $CSS;
    /** @var string information about the current state of the question. */
    public $progressInfo;
    /** @var bool whether the question is now ended. */
    public $questionEnd;
    /** @var array of local_testopaqueqe_resource. */
    public $resources;
    /** @var local_testopaqueqe_results the results, should be null unless questionEnd is true. */
    public $results = null;
}


/**
 * Implementes the methods from the Opaque API.
 *
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_testopaqueqe_engine {

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
     * @param string $sessionid which will be of the form "$questionid|$version".
     * @param string $method identifies the calling method.
     */
    protected function handle_special_from_sessionid($sessionid, $method) {
        list($questionid, $version) = explode('|', $sessionid, 2);
        $this->handle_special_from_questionid($questionid, $version, $method);
    }

    /**
     * Generate the HTML we will send back in reply to start/process calls.
     * @param array $params to display, and add as hidden form fields.
     * @return string HTML code.
     */
    protected function get_html($params) {
        return '
<div class="forumlation">
<p class="local_testopaqueqe_engine_greeting">Hello <img src="%%RESOURCES%%/world.gif" alt="world" />!</p>
</div>
';
    }

    /**
     * Get the CSS that we use in our return values.
     * @return string CSS code.
     */
    protected function get_css() {
        return '
.local_testopaqueqe_engine_greeting {
    background: black;
    height: 60px;
    font-size: 50px;
    font-weight: bold;
    color: #C2B280;
}
';
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

        return "<questionmetadata>
                     <scoring><marks>3</marks></scoring>
                     <plainmode>no</plainmode> 
                 </questionmetadata>";
    }

    /**
     * A dummy implementation of the stop method.
     * @param $questionsession the question session id.
     */
    public function stop($questionsession) {
        $this->handle_special_from_sessionid($questionsession, 'stop');
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
        $this->handle_special_from_questionid($remoteid, $remoteversion, 'start');

        $initparams = array_combine($paramNames, $paramValues);

        $result = new local_testopaqueqe_start_return($questionid, $questionversion);

        $result->XHTML = $this->get_html($initparams);
        $result->CSS = $this->get_css();
        $result->progressInfo = "Testing Opaque";
        $result->addResource(local_testopaqueqe_resource::make_from_file(
                $CFG->dirroot . '/local/testopaqueqe/pix/world.gif', 'world.gif', 'image/gif'));

        return $result;
    }

    /**
     * returns an object (the structure of the object is taken from an OM question)
     *
     * @param $startresultquestionSession
     * @param $keys
     * @param $values
     * @return object
     */
    function process($startresultquestionSession, $keys, $values) {
        if ($startresultquestionSession == 'processfails.stopfails.log') {
            return array();
        }
    
        $scores = new stdClass();
        $scores->axis = '';
        $scores->marks = '';
    
        $subresult = new stdClass();
        $subresult->actionSummary = "Attempt 1:  C";
        $subresult->answerLine = "C";
        $subresult->attempts = 3;
        $subresult->customResults = array();
        $subresult->questionLine = "Which of the following should you do to try to prevent accidents when working with computers.";
        $subresult->scores = $scores;
    
        $result = new stdClass();
        $result->CSS = '';//get_process_css();
        $result->XHTML = get_progress_xhtml();
        $result->progressInfo = $scores->marks;
        $result->questionEnd = false;
        $result->resources = array();
        $result->results = $subresult;
        return $result;
    }
}


function get_start_xhtml() {
//    $result->XHTML = "<div class='testquestion'>
//                        <div>questionid = $questionid</div>
//                        <div>questionversion = $questionversion</div>
//                        <div>url = $url</div>
//                        <p>
//                        <div>Some appropriate HTML needed here!</div>
//                        <div>question text here ...</div>
//                        <p>
//                        <div><div>$submitbutton</div></div>
//                    </div>";
    $xhtml = '';
    $xhtml .= "
<div class=\"om\" id=\"om\"><div class=\"om\" onkeypress=\"return checkEnter(event);\"><script src=\"%%RESOURCES%%/script.js\" type=\"text/javascript\"></script><div class=\"gridgroup\" style=\"width:298px; _height:300px; min-height:300px; \"><div class=\"gridcontainer\" style=\"width:282px; height:292px; background-color:#e4f1fa;\">

  Which of the following should you do to try to prevent accidents when working with computers.

    <div class=\"gap\"></div>
<div class=\"layoutgrid\"><div class=\"layoutgridrow\"><div class=\"layoutgriditem\" style=\"width:99.99%\"><div class=\"layoutgridinner\"><div class=\"radiobox\" id=\"%%IDPREFIX%%box1\" onclick=\"radioBoxOnClick('rb_box1','%%IDPREFIX%%');\" style=\"background-color:#ffffff;border-color:#ffffff;\"><input class=\"radioboxcheck\" id=\"%%IDPREFIX%%rb_box1\" name=\"%%IDPREFIX%%g1\" type=\"radio\" value=\"box1\" /><div class=\"radioboxcontents\">A. Always use air conditioning in an office.</div><script type=\"text/javascript\">addOnLoad(function() { radioBoxFix('box1','%%IDPREFIX%%');});</script></div><script type=\"text/javascript\">addFocusable('%%IDPREFIX%%rb_box1','document.getElementById(\'%%IDPREFIX%%rb_box1\')');</script></div></div><div class=\"clear\"></div></div><div class=\"layoutgridrow\"><div class=\"layoutgriditem\" style=\"width:99.99%\"><div class=\"layoutgridinner\"><div class=\"radiobox\" id=\"%%IDPREFIX%%box2\" onclick=\"radioBoxOnClick('rb_box2','%%IDPREFIX%%');\" style=\"background-color:#ffffff;border-color:#ffffff;\"><input class=\"radioboxcheck\" id=\"%%IDPREFIX%%rb_box2\" name=\"%%IDPREFIX%%g1\" type=\"radio\" value=\"box2\" /><div class=\"radioboxcontents\">B. Turn lights off at the end of the day.</div><script type=\"text/javascript\">addOnLoad(function() { radioBoxFix('box2','%%IDPREFIX%%');});</script></div><script type=\"text/javascript\">addFocusable('%%IDPREFIX%%rb_box2','document.getElementById(\'%%IDPREFIX%%rb_box2\')');</script></div></div><div class=\"clear\"></div></div><div class=\"layoutgridrow\"><div class=\"layoutgriditem\" style=\"width:99.99%\"><div class=\"layoutgridinner\"><div class=\"radiobox\" id=\"%%IDPREFIX%%box3\" onclick=\"radioBoxOnClick('rb_box3','%%IDPREFIX%%');\" style=\"background-color:#ffffff;border-color:#ffffff;\"><input class=\"radioboxcheck\" id=\"%%IDPREFIX%%rb_box3\" name=\"%%IDPREFIX%%g1\" type=\"radio\" value=\"box3\" /><div class=\"radioboxcontents\">C. All trailing cables should be secured.</div><script type=\"text/javascript\">addOnLoad(function() { radioBoxFix('box3','%%IDPREFIX%%');});</script></div><script type=\"text/javascript\">addFocusable('%%IDPREFIX%%rb_box3','document.getElementById(\'%%IDPREFIX%%rb_box3\')');</script></div></div><div class=\"clear\"></div></div><div class=\"layoutgridrow\"><div class=\"layoutgriditem\" style=\"width:99.99%\"><div class=\"layoutgridinner\"><div class=\"radiobox\" id=\"%%IDPREFIX%%box4\" onclick=\"radioBoxOnClick('rb_box4','%%IDPREFIX%%');\" style=\"background-color:#ffffff;border-color:#ffffff;\"><input class=\"radioboxcheck\" id=\"%%IDPREFIX%%rb_box4\" name=\"%%IDPREFIX%%g1\" type=\"radio\" value=\"box4\" /><div class=\"radioboxcontents\">D. Always use screen savers.</div><script type=\"text/javascript\">addOnLoad(function() { radioBoxFix('box4','%%IDPREFIX%%');});</script></div><script type=\"text/javascript\">addFocusable('%%IDPREFIX%%rb_box4','document.getElementById(\'%%IDPREFIX%%rb_box4\')');</script></div></div><div class=\"clear\"></div></div></div>
    <div class=\"gap\"></div> 
  <input id=\"%%IDPREFIX%%omact_gen_20\" name=\"%%IDPREFIX%%omact_gen_20\" onclick=\"if(this.hasSubmitted) { return false; } this.hasSubmitted=true; preSubmit(this.form); return true;\" type=\"submit\" value=\"Submit answer\" /><script type=\"text/javascript\">addFocusable('%%IDPREFIX%%omact_gen_20','document.getElementById(\'%%IDPREFIX%%omact_gen_20\')');</script>
  <input id=\"%%IDPREFIX%%omact_gen_21\" name=\"%%IDPREFIX%%omact_gen_21\" onclick=\"if(this.hasSubmitted) { return false; } this.hasSubmitted=true; preSubmit(this.form); return true;\" type=\"submit\" value=\"Clear\" /><script type=\"text/javascript\">addFocusable('%%IDPREFIX%%omact_gen_21','document.getElementById(\'%%IDPREFIX%%omact_gen_21\')');</script> 
  
 </div></div><div class=\"gridgroup\" style=\"width:290px; _height:300px; min-height:300px; \"><div class=\"gridcontainer\" style=\"width:282px; height:292px;\"></div></div><div class=\"endform\"></div></div></div>
        ";
return $xhtml;
}


function get_progress_xhtml() {
//    $result->XHTML = "<div class='testquestion-process'>
//                     <div>Process html here ... !</div>
//                     <div>startresultquestionSession = $startresultquestionSession,</div>
//                     <div>keys = $keys,</div>
//                     <div>values = $values,</div>";

    $xhtml = '';
    $xhtml .= "
<div class=\"om\" id=\"om\"><div class=\"om\" onkeypress=\"return checkEnter(event);\"><script src=\"%%RESOURCES%%/script.js\" type=\"text/javascript\"></script><div class=\"gridgroup\" style=\"width:298px; _height:300px; min-height:300px; \"><div class=\"gridcontainer\" style=\"width:282px; height:292px; background-color:#e4f1fa;\">

  Which of the following should you do to try to prevent accidents when working with computers.

    <div class=\"gap\"></div>
<div class=\"layoutgrid\"><div class=\"layoutgridrow\"><div class=\"layoutgriditem\" style=\"width:99.99%\"><div class=\"layoutgridinner\"><div class=\"radiobox\" id=\"%%IDPREFIX%%box1\" onclick=\"radioBoxOnClick('rb_box1','%%IDPREFIX%%');\" style=\"background-color:#ffffff;border-color:#ffffff;\"><input class=\"radioboxcheck\" disabled=\"yes\" id=\"%%IDPREFIX%%rb_box1\" name=\"%%IDPREFIX%%g1\" type=\"radio\" value=\"box1\" /><div class=\"radioboxcontents\">A. Always use air conditioning in an office.</div><script type=\"text/javascript\">addOnLoad(function() { radioBoxFix('box1','%%IDPREFIX%%');});</script></div></div></div><div class=\"clear\"></div></div><div class=\"layoutgridrow\"><div class=\"layoutgriditem\" style=\"width:99.99%\"><div class=\"layoutgridinner\"><div class=\"radiobox\" id=\"%%IDPREFIX%%box2\" onclick=\"radioBoxOnClick('rb_box2','%%IDPREFIX%%');\" style=\"background-color:#ffffff;border-color:#ffffff;\"><input class=\"radioboxcheck\" disabled=\"yes\" id=\"%%IDPREFIX%%rb_box2\" name=\"%%IDPREFIX%%g1\" type=\"radio\" value=\"box2\" /><div class=\"radioboxcontents\">B. Turn lights off at the end of the day.</div><script type=\"text/javascript\">addOnLoad(function() { radioBoxFix('box2','%%IDPREFIX%%');});</script></div></div></div><div class=\"clear\"></div></div><div class=\"layoutgridrow\"><div class=\"layoutgriditem\" style=\"width:99.99%\"><div class=\"layoutgridinner\"><div class=\"radiobox\" id=\"%%IDPREFIX%%box3\" onclick=\"radioBoxOnClick('rb_box3','%%IDPREFIX%%');\" style=\"background-color:#ffffff;border-color:#000;\"><input checked=\"yes\" class=\"radioboxcheck\" disabled=\"yes\" id=\"%%IDPREFIX%%rb_box3\" name=\"%%IDPREFIX%%g1\" type=\"radio\" value=\"box3\" /><div class=\"radioboxcontents\">C. All trailing cables should be secured.</div><script type=\"text/javascript\">addOnLoad(function() { radioBoxFix('box3','%%IDPREFIX%%');});</script></div></div></div><div class=\"clear\"></div></div><div class=\"layoutgridrow\"><div class=\"layoutgriditem\" style=\"width:99.99%\"><div class=\"layoutgridinner\"><div class=\"radiobox\" id=\"%%IDPREFIX%%box4\" onclick=\"radioBoxOnClick('rb_box4','%%IDPREFIX%%');\" style=\"background-color:#ffffff;border-color:#ffffff;\"><input class=\"radioboxcheck\" disabled=\"yes\" id=\"%%IDPREFIX%%rb_box4\" name=\"%%IDPREFIX%%g1\" type=\"radio\" value=\"box4\" /><div class=\"radioboxcontents\">D. Always use screen savers.</div><script type=\"text/javascript\">addOnLoad(function() { radioBoxFix('box4','%%IDPREFIX%%');});</script></div></div></div><div class=\"clear\"></div></div></div>
    <div class=\"gap\"></div> 
  <input disabled=\"yes\" id=\"%%IDPREFIX%%omact_gen_20\" name=\"%%IDPREFIX%%omact_gen_20\" onclick=\"if(this.hasSubmitted) { return false; } this.hasSubmitted=true; preSubmit(this.form); return true;\" type=\"submit\" value=\"Submit answer\" />
  <input disabled=\"yes\" id=\"%%IDPREFIX%%omact_gen_21\" name=\"%%IDPREFIX%%omact_gen_21\" onclick=\"if(this.hasSubmitted) { return false; } this.hasSubmitted=true; preSubmit(this.form); return true;\" type=\"submit\" value=\"Clear\" /> 
  
 </div></div><div class=\"gridgroup\" style=\"width:290px; _height:300px; min-height:300px; \"><div class=\"gridcontainer\" style=\"width:282px; height:292px; background-color:#fff3bf;\">
  
   
    
    
         
    Yes, that is correct.
    
      

    <div class=\"gap\"></div>
  
       
   
    
    Trailing cables could cause injury to someone tripping over them. This 
    is why it is good practice to cover them or fix them in place.
    
      <div class=\"gap\"></div>
  
     
    
    
    <input id=\"%%IDPREFIX%%omact_next\" name=\"%%IDPREFIX%%omact_next\" onclick=\"if(this.hasSubmitted) { return false; } this.hasSubmitted=true; preSubmit(this.form); return true;\" type=\"submit\" value=\"%%lNEXTQUESTION%%\" /><script type=\"text/javascript\">addFocusable('%%IDPREFIX%%omact_next','document.getElementById(\'%%IDPREFIX%%omact_next\')');</script>
   
  </div></div><div class=\"endform\"></div></div></div>
";
    return $xhtml;
}


function get_start_results() {
    $res = new stdClass();
    $res->content = "
    // ---RootComponent---
// Adds to a chain of events that occur onload
var onLoadEvents=new Array();
function addOnLoad(handler)
{
    onLoadEvents.push(handler);
}

function myOnLoad()
{
    window.mLoaded=true;
    for(var i=0;i<onLoadEvents.length;i++)
    {
        onLoadEvents[i]();
    }
    setTimeout(myPostLoad,0);
}

window.onload=myOnLoad;

var postLoadEvents=new Array();
function addPostLoad(handler)
{
    postLoadEvents.push(handler);
}
function myPostLoad()
{
    for(var i=0;i<postLoadEvents.length;i++)
    {
        postLoadEvents[i]();
    }
}

// Adds to chain of handles that are called onKeyPress on the specified form
function addFormKeypress(specifiedForm,handler)
{
    if(specifiedForm.onkeypress)
    {
        var previous=specifiedForm.onkeypress;
        specifiedForm.onkeypress=function() { return previous() && handler(); };
    }
    else
        specifiedForm.onkeypress=handler;
}

// Adds to chain of events that occur before submitting the specified form
function addPreSubmit(specifiedForm,handler)
{
    if(specifiedForm.preSubmit)
    {
        var previous=specifiedForm.preSubmit;
        specifiedForm.preSubmit=function() { previous(); handler(); };
    }
    else
        specifiedForm.preSubmit=handler;
}

// Must call before submitting specified form
function preSubmit(specifiedForm)
{
    if(specifiedForm.preSubmit) specifiedForm.preSubmit();
}

// Detect whether a DOM node is a particular thing. Can call with one or more
// attribute/value pairs as arrays e.g. isDomElement(n,\"input\",[\"type\",\"text\"])
function isDomElement(node,tagName)
{
    if(!node.tagName || node.tagName.toLowerCase()!=tagName) return false;

    for(var i=1;i<arguments.length;i++)
    {
        if(node.getAttribute(arguments[i][0])!=arguments[i][1]) return false;
    }

    return true;
}

// Utility for use when debugging: log messages to a 'console'
var logBox;
function log(line)
{
    if(!logBox)
    {
        logBox=document.createElement(\"div\");
        document.body.appendChild(logBox);
        logBox.style.overflow=\"scroll\";
        logBox.style.width=\"100%\";
        if(isIE)
        {
            logBox.style.position=\"absolute\";
            logBox.style.top=(document.documentElement.clientHeight-120)+\"px\";
            logBox.style.left=\"0\";
        }
        else
        {
            logBox.style.position=\"fixed\";
        logBox.style.bottom=\"0\";
    }
        logBox.style.height=\"120px\";
        logBox.style.fontFamily=\"Andale Mono, monospace\";
        logBox.style.fontSize=\"11px\";
        logBox.style.borderTop=\"2px solid #888\";
    }

    var newLine=document.createElement(\"div\");
    newLine.appendChild(document.createTextNode(line));

    if(!logBox.firstChild)
        logBox.appendChild(newLine);
    else
        logBox.insertBefore(newLine,logBox.firstChild);
}


// We don't really care about KHTML and Opera, they will both be served the
// 'correct' version and are welcome to either work or not. They are detected
// only so we know they're not the 'real' ones they imitate.
var isKHTML=navigator.userAgent.indexOf('KHTML')!=-1;
var isGecko=!isKHTML && navigator.userAgent.indexOf('Gecko/')!=-1;
var isOpera=navigator.userAgent.indexOf('Opera')!=-1;
var isIE=!isOpera && navigator.userAgent.match('.*MSIE.*Windows.*');
var isIE7OrBelow=!isOpera && navigator.userAgent.match('.*MSIE [1-7].*Windows.*');
var isGecko18;
if(isGecko)
{
    var re=/^.*rv:([0-9]+).([0-9]+)[^0-9].*$/;
    var matches=re.exec(navigator.userAgent);
    if(matches)
    {
        isGecko18 = (matches[1]==1) ? (matches[2]>=8) : (matches[1]>1);
    }
}


// Fix position of placeholders relative to an image. First argument is image Id,
// following arguments are arrays of [id, x, y].
function inlinePositionFix(imageID)
{
    var imageElement=document.getElementById(imageID);
    resolvePageXY(imageElement);

    for(var i=1;i<arguments.length;i++)
    {
        var ph=document.getElementById(arguments[i][0]);
        resolvePageXY(ph);
        var deltaX = imageElement.pageX + arguments[i][1] - ph.pageX;
        var deltaY = imageElement.pageY + arguments[i][2] - ph.pageY;

        // Hack for IE positioning editfields one too far down (?!)
        var moveUp=0;
        if(isIE && (ph.childNodes.length==1 ||
            (ph.childNodes.length==2 && ph.childNodes[1].nodeName.toLowerCase()==\"script\")) &&
            isDomElement(ph.childNodes[0],\"input\",[\"type\",\"text\"]))
        {
            moveUp=1;
        }

        // There used to be some very hacky code here to fix layout problems in IE when
        // the placeholder contained an element, but it now seems to do more harm than good.
        // I think it is the same issue that is now handled by the workaround in resolvePageXY.
        ph.style.left=(Number(ph.style.left.replace(\"px\",\"\")) + deltaX)+'px';
        ph.style.top=(Number(ph.style.top.replace(\"px\",\"\")) + deltaY - moveUp)+'px';
        ph.style.visibility='visible';
    }
}

function checkEnter(e)
{
    var target = e.target ? e.target : e.srcElement;
    var keyCode = e.keyCode ? e.keyCode : e.which;

    // Stifle Enter on anything except submit buttons and textareas
    if(keyCode==13 && (!target.type || (target.type!='submit' && target.type!='textarea')))
        return false;
    else
        return true;
}

// Convert event object so it works with both browsers
function fixEvent(e)
{
    if(!e) e=window.event;

    if(e.pageX)
        e.mPageX=e.pageX;
    else if(e.clientX)
        e.mPageX=e.clientX+document.body.scrollLeft;
    if(e.pageY)
        e.mPageY=e.pageY;
    else if(e.clientY)
        e.mPageY=e.clientY+document.body.scrollTop;
    if(e.target)
        e.mTarget=e.target;
    else if(e.srcElement)
        e.mTarget=e.srcElement;
    if(e.keyCode)
        e.mKey=e.keyCode;
    else if(e.which)
        e.mKey=e.which;

    return e;
}

function getUrlParameter(name)
{
    var regexStr = \"[\\?&]\"+name+\"=([^&#]*)\";  
    var regex = new RegExp(regexStr);  
    var match = regex.exec(window.location.href);  
    if(match==null)    
        return \"\";  
    else    
        return match[1];
}

function isAutoFocusOn()
{
    var focusOn = true;
    if(getUrlParameter('autofocus')=='off')
        focusOn = false;
    return focusOn;
}

// Keep track of focusable objects
var focusList=new Array();

function addFocusable(id,expr)
{
    var o=new Object();
    o.id=id;
    o.expr=expr;

    if(focusList.length==0 && isAutoFocusOn())
    {
        addOnLoad(function() {
        setTimeout(o.expr+'.focus();setTimeout(\"window.scroll(0,0);\",0)',100);
        });
    }

    focusList.push(o);
}

// Focus the next (offset=1) or previous (offset=-1) one
function focusFromList(idThis,offset)
{
    for(var i=0;i<focusList.length;i++)
    {
        if(focusList[i].id==idThis)
        {
            var index=i+offset;
            while(index<0) index+=focusList.length;
            while(index>=focusList.length) index-=focusList.length;
            setTimeout(focusList[index].expr+\".focus();\",0);
        }
    }
}

// Adds pageX and pageY to the element
function resolvePageXY(e)
{
    e.pageX=e.offsetLeft;
    e.pageY=e.offsetTop;

    var parent=e.offsetParent;
    while(parent!=null)
    {
        e.pageX+=parent.offsetLeft;
        e.pageY+=parent.offsetTop;
        parent=parent.offsetParent;
    }

    // Bug fix for IE7. When an eplace containing a text field is in an equation
    // in an indented line of text, then IE7 was applying the left margin from the
    // line of text to the input. In this case, add the offsetLeft from the first 
    // child to the position of this element, so the input ends up in the right place.
    if (e.firstChild && e.firstChild.nodeType==1 &&
            e.firstChild.tagName.toLowerCase() == 'input' &&
            e.firstChild.offsetLeft != 0) {
        e.pageX += e.firstChild.offsetLeft;
    }

    e.pageX2=e.pageX+e.offsetWidth;
    e.pageY2=e.pageY+e.offsetHeight;
}

// overflow hidden is set on the divs just so floats don't bounce
// around while loading, turn it off now
if(window.isDevServlet===undefined || !window.isDevServlet)
{
    addPostLoad(function()
    {
        var divs=document.getElementsByTagName(\"div\");
        for(var i=0;i<divs.length;i++)
        {
            if(divs[i].className!=\"gridcontainer\") continue;
            divs[i].style.overflow=\"visible\";
            if(!isIE)
            {
                divs[i].style.minHeight=divs[i].style.height;
                divs[i].style.height=\"auto\";
            }
        }
    });
}
// ---RadioBoxComponent---
function radioBoxOnClick(radioboxID,idPrefix)
{
  var radiobox=document.getElementById(idPrefix+radioboxID);
  if (!radiobox.disabled) radiobox.checked = true;
}

function radioBoxFix(radioboxID,idPrefix)
{
  var container=document.getElementById(idPrefix+radioboxID);

  // Find ancestor row
  var row=container;
  while(row!=null && (row.tagName.toLowerCase()!='div' || row.className!='layoutgridrow'))
  {
    row=row.parentNode;
  }
  if(row==null) return; // Not in a layoutgrid

  // Fix height to same, less 10px for padding and border, less 4px padding on row
  // Note that I tried to use row.style.height but it doesn't work.
  container.style.height=(row.offsetHeight-14)+\"px\";
}
    ";
    $res->encoding = 'UTF-8';
    $res->filename = 'script.js';
    $res->mimeType = 'text/javascript';
    
    return $res;
}
