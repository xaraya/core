<?php
/**
 * VarEntityNode
 *
 * Variable entities, treated as BL expression
 *
 * @package blocklayout
 * @access private
 */
class VarEntityNode extends EntityNode
{
    function render()
    {
        if (count($this->parameters) != 1) {
            $this->raiseError(XAR_BL_MISSING_PARAMETER,'Parameters mismatch in &xar-var entity.');
            return;
        }
        $name = ExpressionTransformer::transformBLExpression($this->parameters[0]);
        if (!isset($name)) return; // throw back
        
        return XAR_TOKEN_VAR_START . $name;
    }
}
?>
