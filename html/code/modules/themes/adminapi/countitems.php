<?php

/**
 * @package modules\themes
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Themes\AdminApi;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Themes\AdminApi;
use ixarTheme;
use sys;

sys::import('xaraya.modules.method');

/**
 * themes adminapi countitems function
 * @extends MethodClass<AdminApi>
 */
class CountitemsMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     *
     * @package modules\themes
     * @subpackage themes
     * @copyright see the html/credits.html file in this release
     * @category Xaraya Web Applications Framework
     * @version 2.4.0
     * @copyright see the html/credits.html file in this release
     * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
     * @link http://xaraya.info/index.php/release/70.html
     * @see AdminApi::countitems()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        if (!isset($state)) {
            $state = ixarTheme::STATE_ACTIVE;
        }

        if (!isset($class)) {
            $class = 3;
        } // any

        // Determine the tables we are going to use
        $dbconn = $this->db()->getConn();
        $tables = $this->db()->getTables();
        $themes_table = $tables['themes'];

        $where = [];
        $bindvars = [];

        if ($state != ixarTheme::STATE_ANY) {
            if ($state != ixarTheme::STATE_INSTALLED) {
                $where[] = 'themes.state = ?';
                $bindvars[] = $state;
            } else {
                $where[] = 'themes.state != ? AND themes.state < ? AND themes.state != ?';
                $bindvars[] = ixarTheme::STATE_UNINITIALISED;
                $bindvars[] = ixarTheme::STATE_MISSING_FROM_INACTIVE;
                $bindvars[] = ixarTheme::STATE_MISSING_FROM_UNINITIALISED;
            }
        }
        if (isset($class) && $class != 3) {
            $where[] = 'themes.class = ?';
            $bindvars[] = $class;
        }
        // build query
        $query = "SELECT COUNT(themes.id)";
        $query .= " FROM $themes_table themes";
        if (!empty($where)) {
            $query .= ' WHERE ' . join(' AND ', $where);
        }
        $result = $dbconn->Execute($query, $bindvars);
        if (!$result) {
            return;
        }
        $result->first();
        [$count] = $result->fields;

        $result->Close();

        return $count;

    }
}
