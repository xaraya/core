<?php
/**
 * ApiInstructionNode
 *
 * API function node, treated as php expression
 *
 * @package blocklayout
 * @access private
 */
class ApiInstructionNode extends InstructionNode
{
    function render()
    {
        if (strlen($this->instruction) <= 1) {
            $this->raiseError(XAR_BL_INVALID_INSTRUCTION,'Invalid API reference instruction.');
        }
        // pretty weak, but sufficient in many cases
        $funcName = substr($this->instruction, 0, strpos($this->instruction, '('));

        // FIXME: Temporary hack for bug 5369
        if(strtolower($funcName) != 'xarml') {
            // This is "reasonably" save here because xarML($somevarorphpexpression) wont work anyway, so we
            // can reasonably count on it being a string only.
            $instruction = ExpressionTransformer::transformPHPExpression($this->instruction);
            if (!isset($instruction)) return; // throw back
        } else {
            $instruction = $this->instruction;
        }

        // The funcname can take the shape of:
        // 1. a normal function call :  xarFuncName()
        // 2. a static function call :  xarClassName::xarFuncName()
        if(!is_callable($funcName)) {
            $this->raiseError(XAR_BL_INVALID_INSTRUCTION,'Invalid API reference instruction or invalid function syntax.');
            return;
        }
        return $instruction;
    }
}
?>