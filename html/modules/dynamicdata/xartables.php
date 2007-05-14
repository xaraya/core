<?php
/**
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata
 * @link http://xaraya.com/index.php/release/182.html
 */

/**
 * This function is called internally by the core whenever the module is
 * loaded.  It adds the table names to a globally accessible array
 *
 * @author mikespub <mikespub@xaraya.com>
 * @return array with tablenames
 */
function dynamicdata_xartables()
{
    $tables = array();

    $tables['dynamic_objects'] = xarDBGetSiteTablePrefix() . '_dynamic_objects';
    $tables['dynamic_properties'] = xarDBGetSiteTablePrefix() . '_dynamic_properties';
    $tables['dynamic_data'] = xarDBGetSiteTablePrefix() . '_dynamic_data';
    $tables['dynamic_relations'] = xarDBGetSiteTablePrefix() . '_dynamic_relations';
    $tables['dynamic_properties_def'] = xarDBGetSiteTablePrefix() . '_dynamic_properties_def';

    return $tables;
}
?>
