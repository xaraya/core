<?php
/**
 * @package modules
 * @subpackage blocks module
 * @scenario soloblock
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/13.html
 */
/**
 * @author Chris Powis <crisp@xaraya.com>
 * @todo 
**/
/**
 * Get a list of available block types from the file system
 *
 * Recursively traverses the following paths...
 * /code/blocks/typename/* - looks for file named typename.php (solo blocks)
 * /code/modules/modulename/xarblocks/typename/* - looks for file named typename.php (module blocks)
 * /code/modules/modulename/xarblocks/* - looks for files that don't have an _ (ugly, legacy, deprecated)
**/
function blocks_typesapi_getfiles(Array $args=array())
{
    static $types = array();
    
    if (!empty($types)) return $types;

    $paths = array();        

    // look for solo blocks
    // code/blocks/*/*.php
    $paths[] = sys::code() . 'blocks/';

    // look for blocks belonging to modules
    $modules = xarMod::apiFunc('modules','admin','getlist',
        array('filter' => array('State' => XARMOD_STATE_ACTIVE)));
    $modpath = sys::code() . 'modules/';
    foreach ($modules as $modinfo) {
        $paths[$modinfo['name']] = $modpath . $modinfo['name'] . '/xarblocks/';
    }

    foreach ($paths as $scope => $path) {
        if (!is_dir($path)) continue;
        foreach ( new DirectoryIterator($path) as $item ) {
            if ($item->isDir() &&
                !$item->isDot() &&
                $item->isReadable()) {
                    // subfolders containing file(s) for individual blocks - the new way
                       
                    // the block type is the name of the subfolder,
                    // matches code/blocks/example/example.php
                    // or code/modules/modulename/xarblocks/example/example.php
                    $type = $item->getFilename();
                    // blocks in subfolders *must* have a file with same type name, eg example.php
                    $filepath = $item->getPathname() . "/{$type}.php";
                    if (!file_exists($filepath)) {
                        unset($type, $filepath);
                        continue;
                    }
                           
            } elseif ($item->isFile() &&
                !$item->isDot() &&
                $item->isReadable() &&
                pathinfo($item->getPathname(), PATHINFO_EXTENSION) == 'php' &&
                strpos($item->getFilename(), '_') === false) {
                        
                    // folder containing block file(s) for multiple blocks - the legacy way
                    // matches code/modules/modulename/xarblocks/example.php          
                    $type = substr($item->getFilename(), 0, -4);
                    $filepath = $item->getPathname();
                        
            } else {
                continue;
            }
                
            if (empty($scope)) {
                // solo block
                $classname = ucfirst($type) . 'Block';
                $id = strtolower($type);
                $dotpath = "blocks.{$type}.{$type}";
                $name = ucfirst($type) . ' Block';
            } else {
                // module block
                $classname = ucfirst($scope) . '_' . ucfirst($type) . 'Block';
                $id = strtolower($type . $scope);
                $name = ucfirst($scope) . ' ' . ucfirst($type) . ' Block';
                if (str_replace(sys::code() . "modules/{$scope}/xarblocks/", '', $filepath) == "{$type}/{$item->getFilename()}") {
                    // block files in subfolder - the new way
                    $dotpath = "modules.{$scope}.xarblocks.{$type}.{$type}";
                } else {
                    // block files in xarblocks - the legacy way
                    $dotpath = "modules.{$scope}.xarblocks.{$type}";
                }
            }
            $types[$id] = array(
                //'id' => $id,
                'name' => $name,
                'type' => $type,
                'module' => !empty($scope) ? $scope : '',
                'classname' => $classname,
                'dotpath' => $dotpath,
                'filepath' => $filepath,
            );
            unset($type, $filepath, $classname, $id, $name, $dotpath);
        }
    }
        
    return $types;
}
?>