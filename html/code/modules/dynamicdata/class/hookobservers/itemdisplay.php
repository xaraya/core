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

namespace Xaraya\DataObject\HookObservers;

use xarTpl;
use DataObjectDescriptor;
use DataObjectFactory;
use sys;

sys::import('modules.dynamicdata.class.hookobservers.generic');

class ItemDisplay extends DataObjectHookObserver
{
    /**
     *
     * @param array<string, mixed> $args
     * with
     *     $args['objectid'] ID of the object
     *     $args['extrainfo'] extra information
     * @return string|void output display string
     */
    public static function run(array $args = [], $context = null)
    {
        extract($args);
        $extrainfo ??= [];

        // everything is already validated in HookSubject, except possible empty objectid/itemid for create/display
        $modname = $extrainfo['module'];
        $itemtype = $extrainfo['itemtype'];
        $itemid = $extrainfo['itemid'];
        $module_id = $extrainfo['module_id'];

        $descriptorargs = DataObjectDescriptor::getObjectID(['moduleid'  => $module_id,
                                        'itemtype'  => $itemtype]);
        // set context if available in hook call
        $object = DataObjectFactory::getObject(['name' => $descriptorargs['name'],
                                        'itemid'   => $itemid],
                                        $context);
        if (!isset($object) || empty($object->objectid)) {
            return;
        }
        if (!$object->checkAccess('display')) {
            return xarML('Display #(1) is forbidden', $object->label);
        }

        $object->getItem();

        if (!empty($object->template)) {
            $template = $object->template;
        } else {
            $template = $object->name;
        }
        return xarTpl::module(
            'dynamicdata',
            'user',
            'displayhook',
            ['properties' => & $object->properties],
            $template
        );
    }
}
