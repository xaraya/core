<?php
/**
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 */

sys::import('modules.dynamicdata.class.objects.descriptor');
sys::import('modules.dynamicdata.class.utilapi');
use Xaraya\DataObject\UtilApi;

/**
 * Generate the variables necessary to instantiate a *virtual* DataObject class (= not defined in database)
 *
 * By default the virtual dataobjects will use a data store supported by cache storage (transient or persistent)
 *
 * // define the object and its properties using the descriptor first
 * $descriptor = new VirtualObjectDescriptor(['name' => 'hello']);
 * $descriptor->addProperty(['name' => 'my_id', 'type' => 'itemid']);
 * $descriptor->addProperty(['name' => 'my_title', 'type' => 'textbox']);
 *
 * // you can use the same descriptor for DataObject & DataObjectList afterwards
 * $object = new DataObject($descriptor);
 * $object->createItem(['my_id' => 5, 'my_title' => 'Hi there']);
 * ...
 * // you can use the same descriptor for DataObject & DataObjectList afterwards
 * $objectlist = new DataObjectlist($descriptor);
 * $items = $objectlist->getItems(['itemids' => [5]]);
 *
 * You can also use an existing database table as relational data store for the virtual objects
 *
 * $descriptor = new TableObjectDescriptor(['table' => 'xar_other_table']);
 * $objectlist = new DataObjectList($descriptor);
 * $items = $objectlist->getItems();
 */
class VirtualObjectDescriptor extends DataObjectDescriptor
{
    /** @var array<string, mixed> */
    protected $args = [
        'objectid' => 0,
        'name' => 'virtual',
        'label' => 'Virtual Object',
        'moduleid' => 182,
        'itemtype' => 0,
        'module_id' => 182,
        'template' => '',
        // Data Store is supported by cacheStorage (dummy = 1 request only or apcu = somewhat persistent by default)
        'datastore' => 'cache',
        'cachestorage' => 'apcu', // or 'dummy' etc.
        'propertyargs' => [],
    ];

    /**
     * Make an object descriptor to create a new (virtual) type of Dynamic Object
     *
     * @param array<string, mixed> $args
     * with
     *     $args['name'] name of the object to create (required)
     *     $args['objectid'] id of the object you want to create (optional)
     *     $args['label'] label of the object to create
     *     $args['moduleid'] module id of the object to create
     *     $args['itemtype'] item type of the object to create
     *     $args['urlparam'] URL parameter to use for the object items (itemid, exid, aid, ...)
     *     $args['maxid'] for purely dynamic objects, the current max. itemid (for import only)
     *     $args['config'] some configuration for the object (free to define and use)
     *     $args['isalias'] flag to indicate whether the object name is used as alias for short URLs
     *     $args['class'] optional classname (e.g. <module>_DataObject)
     * @param bool $offline do we want to work offline = without database connection (need to export cache first)
     * @return object virtual object descriptor for use in $object = new DataObject($descriptor); or $objectlist = new DataObjectList($descriptor);
    **/
    public function __construct(array $args = [], bool $offline = false)
    {
        $args['moduleid'] ??= 182;
        $args['module_id'] = $args['moduleid'];
        //$args = self::getObjectID($args);
        ObjectDescriptor::__construct($args);
        if ($offline) {
            static::loadCoreCache();
        }
    }

    /**
     * Add a property to the descriptor before building this object
     *
     * @param array<string, mixed> $args
     * with
     *     $args['name'] the name for the dynamic property (required)
     *     $args['type'] the type of dynamic property (required)
     *     $args['label'] the label for the dynamic property
     *     $args['source'] the source for the dynamic property
     *     $args['defaultvalue'] the default value for the dynamic property
     *     $args['status'] the input and display status for the dynamic property
     *     $args['seq'] the place in sequence this dynamic property appears in
     *     $args['configuration'] the configuration (serialized array) for the dynamic property
     *     $args['id'] the id for the dynamic property
     * @return void
    **/
    public function addProperty(array $args = [])
    {
        if (empty($args['name']) || empty($args['type'])) {
            throw new Exception('You need to specify at least a name and type for each property');
        }
        $this->args['propertyargs'] ??= [];
        if (!isset($args['id'])) {
            $args['id'] = count($this->args['propertyargs']) + 1;
        }
        $this->args['propertyargs'][] = $args;
    }

    /**
     * Magic method to re-create object descriptor based on result of var_export($object->descriptor, true)
     * @param array<string, mixed> $args
    **/
    public static function __set_state($args)
    {
        $var = get_called_class();
        $c = new $var($args['args']);
        return $c;
    }

