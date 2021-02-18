<?php
/**
 * @package modules\dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
 *
 * @author mikespub <mikespub@xaraya.com>
 */
sys::import('modules.dynamicdata.class.objects.master');
/**
 * Class to build DataObject REST API
**/
class DataObjectRESTBuilder extends xarObject
{
    protected static $openapi;
    protected static $endpoint = 'rst.php/v1';
    protected static $objects = array();
    protected static $internal = array('objects', 'properties', 'configurations');
    protected static $proptype_names = array();
    protected static $paths = array();
    protected static $schemas = array();
    protected static $responses = array();
    protected static $parameters = array();
    protected static $requestBodies = array();
    protected static $securitySchemes = array();
    protected static $tags = array();

    public static function init(array $args = array())
    {
        if (isset(self::$openapi)) {
            return;
        }
        self::$openapi = sys::varpath() . '/cache/openapi.json';
        self::parse_openapi();
    }

    public static function parse_openapi()
    {
        if (!file_exists(self::$openapi)) {
            self::create_openapi();
        }
        $content = file_get_contents(self::$openapi);
        $doc = json_decode($content, true);
        return $doc;
    }

    public static function create_openapi()
    {
        self::init_openapi();
        self::add_objects();
        self::add_whoami();
        self::dump_openapi();
    }

