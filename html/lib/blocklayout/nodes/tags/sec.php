<?php
/**
 * SecTagNode: <xar:sec> tag class
 *
 * @package blocklayout
 */
class SecTagNode extends TagNode implements ElementTag
{
    function __construct(&$parser, $tagName, $parentTagName='', $parameters=array())
    {
        parent::__construct($parser, $tagName, $parentTagName, $parameters);
        $this->hasChildren = true;
        $this->hasText = true;
        $this->isAssignable = false;
    }

    function renderBeginTag()
    {
        $catch = 'true';  // Catch exceptions by default
        $component = '';  // Component is empty by default
        $instance = '';   // Instance is empty by default
        extract($this->attributes);
        
        if (!isset($mask)) {
            $this->raiseError(XAR_BL_MISSING_ATTRIBUTE,'Missing \'mask\' attribute in <xar:sec> tag.');
            return;
        }
        
        if ($catch == 'true') {
            $catch = 1;
        } elseif ($catch == 'false') {
            $catch = 0;
        } else {
            $this->raiseError(XAR_BL_INVALID_ATTRIBUTE,'Invalid \'catch\' attribute in <xar:sec> tag.'.
                              ' \'catch\' must be boolean (true or false).');
            return;
        }
        
        $component = ExpressionTransformer::transformPHPExpression($component);
        $instance = ExpressionTransformer::transformPHPExpression($instance);
        
        return "if (xarSecurityCheck(\"$mask\", $catch, \"$component\", \"$instance\")) { ";
    }
    
    function renderEndTag()
    {
        return "} ";
    }
}
?>