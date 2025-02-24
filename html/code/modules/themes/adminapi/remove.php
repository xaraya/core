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
use ForbiddenOperationException;
use xarDB;
use xarMod;
use xarModVars;
use xarSecurity;
use xarTheme;
use sys;

sys::import('xaraya.modules.method');

/**
 * themes adminapi remove function
 * @extends MethodClass<AdminApi>
 */
class RemoveMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Remove a theme
     * @author Marty Vance
     * @param array<string,mixed> $args array of optional parameters<br/>
     * integer  $args['regid'] the id of the theme
     * string   $args['name'] theme's name
     * @return bool|void true on success, false on failure
     * @throws \ForbiddenOperationException
     * @see AdminApi::remove()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        if (!$this->sec()->checkAccess('AdminThemes')) {
            return;
        }

        // Remove variables and theme
        $dbconn = $this->db()->getConn();
        $tables = $this->db()->getTables();

        // Get theme information
        if (isset($name)) {
            $regid = xarTheme::getRegID($name);
        }
        $themeInfo = xarTheme::getInfo($regid);
        $defaultTheme = $this->mod()->getVar('default_theme');

        // Bail out if we're trying to remove the default theme
        if ($defaultTheme == $themeInfo['name']) {
            $msg = 'The theme you are trying to remove is the current default theme. Select another default theme first, then try again.';
            throw new ForbiddenOperationException(null, $msg);
        }

        // Bail out if we're trying to remove while one of our users
        // has it set to their default theme
        $mvid = $this->mod('themes')->getVarID('default_theme');
        $sql = "SELECT COUNT(*) FROM $tables[module_itemvars] WHERE module_var_id =? AND value = ?";
        $result = $dbconn->Execute($sql, [$mvid,$defaultTheme]);

        // count should be zero
        $count = $result->fields[0];
        if ($count != 0) {
            $msg = 'The theme you are trying to remove is used by #(1) users on this site as their default theme. Theme cannot be removed.';
            throw new ForbiddenOperationException($count, $msg);
        }

        // Delete the theme from the themes table
        $sql = "DELETE FROM $tables[themes] WHERE regid = ?";
        $dbconn->Execute($sql, [$regid]);

        return true;
    }
}
