<?php

/**
 * The default xml handler constructs a tree out of the
 * parsed xml
 *
 * @package xml
 * 
 */
class xarXmlToArray extends xarXmlTransformer
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


?>