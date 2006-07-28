<?php
/**
 * Xaraya CSS class library
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Themes module
 * @link http://xaraya.com/index.php/release/70.html
**/

/**
 * Defines for this library
 *
 * @author Andy Varganov <andyv@xaraya.com>
**/

define("CSSRELSTYLESHEET", "stylesheet");
define("CSSRELALTSTYLESHEET", "alternate stylesheet");
define("CSSTYPETEXT", "text/css");
define("CSSMEDIA", "media");
define("CSSMEDIATV", "tv");
define("CSSMEDIATTY", "tty");
define("CSSMEDIAALL", "all");
define("CSSMEDIAPRINT", "print");
define("CSSMEDIAAURAL", "aural");
define("CSSMEDIASCREEN", "screen");
define("CSSMEDIABRAILLE", "braille");
define("CSSMEDIAHANDHELD", "handheld");
define("CSSMEDIAPROJECTION", "projection");
define("CSSCOMMONSOURCE", "xarcore-xhtml1-strict");
define("CSSCOMMONBASE", "base");

/**
 * Base CSS class
 *
 *
 * @package themes
**/
class xarCSS
{
    // class vars and their defaults
    public $language   = 'html';       // only (x)html compliant css inclusion is supported out of the box

    public $method       = 'link';      // supported are 'link', 'import', 'embed'

    // SUPPORTED SCOPES ARE MODULE, THEME, COMMON
    public $scope      = 'theme';      // component type - 'module, 'theme' or 'common'
    public $compcssdir = 'xarstyles';  // component css directory name (e.g. 'xarstyles')

    public $base       = 'theme';      // component name (e.g. module's name 'base')
    public $filename   = 'style';      // default css file name (without extension)
    public $fileext    = 'css';        // default css file extension
    public $commonbase = CSSCOMMONBASE;// base dirctory for common css
    public $commonsource = CSSCOMMONSOURCE;  // filename for common css

    public $source     = null;         // empty source should not be included (ideally)

    public $condition  = null;         // encase in a conditions comment (think ie-win)

    public $dynfile; // not implemented yet

    // TYPICAL REQUIRED ATTRIBUTES FOR WELL-FORMED CSS REFERENCE TAGS (xhtml-wise)
    public $rel        = CSSRELSTYLESHEET;
    public $type       = CSSTYPETEXT;
    public $media      = CSSMEDIASCREEN;
    public $title      = '';           // empty string title attribute will not be included
    public $id         = '';           // may be supported in the future (TODO?)

    // BASIC OVERRIDES SETTINGS (still TODO)
    public $overridden = false;        // true == stylesheet has been overridden in theme or elsewhere
    public $alternatedir     = '';     // alternative directory for overridden css file

    // SUPPORT FOR DYNAMIC CSS SERVING AND ADMIN GUI (TODO)
    public $cssdecl;                   // TODO: associative array containing css declarations
                                    // $this->componentCSS["body"]["background-color"]
    public $cssconf    = false;        // Use runtime configuration parameters (with db backend)
    public $suppresstype;              // true == tags of this type are suppressed
    public $suppressscope;             // true == tags of this scope are suppressed
    public $sort       = true;         // true == tags will be sorted
    public $comments   = true;         // true == comments will be shown in the templates
    public $debug      = false;        // true == debug mode enabled
    public $parse      = false;        // true == parse mode enabled
    public $suppress   = false;        // true == this css is suppressed

    // constructor
    function xarCSS($args)
    {
        extract($args);
        if (isset($method)) $this->method               = $method;
        if (isset($scope)) $this->scope                 = $scope;
        if ($this->scope == 'common') {
            $this->base = $this->commonbase;
            $this->filename = $this->commonsource;
        } elseif ($this->scope == 'module') {
            $this->base = xarModGetName();
        } elseif ($this->scope == 'block') {
            // we basically need to find out which module this block belongs to 
            // and then procede as with module scope
            $this->base = xarVarGetCached('Security.Variables', 'currentmodule');
        }
        if (isset($media)) $this->media                 = $media;
        if (isset($module)) $this->base                 = $module;
        if (isset($file)) $this->filename               = $file;
        if (isset($title)) $this->title                 = $title;
        if (isset($fileext)) $this->fileext             = $fileext;
        if (isset($alternate) && $alternate == 'true') {
            $this->rel = 'alternate stylesheet';
        }
        if($this->method == 'import' && isset($media)) {
            $this->media = str_replace(' ', ', ', $media);
        }

        if (isset($source)) $this->source               = $source;
        if (isset($condition)) $this->condition         = $condition;

        $this->tagdata = array(
                            'scope'            => $this->scope,
                            'method'           => $this->method,
                            'base'             => $this->base,
                            'file'             => $this->filename,
                            'fileext'          => $this->fileext,
                            'source'           => $this->source,
                            'rel'              => $this->rel,
                            'type'             => $this->type,
                            'media'            => $this->media,
                            'title'            => $this->title,
                            'condition'        => $this->condition );
    }

