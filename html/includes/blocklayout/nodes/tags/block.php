<?php

/**
 * xarTpl__XarBlockNode: <xar:block> tag class
 *
 * Tag summary:
 *   Mandatory attributes: either 'instance' or ('module' and 'type')
 *   Optional attributes: 'title', 'template', 'name', 'state'
 *   Other attributes: all remaining, collected into an array
 *   Tag content: not supported for the present time
 * @package blocklayout
 * @access private
 * @todo try to get rid of the dependency with xarVar.php (xarVar_addslashes)
 * @todo there is a return in the middle of this handler (effectively ignoring children) CORRECT THIS!
 */
class xarTpl__XarBlockNode extends xarTpl__TplTagNode
{
    public $blockgrouptemplate = NULL; // is written to by blockgroup node 
    
    function __construct(&$parser, $tagName, $parentTagName='', $parameters=array())
    {
        parent::__construct($parser, $tagName, $parentTagName, $parameters);
        $this->hasText = true;
    }
    
    function renderBeginTag()
    {
        extract($this->attributes);
        
        if (empty($instance) && (empty($module) || empty($type))) {
            $this->raiseError(XAR_BL_MISSING_ATTRIBUTE, 'Tag <xar:block> requires either an \'instance\' or both a \'module\' and \'type\' tag.', $this);
            return;
        }
        
        // Collect the remaining attributes together.
        $content = $this->attributes;
        
        // Remove the attributes that are handled outside the content.
        foreach(array('instance', 'module', 'type', 'name', 'title', 'template', 'state') as $std_attribute) {
            if (isset($content[$std_attribute])) {
                $$std_attribute = '"' . xarVar_addSlashes($content[$std_attribute]) . '"';
                unset ($content[$std_attribute]);
            } else {
                $$std_attribute = 'NULL';
            }
        }
        
        // PHP code for the block parameter override array.
        foreach($content as $attr_name => $attr_value) {
            $content[$attr_name] = '\'' . $attr_name . '\'=>"' . xarVar_addSlashes($attr_value) . '"';
        }
        $override = 'array(' . implode(', ', $content) . ')';
                   
        // Code for rendering the block tag.
        // Use double-quotes so variables can be expanded within the attributes
        // for more dynamic blocks.
        $blockgrouptemplate = isset($this->blockgrouptemplate) ? $this->blockgrouptemplate : '';
        $code = <<<EOT
                   xarBlock_renderBlock(
                                        array(
                                              'instance' => $instance,
                                              'module' => $module,
                                              'type' => $type,
                                              'name' => $name,
                                              'title' => $title,
                                              'template' => $template,
                                              // Allow the box template to be set from a xar:blockgroup tag.
                                              'box_template' => ('$blockgrouptemplate'),
                                              'state' => $state,
                                              'content' => $override
                                              )
                                        )
EOT;
        return $code;
                  
        // TODO: what shall we do about the content?
        // Ideally we could have child tags to supply content not appropriate to attributes.
        if (isset($this->children) && count($this->children) > 0) {
            $contentNode = $this->children[0];
            if (isset($contentNode)) {
                $content = trim(addslashes($contentNode->render()));
            }
        }
    }

    function renderEndTag()
    {
        return '';
    }
}
?>
