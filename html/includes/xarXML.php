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
 * @author Marcel van der Boom <marcel@xaraya.com>
 */

/** 
 * This subsystem offers an interface to Xaraya to handle XML data
 * It includes:
 * - Generic XML parser with an interface to parse any XML data including the
 *   callback functions
 * - xml producers   : [   ? -> xml ] make input available as XML data
 * - xml transformers: [ xml -> xml ] transform XML into XML
 * - xml processors  : [ xml ->   ? ] process XML data to output format
 *
 * By linking a producer to a tranformer(-chain) and zero or one processor we can
 * theoretically handle any input to be transformed into any output.
 *
 */

error_reporting(E_ALL);

/**
 * Defines make our life a bit easier.
 *
 */
define('XARXML_VERSION','0.0.2');
define('XARXML_PARSERCLASS' ,'xarXmlParser');
define('XARXML_HANDLERCLASS','xarAbstractXmlHandler');

// What class are we instatiating as the default handler?
define('XARXML_DEFAULTHANDLER','xarXmlDefaultHandler');

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

// Parse directly when reading a file
// This is mainly a switch for using xml_parse or
// xml_parse_into_struct. It all depends whether the
// re-arranging of the php generated struct is faster
// then creating our own struct 
// Using the chunk based parser, for large files 
// and using 'true' SAX parsing (like direct filters or
// chaining handlers, will be much faster and cost less
// memory. We make it definable anyway, so we can prove
// the above statement.
define('XARXML_PARSEWHILEREAD',true);

// Handler states
define('XARXML_HSTATE_INITIAL'          , 0);
define('XARXML_HSTATE_NORMAL'           , 1);
define('XARXML_HSTATE_DTDNAME_EXPECTED' , 2);
define('XARXML_HSTATE_WAITCONDOPEN'     , 4);
define('XARXML_HSTATE_DTDCLOSE_EXPECTED', 8);
define('XARXML_HSTATE_DTDGATHERING'     ,16);

// Attribute names for the tree constructed by the default handler
define('XARXML_ATTR_TAGINDEX','tagindex'); 
define('XARXML_ATTR_TYPE','type');
define('XARXML_ATTR_NAME','name');
define('XARXML_ATTR_CHILDREN','children');
define('XARXML_ATTR_CONTENT','content');
define('XARXML_ATTR_ATTRIBUTES','attributes');
define('XARXML_ATTR_NAMESPACES','namespaces');

// Include the parser
include "includes/xml/xarXMLparser.php";

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
 * Base class for XML parse handlers
 *
 * This class forms the base for defining handlers. Override
 * this class with your own methods with the same name to create
 * a xml handler object which handles the parsing for you. The default
 * behaviour of this handler is to do nothing.
 * 
 * @package xml
 * @todo test,test,test
 * @todo document the strange xml_set_object thingie
 */
class xarAbstractXmlHandler
{
    var $output;    // The output the handler produces.

    // Abstract functions
    function default_handler(){}
    function character_data(){}
    function open_tag(){}
    function close_tag(){}
    function process_instruction(){}
    function unparsed_entity(){}
    function notation_declaraion(){}
    function external_entity_reference(){}
    function start_namespace(){}
    function end_namespace(){}
}

/**
 * XML Copy handler
 *
 * This handler just copies the input XML document to it's output 
 * producing a *syntactically* equivalent output document, mainly for
 * your overriding pleasure
 *
 * @todo evaluate whether <tag/> to <tag></tag> is bad
 */
class xarXmlCopyHandler extends xarAbstractXmlHandler
{
    var $_nsregister = array();  // Total available namespaces
    var $_nsdecl     = array();  // Namespaces declarations

    function xarXmlCopyHandler() { $this->output = ''; }
    function default_handler($parser,$data) { $this->output .= $data; }
    function character_data($parser,$data) {  $this->output .= $data; }
    function process_instruction($parser, $target, $data) { $this->output .= "<?$target $data ?>";}

    function open_tag($parser, $tagname, $attribs)
    {
        $tag  = $this->__resolveTagPrefix($tagname);
        $this->output .="<$tag";

        // Handle namespace declarations.
        if(count($this->_nsdecl) > 0 ) {
            foreach($this->_nsdecl as $prefix => $uri) {
                $this->output .= " xmlns";
                if($prefix) $this->output .= XARXML_NAMESPACE_SEP . "$prefix";
                $this->output .= "=\"$uri\"";
            }
        }
        $this->_nsdecl = array();

        foreach($attribs as $attrib => $value) {
            $this->output .= " $attrib=\"$value\"";
        }
        $this->output .= ">";
    }

