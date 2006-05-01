<?php

/**
* xarTpl__XarEventNode: <xar:event> tag class
 *
 * @package blocklayout
 * @access private
 * @todo Events are triggered by core only, how does this tag fit in?
 */
class xarTpl__XarEventNode extends xarTpl__TplTagNode
{
    function __construct(&$parser, $tagName, $parentTagName='', $attributes=array())
    {
        parent::__construct($parser, $tagName, $parentTagName, $attributes);
        $this->isAssignable = false;
    }

    function render()
    {
        extract($this->attributes);
        
        if (!isset($name)) {
            $this->raiseError(XAR_BL_MISSING_ATTRIBUTE,'Missing \'name\' attribute in <xar:event> tag.', $this);
            return;
        }
        
        return "xarEvents::trigger('$name')";
    }
}
?>