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
            // Open Properties Directory if it exists, otherwise go to the next one
            if(!file_exists($PropertiesDir)) continue;
            if ($pdh = opendir($PropertiesDir)) {
                // Loop through properties directory
                // TODO: use an iterator here.
                while (($propertyfile = readdir($pdh)) !== false) {
                    $propertyfilepath = $PropertiesDir . $propertyfile;
                    // Only Process files, not directories
                    if(!is_file($propertyfilepath)) continue;
                        
                    // Get the name of each file, assumed to be the name of the property
                    // FIXME: <mrb> decouple the classname from the filename someday
                    $fileparts = explode('.',$propertyfile);
                    // Only worry about php files, not backup files or other garbage that might be present
                    if (count($fileparts) != 2) continue;
                    $propertyClass = $fileparts[0];
                    $type = $fileparts[1];
                    
                    // Only worry about php files, not security place holder .html files or other garbage that might be present
                    if( $type != 'php') continue;
                    
                    // Include the file into the environment
                    xarInclude($propertyfilepath);
                    // Tell the property to skip initialization, this is only really needed for Dynamic_FieldType_Property
                    // because it causes this function to recurse.
                    $args['skipInit'] = true;
                    
                    // Instantiate a copy of this class
                    if(!class_exists($propertyClass)) {
                        $vars = array($propertyClass, $propertyfile);
                        $msg = 'The class "#(1)" could not be found. (does the class name match the filename?) [Filename: "#(2)"]';
                        throw new ClassNotFoundException($vars,$msg);
                    }
                    // Call the class method on each property to get the registration info
                    if (!is_callable(array($propertyClass,'getRegistrationInfo'))) continue;
                    $baseInfo = call_user_func(array($propertyClass, 'getRegistrationInfo'));
 
                    // If required files are not present, continue with the next property
                    if(!empty($baseInfo->reqfiles)) {
                        $files = explode(';',$baseInfo->reqfiles);
                        foreach($files as $required) {
                            if(!file_exists($required)) continue;
                        }
                    }
                    
                    // If required modules are not present, continue with the next property
                    if(!empty($baseInfo->reqmodules)) {
                        $modules = explode(';', $baseInfo->reqmodules);
                        foreach($modules as $required) {
                            if(!xarModIsAvailable($required)) continue;
                        }
                    }
                                           
                    // Save the name of the property
                    $baseInfo->class = $propertyClass;
                    $baseInfo->filepath = $propertyfilepath;
                    
                    // Check for aliases
                    if(!empty($baseInfo->aliases)) {
                        // There are aliases
                        $aliasList = '';
                        // Each alias is also a propertyRegistration object
                        foreach($baseInfo->aliases as $aliasInfo) {
                            $aliasInfo->class = $propertyClass;
                            $aliasInfo->filepath = $propertyfilepath;
                            $aliasInfo->reqmodules = $baseInfo->reqmodules;
                            $proptypes[$aliasInfo->id] = $aliasInfo;
                            $aliasList .= $aliasInfo->id . ',';
                            // Update Database
                            $aliasInfo->Register();
                        }
                    } 
                    $proptypes[$baseInfo->id] = $baseInfo;

                    // Update database entry for this property (the aliases array, if any, will now be an aliaslist)
                    $baseInfo->Register();
                }
                closedir($pdh);
            }
        }
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
