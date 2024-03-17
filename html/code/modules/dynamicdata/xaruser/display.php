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
 * display an item
 * This is a standard function to provide detailed informtion on a single item
 * available from the module.
 *
 * @param array<string, mixed> $args an array of arguments (if called by other modules)
 * @return string|void output display string
 */
function dynamicdata_user_display(array $args = [], $context = null)
{
    extract($args);

    if(!xarVar::fetch('objectid', 'isset', $objectid, null, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('name', 'isset', $name, null, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('module_id', 'isset', $moduleid, null, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('itemid', 'isset', $itemid, null, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('template', 'isset', $template, null, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('tplmodule', 'isset', $tplmodule, null, xarVar::DONT_SET)) {
        return;
    }

    // set context if available in function
    $myobject = DataObjectFactory::getObject(
        ['objectid' => $objectid,
        'name' => $name,
        'itemid'   => $itemid,
        'tplmodule' => $tplmodule],
        $context
    );
    if (!isset($myobject)) {
        return;
    }
    if (!$myobject->checkAccess('display')) {
        if (!empty($context)) {
            $context->setStatus(403);
        }
        return xarResponse::Forbidden(xarML('Display #(1) is forbidden', $myobject->label));
    }

    $args = $myobject->toArray();
    $myobject->getItem();

    $data = [];

    // *Now* we can set the data stuff
    $data['object'] = & $myobject;
    $data['objectid'] = $args['objectid'];
    $data['itemid'] = $args['itemid'];

    // Display hooks - not called automatically (yet)
    $myobject->callHooks('display');
    $data['hooks'] = $myobject->hookoutput;

    xarTpl::setPageTitle($myobject->label);

    // Return the template variables defined in this function
    if (file_exists(sys::code() . 'modules/' . $args['tplmodule'] . '/xartemplates/user-display.xt') ||
        file_exists(sys::code() . 'modules/' . $args['tplmodule'] . '/xartemplates/user-display-' . $args['template'] . '.xt')) {
        return xarTpl::module($args['tplmodule'], 'user', 'display', $data, $args['template']);
    } else {
        return xarTpl::module('dynamicdata', 'user', 'display', $data, $args['template']);
    }
}
