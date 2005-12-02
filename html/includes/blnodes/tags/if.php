<?php

/**
* xarTpl__XarIfNode : <xar:if> tag class
 *
 * @package blocklayout
 */
class xarTpl__XarIfNode extends xarTpl__TplTagNode
{
    function renderBeginTag()
    {
        extract($this->attributes);
        
        if (!isset($condition) or trim($condition) == '') {
            $this->raiseError(XAR_BL_MISSING_ATTRIBUTE,'Missing \'condition\' attribute in <xar:if> tag.', $this);
            return;
        }
        
        $condition = xarTpl__ExpressionTransformer::transformPHPExpression($condition);
        if (!isset($condition)) return; // throw back
        
        return "if ($condition) { ";
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
}
?>