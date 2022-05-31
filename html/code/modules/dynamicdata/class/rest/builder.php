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
    protected static $config;
    protected static $endpoint = 'rst.php/v1';
    protected static $objects = [];
    protected static $internal = ['objects', 'properties', 'configurations'];
    protected static $proptype_names = [];
    protected static $paths = [];
    protected static $schemas = [];
    protected static $responses = [];
    protected static $parameters = [];
    protected static $requestBodies = [];
    protected static $securitySchemes = [];
    protected static $tags = [];
    protected static $modules = [];
    protected static $storage = 'database';  // database or apcu
    protected static $expires = 12 * 60 * 60;  // 12 hours
    protected static $timer = false;  // use xarTimerTrait
    protected static $cache = false;  // use xarCacheTrait

    public static function init(array $args = [])
    {
        if (isset(self::$openapi)) {
            return;
        }
        self::$openapi = sys::varpath() . '/cache/api/openapi.json';
        self::parse_openapi();
        self::$config = [];
        $configFile = sys::varpath() . '/cache/api/restapi_config.json';
        if (file_exists($configFile)) {
            $contents = file_get_contents($configFile);
            self::$config = json_decode($contents, true);
        }
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

    public static function create_openapi($selectedList = [], $storage = 'database', $expires = 12 * 60 * 60, $timer = false, $cache = false)
    {
        self::$storage = $storage;
        self::$expires = intval($expires);
        self::$timer = !empty($timer) ? true : false;
        self::$cache = !empty($cache) ? true : false;
        self::init_openapi();
        self::add_objects($selectedList);
        self::add_whoami();
        self::add_modules($selectedList);
        self::add_token();
        self::dump_openapi();
    }

    public static function dump_openapi()
    {
        $doc = [];
        $doc['openapi'] = '3.0.2';
        $doc['info'] = [
            'title' => 'DynamicData REST API',
            'description' => 'This provides a REST API endpoint as proof of concept to access Dynamic Data Objects stored in dynamic_data. Access to all objects is limited to read-only mode by default. The Sample object requires cookie authentication (after login on this site) or token authentication to create/update/delete items. Some internal DD objects are also available in read-only mode for use in Javascript on the site.',
            'version' => '1.3.0',
        ];
        $doc['info']['x-generated'] = date('c');
        $doc['servers'] = [
            ['url' => xarServer::getBaseURL() . self::$endpoint],
        ];
        $doc['paths'] = self::$paths;
        $doc['components'] = [
            'schemas' => self::$schemas,
            'responses' => self::$responses,
            'parameters' => self::$parameters,
            'requestBodies' => self::$requestBodies,
            'securitySchemes' => self::$securitySchemes,
        ];
        $doc['tags'] = self::$tags;
        $content = json_encode($doc, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        file_put_contents(self::$openapi, $content);
        $configFile = sys::varpath() . '/cache/api/restapi_config.json';
        $configData = [];
        $configData['generated'] = date('c');
        $configData['caution'] = 'This file is updated when you rebuild the openapi.json document in Dynamic Data - Utilities - Test APIs';
        $configData['start'] = ['objects', 'whoami', 'modules'];
        $configData['objects'] = self::$objects;
        $configData['modules'] = self::$modules;
        $configData['storage'] = self::$storage;
        $configData['expires'] = self::$expires;
        $configData['timer'] = self::$timer;
        $configData['cache'] = self::$cache;
        file_put_contents($configFile, json_encode($configData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    public static function init_openapi()
    {
        self::$paths = [];
        self::$schemas = [];
        self::$responses = [];
        self::$parameters = [];
        self::$requestBodies = [];
        self::$securitySchemes = [];
        self::$tags = [];
        self::add_parameters();
        self::add_responses();
        self::add_securitySchemes();
    }

    public static function add_parameters()
    {
        self::$parameters['itemid'] = [
            'name' => 'id',
            'in' => 'path',
            'schema' => [
                'type' => 'integer',
            ],
            'description' => 'itemid value',
            'required' => true,
        ];
        self::$parameters['limit'] = [
            'name' => 'limit',
            'in' => 'query',
            'schema' => [
                'type' => 'integer',
                'default' => 100,
            ],
            'description' => 'Number of items to return',
        ];
        self::$parameters['offset'] = [
            'name' => 'offset',
            'in' => 'query',
            'schema' => [
                'type' => 'integer',
                'default' => 0,
            ],
            'description' => 'Offset to start items from',
        ];
        // style = form + explode = false
        // Value: order = array('module_id', '-name')
        // Query: order=module_id,-name
        self::$parameters['order'] = [
            'name' => 'order',
            'in' => 'query',
            'schema' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
            ],
            'style' => 'form',
            'explode' => false,
            'description' => 'Property to sort on and optional -direction (comma separated)',
        ];
        // style = form + explode = true
        // Value: filter = array('datastore,eq,dynamicdata', 'class,eq,DataObject')
        // Query: filter[]=datastore,eq,dynamicdata&filter[]=class,eq,DataObject (+ url-encode [],)
        self::$parameters['filter'] = [
            'name' => 'filter[]',
            'in' => 'query',
            'schema' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
            ],
            'style' => 'form',
            'explode' => true,
            'description' => 'Filters to be applied. Each filter consists of a property, an operator and a value (comma separated)',
        ];
        // style = form + explode = false
        // Value: expand = array('sources', 'config')
        // Query: expand=sources,config
        self::$parameters['expand'] = [
            'name' => 'expand',
            'in' => 'query',
            'schema' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
            ],
            'style' => 'form',
            'explode' => false,
            'description' => 'Properties to expand in items (comma separated)',
        ];
    }

    public static function add_responses()
    {
        self::$responses['itemid'] = [
            'description' => 'Return itemid value',
            'content' => [
                'application/json' => [
                    'schema' => [
                        'type' => 'integer',
                    ],
                ],
            ],
        ];
        self::$responses['unauthorized'] = [
            'description' => 'Authorization information is missing or invalid',
            'headers' => [
                'WWW-Authenticate' => [
                    'schema' => [
                        'type' => 'string',
                    ],
                ],
            ],
        ];
        self::$responses['forbidden'] = [
            'description' => 'Access to the requested resource is forbidden',
        ];
    }

    public static function add_securitySchemes()
    {
        self::$securitySchemes['cookieAuth'] = [
            'type' => 'apiKey',
            'description' => 'Use Xaraya Session Cookie (after login on the site)',
            'name' => 'XARAYASID',
            'in' => 'cookie',
        ];
        // @checkme still issues getting the Authorization header passed to PHP with Apache
        // See https://stackoverflow.com/questions/40582161/how-to-properly-use-bearer-tokens
        //self::$securitySchemes['bearerAuth'] = [
        //    'type' => 'http',
        //    'scheme' => 'bearer',
        //    'bearerFormat' => 'Not JWT',
        //    'description' => 'Use API access token after sign-in on /token',
        //];
        self::$securitySchemes['headerAuth'] = [
            'type' => 'apiKey',
            'description' => 'Use API access token after sign-in on /token',
            'name' => 'X-AUTH-TOKEN',
            'in' => 'header',
        ];
    }

    public static function add_operation_security($path, $method = 'get', $add_auth = true)
    {
        self::$paths[$path][$method]['responses']['401'] = [
            '$ref' => '#/components/responses/unauthorized',
        ];
        if (!$add_auth) {
            return;
        }
        self::$paths[$path][$method]['responses']['403'] = [
            '$ref' => '#/components/responses/forbidden',
        ];
        self::$paths[$path][$method]['security'] = [
            ['cookieAuth' => []],
            //['bearerAuth' => []],
            ['headerAuth' => []],
        ];
    }

    public static function add_operation_requestBody($path, $method, $schema, $properties, $mediaType = 'application/json')
    {
        self::$paths[$path][$method]['requestBody'] = [
            '$ref' => '#/components/requestBodies/' . $schema,
        ];
        $description = ucfirst(str_replace('-', ' ', $schema));
        self::$requestBodies[$schema] = [
            'description' => $description,
            'content' => [
                $mediaType => [
                    'schema' => [
                        '$ref' => '#/components/schemas/' . $schema . '-body',
                    ],
                ],
            ],
        ];
        self::$schemas[$schema . '-body'] = [
            'type' => 'object',
            'properties' => $properties,
        ];
    }

    public static function add_operation_response($path, $method, $schema, $properties = [], $code = '200', $mediaType = 'application/json')
    {
        self::$paths[$path][$method]['responses'] = [
            $code => [
                '$ref' => '#/components/responses/' . $schema,
            ],
        ];
        if (array_key_exists($schema, self::$responses)) {
            return;
        }
        $description = ucfirst(str_replace('-', ' ', $schema));
        self::$responses[$schema] = [
            'description' => $description,
            'content' => [
                $mediaType => [
                    'schema' => [
                        '$ref' => '#/components/schemas/' . $schema,
                    ],
                ],
            ],
        ];
        if (array_key_exists($schema, self::$schemas)) {
            return;
        }
        if (empty($properties)) {
            // @checkme default to string or object?
            self::$schemas[$schema] = [
                'type' => 'string',
            ];
        } elseif (!empty($properties['type']) && is_string($properties['type'])) {
            // we have a complete schema definition here
            self::$schemas[$schema] = $properties;
        } else {
            // we have json object properties here
            self::$schemas[$schema] = [
                'type' => 'object',
                'properties' => $properties,
            ];
        }
    }

    public static function get_page_properties($itemproperties)
    {
        $pageproperties = [
            'count' => [
                'type' => 'integer',
            ],
            'limit' => [
                'type' => 'integer',
            ],
            'offset' => [
                'type' => 'integer',
            ],
            'order' => [
                'type' => 'string',
            ],
            'filter' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
            ],
            'expand' => [
                'type' => 'string',
            ],
            'items' => [
                'type' => 'array',
                'items' => [
                    'type' => 'object',
                    'properties' => $itemproperties,
                ],
            ],
        ];
        return $pageproperties;
    }

    public static function get_list_properties($itemproperties)
    {
        $listproperties = [
            'count' => [
                'type' => 'integer',
            ],
            'items' => [
                'type' => 'array',
                'items' => [
                    'type' => 'object',
                    'properties' => $itemproperties,
                ],
            ],
        ];
        return $listproperties;
    }

    public static function get_potential_objects($selectedList = [])
    {
        $objectname = 'objects';
        $fieldlist = ['objectid', 'name', 'label', 'module_id', 'itemtype', 'datastore'];
        $params = ['name' => $objectname, 'fieldlist' => $fieldlist];
        $objectlist = DataObjectMaster::getObjectList($params);
        $items = $objectlist->getItems();
        foreach (array_keys($items) as $itemid) {
            $item = $items[$itemid];
            if ($item['datastore'] !== 'dynamicdata' && !in_array($item['name'], self::$internal) && (empty($selectedList) || !in_array($item['name'], $selectedList))) {
                unset($items[$itemid]);
            }
        }
        return $items;
    }

    public static function add_objects($selectedList = [])
    {
        self::get_proptype_names();
        self::$objects = [];
        $objectname = 'start';
        $fieldlist = ['objectid', 'name', 'label', 'module_id', 'itemtype', 'datastore'];
        $prop_view = [];
        foreach ($fieldlist as $field) {
            $prop_view[$field] = ['type' => 'string'];
        }
        $fieldlist = ['id', 'name', 'label', 'type', 'status', 'seq', 'basetype'];
        $properties = [];
        foreach ($fieldlist as $field) {
            $properties[$field] = ['type' => 'string'];
        }
        $prop_view['properties'] = [
            'type' => 'array',
            'items' => [
                'type' => 'object',
                'properties' => $properties,
            ],
        ];
        self::add_object_view($objectname, $prop_view, '/objects');
        self::$tags[] = ['name' => $objectname, 'description' => $objectname . ' operations'];
        $items = self::get_potential_objects($selectedList);
        foreach ($items as $itemid => $item) {
            if (!empty($selectedList)) {
                if (!in_array($item['name'], $selectedList)) {
                    continue;
                }
            } elseif ($item['datastore'] !== 'dynamicdata' && !in_array($item['name'], self::$internal)) {
                continue;
            }
            self::$objects[$item['name']] = $item;
            self::$objects[$item['name']]['x-operations'] = [];
            self::$objects[$item['name']]['properties'] = self::get_object_properties($item['name']);
        }
        return self::$objects;
    }

    public static function get_proptype_names()
    {
        if (empty(self::$proptype_names)) {
            self::$proptype_names = [];
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
            self::add_modules();
            self::add_token();
        }
        return self::$objects;
    }

    public static function get_object_properties($objectname)
    {
        $properties = [];
        $params = ['name' => $objectname];
        $objectref = DataObjectMaster::getObject($params);
        $prop_display = [];
        $prop_view = [];
        $prop_create = [];
        // @todo add fields based on object descriptor?
        $fieldlist = ['id', 'name', 'label', 'type', 'status', 'seq', 'basetype'];
        foreach ($objectref->getProperties() as $key => $property) {
            //if (array_key_exists($property->type, self::$proptype_names)) {
            //    $properties[$property->name] = self::$proptype_names[$property->type] . ' (' . $property->basetype . ')';
            //} else {
            //    $properties[$property->name] = $property->basetype;
            //}
            $propinfo = [];
            foreach ($property->getPublicProperties() as $name => $value) {
                if (!in_array($name, $fieldlist)) {
                    continue;
                }
                if (is_object($value)) {
                    $propinfo[$name] = get_class($value);
                } else {
                    $propinfo[$name] = $value;
                }
            }
            $propinfo["type"] = self::$proptype_names[$property->type];
            // @todo improve matching types
            $datatype = self::match_proptype($property);
            switch ($property->getDisplayStatus()) {
                case DataPropertyMaster::DD_DISPLAYSTATE_DISABLED:
                    //$prop_create[$property->name] = $datatype;
                    $propinfo["status"] = "disabled";
                    break;
                case DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE:
                    $prop_display[$property->name] = $datatype;
                    $prop_view[$property->name] = $datatype;
                    $prop_create[$property->name] = $datatype;
                    $propinfo["status"] = "active";
                    break;
                case DataPropertyMaster::DD_DISPLAYSTATE_DISPLAYONLY:
                    $prop_display[$property->name] = $datatype;
                    $prop_create[$property->name] = $datatype;
                    $propinfo["status"] = "displayonly";
                    break;
                case DataPropertyMaster::DD_DISPLAYSTATE_HIDDEN:
                    //$prop_create[$property->name] = $datatype;
                    $propinfo["status"] = "hidden";
                    break;
                case DataPropertyMaster::DD_DISPLAYSTATE_VIEWONLY:
                    $prop_view[$property->name] = $datatype;
                    $prop_create[$property->name] = $datatype;
                    $propinfo["status"] = "viewonly";
                    break;
                default:
                    throw new Exception('Unsupported display status ' . $property->getDisplayStatus());
                    break;
            }
            $properties[] = $propinfo;
        }
        self::add_object_view($objectname, $prop_view);
        self::add_object_display($objectname, $prop_display);
        if ($objectname == 'sample') {
            self::add_object_create($objectname, $prop_create);
            self::add_object_update($objectname, $prop_create);
            self::add_object_delete($objectname, $prop_create);
            //self::add_object_patch($objectname, $prop_create);
        }
        self::$tags[] = ['name' => $objectname, 'description' => $objectname . ' operations'];
        return $properties;
    }

    public static function add_object_view($objectname, $properties, $path = '')
    {
        if (empty($path)) {
            $path = '/objects/' . $objectname;
        }
        $method = 'get';
        $schema = 'view-' . $objectname;
        $operationId = str_replace('-', '_', $schema);
        $description = ucfirst(str_replace('-', ' ', $schema));
        self::$paths[$path] = [
            $method => [
                'parameters' => [
                    ['$ref' => '#/components/parameters/limit'],
                    ['$ref' => '#/components/parameters/offset'],
                    ['$ref' => '#/components/parameters/order'],
                    ['$ref' => '#/components/parameters/filter'],
                    ['$ref' => '#/components/parameters/expand'],
                ],
                'tags' => [$objectname],
                'operationId' => $operationId,
                'description' => $description,
            ],
        ];
        $pageproperties = self::get_page_properties($properties);
        self::add_operation_response($path, $method, $schema, $pageproperties);
        if (in_array($objectname, self::$internal)) {
            self::add_operation_security($path, $method);
            $do_security = true;
            $do_cache = false;
        } else {
            $do_security = false;
            $do_cache = true;
        }
        if ($objectname !== 'start') {
            self::$objects[$objectname]['x-operations']['view'] = [
                'properties' => array_keys($properties),
                'security' => $do_security,
                'caching' => $do_cache,
                'parameters' => ['object', 'limit', 'offset', 'order', 'filter', 'expand'],
                'timeout' => 7200,
            ];
        }
    }

    public static function add_object_display($objectname, $properties)
    {
        $path = '/objects/' . $objectname . '/{id}';
        $method = 'get';
        $schema = 'display-' . $objectname;
        $operationId = str_replace('-', '_', $schema);
        $description = ucfirst(str_replace('-', ' ', $schema));
        self::$paths[$path] = [
            $method => [
                'parameters' => [
                    ['$ref' => '#/components/parameters/itemid'],
                    ['$ref' => '#/components/parameters/expand'],
                ],
                'tags' => [$objectname],
                'operationId' => $operationId,
                'description' => $description,
            ],
        ];
        self::add_operation_response($path, $method, $schema, $properties);
        if (in_array($objectname, self::$internal)) {
            self::add_operation_security($path, $method);
            $do_security = true;
            $do_cache = false;
        } else {
            $do_security = false;
            $do_cache = true;
        }
        self::$objects[$objectname]['x-operations']['display'] = [
            'properties' => array_keys($properties),
            'security' => $do_security,
            'caching' => $do_cache,
            'parameters' => ['object', 'itemid', 'expand'],
            'timeout' => 7200,
        ];
    }

    public static function add_object_create($objectname, $properties)
    {
        $path = '/objects/' . $objectname;
        $method = 'post';
        $schema = 'create-' . $objectname;
        $operationId = str_replace('-', '_', $schema);
        $description = ucfirst(str_replace('-', ' ', $schema));
        self::$paths[$path][$method] = [
            'tags' => [$objectname],
            'operationId' => $operationId,
            'description' => $description,
        ];
        // @checkme this returns the itemid
        self::add_operation_response($path, $method, 'itemid');
        self::add_operation_requestBody($path, $method, $schema, $properties);
        self::add_operation_security($path, $method);
        self::$objects[$objectname]['x-operations']['create'] = [
            'properties' => array_keys($properties),
            'security' => true,
        ];
    }

    public static function add_object_update($objectname, $properties)
    {
        $path = '/objects/' . $objectname . '/{id}';
        $method = 'put';
        $schema = 'update-' . $objectname;
        $operationId = str_replace('-', '_', $schema);
        $description = ucfirst(str_replace('-', ' ', $schema));
        self::$paths[$path][$method] = [
            'parameters' => [
                ['$ref' => '#/components/parameters/itemid'],
            ],
            'tags' => [$objectname],
            'operationId' => $operationId,
            'description' => $description,
        ];
        self::add_operation_response($path, $method, 'itemid');
        self::add_operation_requestBody($path, $method, $schema, $properties);
        self::add_operation_security($path, $method);
        self::$objects[$objectname]['x-operations']['update'] = [
            'properties' => array_keys($properties),
            'security' => true,
        ];
    }

    public static function add_object_delete($objectname, $properties)
    {
        $path = '/objects/' . $objectname . '/{id}';
        $method = 'delete';
        $schema = 'delete-' . $objectname;
        $operationId = str_replace('-', '_', $schema);
        $description = ucfirst(str_replace('-', ' ', $schema));
        self::$paths[$path][$method] = [
            'parameters' => [
                ['$ref' => '#/components/parameters/itemid'],
            ],
            'tags' => [$objectname],
            'operationId' => $operationId,
            'description' => $description,
        ];
        self::add_operation_response($path, $method, 'itemid');
        self::add_operation_security($path, $method);
        self::$objects[$objectname]['x-operations']['delete'] = [
            'properties' => array_keys($properties),
            'security' => true,
        ];
    }

    public static function add_whoami()
    {
        $path = '/whoami';
        $method = 'get';
        $schema = 'show-whoami';
        $operationId = str_replace('-', '_', $schema);
        $description = 'Display current user based on token or cookie';
        self::$paths[$path] = [
            $method => [
                'tags' => ['start'],
                'operationId' => $operationId,
                'description' => $description,
            ],
        ];
        $properties = [
            'id' => [
                'type' => 'integer',
            ],
            'name' => [
                'type' => 'string',
            ],
        ];
        self::add_operation_response($path, $method, $schema, $properties);
        self::add_operation_security($path, $method);
    }

    public static function add_modules($selectedList = [])
    {
        $path = '/modules';
        $method = 'get';
        $schema = 'list-modules';
        $operationId = str_replace('-', '_', $schema);
        $description = 'Show available REST API calls for modules defined in code/modules/{module}/xarrestapi/getlist.php';
        self::$paths[$path] = [
            $method => [
                'tags' => ['start'],
                'operationId' => $operationId,
                'description' => $description,
            ],
        ];
        $properties = [
            'module' => [
                'type' => 'string',
            ],
            'apilist' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
            ],
        ];
        $listproperties = self::get_list_properties($properties);
        self::add_operation_response($path, $method, $schema, $listproperties);
        if (empty(self::$modules)) {
            self::$modules = self::get_potential_modules($selectedList);
        }
        foreach (self::$modules as $itemid => $item) {
            self::add_module_apilist($item['module'], $item['apilist']);
        }
    }

    public static function add_module_apilist($module, $apilist)
    {
        $path = '/modules/' . $module;
        $method = 'get';
        $schema = $module . '-apilist';
        $operationId = str_replace('-', '_', $schema);
        $description = 'Show REST API calls for module ' . $module . ' defined in code/modules/' . $module . '/xarrestapi/getlist.php';
        self::$paths[$path] = [
            $method => [
                'tags' => [$module . '_module'],
                'operationId' => $operationId,
                'description' => $description,
                'responses' => [
                    '200' => [
                        '$ref' => '#/components/responses/' . $schema,
                    ],
                ],
            ],
        ];
        //self::add_operation_response($path, $method, $schema, $properties);
        self::$responses[$schema] = [
            'description' => $description,
            'content' => [
                'application/json' => [
                    'schema' => [
                        '$ref' => '#/components/schemas/' . $schema,
                    ],
                ],
            ],
        ];
        $properties = [
            'name' => [
                'type' => 'string',
            ],
            'type' => [
                'type' => 'string',
                'default' => 'rest',
            ],
            'path' => [
                'type' => 'string',
            ],
            'method' => [
                'type' => 'string',
                'default' => 'get',
            ],
            'security' => [
                'type' => 'boolean',
                'default' => false,
            ],
            'description' => [
                'type' => 'string',
            ],
            'parameters' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
            ],
            'requestBody' => [
                'type' => 'object',
                'additionalProperties' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'string',
                    ],
                ],
            ],
        ];
        self::$schemas[$schema] = [
            'type' => 'object',
            'properties' => [
                'module' => [
                    'type' => 'string',
                ],
                'apilist' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => $properties,
                    ],
                ],
                'count' => [
                    'type' => 'integer',
                ],
            ],
        ];
        foreach ($apilist as $api => $item) {
            if (isset($item['enabled']) && empty($item['enabled'])) {
                continue;
            }
            self::add_module_api($module, $api, $item);
        }
        self::$tags[] = ['name' => $module . '_module', 'description' => $module . ' module operations'];
    }

    public static function add_module_api($module, $api, $item)
    {
        $path = '/modules/' . $module . '/' . $item['path'];
        $schema = $module . '-' . $api;
        $operationId = str_replace('-', '_', $schema);
        if (empty($item['description'])) {
            $item['description'] = 'Call REST API ' . $api . ' in module ' . $module;
        }
        if (empty(self::$paths[$path])) {
            self::$paths[$path] = [];
        }
        self::$paths[$path][$item['method']] = [
            'tags' => [$module . '_module'],
            'operationId' => $operationId,
            'description' => $item['description'],
            'responses' => [
                '200' => [
                    '$ref' => '#/components/responses/' . $schema,
                ],
            ],
        ];
        if (!empty($item['security'])) {
            self::add_operation_security($path, $item['method']);
        }
        if (!empty($item['parameters'])) {
            $parameters = [];
            foreach ($item['parameters'] as $name) {
                $parameters[] = [
                    'name' => $name,
                    'in' => 'query',
                    'schema' => [
                        'type' => 'string',
                    ],
                    'description' => $name . ' value',
                ];
            }
            self::$paths[$path][$item['method']]['parameters'] = $parameters;
        }
        // @checkme verify/expand how POSTed values are defined - assuming simple json object with string props for now
        if (!empty($item['requestBody'])) {
            foreach ($item['requestBody'] as $mediatype => $vars) {
                $properties = [];
                foreach ($vars as $name) {
                    $properties[$name] = ['type' => 'string'];
                }
                self::add_operation_requestBody($path, $item['method'], $schema, $properties, $mediatype);
            }
        }
        //self::add_operation_response($path, $method, $schema, $properties);
        self::$responses[$schema] = [
            'description' => $item['description'],
            'content' => [
                'application/json' => [
                    'schema' => [
                        '$ref' => '#/components/schemas/' . $schema,
                    ],
                ],
            ],
        ];
        if (empty($item['response'])) {
            $item['response'] = [
                'type' => 'string',
            ];
        }
        self::$schemas[$schema] = $item['response'];
    }

    public static function get_potential_modules($selectedList = [])
    {
        $moduleList = ['dynamicdata'];
        $allowed = [];
        foreach ($selectedList as $item) {
            if (strpos($item, '.') === false) {
                continue;
            }
            [$module, $api] = explode('.', $item);
            if (!in_array($module, $moduleList)) {
                $moduleList[] = $module;
            }
            if (!array_key_exists($module, $allowed)) {
                $allowed[$module] = [];
            }
            $allowed[$module][] = $api;
        }
        xarMod::init();
        $items = [];
        foreach ($moduleList as $module) {
            $items[$module] = [
                'module' => $module,
                'apilist' => [],
            ];
            $apiList = xarMod::apiFunc($module, 'rest', 'getlist');
            foreach ($apiList as $api => $info) {
                if (empty($allowed)) {
                    $info['enabled'] = true;
                } elseif (!empty($allowed[$module]) && in_array($api, $allowed[$module])) {
                    $info['enabled'] = true;
                } else {
                    $info['enabled'] = false;
                }
                $items[$module]['apilist'][$api] = $info;
            }
        }
        return $items;
    }

    public static function add_token()
    {
        $path = '/token';
        $method = 'post';
        $schema = 'access-token';
        $operationId = str_replace('-', '_', $schema);
        $description = 'Get API access token';
        self::$paths[$path] = [
            $method => [
                'tags' => ['start'],
                'operationId' => $operationId,
                'description' => $description,
            ],
        ];
        $properties = [
            'uname' => [
                'type' => 'string',
            ],
            'pass' => [
                'type' => 'string',
                'format' => 'password',
            ],
            'access' => [
                'type' => 'string',
                'default' => 'display',
                'enum' => ['view', 'display', 'update', 'create', 'delete', 'config', 'admin'],
            ],
        ];
        self::add_operation_requestBody($path, $method, $schema, $properties);
        $properties = [
            'access_token' => [
                'type' => 'string',
                'format' => 'byte',
            ],
            'expiration' => [
                'type' => 'string',
                'format' => 'date-time',
            ],
        ];
        self::add_operation_response($path, $method, $schema, $properties);
        self::add_operation_security($path, $method, false);
        $method = 'delete';
        $schema = 'delete-token';
        $operationId = str_replace('-', '_', $schema);
        $description = 'Delete API access token';
        self::$paths[$path][$method] = [
            'tags' => ['start'],
            'operationId' => $operationId,
            'description' => $description,
        ];
        $properties = [
            'type' => 'boolean',
        ];
        self::add_operation_response($path, $method, $schema, $properties);
        self::add_operation_security($path, $method);
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
            case 'userlist':
            case 'username':
            //case 'integer':
                $datatype = ['type' => 'integer'];
                break;
            case 'textbox':
            case 'textarea':
            case 'textarea_medium':
            case 'textarea_large':
            case 'objectref':
            case 'propertyref':
            case 'object':
            case 'module':
            case 'categories':
            case 'fieldtype':
            case 'datasource':
            case 'fieldstatus':
            case 'dropdown':
            case 'crontab':
            //case 'string':
                $datatype = ['type' => 'string'];
                break;
            case 'deferitem':
                $datatype = ['type' => 'object'];
                break;
            case 'array':
            case 'configuration':
                $datatype = ['type' => 'array', 'items' => ['type' => 'string']];
                break;
            case 'defermany':
            case 'deferlist':
                $datatype = ['type' => 'array', 'items' => ['type' => 'object']];
                break;
            case 'datetime':
            case 'calendar':
                $datatype = ['type' => 'string', 'format' => 'date-time'];
                break;
            case 'url':
            case 'image':
                $datatype = ['type' => 'string', 'format' => 'uri'];
                break;
            case 'checkbox':
                $datatype = ['type' => 'boolean'];
                break;
            default:
                throw new Exception('Unsupported property type ' . $property->type . '=' . self::$proptype_names[$property->type] . ' (' . $property->basetype . ')');
                break;
        }
        return $datatype;
    }
}
