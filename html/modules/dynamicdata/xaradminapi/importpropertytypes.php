<?php
/**
 * Check the properties directory for properties and import them into the Property Type table.
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata module
 * @author Michael Cortez <mcortez@fullcoll.edu>
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
    if(isset($dirs) && is_array($dirs)) {
      // We got an array of directories passed in for which to import properties
      // typical usecase: a module which has its own property, during install phase needs that property before
      // the module is active.
      $propDirs = $dirs;
    } else {
      // Get a list of active modules which might have properties
      $clearCache = "DELETE FROM $dynamicproptypes";
      $result =& $dbconn->Execute($clearCache);
      if(!$result) return; // db error

      $activeMods = xarModApiFunc('modules','admin','getlist', array('filter' => array('State' => XARMOD_STATE_ACTIVE)));
      if(empty($activeMods)) return; // this should never happen
      $propDirs[] = 'includes/properties/'; // Initialize it with the core location of properties
        
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
            while (($propertyfile = readdir($pdh)) !== false) 
            {
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
                require_once $propertyfilepath;
                
                // Tell the property to skip initialization, this is only really needed for Dynamic_FieldType_Property
                // because it causes this function to recurse.
                $args['skipInit'] = true;
                
                // Instantiate a copy of this class
                if(!class_exists($propertyClass)) {
                    // TODO: <mrb> raise exception?
                  xarLogMessage("DD : The class $propertyClass does not match the filename $propertyfile",XARLOG_LEVEL_WARNING);
                    continue;
                }
                $property = new $propertyClass($args);
                
                // Get the base information that used to be hardcoded into /modules/dynamicdata/class/properties.php
                $baseInfo = $property->getBasePropertyInfo();
                
                // Ensure that the base properties are all present.
                if( !isset($baseInfo['dependancies']) )   $baseInfo['dependancies'] = '';
                if( !isset($baseInfo['requiresmodule']) ) $baseInfo['requiresmodule'] = '';
                if( !isset($baseInfo['aliases']) )        $baseInfo['aliases'] = '';
                if( empty($baseInfo['args']) )            $baseInfo['args'] = serialize(array());
       
                // If the property needs specific files to exist, check for them
                // Example: HTML Area property needs to check to see if HTMLArea javascript files are present
                if( isset($baseInfo['dependancies']) && ($baseInfo['dependancies'] != '') )
                {
                    $dependancies = explode(';', $baseInfo['dependancies']);
                    foreach( $dependancies as $dependancy ) {
                        // If the file is not there continue to the next property
                        if( !file_exists($dependancy) )  continue 2;
                    }
                }

                // Check if any Modules are required
                // For Example: Categories, Ratings, Hitcount properties all require their respective modules to be enabled
                // CHECK: <mrb> do we want the owning module in here?
                // ANSWER: probably not, see above (if the $dirs are passed in)
                if( isset($baseInfo['requiresmodule']) && ($baseInfo['requiresmodule'] != '') )
                {
                    $modulesNeeded = explode(';', $baseInfo['requiresmodule']);
                    foreach( $modulesNeeded as $moduleName )
                    {
                        // If a required module is not available continue with the next property
                        if( !xarModIsAvailable($moduleName) ) continue 2;
                    }
                }


                // Save the name of the property
                $baseInfo['propertyClass'] = $propertyClass;
                $baseInfo['filepath'] = $propertyfilepath;
                
               
                // Check for aliases
                if( !isset($baseInfo['aliases']) || ($baseInfo['aliases'] == '') || !is_array($baseInfo['aliases']) )
                {
                    // Make sure that this is always available
                    $baseInfo['aliases'] = '';
                
                    // Add the property to the property type list
                    $proptypes[$baseInfo['id']] = $baseInfo;
                    
                } else if ( is_array($baseInfo['aliases']) && (count($baseInfo['aliases']) > 0) ) {
                    // if aliases are present include them as seperate entries
                    $aliasList = '';
                    foreach( $baseInfo['aliases'] as $aliasInfo )
                    {
                        // Save the name of the property, for the alias
                        $aliasInfo['propertyClass'] = $propertyClass;
                        $aliasInfo['aliases']       = '';
                        $aliasInfo['filepath']      = $propertyfilepath;
                        
                        // Add the alias to the property type list
                        $proptypes[$aliasInfo['id']] = $aliasInfo;
                        $aliasList .= $aliasInfo['id'].',';
                        
                        // Update Database
                        updateDB( $aliasInfo, $baseInfo['id'], $propertyfilepath );
                        
                    }
                    
                    // Store a list of reference ID's from the base property it's aliases
                    // FIXME: strip the last comma off?
                    $baseInfo['aliases'] = $aliasList;

                    // Add the base property to the property type list
                    $proptypes[$baseInfo['id']] = $baseInfo;
                }
                
                // Update database entry for this property (the aliases array, if any, will now be an aliaslist)
                updateDB( $baseInfo, '', $propertyfilepath );
            }
            closedir($pdh);
        }

        // Sort the property types
        ksort( $proptypes );
        
    }
    return $proptypes;
}

function updateDB( $proptype, $parent, $filepath )
{
    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();

    $dynamicproptypes = $xartable['dynamic_properties_def'];

    $insert = "INSERT INTO $dynamicproptypes
                ( xar_prop_id, xar_prop_name, xar_prop_label,
                  xar_prop_parent, xar_prop_filepath, xar_prop_class,
                  xar_prop_format, xar_prop_validation, xar_prop_source,
                  xar_prop_reqfiles, xar_prop_reqmodules, xar_prop_args,
                  xar_prop_aliases
                ) 
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)";
        
    $bindvars = array((int) $proptype['id'], $proptype['name'], $proptype['label'],
                      $parent, $filepath, $proptype['propertyClass'], 
                      $proptype['format'], $proptype['validation'], $proptype['source'], 
                      $proptype['dependancies'], $proptype['requiresmodule'], $proptype['args'], 
                      $proptype['aliases']);
    $result =& $dbconn->Execute($insert,$bindvars);
}
?>
