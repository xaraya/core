<?php
/**
 * Dynamic data initilazation
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamic Data module
 * @link http://xaraya.com/index.php/release/182.html
 */

/**
 * This function is called internally by the core whenever the module is
 * loaded.  It adds in the information
 * @author mikespub <mikespub@xaraya.com>
 * @return array with tablenames
 */
function dynamicdata_xartables()
{
    // Initialise table array
    $xartable = array();

    // Get the name for the dynamicdata item table.  This is not necessary
    // but helps in the following statements and keeps them readable
    $dynamic_objects = xarDBGetSiteTablePrefix() . '_dynamic_objects';
    $dynamic_properties = xarDBGetSiteTablePrefix() . '_dynamic_properties';
    $dynamic_data = xarDBGetSiteTablePrefix() . '_dynamic_data';
    $dynamic_relations = xarDBGetSiteTablePrefix() . '_dynamic_relations';
    $dynamic_properties_def = xarDBGetSiteTablePrefix() . '_dynamic_properties_def';


    // Set the table names
    $xartable['dynamic_objects'] = $dynamic_objects;
    $xartable['dynamic_properties'] = $dynamic_properties;
    $xartable['dynamic_data'] = $dynamic_data;
    $xartable['dynamic_relations'] = $dynamic_relations;
    $xartable['dynamic_properties_def'] = $dynamic_properties_def;


    // Return the table information
    return $xartable;
}

?>
