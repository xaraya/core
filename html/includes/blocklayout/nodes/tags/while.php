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
    function __construct(&$parser, $tagName, $parentTagName='', $parameters=array())
    {
        parent::__construct($parser, $tagName, $parentTagName, $parameters);
        $this->hasChildren = true;
        $this->hasText = true;
        $this->isAssignable = false;
    }

    function renderBeginTag()
    {
        extract($this->attributes);
        
        if (!isset($condition)) {
            $this->raiseError(XAR_BL_MISSING_ATTRIBUTE,'Missing \'condition\' attribute in <xar:while> tag.');
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
}
?>