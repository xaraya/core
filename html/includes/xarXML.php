<?php
/**
 * File: $Id$
 *
 * XML services for Xaraya
 *
 * @package xml
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @subpackage Generic XML services 
 * @author Marcel van der Boom <marcel@xaraya.com>
 */

/** 
 * This subsystem offers an interface to Xaraya to handle XML data
 * It includes:
 * - Generic XML parser with an interface to parse any XML data including the
 *   callback functions
 *
 * 2003-06-08: xmlrpc, translations, rss, dynamicdata all use xml. This core
 *             subsystem can be reused by them all
 */


/**
 * Defines make our life a bit easier.
 *
 */
define('XARXML_VERSION','0.0.1');
define('XARXML_PARSERCLASS' ,'xarXmlParser');
define('XARXML_HANDLERCLASS','xarXmlHandler');

// What class are we instatiating as the default handler?
define('XARXML_DEFAULTHANDLER','xarXmlHandler');

// PHP xml extension supports only three encodings
define('XARXML_CHARSET_USASCII'  ,'US-ASCII');
define('XARXML_CHARSET_ISO8859_1','ISO-8859-1');
define('XARXML_CHARSET_UTF8'     ,'UTF-8');
// The default input encoding for the parser can be set below
define('XARXML_CHARSET_DEFAULT',XARXML_CHARSET_UTF8);

// Separators
define('XARXML_NAMESPACE_SEP',':');
define('XARXML_PATH_SEP','/');
define('XARXML_ENTITY_SEP', chr(12));

// Nodes for parsing, copied from domxml extension
if(!defined('XML_ELEMENT_NODE'))       define('XML_ELEMENT_NODE'      , 1);
if(!defined('XML_ATTRIBUTE_NODE'))     define('XML_ATTRIBUTE_NODE'    , 2);
if(!defined('XML_TEXT_NODE'))          define('XML_TEXT_NODE'         , 3);
if(!defined('XML_CDATA_SECTION_NODE')) define('XML_CDATA_SECTION_NODE', 4);	 
if(!defined('XML_ENTITY_REF_NODE'))    define('XML_ENTITY_REF_NODE'   ,	5);	 
if(!defined('XML_ENTITY_NODE'))        define('XML_ENTITY_NODE'       , 6);
if(!defined('XML_PI_NODE'))            define('XML_PI_NODE'           , 7);
if(!defined('XML_COMMENT_NODE'))       define('XML_COMMENT_NODE'      , 8);
if(!defined('XML_DOCUMENT_NODE'))      define('XML_DOCUMENT_NODE'     , 9);
if(!defined('XML_DOCUMENT_TYPE_NODE')) define('XML_DOCUMENT_TYPE_NODE',10);	 
if(!defined('XML_DOCUMENT_FRAG_NODE')) define('XML_DOCUMENT_FRAG_NODE',11);	 
if(!defined('XML_NOTATION_NODE'))      define('XML_NOTATION_NODE'     ,12);
	 
if(!defined('XML_ENTITY_DECL_NODE'))   define('XML_ENTITY_DECL_NODE'  ,17);

if(!defined('XML_GLOBAL_NAMESPACE')) define('XML_GLOBAL_NAMESPACE', 1);
if(!defined('XML_LOCAL_NAMESPACE'))  define('XML_LOCAL_NAMESPACE' , 2);

// Miscellaneous
define('XARXML_BLOCKREAD_SIZE',4096);

// Handler states
define('XARXML_HSTATE_INITIAL'          , 0);
define('XARXML_HSTATE_NORMAL'           , 1);
define('XARXML_HSTATE_DTDNAME_EXPECTED' , 2);
define('XARXML_HSTATE_WAITCONDOPEN'     , 4);
define('XARXML_HSTATE_DTDCLOSE_EXPECTED', 8);
define('XARXML_HSTATE_DTDGATHERING'     ,16);

define('XARXML_DTD_TRACE',false);
define('XARXML_DTD_GUESSROOT',false);


