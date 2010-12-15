<?php
/**
 * @package modules
 * @subpackage dynamicdata module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/182.html
 *
 * @author mrb <marcel@xaraya.com>
 */

/**
 * Class to model registration information for a property
 *
 * This corresponds directly to the db info we register for a property.
 *
 */
class ThemeInitialization extends Object
{
    static public function clearCache()
    {
        $dbconn = xarDB::getConn();
        xarMod::loadDbInfo('themes','themes');
        $tables = xarDB::getTables();
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
    static public function importConfigurations($flush = true, $dirs = array())
    {
        sys::import('xaraya.structures.relativedirectoryiterator');

        $dbconn = xarDB::getConn(); // Need this for the transaction
        $themeDirs = array();

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

                $activeThemes = xarMod::apiFunc('themes','admin','getlist', array('filter' => array('State' => XARTHEME_STATE_ACTIVE)));
                assert('!empty($activeThemes)'); // this should never happen

                foreach($activeThemes as $themeInfo) {
                    // FIXME: the themeInfo directory does NOT end with a /
                    $themeDirs[] = $themeInfo['directory'];
                }
            }

            // Loop through theme directories
            foreach($themeDirs as $dir) {
                // Run the initialization routine
                self::inittheme($dir);
            } 
            $dbconn->commit();
        } catch(Exception $e) {
            // TODO: catch more specific exceptions than all?
            $dbconn->rollback();
            throw $e;
        }

                
        // Clear the property types from cached memory
//        xarCoreCache::delCached('DynamicData','PropertyTypes');
        
        return true;
    }
    
    static public function inittheme($dir) 
    {
        sys::import('modules.dynamicdata.class.objects.descriptor');
        $class = UCFirst($dir) . 'Init';
        if (file_exists($dir . '/init.php')) {
            // Assume this theme has its own init routine
            sys::import($dir . '.init');
        } else {
            $class = 'ThemeInit';
            sys::import('modules.themes.class.init');
        }
        $descriptor = new DataObjectDescriptor();
        $installer = new $class($descriptor);
        $installer->init(array('name' => $dir));
    }
}
?>