<?php
/**
 * Dynamic Data Source Property
 *
 * @package dynamicdata
 * @subpackage properties
 */

/**
 * Include the base class
 *
 */
include_once "includes/properties/Dynamic_Select_Property.php";

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
    }

    // default methods from Dynamic_Select_Property
}

?>