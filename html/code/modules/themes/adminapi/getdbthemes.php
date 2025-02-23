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
use xarDB;
use xarTheme;
use sys;

sys::import('xaraya.modules.method');

/**
 * themes adminapi getdbthemes function
 * @extends MethodClass<AdminApi>
 */
class GetdbthemesMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Get all themes in the database
     * @author Marty Vance
     * @return array|void of themes in the database
     * @see AdminApi::getdbthemes()
     */
    public function __invoke(array $args = [])
    {
        $dbconn = $this->db()->getConn();
        $xartable = $this->db()->getTables();

        $dbThemes = [];

        // Get all themes in DB
        $sql = "SELECT regid  FROM $xartable[themes]";
        $result = $dbconn->executeQuery($sql);

        while ($result->next()) {
            [$themeRegId] = $result->fields;
            //Get Theme Info
            $themeInfo = xarTheme::getInfo($themeRegId);
            if (!isset($themeInfo)) {
                return;
            }

            $name = $themeInfo['name'];
            //Push it into array (should we change to index by regid instead?)
            $dbThemes[$name] = ['name'    => $name,
                'regid'   => $themeRegId,
                'version' => $themeInfo['version'],
                'state'   => $themeInfo['state'],
                'class'   => $themeInfo['class']];
        }
        $result->close();

        return $dbThemes;
    }
}
