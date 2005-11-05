<?php

/**
* xarTpl__XarContinueNode: <xar:continue/> tag class
 *
 * @package blocklayout
 * @access private
 */
class xarTpl__XarContinueNode extends xarTpl__TplTagNode
{
    function render()
    {
        $depth = 1;
        extract($this->attributes);
        return  " continue $depth; ";
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
