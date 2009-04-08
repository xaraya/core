<?php
/**
 * VarInstructionNode
 *
 * models variables in the template, treats them as php expressions
 *
 * @package blocklayout
 * @access private
 */
class VarInstructionNode extends InstructionNode
{
    function render()
    {
        if (strlen($this->instruction) <= 1) {
            $this->raiseError(XAR_BL_INVALID_INSTRUCTION,'Invalid variable reference instruction.');
            return;
        }
        // FIXME: Can we pre-determine here whether a variable exist?
        $instruction = ExpressionTransformer::transformPHPExpression($this->instruction);
        if (!isset($instruction)) return; // throw back
        
        return $instruction;
    }
}
?>