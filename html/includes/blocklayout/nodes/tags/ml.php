<?php

/**
* xarTpl__XarMlNode: <xar:ml> tag class
 *
 * @package blocklayout
 * @todo this needs to be redone completely.
 */
class xarTpl__XarMlNode extends xarTpl__TplTagNode
{
    function __construct(&$parser, $tagName, $parentTagName='', $parameters=array())
    {
        parent::__construct($parser, $tagName, $parentTagName, $parameters);
        $this->hasChildren = true;
    }

    function renderBeginTag()
    {
        if (isset($this->cachedOutput)) {
            return $this->cachedOutput;
        }
        
        if (count($this->children) == 0 ||
            ($this->children[0]->tagName != 'mlkey' &&
             $this->children[0]->tagName != 'mlstring')) {
            $this->raiseError(XAR_BL_INVALID_TAG,'Missing mlkey and mlstring tags in <xar:ml> tag.');
            return;
        }
        $mlNode = $this->children[0];
        if (!isset($mlNode)) {
            $this->raiseError(XAR_BL_INVALID_TAG,'Missing \'mlkey\' and \'mlstring\' tags in <xar:ml> tag.');
            return;
        }
        $params = '';
        foreach($this->children as $node) {
            if ($node->tagName == 'mlkey' ||
                $node->tagName == 'mlstring' ||
                $node->tagName == 'mlcomment') {
                continue;
            }
            if ($node->tagName != 'mlvar') {
                $node->raiseError(XAR_BL_INVALID_TAG,"The '".$this->tagName."' tag cannot have children of type '".$node->tagName."'.");
                return;
            }
            $params .= $node->render();
        }
        $output = $mlNode->renderBeginTag() . $params . $mlNode->renderEndTag();
        
        $this->cachedOutput = $output;
        // Need to delete our children since this tag has specific knowledge about
        // its children and need to behave properly, so it renders in a custom way,
        // and caches the result.
        $this->children = array();
        
        return $output;
    }
    
    function renderEndTag()
    {
        return '';
    }
}
?>