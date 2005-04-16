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

    var $tagdata;                   // holds all the parameters that define a tag
    var $method       = 'link';      // supported are 'link', 'import', 'embed', 'render'

    // SUPPORTED SCOPES ARE MODULE, THEME, COMMON
    var $components;                // array of all known and supported components
    var $scope      = 'module';     // component type - 'module, 'theme' or 'common'
    var $compcssdir = 'xarstyles';  // component css directory name (e.g. 'xarstyles')

    var $base       = CSSCOMMONBASE;// component name (e.g. module's name 'base')
    var $filename   = 'style';      // default css file name (without extension)
    var $fileext    = 'css';        // default css file extension
    var $commonbase = CSSCOMMONBASE;// base dirctory for common css
    var $commonsource = CSSCOMMONSOURCE;  // filename for common css

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
    var $alternatedir     = '';           // alternative directory for overridden css file

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
        if (isset($scope)) $this->scope                 = $scope;
        if (isset($method)) $this->method               = $method;
        if (isset($media)) $this->media                 = $media;
        if (isset($filename)) $this->filename           = $filename;
        if ($this->scope == 'common') {
            $this->base   = $this->commonbase;
            $this->filename   = $this->commonsource;
        }
        $this->tagdata = array(
                            'scope'            => $this->scope,
                            'method'           => $this->method,
                            'base'             => $this->base,
                            'filename'         => $this->filename,
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

    function getrelativeurl() {

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

            $original = "modules/" . $this->base . "/xarstyles/" . $this->filename . "." . $this->fileext;
            // we do not want to supply path for a non-existent original css file or override a bogus file
            // so lets check starting from original then fallback if there arent overriden versions
            if(file_exists($original)) {
                // how about the overridden one?
                if($this->alternatedir != '') {
                    $overridden = xarTplGetThemeDir() . "/" . $this->alternatedir . "/" . $this->filename . "." . $this->fileext;
                } else {
                    $overridden = xarTplGetThemeDir() . "/modules/" . $this->base . "/xarstyles/" . $this->filename . "." . $this->fileext;
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

//-----------------------
// These methods are currently not used
// TODO: check what makes  sense to keep for the UI

    function _set_other_method_vars($args)
    {
        if(is_array($args)) extract($args);

        // media attribute
        if(isset($media)) $this->set_media_attribute($media);

        // title attribute
        if(isset($title)) $this->set_tag_title($title);

        // id attribute
        if(isset($id)) $this->set_tag_id($id);

        // rel attribute
        if(isset($alternate) && $alternate == true) {
            $this->set_rel_alternate();
        } else {
            $this->set_rel_stylesheet();
        }

        // stylesheet is located in a non-standard theme folder
        if(isset($themefolder)) {
            $this->_set_altdir($themefolder);
        } else {
            $this->_set_altdir('');
        }

        // referenced file has the standard or a non-standard extension
        if(isset($fileext)) {
            $this->fileext = $fileext;
        } else {
            $this->set_fileext_css();
        }

        // TODO: remove the already set variables from the $args, perhaps?
    }

    function _suppress()
    {
        $this->suppress = true;
    }

    function _add_component($compname)
    {
        $this->components[] = $compname;
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


    // PRIVATE (and PROTECTED) UTILITY METHODS

    // alternative override dir accessor
    function _altdir()
    {
        return $this->alternatedir;
    }

    function _set_altdir($alternatedir = '')
    {
        $this->alternatedir = $alternatedir;
    }

    // PROTECTED HELPERS


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

/**
 * Queue class. Holds the tag data until it is sent to the template
 *
 *
 * @package themes
 */

global $queue;

class tagqueue {

    function register($args) {
        global $queue;
        $queue[$args['method']][$args['scope']][$args['scope']] = $args;
    }

    function deliver($sort=true) {
        global $queue;
        $styles = $queue;
        if($sort) {
            krsort($styles);
            reset($styles);
        }
        $queue = array();
        return $styles;
    }
}
?>