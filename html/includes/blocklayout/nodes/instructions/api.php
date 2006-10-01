<?php

/**
* xarTpl__XarApiInstructionNode
 *
 * API function node, treated as php expression
 *
 * @package blocklayout
 * @access private
 */
class xarTpl__XarApiInstructionNode extends xarTpl__InstructionNode
{
    function render()
    {
        if (strlen($this->instruction) <= 1) {
            $this->raiseError(XAR_BL_INVALID_INSTRUCTION,'Invalid API reference instruction.');
        }
        $funcName = substr($this->instruction, 0, strpos($this->instruction, '('));

        // FIXME: Temporary hack for bug 5369
        if(strtolower($funcName) != 'xarml') {
            // This is "reasonably" save here because xarML($somevarorphpexpression) wont work anyway, so we
            // can reasonably count on it being a string only.
            $instruction = xarTpl__ExpressionTransformer::transformPHPExpression($this->instruction);
            if (!isset($instruction)) return; // throw back
        } else {
            $instruction = $this->instruction;
        }
        
        if(!function_exists($funcName)) {
            $this->raiseError(XAR_BL_INVALID_INSTRUCTION,'Invalid API reference instruction or invalid function syntax.');
            return;
        }
        return $instruction;
    }
}
?>