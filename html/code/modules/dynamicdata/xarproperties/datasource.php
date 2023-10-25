<?php
/**
 * Include the base class
 */
sys::import('modules.base.xarproperties.dropdown');
/**
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */

sys::import('xaraya.datastores.factory');
use Xaraya\DataObject\DataStores\DataStoreFactory;

/**
 * This property displays a dropdown of data sources
 * If a relational datastore is defined, it shows the database fields available as sources
 * if the module variable datastore is defined, it shows the module variable items available as sources
 * If the dynamicdata or virtual datastore is defined, it shows nothing
 */
class DataSourceProperty extends SelectProperty
{
    public $id         = 23;
    public $name       = 'datasource';
    public $desc       = 'Data Source';
    public $reqmodules = ['dynamicdata'];

    public $include_reference   = 1;
    public $validation_override = true;

    public function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->filepath = 'modules/dynamicdata/xarproperties';
    }

    /**
    * Retrieve the list of options
    *
    */
    public function getOptions()
    {
        if (count($this->options) > 0) {
            return $this->options;
        }
        if (is_object($this->objectref)) {
            return DataStoreFactory::getDataSources($this->objectref);
        }
        return DataStoreFactory::getDataSources();
    }
}
