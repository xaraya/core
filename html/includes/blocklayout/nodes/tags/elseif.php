<?php
/**
 * xarTpl__XarElseIfNode: <xar:elseif> tag class
 *
 * Takes care of ean } elseif(condition) { construct
 *
 * @package blocklayout
 * @access private
**/
class xarTpl__XarElseifNode extends xarTpl__TplTagNode
{
    function __construct(&$parser, $tagName, $parentTagName='', $attributes=array())
    {
        parent::__construct($parser, $tagName, $parentTagName, $attributes);
        $this->isAssignable = false;
    }

    function render()
    {
        extract($this->attributes);
        
        if (!isset($condition)) {
            $this->raiseError(XAR_BL_MISSING_ATTRIBUTE,'Missing \'condition\' attribute in <xar:elseif> tag.');
            return;
        }
        
        $condition = xarTpl__ExpressionTransformer::transformPHPExpression($condition);
        if (!isset($condition)) return; // throw back
        
        return "} elseif ($condition) { ";
    }
}
?>
