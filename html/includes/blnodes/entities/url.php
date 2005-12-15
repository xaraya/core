<?php

/**
* xarTpl_XarUrlEntityNode
 *
 * More generic than ModUrlEntityNode, supports args
 * this wraps xarModURL('$module', '$type', '$func'$args)
 *
 * @package blocklayout
 * @access private
 * @todo model this class and the xarTpl__XarModUrlEntityNode as parent/derived pair.
 */
class xarTpl__XarUrlEntityNode extends xarTpl__EntityNode
{
    function render()
    {
        if (count($this->parameters) < 3) {
            $this->raiseError(XAR_BL_MISSING_PARAMETER,'Parameters mismatch in &xar-url entity.', $this);
            return;
        }
        $module = $this->parameters[0];
        if ($module == '') {
            $tplVars =& xarTpl__TemplateVariables::instance();
            $module = $tplVars->get('module');
            if (empty($module)) {
                $this->raiseError(XAR_BL_MISSING_PARAMETER,'Empty module parameter in &xar-url entity.', $this);
                return;
            }
        }
        $type = $this->parameters[1];
        if ($type == '') {
            $type = 'user';
        }
        $func = $this->parameters[2];
        if ($func == '') {
            $func = 'main';
        }
        $args = '';
        if (isset($this->parameters[3])) {
            $args = ', array($'.$this->parameters[3].')';
        } elseif($this->hasExtras) {
            // If the template specifies extra params with &amp;, notify xarModUrl of this,
            // so it can generate the proper ?. Workaround for bug 3603
            $args = ", array(NULL=>NULL)";
        }
        return "xarModURL('$module', '$type', '$func'$args)";
    }
}
?>
