<?php
/**
 * Check for properties and import to properties table
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamicdata module
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * Check the properties directory for properties and import them into the Property Type table.
 *
 * @author the DynamicData module development team
 * @param $args['flush'] flush the property type table before import true/false (optional)
 * @returns array
 * @return an array of the property types currently available
 * @raise BAD_PARAM, NO_PERMISSION
 */

class PropertyDirectoryIterator extends DirectoryIterator
{
    public function __construct($file) 
    {
        parent::__construct(realpath($file));
    }

    public function getExtension()
    {
        $filename = $this->GetFilename();
        $extension = strrpos($filename, ".", 1) + 1;
        if ($extension != false)
            return strtolower(substr($filename, $extension, strlen($filename) - $extension));
        else
            return "";
    }
}

function dynamicdata_adminapi_importpropertytypes( $args )
{
    extract( $args );

    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();

    $dynamicproptypes = $xartable['dynamic_properties_def'];
    $propDirs = array();

    // We do the whole thing, or not at all (given proper db support)
    try {
        $dbconn->begin();

        if(isset($dirs) && is_array($dirs)) {
            // We got an array of directories passed in for which to import properties
            // typical usecase: a module which has its own property, during install phase needs that property before
            // the module is active.
            $propDirs = $dirs;
        } else {
            // Clear the cache
            PropertyRegistration::ClearCache();
        
            $activeMods = xarModApiFunc('modules','admin','getlist', array('filter' => array('State' => XARMOD_STATE_ACTIVE)));
            assert('!empty($activeMods)'); // this should never happen
            
            foreach($activeMods as $modInfo) {
                // FIXME: the modinfo directory does NOT end with a /
                $propDirs[] = 'modules/' .$modInfo['osdirectory'] . '/xarproperties/';
            }
        }

        // Get list of properties in properties directories
        $proptypes = array(); $numLoaded = 0;
        foreach($propDirs as $PropertiesDir) {
            // The iterator takes an absolute directory
            $dir = new PropertyDirectoryIterator($PropertiesDir);
            // Loop through properties directory
            for($dir->rewind();$dir->valid();$dir->next()) { 
                if($dir->isDir()) continue;
                if($dir->getExtension() != 'php') continue;
                if(substr($dir->getFileName(),0,1) == '.') continue; // temp for emacs insanity

                // Include the file into the environment
                xarInclude($dir->getPathName());
                
                // See what class we have here
                // TODO: make this independent from the file someday
                $propertyClass = basename($dir->getFileName(),'.php');

                if(!class_exists($propertyClass)) {
                    $vars = array($propertyClass, $dir->getFileName());
                    $msg = 'The class "#(1)" could not be found. (does the class name match the filename?) [Filename: "#(2)"]';
                    throw new ClassNotFoundException($vars,$msg);
                }

                // Main part
                // Call the class method on each property to get the registration info
                if (!is_callable(array($propertyClass,'getRegistrationInfo'))) continue;
                $baseInfo = call_user_func(array($propertyClass, 'getRegistrationInfo'));
                
                // If required files are not present, continue with the next file
                // TODO: move this outa here
                foreach($baseInfo->reqfiles as $required) {
                    if(!file_exists($required)) continue;
                }
                
                // If required modules are not present, continue with the next file
                // TODO: move this outa here
                foreach($baseInfo->reqmodules as $required) {
                    if(!xarModIsAvailable($required)) continue;
                }
                                                
                // Save the name of the property
                $baseInfo->class = $propertyClass;
                $baseInfo->filepath = $PropertiesDir;
                
                // Check for aliases
                if(!empty($baseInfo->aliases)) {
                    // Each alias is also a propertyRegistration object
                    foreach($baseInfo->aliases as $aliasInfo) {
                        $proptypes[$aliasInfo->id] = $aliasInfo;
                    }
                } 
                $proptypes[$baseInfo->id] = $baseInfo;
                
                // Update database entry for this property 
                // This will also do the aliases
                $baseInfo->Register();
            } // loop over the file in a directory
        } // loop over the directories
        $dbconn->commit();
    } catch(Exception $e) {
        // TODO: catch more specific exceptions than all?
        $dbconn->rollback();
        throw $e;
    }

    // Sort the property types
    ksort( $proptypes );

    return $proptypes;
}
?>