/**
 * Start the XML subsystem
 *
 * This initializes the XML subsystem, not much for now
 * and i actually want to keep it that way. ;-)
 *
 * @access protected
 */
function xarXml_init($args, $whatElseIsGoingLoaded) 
{
    return true;

}

/**
 * Xaraya Generic namespace aware XML parser
 *
 * The Xaraya XML parser. This parser is generic in nature
 * in that it isn't configured to handle a specific XML variety
 * Using the public methods modules can instantiate a parser
 * and set the handlers of it, so it can parse a certain XML
 * document structure and actt accordingly.
 *
 * @access public
 */
class xarXmlParser 
{
    var $encoding;      // Which input encoding are we gonna use for parsing?
    var $handler;       // Which handler object is attached to this parser (of class xarXMLHandler)
    var $parser=NULL;   // The parser object itself
    var $tree=array();  // Resulting parse tree
    
    /**
     * Construct the xarXmlParser object
     *
     * For xaraya we need to be able to set encoding, 
     * and have support for namespaces, this isn't in 
     * the base class.
     *
     * @access public
     * @param string $encoding character encoding to use (see top of file) 
     */
    function xarXmlParser($encoding=XARXML_CHARSET_DEFAULT,$handler=NULL) 
    {
        $this->encoding=$encoding;
        // TODO : based on capabilities instantiate other handler here as default
        //        for example the DOMXML if available :-)
        $defHandlerClass = XARXML_DEFAULTHANDLER;
        if($handler)
            $this->handler =& $handler;
        else
            $this->handler =& new $defHandlerClass();
    }
    
    /**
     * Parse a string
     *
     * @access public
     * @param string $xmldata string representation of xmldata to parse
     */
    function parseString($xmldata) 
    {
        $this->__activate();
        // TODO: check the string for stuff, this can be very delicate
        if(!xml_parse($this->parser, $xmldata, true)) {
            $this->__deactivate();
            return false;
        }
        return $this->__deactivate();
    }
    
    /**
     * Parse a file
     *
     * @access public
     * @param string $fileName path to file to parse
     */
    function parseFile($fileName) 
    {
        $fp = fopen($fileName,"r");
        if(!is_resource($fp)) {
            $this->lastmsg="Could't open $fileName";
            return false;
        }
        // If doc is empty return false
        if(filesize($fileName) == 0) {
            $this->lastmsg="File is empty";
            return false;
        }
        // Activate the parser with resolve base the base path of the file
        $resolve_base = dirname($fileName);
        $this->__activate($resolve_base);
        while ($xmldata = fread($fp, XARXML_BLOCKREAD_SIZE)) {
            if(!xml_parse($this->parser, $xmldata, feof($fp))) {
                $error = xml_get_error_code($this->parser);
                $this->lastmsg= "[".xml_get_current_line_number($this->parser).":"
                                   .xml_get_current_column_number($this->parser)."]-"
                                   .xml_error_string($error);
                $this->__deactivate();
                return false;
            }
        }
        return $this->__deactivate();
    }

    /**
     * Set a parser option
     * 
     * @access public
     * @param integer $option option to be set, one of the XML_OPTION_* constants
     * @param mixed   $value  value to set the option to
     */
    function setOption($option, $value) 
    {
        return xml_parser_set_option($this->parser, $option, $value);
    }
    
    /**
     * Get a parser option
     *
     * @access public
     * @param  integer $option option to retrieve, one of the XML_OPTION_* constants
     */
    function getOption($option)
    {
        return xml_parser_get_option($this->parser, $option);
    }

    /** 
     * Private methods
     *
     */

