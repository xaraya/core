<?php

/**
* xarTpl__XarMlKeyNode: <xar:mlkey> tag class
 *
 * @package blocklayout
 * @access private
 */
class xarTpl__XarMlkeyNode extends xarTpl__TplTagNode
{
    function render()
    {
        return $this->renderBeginTag() . $this->renderEndTag();
    }
    
    function renderBeginTag()
    {
        $key = '';
        
        if (count($this->children) == 0) {
            $this->raiseError(XAR_BL_INVALID_TAG,'Missing the key inside <xar:mlkey> tag.', $this);
            return;
        }
        if (count($this->attributes) != 0) {
            $this->raiseError(XAR_BL_INVALID_TAG,'The <xar:mlkey> tag takes no attributes.', $this);
            return;
        }
        // Children of mlkey are only of text type (the text to be translated)
        // so this goes to TextNode render
        // MrB: isn't there always 1 child here?
        foreach($this->children as $child) {
            $key .= $child->render();
        }
        
        // FIXME: bug#45 makes this into a parse error if we don't
        //        add slashes here.
        // 1. can't be done in xarMLKey-> too late
        // 2. we can test for it above and raise an exception if we don't
        //    want to allow unescaped quotes in templates (unfriendly but right)
        //    (offer developer to use xarMLString instead)
        // 3. we can silently escape the key -> problem transferred to translators
        // FIXME: chose 3 for now, out of laziness.
        $key = trim(addslashes($key));
        if ($key == '') {
            $this->raiseError(XAR_BL_INVALID_TAG,'Missing content in <xar:mlkey> tag.', $this);
            return;
        }
        
        return "xarMLByKey(\"$key\"";
    }
    
    function renderEndTag()
    {
        return ")";
    }
    
    function hasText()
    {
        return true;
    }
}
?>
