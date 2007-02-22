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
 * Show an input form in a template
 *
 * @param array containing the item or fields to show
 * @return string containing the HTML (or other) text to output in the BL template
 */
function dynamicdata_adminapi_showform($args)
{
    extract($args);
    $args['fallbackmodule'] = 'current';
    $descriptor = new DataObjectDescriptor($args);
    $args = $descriptor->getArgs();

    $itemtype = $args['itemtype'];

    // optional layout for the template
    if (empty($layout)) {
        $layout = 'default';
    }
    // or optional template, if you want e.g. to handle individual fields
    // differently for a specific module / item type
    if (empty($template)) {
        $template = '';
    }

    // we got everything via template parameters
    if (isset($fields) && is_array($fields) && count($fields) > 0) {
        return xarTplModule('dynamicdata','admin','showform',
                            array('fields' => $fields,
                                  'layout' => $layout),
                            $template);
    }

    // try getting the item id via input variables if necessary
    if (!isset($itemid) || !is_numeric($itemid)) {
        if (!xarVarFetch('itemid', 'isset', $itemid,  NULL, XARVAR_DONT_SET)) {return;}
    }

    // check the optional field list
    if (!empty($fieldlist)) {
        // support comma-separated field list
        if (is_string($fieldlist)) {
            $myfieldlist = explode(',',$fieldlist);
        // and array of fields
        } elseif (is_array($fieldlist)) {
            $myfieldlist = $fieldlist;
        }
    } else {
        $myfieldlist = null;
    }

    // join a module table to a dynamic object
    if (empty($join)) {
        $join = '';
    }
    // make some database table available via DD
    if (empty($table)) {
        $table = '';
    }

    // throw an exception if you can't edit this
    if (empty($itemid)) {
        if(!xarSecurityCheck('AddDynamicDataItem',1,'Item',"$modid:$itemtype:All")) return;
    } else {
        if(!xarSecurityCheck('EditDynamicDataItem',1,'Item',"$modid:$itemtype:$itemid")) return;
    }

    $object = & DataObjectMaster::getObject(array('moduleid'  => $modid,
                                       'itemtype'  => $itemtype,
                                       'itemid'    => $itemid,
                                       'join'      => $join,
                                       'table'     => $table,
                                       'fieldlist' => $myfieldlist));
    if (!empty($itemid)) {
        $object->getItem();
    }
    // if we are in preview mode, we need to check for any preview values
    //if (!xarVarFetch('preview', 'isset', $preview,  NULL, XARVAR_DONT_SET)) {return;}
    if (!empty($preview)) {
        $object->checkInput();
    }

    return $object->showForm(array('layout'   => $layout,
                                   'template' => $template));
}
?>
