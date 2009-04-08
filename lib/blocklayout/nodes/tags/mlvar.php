<?php
/**
 * MlVarTagNode: <xar:mlvar> tag class
 *
 * @package blocklayout
 * @access private
 */
class MlvarTagNode extends TagNode implements ElementTag, EmptyElementTag
{
    function __construct(&$parser, $tagName, $parentTagName='', $parameters=array())
    {
        parent::__construct($parser, $tagName, $parentTagName, $parameters);
        $this->hasChildren = true;
        $this->needParameter = true;
    }

    function renderBeginTag()
    {
        return '';
    }
    
    function renderEndTag()
    {
        return '';
    }
    
    function render()
    {
        if (isset($this->cachedOutput)) {
            return $this->cachedOutput;
        }
        
        if (count($this->children) != 1) {
            $this->raiseError(XAR_BL_INVALID_TAG,'The <xar:mlvar> tag can contain only one child tag.');
            return;
        }
        
        if (count($this->attributes) != 0) {
            $this->raiseError(XAR_BL_INVALID_TAG,'The <xar:mlvar> tag takes no attributes.');
            return;
        }
        
        $codeGenerator = new TemplateCodeGenerator();
        $codeGenerator->setPHPBlock(true);
        
        $output = ', ';
        $output .= $codeGenerator->generateNode($this->children[0]);
        $this->cachedOutput = $output;
        return $output;
    }
}
?>