    public static function dump_openapi()
    {
        $doc = array();
        $doc['openapi'] = '3.0.2';
        $doc['info'] = array(
            'title' => 'DynamicData REST API',
            'description' => 'This provides a REST API endpoint as proof of concept to access Dynamic Data Objects stored in dynamic_data. Access to all objects is limited to read-only mode by default. The Sample object requires cookie authentication to create/update/delete items (after login on this site).',
            'version' => '1.1.0'
        );
        $doc['servers'] = array(
            array('url' => xarServer::getBaseURL() . self::$endpoint)
        );
        $doc['paths'] = self::$paths;
        $doc['components'] = array(
            'schemas' => self::$schemas,
            'responses' => self::$responses,
            'parameters' => self::$parameters,
            'requestBodies' => self::$requestBodies,
            'securitySchemes' => self::$securitySchemes
        );
        $doc['tags'] = self::$tags;
        $content = json_encode($doc, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        file_put_contents(self::$openapi, $content);
    }

    public static function init_openapi()
    {
        self::$paths = array();
        self::$schemas = array();
        self::$responses = array();
        self::$parameters = array();
        self::$requestBodies = array();
        self::$securitySchemes = array();
        self::$tags = array();
        self::add_parameters();
        self::add_responses();
        self::add_securitySchemes();
    }

    public static function add_parameters()
    {
        self::$parameters['itemid'] = array(
            'name' => 'id',
            'in' => 'path',
            'schema' => array(
                'type' => 'string'
            ),
            'description' => 'itemid value',
            'required' => true
        );
        self::$parameters['limit'] = array(
            'name' => 'limit',
            'in' => 'query',
            'schema' => array(
                'type' => 'integer',
                'default' => 20
            ),
            'description' => 'Number of items to return',
        );
        self::$parameters['offset'] = array(
            'name' => 'offset',
            'in' => 'query',
            'schema' => array(
                'type' => 'integer',
                'default' => 0
            ),
            'description' => 'Offset to start items from',
        );
        self::$parameters['order'] = array(
            'name' => 'order',
            'in' => 'query',
            'schema' => array(
                'type' => 'array',
                'items' => array(
                    'type' => 'string'
                )
            ),
            'description' => 'Property to sort on and optional direction (comma separated)',
        );
        self::$parameters['filter'] = array(
            'name' => 'filter',
            'in' => 'query',
            'schema' => array(
                'type' => 'array',
                'items' => array(
                    'type' => 'string'
                )
            ),
            'description' => 'Filters to be applied. Each filter consists of a property, an operator and a value (comma separated)',
        );
    }

    public static function add_responses()
    {
        self::$responses['itemid'] = array(
            'description' => 'Return itemid value',
            'content' => array(
                'application/json' => array(
                    'schema' => array(
                        'type' => 'string'
                    )
                )
            )
        );
        self::$responses['unauthorized'] = array(
            'description' => 'Authorization information is missing or invalid',
            'headers' => array(
                'WWW-Authenticate' => array(
                    'schema' => array(
                        'type' => 'string'
                    )
                )
            )
        );
    }

    public static function add_securitySchemes()
    {
        self::$securitySchemes['cookieAuth'] = array(
            'type' => 'apiKey',
            'description' => 'Use Xaraya Session Cookie (after login on the site)',
            'name' => 'XARAYASID',
            'in' => 'cookie'
        );
    }

    public static function add_objects()
    {
        self::get_proptype_names();
        self::$objects = array();
        $objectname = 'start';
        $fieldlist = array('objectid', 'name', 'label', 'module_id', 'itemtype', 'datastore');
        $prop_view = array();
        foreach ($fieldlist as $field) {
            $prop_view[$field] = array('type' => 'string');
        }
        self::add_object_view($objectname, $prop_view, '/objects');
        self::$tags[] = array('name' => $objectname, 'description' => $objectname . ' operations');
        $objectname = 'objects';
        $params = array('name' => $objectname, 'fieldlist' => $fieldlist);
        $objectlist = DataObjectMaster::getObjectList($params);
        $items = $objectlist->getItems();
        foreach ($items as $itemid => $item) {
            if ($item['datastore'] !== 'dynamicdata' && !in_array($item['name'], self::$internal)) {
                continue;
            }
            //echo $itemid . ': ' . json_encode($item) . "\n";
            $item['properties'] = self::get_object_properties($item['name']);
            self::$objects[$item['name']] = $item;
        }
        return self::$objects;
    }

    public static function get_proptype_names()
    {
        if (empty(self::$proptype_names)) {
            self::$proptype_names = array();
            $proptypes = DataPropertyMaster::getPropertyTypes();
            foreach ($proptypes as $typeid => $proptype) {
                self::$proptype_names[$typeid] = $proptype['name'];
            }
        }
        return self::$proptype_names;
    }

    public static function get_objects()
    {
        if (empty(self::$objects)) {
            self::init_openapi();
            self::add_objects();
            self::add_whoami();
        }
        return self::$objects;
    }

    public static function get_object_properties($objectname)
    {
        $properties = array();
        $params = array('name' => $objectname);
        $objectref = DataObjectMaster::getObject($params);
        $prop_display = array();
        $prop_view = array();
        $prop_create = array();
        // @todo add fields based on object descriptor?
        foreach ($objectref->getProperties() as $key => $property) {
            if (array_key_exists($property->type, self::$proptype_names)) {
                $properties[$property->name] = self::$proptype_names[$property->type] . ' (' . $property->basetype . ')';
            } else {
                $properties[$property->name] = $property->basetype;
            }
            // @todo improve matching types
            $datatype = self::match_proptype($property);
            switch ($property->getDisplayStatus()) {
                case DataPropertyMaster::DD_DISPLAYSTATE_DISABLED:
                    //$prop_create[$property->name] = $datatype;
                    break;
                case DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE:
                    $prop_display[$property->name] = $datatype;
                    $prop_view[$property->name] = $datatype;
                    $prop_create[$property->name] = $datatype;
                    break;
                case DataPropertyMaster::DD_DISPLAYSTATE_DISPLAYONLY:
                    $prop_display[$property->name] = $datatype;
                    $prop_create[$property->name] = $datatype;
                    break;
                case DataPropertyMaster::DD_DISPLAYSTATE_HIDDEN:
                    //$prop_create[$property->name] = $datatype;
                    break;
                case DataPropertyMaster::DD_DISPLAYSTATE_VIEWONLY:
                    $prop_view[$property->name] = $datatype;
                    $prop_create[$property->name] = $datatype;
                    break;
                default:
                    throw new Exception('Unsupported display status ' . $property->getDisplayStatus());
                    break;
            }
        }
        self::add_object_view($objectname, $prop_view);
        self::add_object_display($objectname, $prop_display);
        if ($objectname == 'sample') {
            self::add_object_create($objectname, $prop_create);
            self::add_object_update($objectname, $prop_create);
            self::add_object_delete($objectname, $prop_create);
            //self::add_object_patch($objectname, $prop_create);
        }
        self::$tags[] = array('name' => $objectname, 'description' => $objectname . ' operations');
        return $properties;
    }

    public static function add_object_view($objectname, $properties, $path = '')
    {
        if (empty($path)) {
            $path = '/objects/' . $objectname;
        }
        self::$paths[$path] = array(
            'get' => array(
                'parameters' => array(
                    array('$ref' => '#/components/parameters/limit'),
                    array('$ref' => '#/components/parameters/offset'),
                    array('$ref' => '#/components/parameters/order'),
                    array('$ref' => '#/components/parameters/filter')
                ),
                'tags' => array($objectname),
                'operationId' => 'view_' . $objectname,
                'description' => 'View list of ' . $objectname,
                'responses' => array(
                    '200' => array(
                        '$ref' => '#/components/responses/view-' . $objectname
                    )
                )
            )
        );
        if (in_array($objectname, self::$internal)) {
            self::$paths[$path]['get']['responses']['401'] = array(
                '$ref' => '#/components/responses/unauthorized'
            );
            self::$paths[$path]['get']['security'] = array(
                array('cookieAuth' => array())
            );
        }
        self::$responses['view-' . $objectname] = array(
            'description' => 'View list of ' . $objectname . ' objects',
            'content' => array(
                'application/json' => array(
                    'schema' => array(
                        '$ref' => '#/components/schemas/view-' . $objectname
                    )
                )
            )
        );
        self::$schemas['view-' . $objectname] = array(
            'type' => 'object',
            'properties' => array(
                'count' => array(
                    'type' => 'integer'
                ),
                'limit' => array(
                    'type' => 'integer'
                ),
                'offset' => array(
                    'type' => 'integer'
                ),
                'order' => array(
                    'type' => 'string'
                ),
                'filter' => array(
                    'type' => 'string'
                ),
                'items' => array(
                    'type' => 'array',
                    'items' => array(
                        'type' => 'object',
                        'properties' => $properties
                    )
                )
            )
        );
    }

    public static function add_object_display($objectname, $properties)
    {
        $path = '/objects/' . $objectname . '/{id}';
        self::$paths[$path] = array(
            'get' => array(
                'parameters' => array(
                    array('$ref' => '#/components/parameters/itemid')
                ),
                'tags' => array($objectname),
                'operationId' => 'display_' . $objectname,
                'description' => 'Display single ' . $objectname,
                'responses' => array(
                    '200' => array(
                        '$ref' => '#/components/responses/display-' . $objectname
                    )
                )
            )
        );
        if (in_array($objectname, self::$internal)) {
            self::$paths[$path]['get']['responses']['401'] = array(
                '$ref' => '#/components/responses/unauthorized'
            );
            self::$paths[$path]['get']['security'] = array(
                array('cookieAuth' => array())
            );
        }
        self::$responses['display-' . $objectname] = array(
            'description' => 'Display single ' . $objectname . ' object',
            'content' => array(
                'application/json' => array(
                    'schema' => array(
                        '$ref' => '#/components/schemas/display-' . $objectname
                    )
                )
            )
        );
        self::$schemas['display-' . $objectname] = array(
            'type' => 'object',
            'properties' => $properties
        );
    }

    public static function add_object_create($objectname, $properties)
    {
        $path = '/objects/' . $objectname;
        self::$paths[$path]['post'] = array(
            'requestBody' => array(
                '$ref' => '#/components/requestBodies/create-' . $objectname
            ),
            'tags' => array($objectname),
            'operationId' => 'create_' . $objectname,
            'description' => 'Create ' . $objectname,
            'responses' => array(
                '200' => array(
                    '$ref' => '#/components/responses/itemid'
                ),
                '401' => array(
                    '$ref' => '#/components/responses/unauthorized'
                )
            ),
            'security' => array(
                array('cookieAuth' => array())
            )
        );
        self::$requestBodies['create-' . $objectname] = array(
            'description' => 'Create ' . $objectname . ' object',
            'content' => array(
                'application/json' => array(
                    'schema' => array(
                        '$ref' => '#/components/schemas/create-' . $objectname
                    )
                )
            )
        );
        self::$schemas['create-' . $objectname] = array(
            'type' => 'object',
            'properties' => $properties
        );
    }

    public static function add_object_update($objectname, $properties)
    {
        $path = '/objects/' . $objectname . '/{id}';
        self::$paths[$path]['put'] = array(
            'parameters' => array(
                array('$ref' => '#/components/parameters/itemid')
            ),
            'requestBody' => array(
                '$ref' => '#/components/requestBodies/update-' . $objectname
            ),
            'tags' => array($objectname),
            'operationId' => 'update_' . $objectname,
            'description' => 'Update ' . $objectname,
            'responses' => array(
                '200' => array(
                    '$ref' => '#/components/responses/itemid'
                ),
                '401' => array(
                    '$ref' => '#/components/responses/unauthorized'
                )
            ),
            'security' => array(
                array('cookieAuth' => array())
            )
        );
        self::$requestBodies['update-' . $objectname] = array(
            'description' => 'Update ' . $objectname . ' object',
            'content' => array(
                'application/json' => array(
                    'schema' => array(
                        '$ref' => '#/components/schemas/update-' . $objectname
                    )
                )
            )
        );
        self::$schemas['update-' . $objectname] = array(
            'type' => 'object',
            'properties' => $properties
        );
    }

    public static function add_object_delete($objectname, $properties)
    {
        $path = '/objects/' . $objectname . '/{id}';
        self::$paths[$path]['delete'] = array(
            'parameters' => array(
                array('$ref' => '#/components/parameters/itemid')
            ),
            'tags' => array($objectname),
            'operationId' => 'delete_' . $objectname,
            'description' => 'Delete ' . $objectname,
            'responses' => array(
                '200' => array(
                    '$ref' => '#/components/responses/itemid'
                ),
                '401' => array(
                    '$ref' => '#/components/responses/unauthorized'
                )
            ),
            'security' => array(
                array('cookieAuth' => array())
            )
        );
    }

    public static function add_whoami()
    {
        $path = '/whoami';
        self::$paths[$path] = array(
            'get' => array(
                'tags' => array('start'),
                'operationId' => 'whoami',
                'description' => 'Display current user',
                'responses' => array(
                    '200' => array(
                        '$ref' => '#/components/responses/whoami'
                    ),
                    '401' => array(
                        '$ref' => '#/components/responses/unauthorized'
                    )
                ),
                'security' => array(
                    array('cookieAuth' => array())
                )
            )
        );
        self::$responses['whoami'] = array(
            'description' => 'Display current user',
            'content' => array(
                'application/json' => array(
                    'schema' => array(
                        '$ref' => '#/components/schemas/whoami'
                    )
                )
            )
        );
        self::$schemas['whoami'] = array(
            'type' => 'object',
            'properties' => array(
                'id' => array(
                    'type' => 'integer'
                ),
                'name' => array(
                    'type' => 'string'
                )
            )
        );
    }

    public static function match_proptype($property)
    {
        // @todo improve matching types
        $typename = self::$proptype_names[$property->type];
        //$typename = $property->basetype;
        switch ($typename) {
            case 'integerbox':
            case 'itemid':
            case 'itemtype':
            //case 'integer':
                $datatype = array('type' => 'integer');
                break;
            case 'textbox':
            case 'textarea':
            case 'objectref':
            case 'propertyref':
            case 'object':
            case 'module':
            case 'categories':
            case 'fieldtype':
            case 'datasource':
            case 'fieldstatus':
            case 'dropdown':
            case 'deferitem':
            //case 'string':
                $datatype = array('type' => 'string');
                break;
            case 'array':
            case 'configuration':
            case 'defermany':
            case 'deferlist':
                $datatype = array('type' => 'array', 'items' => array('type' => 'string'));
                break;
            case 'calendar':
                $datatype = array('type' => 'string', 'format' => 'date-time');
                break;
            case 'url':
            case 'image':
                $datatype = array('type' => 'string', 'format' => 'uri');
                break;
            case 'checkbox':
                $datatype = array('type' => 'boolean');
                break;
            default:
                throw new Exception('Unsupported property type ' . $property->type . '=' . self::$proptype_names[$property->type] . ' (' . $property->basetype . ')');
                break;
        }
        return $datatype;
    }
}
