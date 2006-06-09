<?php
/**
 * Standard function to create a new item
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamic Data module
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */

/**
 * This is a standard function that is called with the results of the
 * form supplied by xarModFunc('dynamicdata','admin','new') to create a new item
 * @param 'name' the name of the item to be created
 * @param 'number' the number of the item to be created
 */
function dynamicdata_admin_create($args)
{

    extract($args);

// FIXME: whatever, as long as it doesn't generate Variable "0" should not be empty exceptions
//        or relies on $myobject or other stuff like that...

    if (!xarVarFetch('objectid',    'id',       $objectid,   NULL,                               XARVAR_DONT_SET)) return;
    if (!xarVarFetch('modid',       'isset', $modid,      xarModGetIDFromName('dynamicdata'), XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('itemtype',    'isset', $itemtype,   0,                                  XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('itemid',      'isset', $itemid,     0,                                  XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('preview',     'isset', $preview,    0,                                  XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('return_url',  'isset', $return_url, NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('join',        'isset', $join,       NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('table',       'isset', $table,      NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('template',     'isset', $template,   NULL, XARVAR_DONT_SET)) {return;}

    if (!xarSecConfirmAuthKey()) return;

    $myobject = & Dynamic_Object_Master::getObject(array('objectid' => $objectid,
                                         'moduleid' => $modid,
                                         'itemtype' => $itemtype,
                                         'join'     => $join,
                                         'table'    => $table,
                                         'itemid'   => $itemid));
    $isvalid = $myobject->checkInput();

    if (!empty($preview) || !$isvalid) {
        $data = xarModAPIFunc('dynamicdata','admin','menu');

        $data['object'] = & $myobject;

        $data['authid'] = xarSecGenAuthKey();
        $data['preview'] = $preview;
        if (!empty($return_url)) {
            $data['return_url'] = $return_url;
        }

        $modinfo = xarModGetInfo($myobject->moduleid);
        $item = array();
        foreach (array_keys($myobject->properties) as $name) {
            $item[$name] = $myobject->properties[$name]->value;
        }
        $item['module'] = $modinfo['name'];
        $item['itemtype'] = $myobject->itemtype;
        $item['itemid'] = $myobject->itemid;
        $hooks = array();
        $hooks = xarModCallHooks('item', 'new', $myobject->itemid, $item, $modinfo['name']);
        $data['hooks'] = $hooks;

        if(!isset($template)) {
            $template = $myobject->name;
        }
        return xarTplModule('dynamicdata','admin','new',$data,$template);
    }

    $itemid = $myobject->createItem();

    if (empty($itemid)) return; // throw back

    if (!empty($return_url)) {
        xarResponseRedirect($return_url);
    } elseif (!empty($table)) {
        xarResponseRedirect(xarModURL('dynamicdata', 'admin', 'view',
                                      array('table' => $table)));
    } else {
        xarResponseRedirect(xarModURL('dynamicdata', 'admin', 'view',
                                      array('itemid' => $myobject->objectid)));
    }

    // Return
    return true;
}

?>
