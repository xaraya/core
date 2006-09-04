<?php
/**
 * Dynamic Data source Property
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamic Data module
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */

/**
 * Include the base class
 *
 */
sys::import('modules.base.xarproperties.Dynamic_Select_Property');

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
        $this->filepath   = 'modules/dynamicdata/xarproperties';

        if (count($this->options) == 0) {
            $sources = DataStoreFactory::getDataSources();
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
