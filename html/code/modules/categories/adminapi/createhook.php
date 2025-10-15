<?php

/**
 * @package modules\categories
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Categories\AdminApi;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Categories\AdminApi;
use BadParameterException;
use sys;

sys::import('xaraya.modules.method');

/**
 * categories adminapi createhook function
 * @extends MethodClass<AdminApi>
 */
class CreatehookMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Create linkage for an item - hook for ('item','create','API')
     * Needs $extrainfo['cids'] from arguments, or 'cids' from input
     * @param mixed $args ['objectid'] ID of the object
     * @param mixed $args ['extrainfo'] extra information
     * @return array Data array
     * @throws \BadParameterException Thrown if object was not found.
     * @see AdminApi::createhook()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        if (!isset($extrainfo) || !is_array($extrainfo)) {
            $extrainfo = [];
        }

        if (!isset($objectid) || !is_numeric($objectid)) {
            $msg = $this->ml('Invalid #(1) for #(2) function #(3)() in module #(4)', 'object ID', 'admin', 'createhook', 'categories');
            throw new BadParameterException(null, $msg);
        }

        sys::import('modules.dynamicdata.class.properties.master');
        $categories = $this->prop()->getProperty(['name' => 'categories']);
        if ($categories->checkInput('hookedcategories')) {
            // CHECKME: aren't we supposed to save the categories here ?
            $categories->createValue($objectid);
        }

        // Return the extra info
        return $extrainfo;
    }
}
