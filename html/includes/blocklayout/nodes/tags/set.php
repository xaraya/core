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
    private $_name;             // What are we setting?
    private $_nonmarkup = true; // Do we accept non markup?
    
    private $_showTemplates;    // The ShowTemplates setting we may need to save
    
    function __construct(&$parser, $tagName, $parentTagName='', $parameters=array())
    {
        parent::__construct($parser, $tagName, $parentTagName, $parameters);
        $this->hasChildren = true;
        $this->hasText = true;
        $this->isAssignable = false;
        $this->needAssignment = true;
    }

    // We could leave this out ( <xar:set name="test"/> ) ~ unset($test) ?
    function render()
    {
        return '';
    }
    
    function renderBeginTag()
    {
        $code ='';
        $nonmarkup = 'yes'; // Default is to just use what is produced. 
        extract($this->attributes);
        
        if (!isset($name)) {
            $this->raiseError(XAR_BL_MISSING_ATTRIBUTE,'Missing \'name\' attribute in <xar:set> tag.', $this);
            return;
        }
        // Allow specifying name="test" and name="$test" and deprecate the $ form over time
        if(substr($name,0,1) == XAR_TOKEN_VAR_START) {
            $this->_name = substr($name,1);
        } else {
            $this->_name = $name;
        }
        
        // Allow suppression of template comments (important when using a tag as a child tag)
        if(isset($nonmarkup) && strtolower($nonmarkup) == 'no') {
            $this->_nonmarkup = false;
            $this->_showTemplates = xarModVars::get('themes','ShowTemplates');
            $code.= 'xarModVars::set(\'themes\',\'ShowTemplates\',0);';
        }
        $code.= XAR_TOKEN_VAR_START . $this->_name;
        return $code;
    }
    
    function renderEndTag()
    {
        $code ='';
        
        if(!$this->_nonmarkup) {
            // Restore the setting from just before the set tag
            $code.='xarModVars::set(\'themes\',\'ShowTemplates\','.$this->_showTemplates.');';
        }
        /**
        *  Register the variable in the bl_data array so it's passed to included templates
         *  see the xar:template tag how this will work and bug 1120 for all the details
         */
        // FIXME: add some checking whether $name already is a template variable
        return $code .' $_bl_data[\''.$this->_name.'\'] =& '. XAR_TOKEN_VAR_START . $this->_name.';';
   }
}
?>
