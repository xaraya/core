<?php

/**
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.6.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */

namespace Xaraya\DataObject\HookObservers;

use sys;

sys::import('modules.dynamicdata.class.hookobservers.itemupdate');

class ItemCreate extends ItemUpdate
{
    /**
     * create fields for an item - hook for ('item','create','API')
     * Needs $extrainfo['dd_*'] from arguments, or 'dd_*' from input
     *
     * @param array<string, mixed> $extrainfo extra information
     * @return array<mixed> true on success, false on failure
     */
    public function run(array $extrainfo = [])
    {
        $this->update = false;
        // we rely on the updatehook to do the real work here
        return parent::run($extrainfo);
    }
}
