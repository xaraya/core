<?php

/**
 * XML Copy handler
 *
 * This handler just copies the input XML document to it's output 
 * producing a *syntactically* equivalent output document, mainly for
 * your overriding pleasure
 *
 * @todo evaluate whether <tag/> to <tag></tag> is bad
 */
class xarXmlCopyTransform extends xarXmlTransformer
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

?>