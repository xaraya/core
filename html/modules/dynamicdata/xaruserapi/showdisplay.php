<?php
/**
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */

/**
 * Display an item in a template
 *
 * @param $args array containing the item or fields to show
 * @returns string
 * @return string containing the HTML (or other) text to output in the BL template
 */
function dynamicdata_userapi_showdisplay($args)
{
    extract($args);

    $args['fallbackmodule'] = 'current';
    $descriptor = new DataObjectDescriptor($args);
    $args = $descriptor->getArgs();

    // TODO: what kind of security checks do we want/need here ?
    if(!xarSecurityCheck('ReadDynamicDataItem',1,'Item',$args['moduleid'].":".$args['itemtype'].":".$itemid)) return;


    // we got everything via template parameters
    if (isset($fields) && is_array($fields) && count($fields) > 0) {
        return xarTplModule('dynamicdata','user','showdisplay',
                            $args,
                            $template);
    }

    // check the optional field list
    if (!empty($fieldlist)) {
        // support comma-separated field list
        if (is_string($fieldlist)) {
            $args['fieldlist'] = explode(',',$fieldlist);
        // and array of fields
        } elseif (is_array($fieldlist)) {
            $args['fieldlist'] = $fieldlist;
        }
    } else {
        $args['fieldlist'] = null;
    }

    $object = & DataObjectMaster::getObject($args);
    // we're dealing with a real item, so retrieve the property values
    if (!empty($itemid)) {
        $object->getItem();
    }
    // if we are in preview mode, we need to check for any preview values
    //if (!xarVarFetch('preview', 'isset', $preview,  NULL, XARVAR_DONT_SET)) {return;}
    if (!empty($preview)) {
        $object->checkInput();
    }

    return $object->showDisplay($args);
}

?>