    function close_tag($parser, $tagname) 
    {
        $tag = $this->__resolveTagPrefix($tagname);
        $this->output .= "</$tag>";
    }
  
    function external_entity_reference($parser, $entity_names, $system_id, $public_id)
    {
        $entity = array_pop(explode(XARXML_ENTITY_SEP, $entity_names));
        $this->output .= "&$entity;";
        return true;
    }

    function start_namespace($parser, $prefix, $uri) 
    {
        // We found a namespace declaration, register them so, the open tag can handle it
        $this->_nsregister[$prefix] = $uri;
        $this->_nsdecl[$prefix] = $uri;
        return true;
    }   

    function __resolveTagPrefix($tagname) 
    {
        $tag_parts = explode(XARXML_NAMESPACE_SEP,$tagname);
        $tag = array_pop($tag_parts);
        $uri = implode(XARXML_NAMESPACE_SEP, $tag_parts);
        $prefix = array_search($uri,$this->_nsregister);
        if($prefix == '0') $prefix=false;
        if($prefix) 
            return $prefix. XARXML_NAMESPACE_SEP .$tag;
        else 
            return $tag;
    }

}

/** 
 * External entity resolver
 *
 * This handlers takes the xml document and inserts the content
 * of all referenced external entities into the xml document 
 * producing a new xml document
 *
 */
class xarXmlEntityHandler extends xarXmlCopyHandler
{
    function external_entity_reference($parser, $entity_names,  $resolve_base, $system_id, $public_id)
    {
        $entity_content = '';
        //echo "External entity ref handler\n";
        if($system_id) {
            // FIXME: I don't know the logic when to use public id and when to use system_id
            //        for now i only use system_id, which is a filename.
            // system_id is a filename, and as the $resolve_base is always empty we have to cope here
            if(!file_exists($system_id)) {
                // couldn't find it directly through absolute reference, try relative
                // if that doesn't help, the parser will raise an error for us
                if($this->_resolve_base) $system_id=$this->_resolve_base ."/". $system_id;
            } 
            if(!file_exists($system_id)) return false;

            // External entities may be empty
            if(filesize($system_id) != 0) {
                $fp = fopen($system_id,"r");
                $entity_content = fread($fp,filesize($system_id));
                fclose($fp);
            }
        }
        // What to do with this content?
        $this->output .= $content;
    }     
}

/**
 * The default xml handler constructs a tree out of the
 * parsed xml
 *
 * @package xml
 * 
 */
