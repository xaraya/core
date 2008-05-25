<?php
/**
 * IfTagNode : <xar:if> tag class
 *
 * @package blocklayout
 */
class IfTagNode extends TagNode implements ElementTag
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
        
        if (!isset($condition) or trim($condition) == '') {
            $this->raiseError(XAR_BL_MISSING_ATTRIBUTE,'Missing \'condition\' attribute in <xar:if> tag.');
            return;
        }
        
        $condition = ExpressionTransformer::transformPHPExpression($condition);
        if (!isset($condition)) return; // throw back
        
        return "if ($condition) { ";
    }
    
    function renderEndTag()
    {
        return "} ";
    }
}
?>
