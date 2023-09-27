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
 * Or you can also use an existing database table as relational data store for the virtual objects
 *
 * $descriptor = new VirtualObjectDescriptor(['table' => 'xar_other_table']);
 * $objectlist = new DataObjectList($descriptor);
 * $items = $objectlist->getItems();
 */
class VirtualObjectDescriptor extends DataObjectDescriptor
{
    protected $args = [
        'objectid' => 0,
        'name' => 'virtual',
        'moduleid' => 182,
        'itemtype' => 0,
        'module_id' => 182,
        'template' => '',
        // Data Store is supported by cacheStorage (dummy = 1 request only or apcu = somewhat persistent by default)
        'datastore' => 'cache',
        'cachestorage' => 'dummy', // or 'apcu' etc.
        'propertyargs' => [],
    ];

    /**
     * Make an object descriptor to create a new (virtual) type of Dynamic Object
     *
     * @param $args['name'] name of the object to create (required)
     * @param $args['objectid'] id of the object you want to create (optional)
     * @param $args['label'] label of the object to create
     * @param $args['moduleid'] module id of the object to create
     * @param $args['itemtype'] item type of the object to create
     * @param $args['urlparam'] URL parameter to use for the object items (itemid, exid, aid, ...)
     * @param $args['maxid'] for purely dynamic objects, the current max. itemid (for import only)
     * @param $args['config'] some configuration for the object (free to define and use)
     * @param $args['isalias'] flag to indicate whether the object name is used as alias for short URLs
     * @param $args['class'] optional classname (e.g. <module>_DataObject)
     * @return object virtual object descriptor for use in $object = new DataObject($descriptor); or $objectlist = new DataObjectList($descriptor);
    **/
    public function __construct(array $args = [])
    {
        //$args = self::getObjectID($args);
        ObjectDescriptor::__construct($args);
        if (!empty($args['table'])) {
            $this->addTable($args['table'], $args['fields'] ?? []);
        }
    }

    /**
     * Add a property to the descriptor before building this object
     *
     * @param $args['name'] the name for the dynamic property (required)
     * @param $args['type'] the type of dynamic property (required)
     * @param $args['label'] the label for the dynamic property
     * @param $args['source'] the source for the dynamic property
     * @param $args['defaultvalue'] the default value for the dynamic property
     * @param $args['status'] the input and display status for the dynamic property
     * @param $args['seq'] the place in sequence this dynamic property appears in
     * @param $args['configuration'] the configuration (serialized array) for the dynamic property
     * @param $args['id'] the id for the dynamic property
    **/
    public function addProperty(array $args = [])
    {
        if (empty($args['name']) || empty($args['type'])) {
            throw new Exception('You need to specify at least a name and type for each property');
        }
        $this->args['propertyargs'][] = $args;
    }

    /**
     * Use an existing database table as relational data store for this virtual object
     *
     * @param $table name of the database table (required)
     * @param $fields list of field specs coming from getmeta() or elsewhere (optional)
    **/
    public function addTable(string $table, array $fields = [])
    {
        if (empty($fields)) {
            $meta = xarMod::apiFunc('dynamicdata', 'util', 'getmeta', ['table' => $table]);
            if (empty($meta[$table])) {
                throw new Exception("Unknown table $table");
            }
            $fields = $meta[$table];
        }
        $this->args['name'] = $table;
        $this->args['datastore'] = 'relational';
        $this->args['sources'] = serialize([$table => $table]);
        foreach ($fields as $field) {
            $this->addProperty($field);
        }
    }

    /**
     * Magic method to re-create object descriptor based on result of var_export($object->descriptor, true)
    **/
    public static function __set_state($args)
    {
        $var = get_called_class();
        $c = new $var($args['args']);
        return $c;
    }
}
