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
    function constructor(&$parser,$tagName, $parentTagName='', $attributes=array())
    {
        parent::constructor($parser, $tagName, $parentTagName, $attributes);
        // Completely skip the contents of the tag
        // FIXME: This is a temporary solution for bug #3111
        $endMarker = XAR_TOKEN_TAG_START . XAR_TOKEN_ENDTAG_START. XAR_NAMESPACE_PREFIX . XAR_TOKEN_NS_DELIM .'comment'. XAR_TOKEN_TAG_END;
        $res = $parser->windTo($endMarker);
        if(isset($res)) {
            // We found it, eat that leave next lines to be able to check easily
            //$end = $parser->peek(strlen($endMarker));
            //xarLogMessage("BL: next should read '$endMarker' : '$end'");
        }
        $this->isPHPCode = false;
    }

    function renderBeginTag()
    {
        // Clear the children array
        // FIXME: while ignoring it in the output, the content is still parsed which can result in
        // errors. A solution would be to wrap in cdata sections then, but then parsing should really not be done
        // meaning that our current RSS solution breaks
        // See also bug #3111
        // Basically it comes down to that our RSS implementation sucks (it's not a theme in the first place)
        $this->children = array();
        return '';
    }

    function renderEndTag()
    {
        return '';
    }

    function render()
    {
        // This is just here to prevent the abstract method to kick in
        // FIXME: see above
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

    function isAssignable()
    {
        return false;
    }
}
?>