    // The main method for generating tag output
    // stick tag data into the tag queue or get it
    function run_output()
    {
        if (!isset($tagqueue)) $tagqueue = new tagqueue();
        switch($this->method) {
            case 'render':
                $data['styles'] = $tagqueue->deliver($this->sort);
                break;
            default:
                $this->tagdata['url'] = $this->geturl();
                $tagqueue->register($this->tagdata);
                return true;
        }

        $data['comments']                   = $this->comments;
        return $data;
    }

    // returns xaraya url for the file
    function geturl($dir = null)
    {
        // it's static var already in core
        $url = xarServerGetBaseURL();

        if(isset($dir)){
            $fullurl = $url.$dir;
        } else {
            $fullurl = $url.$this->getrelativeurl();
        }

        return $fullurl;
    }

    function getrelativeurl()
    {
        // if requested method is 'embed', we dont really need any file checks, urls, scope etc., 
        // all we care about is the css source string as provided by the tag
        if ($this->method == "embed") {
            // could add a TODO to check validity of the actual source string, either here or earlier
            return $this->source;
        }
        
        $msg = xarML("#(1) css stylesheet file cannot be found at this location: ", $this->scope);

        // <mrb> why is this?
        // <andyv> scope common is just a special case of a module based stylesheet ATM - matter of implementation
        // the original idea was to be able to provide common css out of various sources, like db or even inline
        if ($this->scope == 'common') $this->scope = 'module';

        if ($this->scope == 'theme') {
            // pretty straightforward
            $themestylesheet =  xarTplGetThemeDir() . "/style/" . $this->filename . "." . $this->fileext;
            if(!file_exists($themestylesheet)) throw new FileNotFoundException($themestylesheet);
            return $themestylesheet;
        } elseif ($this->scope == 'module' || $this->scope == 'block') {            
            
            $original = "modules/" . strtolower($this->base) . "/xarstyles/" . $this->filename . "." . $this->fileext;
            // we do not want to supply path for a non-existent original css file or override a bogus file
            // so lets check starting from original then fallback if there arent overriden versions
            // how about the overridden one?
            // Look for theme-based stylesheet whether the module contains one or not.
            if($this->alternatedir != '') {
                $overridden = xarTplGetThemeDir() . "/" . $this->alternatedir . "/" . $this->filename . "." . $this->fileext;
            } else {
                $overridden = xarTplGetThemeDir() . "/modules/" . strtolower($this->base) . "/xarstyles/" . $this->filename . "." . $this->fileext;
            }
            if(file_exists($overridden)) {
                // prolly need to check if it's not a directory too (?)
                return $overridden;
            } else {
                // no problem
                return $original;
            }

        } else {
            // no scope, somebody overrode defaults and hasn't assign anything sensible? naughty - lets complain
            $msg = xarML("#(1) (no valid scope attribute could be deduced from this xar:style tag)",$this->scope);
            throw new Exception($msg);
        }
    }
}

/**
 * Queue class. Holds the tag data until it is sent to the template
 *
 *
 * @package themes
 */

class tagqueue
{
    function tagqueue()
    {
    }

    // FIXME: $args is used as boolean OR an array depending on the call,
    // someone is bound to trip over that hack at some point
    function queue($op='register', $args)
    {
        static $queue;

        switch($op) {
            case 'register':
                // Put it in the queue
                $queue[$args['scope']][$args['method']][$args['url']] = $args;
                return true;
            case 'deliver':
                $styles = $queue;
                if($args) {
                    if (is_array($styles)){
                        krsort($styles);
                        reset($styles);
                    }
                }
                $queue = array();
                return $styles;
            default:
                return false;
        }
    }

    function register($args)
    {
        return $this->queue('register',$args);
    }

    function deliver($sort=true)
    {
        return $this->queue('deliver',$sort);
    }
}
?>
