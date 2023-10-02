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

use BadParameterException;
use sys;
use HookObserver;

sys::import('xaraya.structures.hooks.observer');

class ModuleUpdateconfig extends HookObserver
{
    public $module = 'dynamicdata';

/**
 * update configuration for a module - hook for ('module','updateconfig','API')
 * Needs $extrainfo['dd_*'] from arguments, or 'dd_*' from input
 *
 * @param array<string, mixed> $args array of optional parameters<br/>
 *        integer  $args['objectid'] ID of the object<br/>
 *        string   $args['extrainfo'] extra information
 * @return array<mixed> true on success, false on failure
 * @throws BadParameterException
 */
public static function run(array $args = [])
{
    if (!isset($args['extrainfo'])) {
        $args['extrainfo'] = [];
    }
    // Return the extra info
    return $args['extrainfo'];

    /*
     * currently NOT used (we're going through the 'normal' updateconfig for now)
     */
}
}
