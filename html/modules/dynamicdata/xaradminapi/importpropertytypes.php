<?php

/**
 * File: $Id$
 *
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

    $clearCache = "DELETE FROM $dynamicproptypes";
    $result =& $dbconn->Execute($clearCache);
    

    // Get list of properties in properties directory.
    $PropertiesDir = 'includes/properties/';

    // Open Properties Directory
    if ($pdh = opendir($PropertiesDir)) 
    {
        $proptypes = array();

        $numLoaded = 0;
        
        // Loop through properties directory
        while (($propertyfile = readdir($pdh)) !== false) 
        {
            $propertyfilepath = $PropertiesDir . $propertyfile;

            // Only Process files, not directories
            if( is_file($propertyfilepath) )
            {
                // Get the name of each file, assumed to be the name of the property
                // Get file type
                list($propertyClass, $type) = explode('.',$propertyfile);
                
                // Only worry about php files, not security place holder .html files or other garbage that might be present
                if( $type == 'php' )
                {
                    // Include the file into the environment
                    require_once $propertyfilepath;
                    
                    // Tell the property to skip initialization, this is only really needed for Dynamic_FieldType_Property
                    // because it causes this function to recurse.
                    $args['skipInit'] = true;
                    
                    // Instantiate a copy of this class
                    $property = new $propertyClass($args);
                    
                    // Get the base information that used to be hardcoded into /modules/dynamicdata/class/properties.php
                    $baseInfo = $property->getBasePropertyInfo();
                    
                    // Insure that the base properties are all present.
                    if( !isset($baseInfo['dependancies']) )
                    {
                        $baseInfo['dependancies'] = '';
                    }
                    if( !isset($baseInfo['requiresmodule']) )
                    {
                        $baseInfo['requiresmodule'] = '';
                    }
                    if( !isset($baseInfo['aliases']) )
                    {
                        $baseInfo['aliases'] = '';
                    }
                    if( !isset($baseInfo['args']) )
                    {
                        $baseInfo['args'] = '';
                    }

                    // Check if there is any reason why we shouldn't include this property in the property list                    
                    $skipProperty = false;
                    
                    // If the property needs specific files to exist, check for them
                    // Example: HTML Area property needs to check to see if HTMLArea javascript files are present
                    if( isset($baseInfo['dependancies']) && ($baseInfo['dependancies'] != '') )
                    {
                        $dependancies = explode(';', $baseInfo['dependancies']);
                        foreach( $dependancies as $dependancy )
                        {
                            if( !file_exists($dependancy) )
                            {
                                $skipProperty = true;
                                break;
                            }
                        }
                    }

                    // Check if any Modules are required
                    // For Example: Categories, Ratings, Hitcount properties all require their respective modules to be enabled
                    if( isset($baseInfo['requiresmodule']) && ($baseInfo['requiresmodule'] != '') )
                    {
                        $modulesNeeded = explode(';', $baseInfo['requiresmodule']);
                        foreach( $modulesNeeded as $moduleName )
                        {
                            if( !xarModIsAvailable($moduleName) )
                            {
                                $skipProperty = true;
                                break;
                            }
                        }
                    }

                    // If we're still going to add this property to the list
                    if( !$skipProperty )
                    {
                        // Save the name of the property
                        $baseInfo['propertyClass'] = $propertyClass;
                        
                        // Update database entry for this property
                        updateDB( $baseInfo, '', $propertyfilepath );
                        
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
                                
                                
                                // Add the alias to the property type list
                                $proptypes[$aliasInfo['id']] = $aliasInfo;
                                $aliasList .= $aliasInfo['id'].',';
                                
                                // Update Database
                                updateDB( $aliasInfo, $baseInfo['id'], $propertyfilepath );
                                
                            }
                            
                            // Store a list of reference ID's from the base property it's aliases
                            $baseInfo['aliases'] = $aliasList;

                            // Add the base property to the property type list
                            $proptypes[$baseInfo['id']] = $baseInfo;
                        }
                    }
                }                
            }
        }
        closedir($pdh);

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
                  (
                    xar_prop_id
                    , xar_prop_name
                    , xar_prop_label
                    , xar_prop_parent
                    , xar_prop_filepath
                    , xar_prop_class
                    , xar_prop_format 
                    , xar_prop_validation
                    , xar_prop_source
                    , xar_prop_reqfiles
                    , xar_prop_reqmodules
                    , xar_prop_args
                    , xar_prop_aliases
                  )
                  VALUES
                  (
                    ".$proptype['id']."
                    , '".$proptype['name']."'
                    , '".$proptype['label']."'
                    , '".$parent."'
                    , '".$filepath."'
                    , '".$proptype['propertyClass']."'
                    , '".$proptype['format']."'
                    , '".$proptype['validation']."'
                    , '".$proptype['source']."'
                    , '".$proptype['dependancies']."'
                    , '".$proptype['requiresmodule']."'
                    , '".$proptype['args']."'
                    , '".$proptype['aliases']."'
                  )";

    $result =& $dbconn->Execute($insert);
}
?>