<?php
/**
 * @package modules
 * @subpackage dynamicdata module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */

/**
 * display an item
 * This is a standard function to provide detailed informtion on a single item
 * available from the module.
 *
 * @param $args an array of arguments (if called by other modules)
 * @return string output display string
 */
function dynamicdata_user_display(Array $args=array())
{
    extract($args);

    if(!xarVarFetch('objectid', 'isset', $objectid,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('name',     'isset', $name,      NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('module_id',    'isset', $moduleid,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('itemid',   'isset', $itemid,    NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('template', 'isset', $template,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('tplmodule','isset', $tplmodule, NULL, XARVAR_DONT_SET)) {return;}

    $myobject = DataObjectMaster::getObject(array('objectid' => $objectid,
                                         'name' => $name,
                                         'itemid'   => $itemid,
                                         'tplmodule' => $tplmodule));
    if (!isset($myobject)) return;
    if (!$myobject->checkAccess('display'))
        return xarResponse::Forbidden(xarML('Display #(1) is forbidden', $myobject->label));

    $args = $myobject->toArray();
    $myobject->getItem();

    $data = array();

    // *Now* we can set the data stuff
    $data['object'] =& $myobject;
    $data['objectid'] = $args['objectid'];
    $data['itemid'] = $args['itemid'];

    // Display hooks - not called automatically (yet)
    $myobject->callHooks('display');
    $data['hooks'] = $myobject->hookoutput;

    xarTpl::setPageTitle($myobject->label);

    // Return the template variables defined in this function
    if (file_exists(sys::code() . 'modules/' . $args['tplmodule'] . '/xartemplates/user-display.xt') ||
        file_exists(sys::code() . 'modules/' . $args['tplmodule'] . '/xartemplates/user-display-' . $args['template'] . '.xt')) {
        return xarTpl::module($args['tplmodule'],'user','display',$data,$args['template']);
    } else {
        return xarTpl::module('dynamicdata','user','display',$data,$args['template']);
    }
}

?>
