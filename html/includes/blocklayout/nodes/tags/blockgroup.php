<?php
/**
 * xarTpl__XarBlockGroupNode: <xar:blockgroup> tag class
 *
 * @package blocklayout
 * @access private
 * @todo the renderbegintag use of semicolons looks weird, why is that?
 */
class xarTpl__XarBlockGroupNode extends xarTpl__TplTagNode
{
    public $template = NULL;
    
    function __construct(&$parser, $tagName, $parentTagName='', $attributes=array())
    {
        parent::__construct($parser, $tagName, $parentTagName, $attributes);
        $this->hasChildren = true;
        // FIXME: this should be true on closed form and false on open form, but 
        // we only know that while generating the code into the template, so we
        // need to reorganize that a bit
        $this->isAssignable = true;
    }

    function renderBeginTag()
    {
        extract($this->attributes);
        
        if (isset($name)) {
            $this->raiseError(XAR_BL_INVALID_TAG,'Cannot have \'name\' attribute in open <xar:blockgroup> tag.');
            return;
        }
        
        // Template attribute is optional.
        $code ='\'\';';
        // If a grouptemplate is set, notify the children
        // Note that we are just in time here to notify the children that a
        // blockgroup template is going to be used. 
        if (isset($template)) {            
            $children =& $this->children; 
            for($i=0;$i < count($children); $i++) {
                $children[$i]->blockgrouptemplate = $template;
            }
        }
        return $code;
    }
    
    function renderEndTag()
    {
        return '';
    }
    
    function render()
    {
        extract($this->attributes);
        
        if (!isset($name)) {
            $this->raiseError(XAR_BL_MISSING_ATTRIBUTE,'Missing \'name\' attribute in <xar:blockgroup> tag.');
            return;
        }
        
        if (isset($template)) {
            return 'xarBlock_renderGroup("' . xarVar_addSlashes($name) . '", "' . xarVar_addSlashes($template) . '")';
        } else {
            return 'xarBlock_renderGroup("' . xarVar_addSlashes($name) . '")';
        }
    }
}
?>
