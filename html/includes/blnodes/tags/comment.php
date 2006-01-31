<?php

/**
* xarTpl__XarCommentNode: <xar:comment> tag class
 *
 * Produce a comment in the output.
 * @package blocklayout
 * @access private
 * 
 * @todo Do we want to make sure the content validates as valid xml?
 * @todo Now it only goes for xml like content.
 */
class xarTpl__XarCommentNode extends xarTpl__TplTagNode
{
    var $content ='';
    function constructor(&$parser,$tagName, $parentTagName='', $attributes=array())
    {
        parent::constructor($parser, $tagName, $parentTagName, $attributes);
        // Completely skip the contents of the tag
        $endMarker = XAR_TOKEN_TAG_START . XAR_TOKEN_ENDTAG_START. XAR_NAMESPACE_PREFIX . XAR_TOKEN_NS_DELIM .'comment'. XAR_TOKEN_TAG_END;
        $res = $parser->windTo($endMarker);
        if(isset($res)) {
            // Found the endmarker, save it in the content var, so we can produce it
            $this->content = $res;
            // We found it, eat that leave next lines to be able to check easily
            //$end = $parser->peek(strlen($endMarker));
            //xarLogMessage("BL: next should read '$endMarker' : '$end'");
        }
        $this->isPHPCode = false;
        $this->hasChildren = true;
        $this->hasText = true;
        $this->isAssignable = false;
    }

    function renderBeginTag()
    {
        // Clear the children array, since we already scanned and saved them in content
        $this->children = array();
        return '<!--'.$this->content;
    }

    function renderEndTag()
    {
        return '-->';
    }

    function render()
    {
        // This is just here to prevent the abstract method to kick in
        // FIXME: see above
        return '';
    }
}
?>