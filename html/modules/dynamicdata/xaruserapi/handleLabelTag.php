<?php
/**
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * Handle <xar:data-label ...> label tag
 * Format : <xar:data-label object="$object" /> with $object some DataObject
 *       or <xar:data-label property="$property" /> with $property some DataProperty
 *       <xar:data-label property="$property" label="id" /> will use <label for="dd_$property->id">...</label>
 *       <xar:data-label property="$property" label="name" /> will use <label for="$property->name">...</label>
 *       <xar:data-label property="$property" label="something" /> will use <label for="something">...</label>
 *
 * @param array $args containing the object or property
 * @return string the PHP code needed to show the object or property label in the BL template
 */
function dynamicdata_userapi_handleLabelTag($args)
{

    // remove BL handler stuff
    if (isset($args['handler_type'])) {
        unset($args['handler_type']);
    }
    if (isset($args['property'])) {
        $property  = $args['property'];
        unset($args['property']);
    }
    if (isset($args['object'])) {
        $object  = $args['object'];
        unset($args['object']);
    }
    $parts = array();
    foreach ($args as $key => $val) {
        if ($key == 'label') $key = 'for';
        if (is_numeric($val) || substr($val,0,1) == '$') {
            $parts[] = "'$key' => ".$val;
        } else {
            $parts[] = "'$key' => '".$val."'";
        }
    }
    $pargs = 'array('.join(', ',$parts).')';

    if (!empty($object)) {
        return 'echo xarVarPrepForDisplay('.$object.'->label); ';
    } elseif (!empty($property)) {
        if (!empty($args['label'])) {
            if (substr($args['label'],0,1) == '$') {
                return 'echo '.$property.'->showLabel(' . $pargs . '); ';
            } else {
                return 'echo '.$property.'->showLabel(' . $pargs . '); ';
            }
        } else {
            return 'echo xarVarPrepForDisplay('.$property.'->label); ';
        }
    } elseif(isset($args['label'])) {
        // Plain label, we want to use the template nevertheless
        if (!isset($args['title'])) $args['title']='';
        $argsstring = "array('label'=>'".$args['label']."','title'=>'".$args['title']."'";

        if(isset($args['for'])){
            $argsstring.=",'for'=>'".$args['for']."'";
        }

        $argsstring.=")";
        return "echo xarTplProperty('dynamicdata','label','showoutput',$argsstring,'label');";
    } else {
        return 'echo "I need an object or a property or a label attribute"; ';
    }
}

?>
