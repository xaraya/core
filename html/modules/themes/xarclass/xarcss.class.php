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

/**
 * Base CSS class
 *
 *
 * @package themes
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

class xarCSS
{
    // class vars and their defaults
    var $debug      = false;        // true == debug mode enabled
    var $parse      = false;        // true == parse mode enabled
    var $suppress   = false;        // true == this css is suppressed
    
    var $legacy     = true;         // true == legacy pre-csslib support

    var $language   = 'html';       // only (x)html compliant css inclusion is supported out of the box

    var $method     = 'link';       // also supported are 'import' and embedded 'style'

    // SUPPORTED COMPONENTS ARE MODULE (BLOCK), THEME, CORE (anything else out there?)
    var $comptype   = 'module';     // component type - 'module, 'theme' or 'common'
    var $compname   = 'base';       // component name (e.g. module's name 'base')
    var $compcssdir = 'xarstyles';  // component css directory name (e.g. 'xarstyles')

    var $filename   = 'style';      // default css file name (without extension)
    var $fileext    = 'css';        // default css file extension

    var $source     = null;         // empty source should not be included (ideally)
    var $dynfile;

    // TYPICAL REQUIRED ATTRIBUTES FOR WELL-FORMED CSS REFERENCE TAGS (xhtml-wise)
    var $rel        = CSSRELSTYLESHEET;
    var $type       = CSSTYPETEXT;
    var $media      = CSSMEDIAALL;
    var $title      = '';           // empty string title attribute will not be included
    var $id         = '';           // may be supported in the future (TODO?)

    // BASIC OVERRIDES SETTINGS (still TODO)
    var $overridden = false;        // true == stylesheet has been overridden in theme or elsewhere
    var $altdir     = '';           // alternative directory for overridden css file

    // SUPPORT FOR DYNAMIC CSS SERVING AND ADMIN GUI (TODO)
    var $cssdecl;                   // TODO: associative array containing css declarations
                                    // $this->componentCSS["body"]["background-color"]
    var $cssconf    = false;        // Use runtime configuration parameters (with db backend)

    // constructor (defensive)
    function xarCSS()
    {
        // DO NOT EVER ATTEMPT to instantiate this class, if you do you'll get a nasty error
        // subclass it instead and let the polymorphism to do its job :-) <andyv>
        $msg = xarML("you have illegally instantiated class: ") . get_class (&$this);
        $this->_error($msg);
    }

    // PUBLIC METHODS

    // CSS REL - public accessors
    function get_rel_attribute()
    {
        return $this->rel;
    }

    function set_rel_attribute($rel)
    {
        $this->rel = $rel;
    }

    function set_rel_stylesheet()
    {
        $this->rel = CSSRELSTYLESHEET;
    }

    function set_rel_alternate()
    {
        $this->rel = CSSRELALTSTYLESHEET;
    }

    // CSS TYPE - public accessors
    function get_type_attribute()
    {
        return $this->type;
    }

    function set_type_attribute($type)
    {
        $this->type = $type;
    }

    function set_type_text()
    {
        $this->set_type(CSSTYPETEXT);
    }

    // CSS MEDIA - public accessors
    function get_media_attribute()
    {
        return $this->media;
    }

    function set_media_attribute($media)
    {
        $this->media = $media;
    }

    function set_media_all()
    {
        $this->set_media_attribute(CSSMEDIAALL);
    }

    function set_media_screen()
    {
        $this->set_media_attribute(CSSMEDIASCREEN);
    }

    function set_media_print()
    {
        $this->set_media_attribute(CSSMEDIAPRINT);
    }

    function set_media_handheld()
    {
        $this->set_media_attribute(CSSMEDIAHANDHELD);
    }

    function set_media_projection()
    {
        $this->set_media_attribute(CSSMEDIAPROJECTION);
    }

    function set_media_aural()
    {
        $this->set_media_attribute(CSSMEDIAAURAL);
    }

    function add_media($media)
    {
        // support for comma separated list of multiple media types
        $previous = $this->get_media_attribute();

        if (isset($previous)) {
            $extra = $previous . "," . $media;
            $this->set_media_attribute($extra);
        } else {
            // unlikely scenario, but still need to cover for it
            $this->set_media_attribute($media);
        }
    }

    // CSS TAG TITLE ATTRIBUTE - public accessors
    function get_tag_title()
    {
        return $this->title;
    }

    function set_tag_title($title)
    {
        $this->title = $title;
    }

    // CSS TAG ID ATTRIBUTE - public accessors
    function get_tag_id()
    {
        return $this->id;
    }

    function set_tag_id($id)
    {
        $this->id = $id;
    }

    // PUBLIC UTILITY METHODS

    // access css inclusion methods
    function get_method()
    {
        return $this->method;
    }

    function set_method($method)
    {
        $this->method = $method;
    }

    function set_method_link()
    {
        $this->set_method('link');
    }

    function set_method_import()
    {
        $this->set_method('import');
    }

    function set_method_style()
    {
        $this->set_method('style');
    }

    // access css file extension
    function get_fileext()
    {
        return $this->fileext;
    }

    function set_fileext($fileext)
    {
        $this->fileext = $fileext;
    }

    function set_fileext_css()
    {
        $this->set_fileext('css');
    }

    function set_fileext_php()
    {
        $this->set_fileext('php');
    }

    // access embedded styles source code
    function get_source()
    {
        return $this->source;
    }

    function set_source($source)
    {
        $this->source = $source;
    }

    // access doc language for output sensitivity (TODO: xml and some other perhaps)
    function get_language()
    {
        return $this->language;
    }

    function set_language($language)
    {
        $this->language = $language;
    }

    function set_language_html()
    {
        $this->set_language('html');
    }

    function set_language_xml()
    {
        $this->set_language('xml');
    }

    // output css inclusion string for various languages
    function get_output()
    {
        // only (x)html supported ATM
        if($this->language == 'html') {
            $htmlstr = $this->_htmltag();
        } else {
            $htmlstr = '';
        }

        $cssarray = self::_handle_cssdata_var();
        $cssarray["$this->comptype"]["$this->compname"][] = $htmlstr;
        self::_handle_cssdata_var($cssarray);

        // return the result string only if debug is on
        if($this->debug) return $htmlstr;
        return null;
    }

    // PRIVATE (and PROTECTED) UTILITY METHODS

    // alternative override dir accessor
    function _altdir()
    {
        return $this->altdir;
    }

    function _set_altdir($altdir = '')
    {
        $this->altdir = $altdir;
    }

    // returns relative xaraya path for the desired css file (protected)
    function _xarpath()
    {
        static $inspector;
        
        // make sure current module is known in advance
        if(!isset($this->compname)) {
            $path = new xarCSSPath($this);
            $this->compname = $path->currentmoddir();
        }
        
        // do we have the instance already?
        if(!isset($inspector)) $inspector = new cssFileInspector($this);
        
        switch($this->comptype)
        {
            case "common":
            case "module":
                return $inspector->verified_module_csspath();
                break;
            case "theme":
                return $inspector->verified_theme_csspath();
                break;
            default:
                // unrecognised
                return null;
                break;
        }
    }

    // make valid (x)html tag for various css inclusion methods (protected)
    function _htmltag()
    {
        static $tag;

        if(!isset($tag)) $tag = new htmlCSSTag($this);
        return $tag->render();
    }

    // PROTECTED HELPERS
    
    // handle consolidated static css data array
    // to use static method var in a consistent way (between php4 and 5) we seem to need this helper
    function _handle_cssdata_var($add_data = null)
    {
        static $cssdata = array();

        if(!$add_data) {
            return $cssdata;
        }
        $cssdata = $add_data;
        return null;
    }   
    
    function _error($msg = null)
    {
        if(isset($msg)) xarErrorSet(XAR_SYSTEM_EXCEPTION, 'UNKNOWN', new SystemException($msg));
    }

    // toggle legacy, debug and parse modes dynamically
    function _legacy()
    {
        $this->legacy = true;
    }
    
    function _nolegacy()
    {
        $this->legacy = false;
    }
    
    function _debug()
    {
        $this->debug = true;
    }

    function _nodebug()
    {
        $this->debug = false;
    }

    function _parse()
    {
        $this->parse = true;
    }

    function _noparse()
    {
        $this->parse = false;
    }
}

class htmlCSSTag
{
    var $tag;

    // Constructor
    function htmlCSSTag($tag)
    {
        $this->tag = $tag;
    }

    // returns xaraya url for css file
    function href($dir = null)
    {
        // it's static var already in core 
        $url = xarServerGetBaseURL();

        if(isset($dir)){
            $fullurl = $url.$dir;
        } else {
            $fullurl = $url.$this->tag->_xarpath();
        }

        return $fullurl;
    }

    // composes valid (x)html atributes
    function htmlattr($attrname, $attrval)
    {
        $htmlstr = " "; // need a lil space here? hmm..
        $htmlstr .= $attrname;
        $htmlstr .= "=\"";
        $htmlstr .= $attrval;
        $htmlstr .= "\"";
        return $htmlstr;
    }

    function render()
    {
        switch($this->tag->method)
        {
            case "link":
                $htmlstr = "\n<link";
                $htmlstr .= $this->htmlattr("rel", $this->tag->rel);
                $htmlstr .= $this->htmlattr("type", $this->tag->type);
                $htmlstr .= $this->htmlattr("href", $this->href());
                $htmlstr .= $this->htmlattr("media", $this->tag->media);
                if(!empty($this->tag->title)) {
                    $htmlstr .= $this->htmlattr("title", $this->tag->title);
                }
                $htmlstr .= " />";
                break;
            case "import":
                $htmlstr = "\n<style";
                $htmlstr .= $this->htmlattr("type", $this->tag->type);
                $htmlstr .= $this->htmlattr("media", $this->tag->media);
                $htmlstr .= ">";
                $htmlstr .= "@import ";
                $htmlstr .= "url(";
                $htmlstr .= $this->href();
                $htmlstr .= ");";
                if(!empty($this->tag->title)) {
                    $htmlstr .= $this->htmlattr("title", $this->tag->title);
                }
                $htmlstr .= "</style>";
                break;
            case "style":
                $htmlstr = "\n<style";
                $htmlstr .= $this->htmlattr("type", $this->tag->type);
                $htmlstr .= $this->htmlattr("media", $this->tag->media);
                $htmlstr .= ">\n<!-- \n";
                if(empty($this->tag->source)) {
                    $htmlstr .= $this->compname;
                    $htmlstr .= " component has provided no css source at this time\n";
                } else {
                    $htmlstr .= $this->tag->source;
                }
                $htmlstr .= "\n-->\n</style>\n";
                break;
            default:
                // unrecognised
                $htmlstr = "\n";
                break;
        }
         return $htmlstr;
    }
}

/**
 * CSS external stylesheet pathfinder classes
 * (very fast and simple set of methods - no need to instantiate objects)
 *
 * @package themes
 */

