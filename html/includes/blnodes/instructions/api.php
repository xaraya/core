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
            $this->raiseError(XAR_BL_INVALID_INSTRUCTION,'Invalid API reference instruction.', $this);
        }
        $instruction = xarTpl__ExpressionTransformer::transformPHPExpression($this->instruction);
        if (!isset($instruction)) return; // throw back
        
        $funcName = substr($instruction, 0, strpos($instruction, '('));
        if(!function_exists($funcName)) {
            $this->raiseError(XAR_BL_INVALID_INSTRUCTION,'Invalid API reference instruction or invalid function syntax.', $this);
            return;
        }
        return $instruction;
    }
}
?>