    /**
     * Activate the parser
     *
     * This method activates the parser to be set up for parsring a string
     * or a file. This activate/deactivate logic is necessary because the 
     * parser can only parse 1 file/string during it's instantation. When
     * you try to parser consecutive documents with the same instance all
     * kinds of weird errors are happening.
     *
     * @access private
     * @param string $resolve_base the base from which system/public ids are resolved
     *
     */
    function __activate($resolve_base = NULL) 
    {
        $this->parser=xml_parser_create_ns($this->encoding, XARXML_NAMESPACE_SEP);
        $this->setOption(XML_OPTION_CASE_FOLDING,false);
        $this->setOption(XML_OPTION_SKIP_WHITE,true);
        $this->__activateHandlers();
        $this->handler->_resolve_base = $resolve_base;
    }

    /**
     * Deactivate the parse
     *
     * When done parsing, this method deactivates the parser
     *
     * @access private
     */
    function __deactivate() 
    {
        $this->tree = $this->handler->_tree;
        $this->handler->_reset();
        return $this->__free();
    }

    /**
     * Free the parser
     *
     * @access private
     */
    function __free() 
    { 
        return xml_parser_free($this->parser); 
    }

    /**
     * Set the handlers
     *
     * For the registered handler to the parser, this private method 
     * activates them.
     *
     * @access private
     */
    function __activateHandlers()
    {
        $par = $this->parser;
        xml_set_object($par,&$this->handler);
        xml_set_default_handler($par,               'default_handler');
        xml_set_character_data_handler($par,        'character_data');
        xml_set_element_handler($par,               'open_tag',
                                                    'close_tag');
        xml_set_processing_instruction_handler($par,'proces_instruction');
        xml_set_unparsed_entity_decl_handler($par,  'unparsed_entity');
        xml_set_notation_decl_handler($par,         'notation_declaration');
        xml_set_external_entity_ref_handler($par,   'external_entity_reference');
        xml_set_start_namespace_decl_handler($par,  'start_namespace');
        xml_set_end_namespace_decl_handler($par,    'end_namespace');
    }
}

/**
 * Base class for XML parse handlers
 *
 * This class forms the base for defining handlers. Override
 * this class with your own methods with the same name to create
 * a xml handler object which handles the parsing for you.
 * 
 *
 * @todo test,test,test
 * @todo document the strange xml_set_object thingie
 */
class xarXmlHandler 
{
    var $_tree = array();
    var $_depth = 1;
    var $_nsregister=array();
    var $_state = XARXML_HSTATE_INITIAL;
    var $_dtd_data ='';
    
    /** 
     * We need a base for resolving entities when they are not 
     * specified relatively. On creation of the handler this can have a number of values
     * 1. path of the file we're handling, so relative paths can be resolved
     * 2. NULL if we're not in a file at all
     * 3. url?
     */
    var $_resolve_base=NULL;     

