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
 * By linking a producer to a transformer(-chain) and zero or one processor we can
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
define('XARXML_HANDLERCLASS','xarXmlTransformer');

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

// All this is obviously temporary until we have some progress
// Include the parser
include "includes/xml/xarXMLparser.php";
// And the tranformer
include "includes/xml/transformers/xarXMLtransformer.php";

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