    /**
     * Load core cache with property types and configurations
     * @return void
     */
    public static function loadCoreCache()
    {
        static $loaded = false;
        if ($loaded) {
            return;
        }
        if (!xarCoreCache::loadCached('DynamicData', 'PropertyTypes')) {
            throw new Exception('No property types cached yet - you need to export at least 1 object to php');
        }
        if (!xarCoreCache::loadCached('DynamicData', 'Configurations')) {
            throw new Exception('No configurations cached yet - you need to export at least 1 object to php');
        }
        $loaded = true;
    }
}

/**
 * Generate the variables necessary to instantiate a *virtual* DataObject class (= not defined in database)
 *
 * You can also use an existing database table as relational data store for the virtual objects
 *
 * $descriptor = new TableObjectDescriptor(['table' => 'xar_other_table']);
 * $objectlist = new DataObjectList($descriptor);
 * $items = $objectlist->getItems();
 *
 * Or connect to a different database first and use their tables as relational data store
 * $args = ['databaseType' => 'sqlite3', 'databaseName' => $filepath];
 * $conn = xarDB::newConn($args);
 * $dbConnIndex = xarDB::getConnIndex();
 *
 * $descriptor = new TableObjectDescriptor(['table' => 'non_xar_table', 'dbConnIndex' => $dbConnIndex]);
 * $objectlist = new DataObjectList($descriptor);
 * ...
 *
 * Or pass along the connection parameters directly to the descriptor
 * $descriptor = new TableObjectDescriptor(['table' => 'non_xar_table', 'dbConnArgs' => $dbConnArgs]);
 * $objectlist = new DataObjectList($descriptor);
 * ...
 */
class TableObjectDescriptor extends VirtualObjectDescriptor
{
    /**
     * Make an object descriptor to create a new (virtual) type of Dynamic Object for a database table
     *
     * @param array<string, mixed> $args
     * with
     *     ... arguments above, and
     *     $args['table'] name of the database table (required)
     *     $args['fields'] list of field specs coming from getmeta() or elsewhere (optional)
     *     $args['dbConnIndex'] connection index of the database if different from Xaraya DB (optional)
     *     $args['dbConnArgs'] connection params of the database if different from Xaraya DB (optional)
     */
    public function __construct(array $args = [])
    {
        $args['name'] ??= $args['table'] ?? 'unknown';
        $args['label'] ??= 'Table ' . $args['name'];
        //$args = self::getObjectID($args);
        $offline = false;
        parent::__construct($args, $offline);
        if (!empty($args['table'])) {
            $args['fields'] ??= [];
            $args['dbConnIndex'] ??= 0;
            $args['dbConnArgs'] ??= [];
            $this->addTable($args['table'], $args['fields'], $args['dbConnIndex'], $args['dbConnArgs']);
        }
    }

    /**
     * Use an existing database table as relational data store for this virtual object
     *
     * @param string $table name of the database table (required)
     * @param array<string, array<string, mixed>> $fields list of field specs coming from getmeta() or elsewhere (optional)
     * @param int $dbConnIndex connection index of the database if different from Xaraya DB (optional)
     * @param array<string, mixed> $dbConnArgs connection params of the database if different from Xaraya DB (optional)
     * @return void
    **/
    public function addTable(string $table, array $fields = [], int $dbConnIndex = 0, array $dbConnArgs = [])
    {
        if (empty($fields)) {
            //$fields = xarMod::apiFunc('dynamicdata', 'util', 'getstatic', ['module' => 'dynamicdata', 'module_id' => 182, 'table' => $table, 'dbConnIndex' => $dbConnIndex]);
            /** @var array<string, array<string, array<string, mixed>>> $meta */
            $meta = UtilApi::getMeta($table, null, $dbConnIndex, $dbConnArgs);
            if (empty($meta[$table])) {
                throw new Exception("Unknown table $table");
            }
            $fields = $meta[$table];
        }
        $this->args['datastore'] = 'relational';
        unset($this->args['cachestorage']);
        $this->args['sources'] = serialize([$table => $table]);
        foreach ($fields as $name => $field) {
            // cosmetic fix for name and title fields
            if (in_array($field['name'], ['name', 'title']) && $field['type'] == '4') {
                $field['type'] = '2';
                $field['status'] = DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE;
            }
            $this->addProperty($field);
        }
    }
}
