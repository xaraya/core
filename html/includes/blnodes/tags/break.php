<?php

/**
* xarTpl__XarBreakNode: <xar:break/> tag class
 *
 * @package blocklayout
 * @access private
 */
class xarTpl__XarBreakNode extends xarTpl__TplTagNode
{
    function render()
    {
        $depth = 1;
        extract($this->attributes);
        return " break $depth; ";
    }
    
    function isAssignable()
    {
        return false;
    }
    
    function needParameter()
    {
        return false;
    }
}
?>