/*  possible paths to resolve and return:

    CASE 1 - ORIGINAL STYLESHEET OF A MODULE

    (a) Default - No Parameters
        modules/<modname>/xarstyles/<modname.css>

    (b) With Parameters
        modules/<modname>/xarstyles/<filename.fileext>

    CASE 2 - MODULE STYLESHEET OVERRIDDEN IN THEME

    (a) Default - No Parameters
        <themesdir>/<themename>/modules/<modname>/xarstyles/<modname.css>

    (b) With Parameters
        <themesdir>/<themename>/modules/<modname>/xarstyles/<filename.fileext>

    CASE 3 - MODULE STYLESHEET OVERRIDDEN IN THEME WITH ALTERNATIVE FOLDER (?)

    (a) Default - No Parameters
        <themesdir>/<themename>/<altdir>/<modname.css>

    (b) With Parameters
        <themesdir>/<themename>/<altdir>/<filename.fileext>

    CASE 4 - THEME STYLESHEETS (no overrides required)

        <themesdir>/<themename>/style/<filename.fileext>

    CASE 5 - CORE STYLESHEETS (overrides similar to modules)

        modules/themes/xarstyles/core.css

        or its dynamic equivalent

        modules/themes/xarstyles/corecss.php

    CASE 6 - GENERATED STYLESHEETS (new concept? TODO)

        var/css/modules/<modname>/xarstyles/<modname.css>
        var/css/themes/<themename>/modules/<modname>/xarstyles/<modname.css>
        var/css/core/<filename.fileext>

    SEARCH FALLBACK SEQUENCE (component dependent)

    generated -> alternative -> overridden -> original

    existence of original should be guaranteed to continue processing
*/

