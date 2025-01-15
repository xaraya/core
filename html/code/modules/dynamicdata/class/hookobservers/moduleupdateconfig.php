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

use BadParameterException;
use sys;

sys::import('modules.dynamicdata.class.hookobservers.generic');

class ModuleUpdateconfig extends DataObjectHookObserver
{
    /**
     * update configuration for a module - hook for ('module','updateconfig','API')
     * Needs $extrainfo['dd_*'] from arguments, or 'dd_*' from input
     *
     * @param array<string, mixed> $extrainfo extra information
     * @return array<mixed> true on success, false on failure
     * @throws BadParameterException
     */
    public function run(array $extrainfo = [])
    {
        // Return the extra info
        return $extrainfo;

        /*
         * currently NOT used (we're going through the 'normal' updateconfig for now)
         */
    }
}
