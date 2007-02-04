<?php
/**
 * ConfigEntityNode
 *
 * Configuration entities, treated as BL expression, basically
 * a wrapping to xarConfigGetVar()
 *
 * @package blocklayout
 * @access private
 */
class ConfigEntityNode extends EntityNode
{
    function render()
    {
        if (count($this->parameters) != 1) {
            $this->raiseError(XAR_BL_MISSING_PARAMETER,'Parameters mismatch in &xar-config entity.');
            return;
        }
        $name = $this->parameters[0];
        return "xarConfigGetVar('".$name."')";
    }
}
?>