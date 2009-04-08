<?php
/**
 * MlKeyTagNode: <xar:mlkey> tag class
 *
 * @package blocklayout
 * @access private
 * @todo deprecate this or at least make key an attribute, it's not content.
 */
class MlkeyTagNode extends TagNode implements ElementTag
{
    function __construct(&$parser, $tagName, $parentTagName='', $parameters=array())
    {
        parent::__construct($parser, $tagName, $parentTagName, $parameters);
        $this->hasText = true;
    }

    function renderBeginTag()
    {
        $key = '';
        
        if (count($this->children) == 0) {
            $this->raiseError(XAR_BL_INVALID_TAG,'Missing the key inside <xar:mlkey> tag.');
            return;
        }
        if (count($this->attributes) != 0) {
            $this->raiseError(XAR_BL_INVALID_TAG,'The <xar:mlkey> tag takes no attributes.');
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
            $this->raiseError(XAR_BL_INVALID_TAG,'Missing content in <xar:mlkey> tag.');
            return;
        }
        
        return "xarMLByKey(\"$key\"";
    }
    
    function renderEndTag()
    {
        return ")";
    }
}
?>
