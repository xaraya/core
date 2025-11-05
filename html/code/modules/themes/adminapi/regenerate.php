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
use DuplicateException;
use ixarTheme;
use sys;
use Xaraya\Modules\InstallerTool;
use ThemeInitialization;

sys::import('xaraya.modules.method');
sys::import('modules.modules.class.installer');
sys::import('modules.themes.class.initialization');

/**
 * themes adminapi regenerate function
 * @extends MethodClass<AdminApi>
 */
class RegenerateMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Regenerate theme list
     * @author Marty Vance
     * @return bool|void true on success, false on failure
     * @see AdminApi::regenerate()
     */
    public function __invoke(array $args = [])
    {
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();
        // Security Check
        if (!$this->sec()->checkAccess('AdminThemes')) {
            return;
        }

        //Finds and updates missing themes
        sys::import('modules.modules.class.installer');
        $installer = InstallerTool::getInstance('themes');
        if (!$installer->checkformissing()) {
            return;
        }

        //Get all themes in the filesystem
        $fileThemes = $adminapi->getfilethemes();
        if (!isset($fileThemes)) {
            return;
        }

        // Get all themes in DB
        $dbThemes = $adminapi->getdbthemes();
        if (!isset($dbThemes)) {
            return;
        }

        // See if we have lost any themes since last generation
        /*     foreach ($dbThemes as $name => $themeInfo) { */
        /*         if (empty($fileThemes[$name])) { */
        /*             // Old theme */
        /*             // Get theme ID */
        /*             $regId = $themeInfo['regid']; */
        /*             // Set state of theme to 'missing' */
        /*             $set = $this->mod()->apiFunc('themes', */
        /*                                 'admin', */
        /*                                 'setstate', */
        /*                                 array('regid'=> $regId, */
        /*                                       'state'=> XARTHEME_STATE_MISSING)); */
        /*             //throw back */
        /*             if (!isset($set)) return; */
        /*  */
        /*             unset($dbThemes[$name]); */
        /*         } */
        /*     } */
        // See if we have gained any themes since last generation,
        // or if any current themes have been upgraded
        foreach ($fileThemes as $name => $themeinfo) {
            foreach ($dbThemes as $dbtheme) {
                // Bail if 2 themes have the same regid but not the same name
                if (($themeinfo['regid'] == $dbtheme['regid']) && ($themeinfo['name'] != $dbtheme['name'])) {
                    $msg = 'The same registered ID (#(1)) was found belonging to a #(2) theme in the file system and a registered #(3) theme in the database. Please correct this and regenerate the list.';
                    $vars = [$dbtheme['regid'], $themeinfo['name'], $dbtheme['name']];
                    throw new DuplicateException($vars, $msg);
                }
                // Bail if 2 themes have the same name but not the same regid
                if (($themeinfo['name'] == $dbtheme['name']) && ($themeinfo['regid'] != $dbtheme['regid'])) {
                    $msg = 'The theme #(1) is found with two different registered IDs, #(2)  in the file system and #(3) in the database. Please correct this and regenerate the list.';
                    $vars = [$themeinfo['name'], $themeinfo['regid'], $dbtheme['regid']];
                    throw new DuplicateException($vars, $msg);
                }
            }
        }
        //Setup database object for theme insertion
        $dbconn = $this->db()->getConn();
        $xartable = $this->db()->getTables();
        // See if we have gained any themes since last generation,
        // or if any current themes have been upgraded
        foreach ($fileThemes as $name => $themeInfo) {

            if (empty($dbThemes[$name])) {
                // New theme

                $sql = "INSERT INTO $xartable[themes]
                          (name, regid, directory, version, class, configuration)
                        VALUES (?,?,?,?,?,?)";

                // Force a default value on the configuration
                if (!isset($themeInfo['configuration'])) {
                    $themeInfo['configuration'] = '';
                }

                $bindvars = [$themeInfo['name'], $themeInfo['regid'],
                    $themeInfo['directory'], $themeInfo['version'], (int) $themeInfo['class'], $themeInfo['configuration']];
                $result = $dbconn->Execute($sql, $bindvars);

                $set = $adminapi->setstate(['regid' => $themeInfo['regid'],
                    'state' => ixarTheme::STATE_UNINITIALISED]);
                if (!isset($set)) {
                    return;
                }
            } else {
                // BEGIN bugfix (561802) - cmgrote
                if ($dbThemes[$name]['version'] != $themeInfo['version'] && $dbThemes[$name]['state'] != ixarTheme::STATE_UNINITIALISED) {
                    $set = $adminapi->setstate(['regid' => $dbThemes[$name]['regid'], 'state' => ixarTheme::STATE_UPGRADED]);
                    assert(isset($set));
                }
            }
        }
        // Reinit the theme configurations
        sys::import('modules.themes.class.initialization');
        ThemeInitialization::importConfigurations();

        return true;
    }
}
