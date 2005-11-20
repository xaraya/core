<?php

/**
* xarTpl__XarOtherNode: handle module registered tags
 *
 * @package blocklayout
 * @access private
 * @todo improve the flexibility for registered tags/foreign tags
 * @todo add the possibility to be 'relaxed', just ignoring unknown tags?
 * @todo find a way to add renderbegin and renderend methods so custom tags can have open form
 * @todo should expression resolving for attributes be done here or in the handler?
 */
class xarTpl__XarOtherNode extends xarTpl__TplTagNode
{
    var $tagobject;
    
    function constructor(&$parser, $tagName, $parentTagName, $attributes)
    {
        xarLogMessage("Constructing custom tag: $tagName");
        parent::constructor($parser, $tagName, $parentTagName, $attributes);
        $this->tagobject = xarTplGetTagObjectFromName($tagName);
    }
    
    function render()
    {
        assert('isset($this->tagobject); /* The tagobject should have been set when constructing */');
        if (!xarTplCheckTagAttributes($this->tagName, $this->attributes)) return;
        return $this->tagobject->callHandler($this->attributes,'render');
    }
    
    function renderBeginTag()
    {
        assert('isset($this->tagobject); /* The tagobject should have been set when constructing */');
        if (!xarTplCheckTagAttributes($this->tagName, $this->attributes)) return;
        return $this->tagobject->callHandler($this->attributes,'renderbegintag');
    }
    
    function renderEndTag()
    {
        assert('isset($this->tagobject); /* The tagobject should have been set when constructing */');
        return $this->tagobject->callHandler($this->attributes,'renderendtag');
    }
    
    function isAssignable()
    {
        return $this->tagobject->isAssignable();
    }
    
    function isPHPCode()
    {
        return $this->tagobject->isPHPCode();
    }
    
    function hasText()
    {
        return $this->tagobject->hasText();
    }
    
    function needAssignment()
    {
        return $this->tagobject->needAssignement();
    }
    
    function hasChildren()
    {
        return $this->tagobject->hasChildren();
    }
    
    function needParameter()
    {
        return $this->tagobject->needParameter();
    }
    
    function needExceptionsControl()
    {
        return $this->tagobject->needExceptionsControl();
    }
}
?>