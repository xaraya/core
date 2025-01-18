<?php

/**
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */

namespace Xaraya\DataObject\HookObservers;

use xarVar;
use BadParameterException;
use sys;

sys::import('modules.dynamicdata.class.hookobservers.generic');

class ItemUpdate extends DataObjectHookObserver
{
    public bool $update = true;

    /**
     * update fields for an item - hook for ('item','update','API')
     * Needs $extrainfo['dd_*'] from arguments, or 'dd_*' from input
     *
     * @param array<string, mixed> $extrainfo extra information
     * @return array<mixed> true on success, false on failure
     * @throws BadParameterException
     */
    public function run(array $extrainfo = [])
    {
        $verbose = $extrainfo['verbose'] ?? false;

        $dd_function = $this->update ? 'updatehook' : 'createhook';

        // We can exit immediately if the status flag is set because we are just updating
        // the status in the articles or other content module that works on that principle
        // Bug 1960 and 3161
        if ($this->var()->isCached('Hooks.all', 'noupdate') || !empty($extrainfo['statusflag'])) {
            return $extrainfo;
        }

        // everything is already validated in HookSubject, except possible empty objectid/itemid for create/display
        $modname = $extrainfo['module'];
        $itemtype = $extrainfo['itemtype'];
        $itemid = $extrainfo['itemid'];
        $module_id = $extrainfo['module_id'];

        // don't allow hooking to yourself in DD
        if ($modname == 'dynamicdata') {
            return $extrainfo;
        }

        if (empty($itemid)) {
            $msg = 'Invalid #(1) for #(2) function #(3)() in module #(4)';
            $vars = ['item id', 'admin', $dd_function, 'dynamicdata'];
            throw new BadParameterException($vars, $msg);
        }

        $descriptorargs = $this->data()->getObjectID([
            'moduleid'  => $module_id,
            'itemtype'  => $itemtype,
        ]);
        // set context if available in hook call
        $object = $this->data()->getObject([
            'name' => $descriptorargs['name'],
            'itemid'   => $itemid,
        ]);

        // If no object returned, bail and pass the extrainfo to the next hook
        if (!isset($object) || empty($object->objectid)) {
            return $extrainfo;
        }

        $object->getItem();

        // use the values passed via $extrainfo if available
        $isvalid = $object->checkInput($extrainfo);
        if (!$isvalid) {
            $vars = [];
            if ($verbose) {
                $msg = 'Invalid #(1) for #(2) function #(3)() in module #(4)';
                $vars = ['input', 'admin', $dd_function, 'dynamicdata'];
                // Note : we can't use templating here
                $msg .= ' : ';
                $i = 5;
                foreach ($object->properties as $property) {
                    if (!empty($property->invalid)) {
                        $msg .= "#(" . $i++ . ") = invalid #(" . $i++ . ") - ";
                        $vars[] = $property->label;
                        $vars[] = $property->invalid;
                    }
                }
            } else {
                $msg = '';
                foreach ($object->properties as $property) {
                    if (!empty($property->invalid)) {
                        $msg .= $property->invalid . ' ';
                    }
                }
            }
            throw new BadParameterException($vars, $msg);
        }

        if ($dd_function == 'createhook') {
            $itemid = $object->createItem();
        } else {
            $itemid = $object->updateItem();
        }

        if (empty($itemid)) {
            $msg = 'Invalid #(1) for #(2) function #(3)() in module #(4)';
            $vars = ['create/update', 'admin', $dd_function, 'dynamicdata'];
            throw new BadParameterException($vars, $msg);
        }
        // Return the extra info
        return $extrainfo;
    }
}