    /**
     * The default handler catches everything which is not handled by others
     *
     * @param object $parser the parser to which the handler is attached
     * @param string $data   string data found in the construct
     */
    function default_handler($parser, $data) 
    {
        if(!trim($data)) return true; // nothing to do here
        
        // If we've never been here before add the initial doc node
        if($this->_state == XARXML_HSTATE_INITIAL) {
            $this->_tree[0]['type'] = XML_DOCUMENT_NODE;
            $this->_tree[0]['name'] = '#document';
            $this->_state = XARXML_HSTATE_NORMAL;
        }            

        // Subminiparser to extract the DOCTYPE
        //echo "$data\n";
        switch($this->_state) {
        case XARXML_HSTATE_NORMAL:
            // If we have the <?xml decl, add the attributes to the document node
            if(substr(trim($data),0,5) == '<?xml') {
                // get the attributes
                preg_match_all('/ (\w+=".+")/U', $data, $matches);
                foreach($matches[1] as $match) {
                    list($attribute_name, $attribute_value) = (explode('=',$match));
                    $attribute_value = str_replace('"','',$attribute_value);
                    $this->_tree[0]['attributes'][$attribute_name] = $attribute_value;
                }
            }

            if(trim($data) == '<!DOCTYPE') {
                // We expect the next time a name for the doctype
                $this->_state = XARXML_HSTATE_DTDNAME_EXPECTED;
            }
            break;
        case XARXML_HSTATE_DTDNAME_EXPECTED:
            //echo "Adding $data as doctype\n";
            $this->_state = XARXML_HSTATE_WAITCONDOPEN;
            $this->open_tag($parser,$data,array(),XML_DOCUMENT_TYPE_NODE);
            //print_r($this->_tree);
            return true;
            break;
        case XARXML_HSTATE_WAITCONDOPEN:
            if($data=='[') {
                //echo "Gathering dtd data\n";
                $this->_state = XARXML_HSTATE_DTDGATHERING;
            }
            break;
        case XARXML_HSTATE_DTDGATHERING:
            if($data==']') {
                $this->_state = XARXML_HSTATE_DTDCLOSE_EXPECTED;
                // For now just add the dtd data as content to the doctype node
                //echo "Finished dtd gathering, adding $this->_dtd_data as cdata\n";
                $this->character_data($parser,$this->_dtd_data);
                //print_r($this->_tree);
                $this->_dtd_data='';
            } else {
                $this->_dtd_data .= " " . $data;
            }
            break;
        case XARXML_HSTATE_DTDCLOSE_EXPECTED:
            if($data=='>') {
                //echo "Closing the doctype\n";
                $this->_state = XARXML_HSTATE_NORMAL;
                $this->close_tag($parser,'');
                //print_r($this->_tree);
                return true;
            }
            break;

        }
        return true;
     
    }


    /**
     * Character data handler is added as 'data' for the current tag
     *
     * @param object $parser the parser to which this handler is attached
     * @param string $data   character data found
     */
    function character_data($parser, $data) 
    {
        // this handler can be called multiple times, so make sure we're not
        // overwriting ourselves, trust the depth to put things in the right place
        $this->_tree[$this->_depth-1]['content'] .= trim($data);
    }

    /**
     * Start element handler
     *
     * This gets called when the start of a new <tag> is encountered
     * the tagname and its attributes are passed in as parameters. 
     *
     * @param $parser  object the parser which this handler is attached to
     * @param $tagname string the start tag found
     * @param $attribs array  array of attributes with [attribname] => value pairs
     *
     */
    function open_tag($parser, $tagname, $attribs, $type=XML_ELEMENT_NODE) 
    {
        // Next line is basically the crux of the whole thing, to construct the tree
        $this->_tree[$this->_depth] = &$this->_tree[$this->_depth -1]['children'][];
        $this->_tree[$this->_depth]['name']= $tagname;
        $this->_tree[$this->_depth]['type'] = $type;
        $attribs and $this->_tree[$this->_depth]['attributes'] = $attribs;
        // See if the ns handler has registered namespaces
        if(count($this->_nsregister) > 0 ) {
            foreach($this->_nsregister as $prefix => $uri) {
                $this->_tree[$this->_depth]['namespaces'][$prefix] = $uri;
            }
            // We can now reset the ns register, as they are stored in the structure
            $this->_nsregister=array();
        }
        $this->_depth++;
    }
        
    /**
     * Close element handler
     *
     * This handler is called when a closing </tag> is found. As tags in xml
     * should be properly nested we can count on these functions to be 
     * called in order
     *
     * @param $parser object the parser to which handler is attached
     * @param $tagnam string tag which is closing
     *
     */
    function close_tag($parser, $tagname) 
    {
        $this->_depth--;
        // We did the children thing already, so, we can get away with it now
        unset($this->_tree[$this->_depth]);
    }

    /**
     * Processing instruction handler
     *
     * We handle the processing instruction the same as a normal tag, but
     * distinguish it by using the type flag, adding the actual instructions 
     * as content for the tag. This is not entirely right, but enough for now
     *
     * @param object $parser the parser to which this handler is attached
     * @param string $target the part after the <? in the document
     * @param string $data   the contents of the processing instruction
     */
    function proces_instruction($parser, $target , $data) 
    {
        $this->open_tag($parser,$target,array(), XML_PI_NODE);
        $this->character_data($parser,$data);
        $this->close_tag($parser,$target);
    }

