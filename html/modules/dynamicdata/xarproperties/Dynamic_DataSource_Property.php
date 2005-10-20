<?php
/**
 * File: $Id$
 *
 * Dynamic Data Data Source Property
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata properties
 * @author mikespub <mikespub@xaraya.com>
*/

/**
 * Include the base class
 *
 */
include_once "modules/base/xarproperties/Dynamic_Select_Property.php";

/**
 * Class for data source property
 *
 * @package dynamicdata
 */
class Dynamic_DataSource_Property extends Dynamic_Select_Property
{
    function Dynamic_DataSource_Property($args)
    {
        $this->Dynamic_Select_Property($args);
        if (count($this->options) == 0) {
            $sources = Dynamic_DataStore_Master::getDataSources();
            if (!isset($sources)) {
                $sources = array();
            }
            foreach ($sources as $source) {
                $this->options[] = array('id' => $source, 'name' => $source);
            }
        }
        // allow values other than those in the options
        $this->override = true;
    }

    // default methods from Dynamic_Select_Property
    /**
     * Get the base information for this property.
     *
     * @returns array
     * @return base information for this property
     **/
     function getBasePropertyInfo()
     {
         $args = array();
         $baseInfo = array(
                              'id'         => 23,
                              'name'       => 'datasource',
                              'label'      => 'Data Source',
                              'format'     => '23',
                              'validation' => '',
                              'source'         => '',
                              'dependancies'   => '',
                              'requiresmodule' => 'dynamicdata',
                              'aliases'        => '',
                              'args'           => serialize($args),
                            // ...
                           );
        return $baseInfo;
     }
}

?>
