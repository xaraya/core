<?php
/**
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */

/**
 * This is a standard function that is called with the results of the
 * form supplied by xarMod::guiFunc('dynamicdata','admin','new') to create a new item
 * @param int    objectid
 * @param int    itemid
 * @param string preview
 * @param string return_url
 * @param string join
 * @param string table
 * @param string template
 * @param string tplmodule
 * @return boolean
 */
function dynamicdata_admin_create(Array $args=array())
{
    extract($args);

// FIXME: whatever, as long as it doesn't generate Variable "0" should not be empty exceptions
//        or relies on $myobject or other stuff like that...

    if (!xarVarFetch('objectid',    'isset', $objectid,   NULL, XARVAR_DONT_SET)) return;
    if (!xarVarFetch('itemid',      'isset', $itemid,     0,    XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('preview',     'isset', $preview,    0,    XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('return_url',  'isset', $return_url, NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('join',        'isset', $join,       NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('table',       'isset', $table,      NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('template',     'isset', $template,   NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('tplmodule',    'isset', $tplmodule,   'dynamicdata', XARVAR_NOT_REQUIRED)) {return;}

    if (!xarSecConfirmAuthKey()) {
        return xarTpl::module('privileges','user','errors',array('layout' => 'bad_author'));
    }        

    $myobject = DataObjectMaster::getObject(array('objectid' => $objectid,
                                         'join'     => $join,
                                         'table'    => $table,
                                         'itemid'   => $itemid));

    // Security (Bug: 
    if (!$myobject->checkAccess('create'))
        return xarResponse::Forbidden(xarML('Create #(1) is forbidden', $myobject->label));

    $isvalid = $myobject->checkInput();

    // recover any session var information
    $data = xarMod::apiFunc('dynamicdata','user','getcontext',array('module' => $tplmodule));
    extract($data);

    if (!empty($preview) || !$isvalid) {
        $data = array_merge($data, xarMod::apiFunc('dynamicdata','admin','menu'));

        $data['object'] = $myobject;

        $data['authid'] = xarSecGenAuthKey();
        $data['preview'] = $preview;
        if (!empty($return_url)) {
            $data['return_url'] = $return_url;
        }

        // Makes this hooks call explictly from DD - why ???
        ////$modinfo = xarMod::getInfo($myobject->moduleid);
        //$modinfo = xarMod::getInfo(182);
        $myobject->callHooks('new');
        $data['hooks'] = $myobject->hookoutput;

        if(!isset($template)) {
            $template = $myobject->name;
        }
        return xarTpl::module($tplmodule,'admin','new',$data,$template);
    }

    $itemid = $myobject->createItem();

   // If we are here then the create is valid: reset the session var
    xarSession::setVar('ddcontext.' . $tplmodule, array('tplmodule' => $tplmodule));

    if (empty($itemid)) return; // throw back

    if (!empty($return_url)) {
        xarController::redirect($return_url);
    } elseif (!empty($table)) {
        xarController::redirect(xarModURL('dynamicdata', 'admin', 'view',
                                      array('table' => $table)));
    } else {
        xarController::redirect(xarModURL('dynamicdata', 'admin', 'view',
                                      array(
                                      'itemid' => $objectid,
                                      'tplmodule' => $tplmodule
                                      )));
    }
    return true;
}

?>
