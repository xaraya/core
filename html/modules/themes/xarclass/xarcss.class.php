<?php
/**
 * File: $Id$
 *
 * Xaraya CSS class library
 *
 * @package themes
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @author Andy Varganov <andyv@xaraya.com>
 */

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
 */
class xarCSS
{
    // class vars and their defaults
    var $language   = 'html';       // only (x)html compliant css inclusion is supported out of the box

    var $method       = 'link';      // supported are 'link', 'import', 'embed', 'render'

    // SUPPORTED SCOPES ARE MODULE, THEME, COMMON
    var $scope      = 'theme';      // component type - 'module, 'theme' or 'common'
    var $compcssdir = 'xarstyles';  // component css directory name (e.g. 'xarstyles')

    var $base       = 'theme';      // component name (e.g. module's name 'base')
    var $filename   = 'style';      // default css file name (without extension)
    var $fileext    = 'css';        // default css file extension
    var $commonbase = CSSCOMMONBASE;// base dirctory for common css
    var $commonsource = CSSCOMMONSOURCE;  // filename for common css

    var $source     = null;         // empty source should not be included (ideally)
    var $dynfile;

    // TYPICAL REQUIRED ATTRIBUTES FOR WELL-FORMED CSS REFERENCE TAGS (xhtml-wise)
    var $rel        = CSSRELSTYLESHEET;
    var $type       = CSSTYPETEXT;
    var $media      = CSSMEDIASCREEN;
    var $title      = '';           // empty string title attribute will not be included
    var $id         = '';           // may be supported in the future (TODO?)

    // BASIC OVERRIDES SETTINGS (still TODO)
    var $overridden = false;        // true == stylesheet has been overridden in theme or elsewhere
    var $alternatedir     = '';     // alternative directory for overridden css file

    // SUPPORT FOR DYNAMIC CSS SERVING AND ADMIN GUI (TODO)
    var $cssdecl;                   // TODO: associative array containing css declarations
                                    // $this->componentCSS["body"]["background-color"]
    var $cssconf    = false;        // Use runtime configuration parameters (with db backend)
    var $suppresstype;              // true == tags of this type are suppressed
    var $suppressscope;             // true == tags of this scope are suppressed
    var $sort       = true;         // true == tags will be sorted
    var $comments   = true;         // true == comments will be shown in the templates
    var $debug      = false;        // true == debug mode enabled
    var $parse      = false;        // true == parse mode enabled
    var $suppress   = false;        // true == this css is suppressed
    var $legacy     = true;         // true == legacy pre-csslib support


    // constructor
    function xarCSS($args)
    {
        extract($args);
        if (isset($method)) $this->method               = $method;
        if (isset($scope)) $this->scope                 = $scope;
        if ($this->scope == 'common') {
            $this->base   = $this->commonbase;
            $this->filename   = $this->commonsource;
        } elseif ($this->scope == 'module') {
            $this->base   = xarModGetName();
        }
        if (isset($media)) $this->media                 = $media;
        if (isset($module)) $this->base                 = $module;
        if (isset($file)) $this->filename               = $file;
        if (isset($title)) $this->title                 = $title;
        if (isset($alternate) && $alternate == 'true') {
            $this->rel = 'alternate stylesheet';
        }
        if($this->method == 'import') {
            $this->media = str_replace(' ', ', ', $media);
        }

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
                        );
    }

    // The main method for generating tag output
    // stick tag data into the tag queue or get it
    function run_output()
    {
        if (!isset($tagqueue)) $tagqueue = new tagqueue();
        if ($this->method == 'render') {
            $data['styles'] = $tagqueue->deliver($this->sort);
            $data['comments'] = $this->comments;
            $data['legacy'] = $this->legacy;
            $data['additionalstyles'] = $GLOBALS['xarTpl_additionalStyles'];
            // TODO: remove these hardcoded comments when BL + QA can handle them in templates
            $data['opencomment'] = "<!-- ";
            $data['closecomment'] = " -->\n";
            return $data;
        } else {
            $this->tagdata['url'] = $this->geturl();
            $tagqueue->register($this->tagdata);
            return true;
        }
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

        $msg = xarML("#(1) css stylesheet file cannot be found at this location: ",$this->scope);

        if ($this->scope == 'common') $this->scope = 'module';

        if ($this->scope == 'theme') {
            // pretty straightforward
            $themestylesheet =  xarTplGetThemeDir() . "/style/" . $this->filename . "." . $this->fileext;
            if(file_exists($themestylesheet)) {
                // no problem
                return $themestylesheet;
            } else {
                // problem
                xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                           new SystemException($msg.$themestylesheet));
                return;
            }
        } else {

            $original = "modules/" . strtolower($this->base) . "/xarstyles/" . $this->filename . "." . $this->fileext;
            // we do not want to supply path for a non-existent original css file or override a bogus file
            // so lets check starting from original then fallback if there arent overriden versions
            if(file_exists($original)) {
                // how about the overridden one?
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
                // problem
                xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                           new SystemException($msg.$original));
                return;
            }
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
    function queue($op='register', $args)
    {
        static $queue;

        if ($op == 'register') {
            $queue[$args['scope']][$args['method']][$args['url']] = $args;
            return true;
        } else if ($op == 'deliver') {
            $styles = $queue;
            if($args) {
                if (is_array($styles)){
                    krsort($styles);
                    reset($styles);
                }
            }
            $queue = array();
            return $styles;
        } else {
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