<?php
/**
 * SessionEntityNode
 *
 * Session variables entities, wrapps xarSessionGetVar()
 *
 * @package blocklayout
 * @access private
 */
class SessionEntityNode extends EntityNode
{
    function render()
    {
        if (count($this->parameters) != 1) {
            $this->raiseError(XAR_BL_MISSING_PARAMETER,'Parameters mismatch in &xar-session entity.');
            return;
        }
        $name = $this->parameters[0];
        return "xarSessionGetVar('".$name."')";
    }
}
?>