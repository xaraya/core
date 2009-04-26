<?php
/**
 * Implementation of xar:element tag
 *
 * @package blocklayout
 * @subpackage tags
 * @copyright The Digital Development Foundation, 2006
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @author Marcel van der Boom <mrb@hsdev.com>
 **/
 
/**
 * Class for the xar:element tag
 *
 **/
class ElementTagNode extends TagNode implements ElementTag, EmptyElementTag
{
    private $name = ''; // Name of the element to generate
    
    function __construct(&$parser,$tagName, $parentTagName='', $attributes=array())
    {
        parent::__construct($parser, $tagName, $parentTagName, $attributes);

        $this->hasChildren = true;
        $this->hasText = true;
        $this->isAssignable = false;
    }
     
    /**
     * Render the code for the begintag for the open form
     *
     * @return string code to be outputted
     * @todo   take xar:attribute tag into account
     **/
    function renderBeginTag()
    {
        $this->resolve_attrs();
        $code = '"<'.$this->name.'>"';
        return "echo $code;";
    }

    /**
     * Render the endcode for the open form
     *
     * @return string
     **/
    function renderEndTag()
    {
        $code = '"</'.$this->name.'>"';
        return "echo $code;";
    }
    
    /**
     * Render the code for the closed form
     *
     * @return string code for the rendering of the closed form
     **/
    function render()
    {
        $this->resolve_attrs();
        $code = '"<'.$this->name.'/>"';
        return "echo $code;";
    }
    
    /**
     * Resolve the attributes for this tag
     *
     * @return void
     * @todo this method should be part of the inheritance for all tags for common attributes easily (like id, class etc.)
     **/
    private function resolve_attrs()
    {
        $name = '';
        extract($this->attributes);
        if(empty($name)) {
            $this->raiseError(XAR_BL_MISSING_ATTRIBUTE,"The <xar:element/> tag requires a 'name' attribute.");
        }
        $this->name = ExpressionTransformer::transformPHPExpression($name);
    }
 }
 ?>
