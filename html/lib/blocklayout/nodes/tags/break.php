<?php

/**
 * BreakTagNode: <xar:break/> tag class
 *
 * @package blocklayout
 * @access private
 */
class BreakTagNode extends TagNode implements EmptyElementTag
{
    function __construct(&$parser, $tagName, $parentTagName='', $attributes=array())
    {
        parent::__construct($parser, $tagName, $parentTagName, $attributes);
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