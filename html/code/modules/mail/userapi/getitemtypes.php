<?php

/**
 * @package modules\mail
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Mail\UserApi;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Mail\UserApi;
use DataObjectFactory;
use Exception;
use xarController;
use xarMod;
use xarModVars;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * mail userapi getitemtypes function
 * @extends MethodClass<UserApi>
 */
class GetitemtypesMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup * @see UserApi::getitemtypes()
     */

    public function __invoke(array $args = [])
    {
        $itemtypes = [];

        // The mail module defines an item as a mail message. Simply
        // put, mail can be incoming or outgoing.  To be able to hook
        // modules into a subset of messages we define the concept of
        // queues which contain sets of messages. Each queue gets assigned
        // an itemtype (in DD), so it can be extended with the functonality of
        // other modules by hooking to ALL items (regardless of queue) or
        // to a specific queue.
        //
        // Use dd to retrieve the items of the mailqueue object
        $qdefName = xarModVars::get('mail', 'queue-definition');
        if (!$qdefName) {
            return $itemtypes;
            //throw new Exception('Mail queue definition does not exist');
        }
        $qdefObjectInfo = $this->data()->getObjectInfo(['name' => $qdefName]);
        if (!$qdefObjectInfo) {
            return $itemtypes;
        }

        // Shamelessly pasted from DD
        // (we should takes this as a baseline/augmentation for getitemtypes for all mods really)

        // Get objects
        $objects = $this->mod()->apiFunc('dynamicdata', 'user', 'getobjects');
        $modid = $this->mod()->getRegID('mail');
        foreach ($objects as $id => $object) {
            // skip any object that doesn't belong to mail itself
            if ($modid != $object['moduleid']) {
                continue;
            }
            // Should we skip the "internal" mail objects (i.e. the queue-definition)?
            // if ($object['objectid'] == $qdefObjectInfo['objectid'] ) continue;
            $itemtypes[$object['itemtype']] = ['label' => $this->var()->prep($object['label']),
                'title' => $this->var()->prep($this->ml('View #(1)', $object['label'])),
                'url'   => $this->ctl()->getModuleURL('mail', 'user', 'view', ['itemtype' => $object['itemtype']]),
                'info'  => $object,
            ];
        }
        return $itemtypes;
    }
}
