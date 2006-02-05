<?php

/**
* xarTpl__XarBreakNode: <xar:break/> tag class
 *
 * @package blocklayout
 * @access private
 */
class xarTpl__XarBreakNode extends xarTpl__TplTagNode
{
    function constructor(&$parser, $tagName, $parentTagName='', $attributes=array())
    {
        parent::constructor($parser, $tagName, $parentTagName, $attributes);
        $this->isAssignable = false;
    }

    function render()
    {
        $depth = 1;
        extract($this->attributes);
        return " break $depth; ";
    }
}
?>