class xarCSSPath
{
    var $cssobj;

    // constructor
    function xarCSSPath($cssobj)
    {
        $this->cssobj = $cssobj;
    }

    // returns current var css dir for generated css
    function varcssdir()
    {
        // TODO: error checking + get var from core
        return 'var/css';
    }

    // returns current theme dir
    function themedir()
    {
        static $themedir;
        // once per pageload
        if(!isset($themedir)) $themedir = xarTplGetThemeDir();

        return $themedir;
    }

    // returns current module dir
    function currentmoddir()
    {
        // discovered it is a static var already in core
        // TODO: (remove this function? maybe not, the core one has really bad, non-descriptive name)
        return xarModGetName();
    }
}

class cssComponentPath extends xarCSSPath
{
    // returns current theme standard css path
    function std_themecsspath()
    {
        return $this->themedir()."/style/" . $this->cssobj->filename . "." . $this->cssobj->fileext;
    }
    // returns current theme alternative css path
    function alt_themecsspath()
    {
        return $this->themedir()."/".$this->cssobj->altdir."/".$this->cssobj->filename . "." . $this->cssobj->fileext;
    }
    // returns current module standard css path
    function currentmodule_csspath()
    {
        return "modules/".$this->currentmoddir()."/xarstyles/".$this->cssobj->filename . "." . $this->cssobj->fileext;
    }
    // returns current module's overridden css path
    function overridden_currentmodule_csspath()
    {
        return $this->themedir()."/modules/" . $this->currentmoddir() . "/xarstyles/".$this->cssobj->filename . "." . $this->cssobj->fileext;
    }
    // returns any module's standard css path
    function module_csspath()
    {
        return "modules/". $this->cssobj->compname . "/xarstyles/" . $this->cssobj->filename . "." . $this->cssobj->fileext;
    }
    // returns any module's overridden css path
    function overridden_module_csspath()
    {
        return $this->themedir()."/modules/" . $this->cssobj->compname . "/xarstyles/" . $this->cssobj->filename . "." . $this->cssobj->fileext;
    }
}

class cssFileInspector extends cssComponentPath
{
    function verified_module_csspath()
    {
        $msg = xarML("module css stylesheet file cannot be found in this location: ");

        if(!isset($this->cssobj->compname)){
            $original = $this->currentmodule_csspath();
        } else {
            $original = $this->module_csspath();
        }
        // we do not want to supply path for a non-existent original css file or override a bogus file
        // so lets check starting from original then fallback if there arent overriden versions
        if(file_exists($original)) {
            // how about the overridden one?
            if($this->cssobj->altdir != '') {
                $overridden = $this->alt_themecsspath();
            } else if(!isset($this->cssobj->compname)){
                $overridden = $this->overridden_currentmodule_csspath();
            } else {
                $overridden = $this->overridden_module_csspath();
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
            return $this->cssobj->_error($msg.$original);
        }
    }

    function verified_theme_csspath()
    {
        // pretty straightforward
        $themestylesheet = $this->std_themecsspath();
        $msg = xarML("theme css stylesheet file cannot be found in this location: ");
        if(file_exists($themestylesheet)) {
            // no problem
            return $themestylesheet;
        } else {
            // problem
            return $this->_error($msg.$themestylesheet);
        }
    }
}

?>