    /**
     * Handler called when an external entity reference is found
     * 
     * This can get messy. The system_id or public_id or both, refer to the 
     * location where the contents of the exernal entity can be found. 
     *
     * We support the system_id for now. We take the class of the current handler
     * and instantiate a subparser which parses the externatl entity. That subtree
     * is inserted into the children element of the entity reference node as an entity
     * node. 
     * 
    */
    function external_entity_reference($parser, $entity_names,  $resolve_base, $system_id, $public_id) 
    {
        //echo "External entity ref handler\n";
        $entity_list = explode(XARXML_ENTITY_SEP,$entity_names);
        $entity = array_pop($entity_list);
        if($system_id) {
            // Which handler are we in?
            $ee_handlername = get_class($this);
            $ee_handler = new $ee_handlername();
            $ee_parser = new xarXmlParser($parser->encoding,$ee_handler);
            // FIXME: I don't know the logic when to use public id and when to use system_id
            //        for now i only use system_id, which is a filename.
            // system_id is a filename, and as the $resolve_base is always empty we have to cope here
            if(!file_exists($system_id)) {
                // couldn't find it directly through absolute reference, try relative
                // if that doesn't help, the parser will raise an error for us
                if($this->_resolve_base) $system_id=$this->_resolve_base ."/". $system_id;
            }           
            
            if(!$ee_parser->parseFile($system_id)) {
                return false;
            }
            $ee_tree = $ee_parser->tree;
        }
        // The node in the parent is an entity reference
        $this->open_tag($parser,$entity,array(), XML_ENTITY_REF_NODE);
        $this->_tree[$this->_depth-1]['children'] = $ee_tree;
        $this->_tree[$this->_depth-1]['children'][0]['type'] =  XML_ENTITY_NODE;
        $this->_tree[$this->_depth-1]['children'][0]['name'] =  $entity;

        
        $this->close_tag($parser, $entity);
        //print_r($this->_tree);
        return true;
    }

    /**
     * Handler for unparsed entities, non xml data, like images
     *
     * Likely we don't need this, but here it is for your overriding pleasure
     */
    function unparsed_entity($parser, $entity_name, $resolve_base, $system_id, $public_id, $notation_name) 
    {
        return true;
    }
    
    /**
     * Handler for notation declarations
     *
     * Likely we don't need this, but here it is.
     */
    function notation_declaration($parser, $notation_name, $resolve_base, $system_id, $public_id) 
    {
        return true;
    }

    /**
     * Handler for namespace declarations.
     * 
     * Handler to be called when a namespace is declared. 
     * Namespace declarations occur inside start tags. 
     * But the namespace declaration start handler is called 
     * before the start tag handler for each namespace declared 
     * in that start tag.
     */
    function start_namespace($parser, $prefix, $uri) 
    {
        // We found a namespace declaration, register them so, the open tag can handle it
        $this->_nsregister[$prefix]= $uri;
        return true;
    }

    /**
     * Handler for namespace declarations
     *
     * Handler to be called when leaving the scope of a 
     * namespace declaration. This will be called, for each 
     * namespace declaration, after the handler for the end 
     * tag of the element in which the namespace was declared.
     *
     * @access protected
     * @param object $parser parser object to which handler is attached
     * @param string $prefix by which prefix is this namespace identified in the doc
     */
    function end_namespace($parser, $prefix) 
    {
        // Reset the namespace register, bit paranoid, but can't hurt
        $this->_nsregister=array();
        return true;
    }

    /**
     * Handler reset
     *
     * @access protected
     */
    function _reset() 
    {
        $this->_dtd_data='';
        $this->_state = XARXML_HSTATE_INITIAL;
        $this->_tree=array();
        $this->_depth=1;
        $this->_resolve_base=NULL;
    }
}

?>