<?php

/**
* xarTpl__XarWhileNode: <xar:while> tag class
 *
 * takes care of the "while(condition) {" construct
 *
 * @package blocklayout
 * @access private
 */
class xarTpl__XarWhileNode extends xarTpl__TplTagNode
{
    function renderBeginTag()
    {
        extract($this->attributes);
        
        if (!isset($condition)) {
            $this->raiseError(XAR_BL_MISSING_ATTRIBUTE,'Missing \'condition\' attribute in <xar:while> tag.', $this);
            return;
        }
        
        $condition = xarTpl__ExpressionTransformer::transformPHPExpression($condition);
        if (!isset($condition)) return; // throw back
        
        return "while ($condition) { ";
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