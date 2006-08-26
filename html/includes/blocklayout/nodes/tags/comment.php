<?php

/**
* xarTpl__XarCommentNode: <xar:comment> tag class
 *
 * Produce a comment in the output.
 * @package blocklayout
 * @access private
 * 
 * @todo Do we want to make sure the content validates as valid xml?
 * @todo Now it only goes for xml like content.
 */
class xarTpl__XarCommentNode extends xarTpl__TplTagNode
{
    private $iecondition = '';

    function __construct(&$parser,$tagName, $parentTagName='', $attributes=array())
    {
        parent::__construct($parser, $tagName, $parentTagName, $attributes);
        // We parse the content inside, what to do when it generates '--' in the output, that would make the output
        // invalid. 
        // Do we care? we're dealing with templates here, what content will be generated is of another parsers concern.
        // We do care, because the effects can be so confusing. Option?
        
        $this->hasChildren = true;
        $this->hasText = true;
        $this->isAssignable = false;
    }

    function renderBeginTag()
    {
        // Ok, strike my heart, we'll support ie conditional comments.
        // i.e. <!--[if lt IE 7]<something>blah</something><![endif]-->
        $iecondition='';
        extract($this->attributes);

        // Resolve it.
        $this->iecondition = xarTpl__ExpressionTransformer::transformPHPExpression($iecondition);
        
        if($this->iecondition!='') {
            $code = "'<!--[if '.".$this->iecondition.".']>'";
        } else {
            $code = "'<!--'";
        }
        return "echo $code;";
    }

    function renderEndTag()
    {
        if($this->iecondition!='') {
            $code = "'<![endif]-->'";
        } else {
            $code = "'-->'";
        }
        return "echo $code;";
    }
}
?>