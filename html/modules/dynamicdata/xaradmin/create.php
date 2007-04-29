<?php
/**
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

    if (!xarVarFetch('objectid',    'id',    $objectid,   NULL,                               XARVAR_DONT_SET)) return;
    if (!xarVarFetch('itemid',      'isset', $itemid,     0,                                  XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('preview',     'isset', $preview,    0,                                  XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('return_url',  'isset', $return_url, NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('join',        'isset', $join,       NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('table',       'isset', $table,      NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('template',     'isset', $template,   NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('tplmodule',    'isset', $tplmodule,   'dynamicdata', XARVAR_NOT_REQUIRED)) {return;}

    if (!xarSecConfirmAuthKey()) return;

    $myobject = & DataObjectMaster::getObject(array('objectid' => $objectid,
                                         'join'     => $join,
                                         'table'    => $table,
                                         'itemid'   => $itemid));
    $isvalid = $myobject->checkInput();

    $data = xarModAPIFunc('dynamicdata','user','getcontext',array('module' => $tplmodule));
    extract($data);

    if (!empty($preview) || !$isvalid) {
        $data = array_merge($data, xarModAPIFunc('dynamicdata','admin','menu'));

        $data['object'] = & $myobject;

        $data['authid'] = xarSecGenAuthKey();
        $data['preview'] = $preview;
//        $data['tplmodule'] = $tplmodule;
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
        return xarTplModule($tplmodule,'admin','new',$data,$template);
    }

    $itemid = $myobject->createItem();

    if (empty($itemid)) return; // throw back

    if (!empty($return_url)) {
        if (strpos($return_url,'?') === false)
            $return_url .= '?';
        else
            $return_url .= '&';
        $return_url .= 'itemid=' . $itemid;
        xarResponseRedirect($return_url);
    } elseif (!empty($table)) {
        xarResponseRedirect(xarModURL('dynamicdata', 'admin', 'view',
                                      array('table' => $table)));
    } else {
        xarResponseRedirect(xarModURL('dynamicdata', 'admin', 'view',
                                      array(
                                      'itemid' => $myobject->objectid,
                                      'tplmodule' => $tplmodule
                                      )));
    }
    return true;
}

?>