class xarXmlDefaultHandler extends xarAbstractXmlHandler
{
    var $_tree = array();
    var $_depth = 1;
    var $_tagindex;
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
     * Constructor
     *
     * @param integer $indexstart where to start counting
     */
    function xarXmlDefaultHandler($indexstart=1) 
    {
        $this->_tagindex=$indexstart;
    }

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
            $this->_tree[0][XARXML_ATTR_TYPE] = XML_DOCUMENT_NODE;
            $this->_tree[0][XARXML_ATTR_NAME] = '#document';
            $this->_tree[0][XARXML_ATTR_TAGINDEX]=$this->_tagindex;
            $this->_tagindex++;
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
                    $this->_tree[0][XARXML_ATTR_ATTRIBUTES][$attribute_name] = $attribute_value;
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
        if(array_key_exists(XARXML_ATTR_CONTENT,$this->_tree[$this->_depth-1])) {
            $this->_tree[$this->_depth-1][XARXML_ATTR_CONTENT] .= trim($data);
        } else {
            $this->_tree[$this->_depth-1][XARXML_ATTR_CONTENT] = trim($data);
        }
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
     * @todo the ID attribute should be unique, check for that somehow
     *
     */
    function open_tag($parser, $tagname, $attribs, $type=XML_ELEMENT_NODE) 
    {
        // Next line is basically the crux of the whole thing, to construct the tree
        $this->_tree[$this->_depth] = &$this->_tree[$this->_depth -1][XARXML_ATTR_CHILDREN][];
        $this->_tree[$this->_depth][XARXML_ATTR_NAME]= $tagname;
        $this->_tree[$this->_depth][XARXML_ATTR_TYPE] = $type;
        $this->_tree[$this->_depth][XARXML_ATTR_TAGINDEX]=$this->_tagindex;
        
        $attribs and $this->_tree[$this->_depth][XARXML_ATTR_ATTRIBUTES] = $attribs;
        // See if the ns handler has registered namespaces
        if(count($this->_nsregister) > 0 ) {
            foreach($this->_nsregister as $prefix => $uri) {
                $this->_tree[$this->_depth][XARXML_ATTR_NAMESPACES][$prefix] = $uri;
            }
            // We can now reset the ns register, as they are stored in the structure
            $this->_nsregister=array();
        }
        $this->_tagindex++;
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
    function process_instruction($parser, $target , $data) 
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
     * @todo figure out the logic for public_id and system id 
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
            $ee_handler = new $ee_handlername($this->_tagindex);
            $ee_parser = new xarXmlParser($parser->encoding,$ee_handler);
            // FIXME: I don't know the logic when to use public id and when to use system_id
            //        for now i only use system_id, which is a filename.
            // system_id is a filename, and as the $resolve_base is always empty we have to cope here
            if(!file_exists($system_id)) {
                // couldn't find it directly through absolute reference, try relative
                // if that doesn't help, the parser will raise an error for us
                if($this->_resolve_base) $system_id=$this->_resolve_base ."/". $system_id;
            } 
            if(!file_exists($system_id)) return false;

            // External entities may be empty
            $ee_tree = array();
            if(filesize($system_id) != 0) {
                $fp = fopen($system_id,"r");
                $content = trim(fread($fp,filesize($system_id)));
                fclose($fp);
                str_replace("\r\n", "\n", $content);
                str_replace("\r", "\n", $content);
                if(strlen($content)) {
                    if(!$ee_parser->parseString($content)) {
                        echo $system_id .":". $ee_parser->lastmsg."\n";
                        return false;
                    }
                    $ee_tree = $ee_parser->parsed_result;
                }
            }
        }
        // The node in the parent is an entity reference
        $this->_tagindex = $ee_parser->handler->_tagindex;
        $this->open_tag($parser,$entity,array(), XML_ENTITY_REF_NODE);
        $this->_tree[$this->_depth-1][XARXML_ATTR_CHILDREN] = $ee_tree;
        $this->_tree[$this->_depth-1][XARXML_ATTR_CHILDREN][0][XARXML_ATTR_TYPE] =  XML_ENTITY_NODE;
        $this->_tree[$this->_depth-1][XARXML_ATTR_CHILDREN][0][XARXML_ATTR_NAME] =  $entity;
      
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
        //echo "Unparsed entity handler for $entity_name, $resolve_base, $system_id, $public_id, $notation_name\n";
        return true;
    }
    
    /**
     * Handler for notation declarations
     *
     * Likely we don't need this, but here it is.
     *
     * @todo at least add the node into the tree for this handler
     */
    function notation_declaration($parser, $notation_name, $resolve_base, $system_id, $public_id) 
    {
        //echo "Notation declaration handler for $notation_name, $resolve_base, $system_id, $public_id\n";
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

//
// TEMPORARY FUNCTIONS
//

// Just for convenience for now, should go into separate class
function getElementsByname($name,$tree=NULL)
{
    $results=array();
    $query=array('type'  => XARXML_ATTR_NAME,
                 'match' => $name
                 );
    if(!$tree) return;

    // return array of nodes which are of type XML_ELEMENT_NODE and have name = $name
    // First node of the tree will always be document node
    $results = queryTree($tree[0],$query, XML_ELEMENT_NODE);
    
    return $results;
}

function getSubTree($element_id, $tree=NULL)
{
    $results=array();
    $query =array('type'  => XARXML_ATTR_TAGINDEX,
                  'match' => $element_id);
    if(!$tree) return;
    $results = queryTree($tree[0],$query, XML_ELEMENT_NODE,true);
    
    return $results;
}

/**
 * Just for convenience for now, should go into separate class
 *
 * @todo remove the @
 *
 */
function queryTree($subtree, $query, $nodetype,$returnsubtree=false) {
    $results = array();
    
    // If the node has children inspect them first, so we have simpler code in the second part (the unset)
    if(array_key_exists(XARXML_ATTR_CHILDREN, $subtree)) {
        foreach($subtree[XARXML_ATTR_CHILDREN] as $child) {
            $results = array_merge($results, queryTree($child,$query,$nodetype,$returnsubtree));
        }
    }
    
    // Inspect this node
    if((@$subtree[XARXML_ATTR_TYPE] == $nodetype) && ($subtree[$query['type']] === $query['match'])) {
        // We found a node, add it to the result array
        if(!$returnsubtree) {
            unset($subtree[XARXML_ATTR_CHILDREN]);
        }
        $results[] = $subtree;
    }
    
    return $results;   
}



?>
