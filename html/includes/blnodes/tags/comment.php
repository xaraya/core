<?php

/**
* xarTpl__XarCommentNode: <xar:comment> tag class
 *
 * @package blocklayout
 * @access private
 * @todo let this class or derived ones also handle <!--
 */
class xarTpl__XarCommentNode extends xarTpl__TplTagNode
{
    function constructor(&$parser,$tagName)
    {
        parent::constructor($parser, $tagName);
        // Completely skip the contents of the tag
        // FIXME: This is a temporary solution for bug #3111
        $res = $parser->windTo(XAR_TOKEN_TAG_START . XAR_TOKEN_ENDTAG_START. XAR_NAMESPACE_PREFIX . XAR_TOKEN_NS_DELIM .'comment'. XAR_TOKEN_TAG_END);
    }
    function renderBeginTag()
    {
        // Clear the children array
        // FIXME: while ignoring it in the output, the content is still parsed which can result in
        // errors. A solution would be to wrap in cdata sections then, but then parsing should really not be done
        // meaning that our current RSS solution breaks
        $this->children = array();
        
        
        return '';
    }
    
    function renderEndTag()
    {
        return '';
    }
    
    function hasChildren()
    {
        return true;
    }
    
    function hasText()
    {
        return true;
    }
    
    function isPHPCode()
    {
        return false;
    }
    
    function isAssignable()
    {
        return false;
    }
}
?>