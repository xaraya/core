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
use xarDB;
use xarMod;
use xarSecurity;
use sys;

sys::import('xaraya.modules.method');

/**
 * categories adminapi removehook function
 * @extends MethodClass<AdminApi>
 */
class RemovehookMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Delete all category links for a module - hook for ('module','remove','API')
     * @param mixed $args ['objectid'] ID of the object (must be the module name here !!)
     * @param mixed $args ['extrainfo'] extra information
     * @return array|void Data array
     * @throws \BadParameterException Thrown is invalid parameters have been given
     * @see AdminApi::removehook()
     */
    public function __invoke(array $args = [])
    {
        /**
         * Pending
         * TODO: remove per itemtype ?
         */
        extract($args);

        if (!isset($extrainfo)) {
            $extrainfo = [];
        }

        // When called via hooks, we should get the real module name from objectid
        // here, because the current module is probably going to be 'modules' !!!
        if (!isset($objectid) || !is_string($objectid)) {
            $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)', 'object ID (= module name)', 'admin', 'removehook', 'categories');
            throw new BadParameterException(null, $msg);
        }

        $modid = xarMod::getRegID($objectid);
        if (empty($modid)) {
            $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)', 'module ID', 'admin', 'removehook', 'categories');
            throw new BadParameterException(null, $msg);
        }

        if (!xarSecurity::check('ManageCategoryLink', 1, 'Link', "$modid:All:All:All")) {
            return;
        }

        // Get database setup
        $dbconn = xarDB::getConn();
        $xartable = xarDB::getTables();
        $categorieslinkagetable = $xartable['categories_linkage'];

        // Delete the link
        $sql = "DELETE FROM $categorieslinkagetable
                WHERE module_id = ?";
        $dbconn->Execute($sql, [xarMod::getID($objectid)]);

        if ($dbconn->ErrorNo() != 0) {
            $msg = xarML('Database error for #(1) function #(2)() in module #(3)', 'admin', 'removehook', 'categories');
            throw new BadParameterException(null, $msg);
        }

        // Return the extra info
        return $extrainfo;
    }
}
