<?php

/**
* xarTpl__XarModUrlEntityNode
 *
 * Module url entities, wraps xarModUrl(module, type, func)
 *
 * @package blocklayout
 * @access private
 */
class xarTpl__XarModurlEntityNode extends xarTpl__EntityNode
{
    function render()
    {
        if (count($this->parameters) != 3) {
            $this->raiseError(XAR_BL_MISSING_PARAMETER,'Parameters mismatch in &xar-modurl entity.', $this);
            return;
        }
        $module = $this->parameters[0];
        $type = $this->parameters[1];
        $func = $this->parameters[2];

        // TODO: see xarTpl_EntityNode in xarBLCompiler.php
        $args = '';
        // If the template specifies extra params with &amp;, notify xarModUrl of this,
        // so it can generate the proper ?. Workaround for bug 3603
        if ($this->hasExtras) {
            $args = ", array(NULL=>NULL)";
        }
        return "xarModURL('$module','$type','$func'$args)";
    }
}
?>