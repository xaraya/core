<?php
/**
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */

/**
 * Include the base class
 */
sys::import('modules.base.xarproperties.dropdown');

/**
 * Class for data source property
 */
class DataSourceProperty extends SelectProperty
{
    public $id         = 23;
    public $name       = 'datasource';
    public $desc       = 'Data Source';
    public $reqmodules = array('dynamicdata');

    public $include_reference   = 1;
    public $validation_override = true;

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->filepath = 'modules/dynamicdata/xarproperties';
    }

    function getOptions()
    {
        $options = $this->getFirstline();
        if (count($this->options) > 0) {
            if (!empty($firstline)) $this->options = array_merge($options,$this->options);
            return $this->options;
        }
        $sources = is_object($this->objectref) ? $this->objectref->datasources : array();
        return DataStoreFactory::getDataSources($sources);
    }
}
?>