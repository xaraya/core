<?php
/**
 * Modify Dynamic data for an Item
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

namespace Xaraya\DataObject\HookObservers;

use xarSecurity;
use xarTpl;
use xarVar;
use DataObjectDescriptor;
use DataObjectFactory;
use sys;

sys::import('modules.dynamicdata.class.hookobservers.generic');

class ItemModify extends DataObjectHookObserver
{
    /**
     * modify dynamicdata for an item - hook for ('item','modify','GUI')
     *
     * @param array<string, mixed> $args
     * with
     *     $args['objectid'] ID of the object
     *     $args['extrainfo'] extra information
     * @return string|void output display string
     */
    public static function run(array $args = [])
    {
        // Security
        if (!xarSecurity::check('EditDynamicData')) {
            return;
        }

        extract($args);
        $extrainfo ??= [];

        // everything is already validated in HookSubject, except possible empty objectid/itemid for create/display
        $modname = $extrainfo['module'];
        $itemtype = $extrainfo['itemtype'];
        $itemid = $extrainfo['itemid'];
        $module_id = $extrainfo['module_id'];

        // don't allow hooking to yourself in DD
        if ($modname == 'dynamicdata') {
            return '';
        }

        $descriptorargs = DataObjectDescriptor::getObjectID(['moduleid'  => $module_id,
                                           'itemtype'  => $itemtype]);
        sys::import('modules.dynamicdata.class.objects.factory');
        $object = DataObjectFactory::getObject(['name' => $descriptorargs['name']]);
        if (!isset($object) || empty($object->objectid)) {
            return;
        }
        $object->getItem(['itemid' => $itemid]);

        // if we are in preview mode, we need to check for any preview values
        if (!xarVar::fetch('preview', 'isset', $preview, null, xarVar::DONT_SET)) {
            return;
        }
        if (!empty($preview)) {
            $object->checkInput();
        }

        if (!empty($object->template)) {
            $template = $object->template;
        } else {
            $template = $object->name;
        }

        $properties = $object->getProperties();
        return xarTpl::module(
            'dynamicdata',
            'admin',
            'modifyhook',
            ['properties' => $properties],
            $template
        );
    }
}
