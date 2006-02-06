<?php

/**
* xarTpl__XarForEachNode: <xar:foreach> tag class
 *
 * Takes care of the "foreach($array as $key=>$value) { " construct
 *
 * @package blocklayout
 * @access private
 */
class xarTpl__XarForEachNode extends xarTpl__TplTagNode
{
    public $attr_value = null; // properties to hold the values of any values which might have the same name in
    public $attr_key = null;   // the scope of the foreach loop.
    public $keysavename = null;
    public $valsavename = null;
    
    function constructor(&$parser, $tagName, $parentTagName='', $parameters=array())
    {
        parent::constructor($parser, $tagName, $parentTagName, $parameters);
        $this->hasChildren = true;
        $this->hasText = true;
        $this->isAssignable = false;
    }

    function renderBeginTag()
    {
        extract($this->attributes);
        
        if (!isset($in)) {
            $this->raiseError(XAR_BL_MISSING_ATTRIBUTE,'Missing \'in\' attribute in <xar:foreach> tag.', $this);
            return;
        }
        
        if (!array($in)) {
            $this->raiseError(XAR_BL_INVALID_ATTRIBUTE,'Invalid \'in\' attribute in <xar:foreach> tag. \'in\' must be an array', $this);
            return;
        }
        
        $in = xarTpl__ExpressionTransformer::transformPHPExpression($in);
        // Create a save scope for the attributes using line and column as semi unique identifiers.
        // Note that this is only applicable on merged templates (as in: non existent in current code)
        // it's merely preparation for the one xar compile scenario
        // FIXME: keep an eye on the columns and line number, that they do *not* refer to the original template, but to
        //        the one representation one.
        if(isset($key))
            $this->keysavename = '$_bl_ks_' . substr($key,1) . '_' . $this->line . '_' . $this->column;
        if(isset($value))
            $this->valsavename = '$_bl_vs_' . substr($value,1) . '_' .$this->line .'_' . $this->column;
        
        if (isset($key) && isset($value)) {
            $this->attr_value = $value;
            $this->attr_key = $key;
            return "if(isset($value)) $this->valsavename = $value; if(isset($key)) $this->keysavename = $key; foreach ($in as $key => $value) { ";
        } elseif (isset($value)) {
            $this->attr_value = $value;
            return "if(isset($value)) $this->valsavename = $value; foreach ($in as $value) { ";
        } elseif (isset($key)) {
            $this->attr_key = $key;
            return "if(isset($key)) $this->keysavename = $key; foreach (array_keys($in) as $key) { ";
        } else {
            $this->raiseError(XAR_BL_MISSING_ATTRIBUTE,'Missing \'key\' or \'value\' attribute in <xar:foreach> tag.', $this);
            return;
        }
   }
    
    function renderEndTag()
   {
        if(isset($this->attr_value) && isset($this->attr_key))
            return "} if (isset($this->valsavename)) $this->attr_value = $this->valsavename; if (isset($this->keysavename)) $this->attr_key = $this->keysavename; ";
        if(isset($this->attr_value))
            return "} if (isset($this->valsavename)) $this->attr_value = $this->valsavename; ";
        if(isset($this->attr_key))
            return "} if (isset($this->keysavename)) $this->attr_key = $this->keysavename; ";
        
   }
}
?>