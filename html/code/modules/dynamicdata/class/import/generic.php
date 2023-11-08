<?php
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

namespace Xaraya\DataObject\Import;

use DataObject;
use DataObjectDescriptor;
use DataObjectFactory;
use DataPropertyMaster;
use xarDB;
use sys;

sys::import('modules.dynamicdata.class.objects.factory');
sys::import('modules.dynamicdata.class.import.xmlimporter');
sys::import('modules.dynamicdata.class.import.jsonimporter');
sys::import('modules.dynamicdata.class.import.phpimporter');

/**
 * DataObject Importer
 */
class DataObjectImporter
{
    protected static ?DataObject $dataobject = null;
    protected static ?DataObject $dataproperty = null;
    /** @var array<int, mixed> */
    public array $proptypes = [];
    public string $prefix = 'xar_';
    public bool $overwrite = false;
    public bool $keepitemid = false;
    /** @var array<string, mixed> */
    public array $objectcache = [];
    /** @var array<string, mixed> */
    public array $objectmaxid = [];

    /**
     * Summary of __construct
     * @param ?string $prefix
     * @param bool $overwrite
     * @param bool $keepitemid
     */
    public function __construct($prefix = null, $overwrite = false, $keepitemid = false)
    {
        $this->proptypes = DataPropertyMaster::getPropertyTypes();

        $this->prefix = $prefix ?? (xarDB::getPrefix() . '_');
        $this->overwrite = $overwrite;
        $this->keepitemid = $keepitemid;
    }

    /**
     * Import an object definition or an object item from XML, PHP or JSON
     *
     * @param ?string $file location of the .xml/.php/.json file containing the object definition, or
     * @param ?string $content XML/-/JSON string containing the object definition
     * @param string $format import format to use (default xml)
     * @param ?string $prefix table prefix for local database installation (default xarDB prefix)
     * @param bool $overwrite overwrite existing object definition (default false)
     * @param bool $keepitemid (try to) keep the item id of the different items (default false)
     * //$args['entry'] optional array of external references. (deprecated)
     * @return mixed|null object id on success, null on failure
     */
    public static function import($file = null, $content = null, $format = 'xml', $prefix = null, $overwrite = false, $keepitemid = false)
    {
        if (empty($format)) {
            $format = 'xml';
        }
        if (!isset($prefix)) {
            $prefix = xarDB::getPrefix();
        }
        // @todo allow non-prefixed table names someday
        $prefix .= '_';

        $importer = match ($format) {
            'php' => new PhpImporter($prefix, $overwrite, $keepitemid),
            'json' => new JsonImporter($prefix, $overwrite, $keepitemid),
            default => new XmlImporter($prefix, $overwrite, $keepitemid),
        };

        return $importer->importContent($file, $content);
    }

    /**
     * Summary of importContent
     * @param mixed $file
     * @param mixed $content
     * @return mixed
     */
    public function importContent($file = null, $content = null)
    {
        $objectid = 0;
        return $objectid;
    }

    /**
     * Summary of importObjectDef
     * @param mixed $content
     * @return int|mixed
     */
    public function importObjectDef($content)
    {
        $objectid = 0;
        return $objectid;
    }

    /**
     * Summary of importItems
     * @param mixed $content
     * @return mixed
     */
    public function importItems($content)
    {
        $objectid = 0;
        return $objectid;
    }

    /**
     * Summary of createObject
     * @param DataObjectDescriptor $descriptor
     * @return int|mixed
     */
    public static function createObject($descriptor)
    {
        static::$dataobject ??= DataObjectFactory::getObject(['name' => 'objects']);
        static::$dataproperty ??= DataObjectFactory::getObject(['name' => 'properties']);
        $info = $descriptor->getArgs();
        $propertyargs = $info['propertyargs'];
        unset($info['propertyargs']);
        $objectid = static::$dataobject->createItem($info);
        $sequence = 1;
        foreach ($propertyargs as $propertyarg) {
            $propertyarg = array_filter($propertyarg, function ($key) {
                return !str_starts_with($key, 'object_');
            }, ARRAY_FILTER_USE_KEY);
            $propertyarg['itemid'] = 0;
            $propertyarg['objectid'] = $objectid;
            unset($propertyarg['_objectid']);
            $propertyarg['seq'] ??= $sequence;
            $propertyarg['configuration'] ??= '';
            $propid = static::$dataproperty->createItem($propertyarg);
            $sequence += 1;
        }
        return $objectid;
    }
}
