<?php

/**
* xarTpl__XarElseNode: <xar:else/> tag class
 *
 * Takes care of the "} else {" construct for both if and else tags
 *
 * @package blocklayout
 * @access private
 */
class xarTpl__XarElseNode extends xarTpl__TplTagNode
{
    function render()
    {
        switch ($this->parentTagName) {
            case 'if':
                case 'sec':
                    $output = "} else { ";
                    break;
                default:
                    $this->raiseError(XAR_BL_INVALID_TAG,"The <xar:else> tag cannot be placed under '".$this->parentTagName."' tag.", $this);
                    return;
        }
                return $output;
    }
        
        function isAssignable()
   {
            return false;
   }
        
        function needParameter()
   {
            return false;
   }
}
?>