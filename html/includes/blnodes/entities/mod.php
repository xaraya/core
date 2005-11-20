<?php

/**
* xarTpl__XarModEntityNode
 *
 * Module variables entities, basically wraps xarModGetVar($module,$varname)
 *
 * @package blocklayout
 * @access private
 */
class xarTpl__XarModEntityNode extends xarTpl__EntityNode
{
    function render()
    {
        if (count($this->parameters) != 2) {
            $this->raiseError(XAR_BL_MISSING_PARAMETER,'Parameters mismatch in &xar-mod entity.', $this);
            return;
        }
        $module = $this->parameters[0];
        $name = $this->parameters[1];
        return "xarModGetVar('".$module."', '".$name."')";
    }
    
    function needExceptionsControl()
    {
        return true;
    }
}
?>