<?php

/**
 * @package modules\themes
 * @subpackage themes
 * @copyright see the html/credits.html file in this release
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/70.html
 *
 * @author mrb <marcel@xaraya.com>
 */

sys::import('xaraya.services.xar');
use Xaraya\Services\xar;
// use ixarTheme;

/**
 * Class to model registration information for a property
 *
 * This corresponds directly to the db info we register for a property.
 *
 */
class ThemeInitialization extends xarObject
{
    public static function clearCache()
    {
        $xar = xar::getServicesClass();
        $dbconn = $xar->db()->getConn();
        $xar->mod()->loadDbInfo('themes');
        $tables = $xar->db()->getTables();
        $sql = "DELETE FROM $tables[themes_configurations]";
        $res = $dbconn->ExecuteUpdate($sql);
        return $res;
    }

    /**
     * Import theme configurations into the configurations table
     *
     * @param bool $flush
     * @param array dirs
     * @return boolean true if the table is loaded, else false
     */
    public static function importConfigurations($flush = true, $dirs = [])
    {
        sys::import('xaraya.structures.relativedirectoryiterator');
        $xar = xar::getServicesClass();

        $dbconn = $xar->db()->getConn(); // Need this for the transaction
        $themeDirs = [];

        // We do the whole thing, or not at all (given proper db support)
        try {
            $dbconn->begin();

            if (!empty($dirs) && is_array($dirs)) {
                // We got an array of directories passed in for which to import properties
                // typical usecase: a module which has its own property, during install phase needs that property before
                // the module is active.
                $themeDirs = $dirs;
            } else {
                // Clear the cache
                self::ClearCache();

                $activeThemes = $xar->mod()->apiFunc('themes', 'admin', 'getlist', ['filter' => ['State' => ixarTheme::STATE_ACTIVE]]);
                assert(!empty($activeThemes)); // this should never happen

                foreach ($activeThemes as $themeInfo) {
                    // FIXME: the themeInfo directory does NOT end with a /
                    $themeDirs[] = $themeInfo['directory'];
                }
            }

            // Loop through theme directories
            foreach ($themeDirs as $dir) {
                // Run the initialization routine
                self::inittheme($dir);
            }
            $dbconn->commit();
        } catch (Exception $e) {
            // TODO: catch more specific exceptions than all?
            $dbconn->rollback();
            throw $e;
        }


        // Clear the property types from cached memory
        //        $xar->mem()->del('DynamicData','PropertyTypes');

        return true;
    }

    public static function inittheme($dir)
    {
        sys::import('modules.dynamicdata.class.objects.descriptor');
        $class = UCFirst($dir) . 'Init';
        if (file_exists($dir . '/init.php')) {
            // Assume this theme has its own init routine
            sys::import($dir . '.init');
        } else {
            // Otherwise use the default routine from the themes module
            $class = 'ThemeInit';
            sys::import('modules.themes.class.init');
        }
        $descriptor = new DataObjectDescriptor();
        $installer = new $class($descriptor);
        $installer->init(['name' => $dir]);
    }
}
