<?php
/**
 * Dynamic Data source Property
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
    function __construct($args)
    {
        parent::__construct($args);
 
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

    static function getRegistrationInfo()
    {
        $info = new PropertyRegistration();
        $info->reqmodules = array('dynamicdata');
        $info->id   = 23;
        $info->name = 'datasource';
        $info->desc = 'Data Source';

        return $info;
    }
}
?>
