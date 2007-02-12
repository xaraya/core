<?php
/**
 * MlStringTagNode: <xar:mlstring> tag class
 *
 * @package blocklayout
 * @access private
 */
class MlstringTagNode extends TagNode implements ElementTag
{
    private $_rightspace;

    function __construct(&$parser, $tagName, $parentTagName='', $parameters=array())
    {
        parent::__construct($parser, $tagName, $parentTagName, $parameters);
        $this->hasText = true;
    }
    
    function renderBeginTag()
    {
        $string = '';
        
        // Children are only of text type (if any)
        if(!empty($this->children)) {
            foreach($this->children as $node) {
                $string .= $node->render();
            }
        }
        // Problem here is that we *do* want trimming for translation, but *not* for the displaying as
        // they may be very relevant. Only one space is relevant though.
        // TODO: this is an XML rule (whitespace collapsing), might not apply is we're going for other output formats
        // TODO: it's now getting a bit insane not using a XML parser, this is the kind of mess we need to deal with now
        $leftspace = (strlen(ltrim($string)) != strlen($string)) ? ' ' : '';
        $this->_rightspace =(strlen(rtrim($string)) != strlen($string)) ? ' ' : '';
        $totranslate = trim($string);
       
        return "'$leftspace' . " . 'xarML(\'' . str_replace("'","\'",$totranslate) . "'";
    }

    function renderEndTag()
    {
        return ") . '" . $this->_rightspace ."'";
    }
}
?>
