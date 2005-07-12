<?php
/**
 * File: $Id$
 *
 * Handle data label tags
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata module
 * @author mikespub <mikespub@xaraya.com>
*/
/**
 * Handle <xar:data-label ...> label tag
 * Format : <xar:data-label object="$object" /> with $object some Dynamic Object
 *       or <xar:data-label property="$property" /> with $property some Dynamic Property
 *       <xar:data-label property="$property" label="id" /> will use <label for="dd_$property->id">...</label>
 *       <xar:data-label property="$property" label="name" /> will use <label for="$property->name">...</label>
 *       <xar:data-label property="$property" label="something" /> will use <label for="something">...</label>
 *
 * @param $args array containing the object or property
 * @returns string
 * @return the PHP code needed to show the object or property label in the BL template
 */
function dynamicdata_userapi_handleLabelTag($args)
{
    if (!empty($args['object'])) {
        return 'echo xarVarPrepForDisplay('.$args['object'].'->label); ';
    } elseif (!empty($args['property'])) {
        if (!empty($args['label'])) {
            if (substr($args['label'],0,1) == '$') {
                return 'echo '.$args['property'].'->showLabel(array(\'for\' => '.$args['label'].')); ';
            } else {
                return 'echo '.$args['property'].'->showLabel(array(\'for\' => \''.$args['label'].'\')); ';
            }
        } else {
            return 'echo xarVarPrepForDisplay('.$args['property'].'->label); ';
        }
    } else {
        return 'echo "I need an object or a property"; ';
    }
}

?>
