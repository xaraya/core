<?php
/**
 * ModEntityNode
 *
 * Module variables entities, basically wraps xarModVars::get($module,$varname)
 *
 * @package blocklayout
 * @access private
 */
class ModEntityNode extends EntityNode
{
    function render()
    {
        if (count($this->parameters) != 2) {
            $this->raiseError(XAR_BL_MISSING_PARAMETER,'Parameters mismatch in &xar-mod entity.');
            return;
        }
        $module = $this->parameters[0];
        $name = $this->parameters[1];
        return "xarModVars::get('".$module."', '".$name."')";
    }
}
?>