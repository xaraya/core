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
    
    var $language   = 'html';       // only (x)html compliant css inclusion is supported out of the box
    
    var $method     = 'link';       // also supported are 'import' and embedded 'style'
    
    // SUPPORTED COMPONENTS ARE MODULE (BLOCK), THEME, CORE (anything else out there?)
    var $comptype   = 'module';     // component type - 'module, 'theme' or 'core'
    var $compname   = null;         // component name (e.g. module's name 'base')
    var $compcssdir = 'xarstyles';  // component css directory name
    
    var $filename   = 'style';      // default css file name (without extension)
    var $fileext    = 'css';        // default css file extension    
    
    var $source     = null;         // empty source should not be included (ideally)
    
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
    
    // STYLESHEETS DUMP
//     var $cssdump;      // Collect info for all stylesheets to be used
    
    // constructor
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
    
    function set_type($type)
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
        $this->set_media(CSSMEDIAALL);
    }
    
    function set_media_screen()
    {
        $this->set_media(CSSMEDIASCREEN);
    }
    
    function set_media_print()
    {
        $this->set_media(CSSMEDIAPRINT);
    }
    
    function set_media_handheld()
    {
        $this->set_media(CSSMEDIAHANDHELD);
    }
    
    function set_media_projection()
    {
        $this->set_media(CSSMEDIAPROJECTION);
    }
    
    function set_media_aural()
    {
        $this->set_media(CSSMEDIAAURAL);
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
        $this->method = 'link';
    }
    
    function set_method_import()
    {
        $this->method = 'import';
    }
    
    function set_method_style()
    {
        $this->method = 'style';
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
        $this->fileext = 'css';
    }
    
    function set_fileext_php()
    {
        $this->fileext = 'php';
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
    
    // access doc language for output sensitivity (TODO: xml perhaps)
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
        $this->language = 'html';
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
        
        // that's all we care to do ATM, and rather quietly too
        $GLOBALS['xarTpl_additionalStyles'][$this->comptype][$this->compname][] = $htmlstr;
        
        // return the result only if debug is on
        if($this->debug) return $htmlstr;
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
        // in absence of a more generic core facility
        require_once "csspath.class.php";
        
        // make sure current module is known in advance
        if(!isset($this->compname)) $this->compname = xarCSSPath::currentmoddir();
        
        // check and return
        switch($this->comptype)
        {
            case "core":
            case "module":
                return cssFileInspector::verified_module_csspath();
                break;
            case "theme":
                return cssFileInspector::verified_theme_csspath();
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
        // in absence of a more generic core facility
        require_once "tagmaker.class.php";
        
        switch($this->method)
        {
            case "link":
                return linkCSSTag::render();
                break;
            case "import":
                return importCSSTag::render();
                break;
            case "style":
                return styleCSSTag::render();
                break;
            default:
                // unrecognised
                return '';
                break;
        }
    }
    
    // PROTECTED HELPERS
    function _error($msg = null)
    {
        if(isset($msg)) xarErrorSet(XAR_SYSTEM_EXCEPTION, 'UNKNOWN', new SystemException($msg));
    }
    
    // toggle debug and parse modes dynamically
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