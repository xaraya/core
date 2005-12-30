<?php

/**
* xarTpl__XarSecNode: <xar:sec> tag class
 *
 * @package blocklayout
 */
class xarTpl__XarSecNode extends xarTpl__TplTagNode
{
    function renderBeginTag()
    {
        $catch = 'true';  // Catch exceptions by default
        $component = '';  // Component is empty by default
        $instance = '';   // Instance is empty by default
        extract($this->attributes);
        
        if (!isset($mask)) {
            $this->raiseError(XAR_BL_MISSING_ATTRIBUTE,'Missing \'mask\' attribute in <xar:sec> tag.', $this);
            return;
        }
        
        if ($catch == 'true') {
            $catch = 1;
        } elseif ($catch == 'false') {
            $catch = 0;
        } else {
            $this->raiseError(XAR_BL_INVALID_ATTRIBUTE,'Invalid \'catch\' attribute in <xar:sec> tag.'.
                              ' \'catch\' must be boolean (true or false).', $this);
            return;
        }
        
        $component = xarTpl__ExpressionTransformer::transformPHPExpression($component);
        $instance = xarTpl__ExpressionTransformer::transformPHPExpression($instance);
        
        return "if (xarSecurityCheck(\"$mask\", $catch, \"$component\", \"$instance\")) { ";
    }
    
    function renderEndTag()
    {
        return "} ";
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
    
    function needExceptionsControl()
    {
        return true;
    }
}
?>