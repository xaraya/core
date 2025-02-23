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
use DataPropertyMaster;
use sys;

sys::import('xaraya.modules.method');

/**
 * categories adminapi updateconfighook function
 * @extends MethodClass<AdminApi>
 */
class UpdateconfighookMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Update configuration for a module - hook for ('module','updateconfig','API')
     * Needs $extrainfo['cids'] from arguments, or 'cids' from input
     * @param mixed $args ['objectid'] ID of the object
     * @param mixed $args ['extrainfo'] extra information
     * @return array Returns data array.
     * @see AdminApi::updateconfighook()
     */
    public function __invoke(array $args = [])
    {
        sys::import('modules.dynamicdata.class.properties.master');
        $picker = $this->prop()->getProperty(['name' => 'categorypicker']);
        $picker->checkInput('basecid');

        extract($args);
        return $extrainfo;
    }
}
