<?php

/**
* xarTpl__XarSetNode: <xar:set> tag class
 *
 * @package blocklayout
 * @access private
 * @todo look at supporting xar:set name="$myarray['key']" again
 */
class xarTpl__XarSetNode extends xarTpl__TplTagNode
{
    var $_name;
    
    function render()
   {
        return '';
   }
    
    function renderBeginTag()
   {
        extract($this->attributes);
        
        if (!isset($name)) {
            $this->raiseError(XAR_BL_MISSING_ATTRIBUTE,'Missing \'name\' attribute in <xar:set> tag.', $this);
            return;
        }
        // Allow specifying name="test" and name="$test" and deprecate the $ form over time
        $this->_name = str_replace(XAR_TOKEN_VAR_START,'',$name);
        
        return XAR_TOKEN_VAR_START . $this->_name;
   }
    
    function renderEndTag()
   {
        /**
        *  Register the variable in the bl_data array so it's passed to included templates
         *  see the xar:template tag how this will work and bug 1120 for all the details
         */
        // FIXME: add some checking whether $name already is a template variable
        return ' $_bl_data[\''.$this->_name.'\'] =& '. XAR_TOKEN_VAR_START . $this->_name.';';
   }
    
    function isAssignable()
   {
        return false;
   }
    
    function hasChildren()
   {
        return true;
   }
    
    function needAssignment()
   {
        return true;
   }
    
    function hasText()
   {
        return true;
   }
}
?>
