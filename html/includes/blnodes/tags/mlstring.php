<?php

/**
* xarTpl__XarMlStringNode: <xar:mlstring> tag class
 *
 * @package blocklayout
 * @access private
 */
class xarTpl__XarMlstringNode extends xarTpl__TplTagNode
{
    var $_rightspace;
    
    function render()
   {
        // return $this->renderBeginTag() . $this->renderEndTag();
        // Dracos: copying exception checking here...it isn't getting checked in renderBeginTag() for some reason
        // Dracos: this is not the right fix for bug 229, but it works for now
        if (count($this->attributes) != 0) {
            $this->raiseError(XAR_BL_INVALID_TAG,'The <xar:mlstring> tag takes no attributes.', $this);
            return;
        }
        $output = $this->renderBeginTag();
        if(!empty($output)){
            return $output . $this->renderEndTag();
        }
        else {
            $this->raiseError(XAR_BL_INVALID_TAG,'Missing the string inside <xar:mlstring> tag.', $this);
            return;
        }
   }
    
    function renderBeginTag()
   {
        $string = '';
        
        // Dracos:  these two ifs are never true????
        if (count($this->children) == 0) {
            $this->raiseError(XAR_BL_INVALID_TAG,'Missing the string inside <xar:mlstring> tag.', $this);
            return;
        }
        if (count($this->attributes) != 0) {
            $this->raiseError(XAR_BL_INVALID_TAG,'The <xar:mlstring> tag takes no attributes.', $this);
            return;
        }
        // Children are only of text type
        foreach($this->children as $node) {
            $string .= $node->render();
        }
        // Problem here is that we *do* want trimming for translation, but *not* for the displaying as
        // they may be very relevant. Only one space is relevant though.
        // TODO: this is an XML rule (whitespace collapsing), might not apply is we're going for other output formats
        // TODO: it's now getting a bit insane not using a XML parser, this is the kind of mess we need to deal with now
        $leftspace = (strlen(ltrim($string)) != strlen($string)) ? ' ' : '';
        $this->_rightspace =(strlen(rtrim($string)) != strlen($string)) ? ' ' : '';
        $totranslate = trim($string);
        if ($totranslate == '') {
            $this->raiseError(XAR_BL_INVALID_TAG,'Missing content in <xar:mlstring> tag.', $this);
            return;
        }
        
        return "'$leftspace' . " . 'xarML(\'' . str_replace("'","\'",$totranslate) . "'";
    }

    function renderEndTag()
    {
        return ") . '" . $this->_rightspace ."'";
    }

    function hasText()
    {
        return true;
    }
}
?>
