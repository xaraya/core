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
 * generate the variables necessary to instantiate a *virtual* DataObject class (= not defined in database)
 *
 * $descriptor = new VirtualObjectDescriptor(['name' => 'hello']);
 * $descriptor->addProperty(['name' => 'my_id', 'type' => 'itemid']);
 * $object = new DataObject($descriptor);
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
     * @return object virtual object descriptor for use in $object = new DataObject($descriptor);
    **/
    public function __construct(array $args=[])
    {
        //$args = self::getObjectID($args);
        ObjectDescriptor::__construct($args);
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
    public function addProperty(array $args=[])
    {
        if (empty($args['name']) || empty($args['type'])) {
            throw new Exception('You need to specify at least a name and type for each property');
        }
        $this->args['propertyargs'][] = $args;
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
