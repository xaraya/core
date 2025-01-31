<?php

/**
 * @package core\bridge
 * @subpackage graphql
 * @category Xaraya Web Applications Framework
 * @version 2.6.2
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 */

use GraphQL\Type\Definition\ResolveInfo;

/**
 * For documentation purposes only - available via xarGraphQLDeferredTrait
 */
interface xarGraphQLDeferredInterface
{
    /**
     * Summary of _xar_get_deferred_field
     * @param mixed $fieldname
     * @param mixed $typename
     * @param mixed $islist
     * @return array<string, mixed>
     */
    public static function _xar_get_deferred_field($fieldname, $typename, $islist = false): array;
    /**
     * Get the field resolver for a deferred field - looking up the user names for example
     * @param mixed $typename
     * @param mixed $fieldname
     * @param mixed $object
     * @return callable
     */
    public static function _xar_deferred_field_resolver($typename, $fieldname, $object = null): callable;
    /**
     * Get the property resolver for a deferred field - looking up the user names for example
     * @param mixed $typename
     * @param mixed $fieldname
     * @param mixed $object
     * @return callable
     */
    public static function _xar_deferred_property_resolver($typename, $fieldname, $object): callable;
    /**
     * Add item id to the deferred list of items to be looked up later
     * @param mixed $typename
     * @param mixed $id
     * @param mixed $fieldlist
     * @return void
     */
    public static function _xar_add_deferred($typename, $id, $fieldlist = null): void;
    /**
     * Load values for a deferred field - looking up the user names for example
     * @param mixed $typename
     * @return ?callable
     */
    public static function _xar_load_deferred($typename): ?callable;
    /**
     * Get item from the deferred list of items once they're all loaded
     * @param mixed $typename
     * @param mixed $id
     * @return mixed
     */
    public static function _xar_get_deferred($typename, $id): mixed;
}

/**
 * Trait to handle deferred fields and properties for dataobjects (e.g. username, object, deferitem, ...)
 */
trait xarGraphQLDeferredTrait
{
    /** @var array<string, mixed> */
    protected static $_xar_deferred = [];

    /**
     * Summary of _xar_get_deferred_field
     * @param mixed $fieldname
     * @param mixed $typename
     * @param mixed $islist
     * @return array<string, mixed>
     */
    public static function _xar_get_deferred_field($fieldname, $typename, $islist = false): array
    {
        // xarGraphQL::setTimer('get deferred field ' . $fieldname);
        return [
            'name' => $fieldname,
            'type' => ($islist ? xarGraphQLTypes::getTypeList($typename) : xarGraphQLTypes::getType($typename)),
            // @todo move to resolveField?
            'resolve' => static::_xar_deferred_field_resolver($typename, $fieldname),
        ];
    }

    /**
     * Get the property resolver for a deferred field - looking up the user names for example
     *
     * See Solving N+1 Problem - https://webonyx.github.io/graphql-php/data-fetching/
     * @param mixed $typename
     * @param mixed $fieldname
     * @param mixed $object
     * @throws \Exception
     * @phpstan-type Executor callable(): mixed
     * @return callable
     */
    public static function _xar_deferred_property_resolver($typename, $fieldname, $object): callable
    {
        // @checkme use deferred load resolver for deferitem, deferlist, defermany properties here!?
        $resolver = function ($values, $args, $context, ResolveInfo $info) use ($typename, $fieldname, $object) {
            xarGraphQL::tracePath(array_merge($info->path, ["deferred property $typename $fieldname", $args]));
            // @checkme this will be empty for defermany properties, since we use the id to defer
            // if (empty($values[$fieldname])) {
            //     return;
            // }
            // @checkme are we sure we'll always have this available?
            if (!xarGraphQLObjects::hasObjectRef($object)) {
                // set context if available in resolver
                $objectlist = DataObjectFactory::getObjectList(['name' => $object], $context);
                xarGraphQLObjects::setObjectRef($object, $objectlist);
            }
            /** @var DeferredItemProperty $property */
            $property = (xarGraphQLObjects::getObjectRef($object))->properties[$fieldname];
            if ($property::class === 'DeferredManyProperty') {
                // $fieldname = 'id';
                if (empty($values['id'])) {
                    throw new Exception('Unknown item id for deferred property ' . $fieldname);
                }
            } elseif (empty($values[$fieldname])) {
                return null;
            }
            $fields = $info->getFieldSelection(0);
            if (array_key_exists('id', $fields) && count($fields) < 2) {
                return ['id' => $values[$fieldname]];
            }
            $fieldlist = array_keys($fields);
            if (empty($property->objectname)) {
                // only looking for id's here
            } elseif (!empty(xarGraphQLObjects::getType($property->objectname))) {
                $objtype = strtolower(xarGraphQLObjects::getType($property->objectname));
                if (xarGraphQL::hasQueryFields($objtype)) {
                    $fieldlist = xarGraphQL::getQueryFields($objtype);
                }
            } else {
                throw new Exception('Unknown object ' . $property->objectname);
            }
            xarGraphQL::tracePath(["add deferred $typename $fieldname " . $values['id'], ($values[$fieldname] ?? null), implode(',', $fieldlist)]);
            $loader = $property->getDeferredLoader();
            // set context if available in resolver
            $loader->setContext($context);
            // @checkme limit the # of children per itemid when we use data loader?
            // @checkme preserve fieldlist to optimize loading afterwards too
            if ($loader->checkFieldlist && !empty($fieldlist)) {
                $loader->mergeFieldlist($fieldlist);
                $loader->parseQueryArgs($args);
            }
            // @todo  how to avoid setting this twice for lists?
            $value = $property->setDataToDefer($values['id'], $values[$fieldname] ?? null);

            return new GraphQL\Deferred(function () use ($typename, $values, $fieldname, $property) {
                xarGraphQL::tracePath(["get deferred $typename $fieldname " . $values['id'], ($values[$fieldname] ?? null)]);
                $data = $property->getDeferredData(['value' => ($values[$fieldname] ?? null), '_itemid' => $values['id']]);
                //print_r($data['value']);
                // @checkme convert deferred data into assoc array or list of assoc array
                //if (property_exists($property, 'linkname')) {
                //    return array('count' => 0, 'filter' => array("$typename,eq,".$values['id']), $property->objectname => $data['value']);
                //}
                //xarGraphQL::tracePath(array_merge(["return deferred $typename $fieldname " . $values['id'], ($values[$fieldname] ?? null), $data['value']]));
                return $data['value'];
            });
        };
        return $resolver;
    }

    /**
     * Get the field resolver for a deferred field - looking up the user names for example
     *
     * See Solving N+1 Problem - https://webonyx.github.io/graphql-php/data-fetching/
     * @param mixed $typename
     * @param mixed $fieldname
     * @param mixed $object
     * @phpstan-type Executor callable(): mixed
     * @return callable
     */
    public static function _xar_deferred_field_resolver($typename, $fieldname, $object = null): callable
    {
        // @checkme use deferred load resolver for deferitem, deferlist, defermany properties here!?
        if (!empty($object)) {
            return static::_xar_deferred_property_resolver($typename, $fieldname, $object);
        }
        $object ??= xarGraphQLInflector::pluralize($typename);
        if (!array_key_exists($typename, static::$_xar_deferred)) {
            static::$_xar_deferred[$typename] = new DataObjectLoader($object, ['id']);
            // support equivalent of overridden _xar_load_deferred in inheritance (e.g. usertype)
            $getValuesFunc = static::_xar_load_deferred($typename);
            if (!empty($getValuesFunc)) {
                static::$_xar_deferred[$typename]->setResolver($getValuesFunc);
            }
        }
        $resolver = function ($values, $args, $context, ResolveInfo $info) use ($typename, $fieldname) {
            xarGraphQL::tracePath(array_merge($info->path, ["deferred field $typename $fieldname", $args]));
            if (empty($values[$fieldname])) {
                return;
            }
            $fields = $info->getFieldSelection(0);
            if (array_key_exists('id', $fields) && count($fields) < 2) {
                return ['id' => $values[$fieldname]];
            }
            if (xarGraphQL::hasQueryFields($typename)) {
                $fieldlist = xarGraphQL::getQueryFields($typename);
            } else {
                $fieldlist = array_keys($fields);
            }
            //if (!in_array('name', $queryPlan->subFields('User'))) {
            //    return array('id' => $values[$fieldname]);
            //}
            $loader = static::$_xar_deferred[$typename];
            // set context if available in resolver
            $loader->setContext($context);
            // @checkme limit the # of children per itemid when we use data loader?
            // @checkme preserve fieldlist to optimize loading afterwards too
            if ($loader->checkFieldlist && !empty($fieldlist)) {
                $loader->mergeFieldlist($fieldlist);
                $loader->parseQueryArgs($args);
            }
            xarGraphQL::tracePath(["add deferred $typename $fieldname " . ($values['id'] ?? null), ($values[$fieldname] ?? null), implode(',', $fieldlist)]);
            static::_xar_add_deferred($typename, $values[$fieldname], $fieldlist);

            return new GraphQL\Deferred(function () use ($typename, $values, $fieldname) {
                xarGraphQL::tracePath(["get deferred $typename $fieldname " . ($values['id'] ?? null), ($values[$fieldname] ?? null)]);
                return static::_xar_get_deferred($typename, $values[$fieldname]);
            });
        };
        return $resolver;
    }

    /**
     * Add item id to the deferred list of items to be looked up later
     * @param mixed $typename
     * @param mixed $id
     * @param mixed $fieldlist
     * @return void
     */
    public static function _xar_add_deferred($typename, $id, $fieldlist = null): void
    {
        static::$_xar_deferred[$typename]->add($id);
    }

    /**
     * Load values for a deferred field - looking up the user names for example
     *
     * This method *should* be overridden for each specific object type - unless we rely on the DataObjectLoader
     *
     * See Solving N+1 Problem - https://webonyx.github.io/graphql-php/data-fetching/
     * @param mixed $typename
     * @return ?callable
     */
    public static function _xar_load_deferred($typename): ?callable
    {
        // support equivalent of overridden _xar_load_deferred in inheritance (e.g. usertype)
        // Note: by default we rely on the DataObjectLoader for fields or the DeferredLoader for properties here
        //$object = static::$_xar_object;
        //$fieldlist = ['id', 'name'];
        // get the DD items for a deferred list of item ids here
        //$resolver = function ($itemids) use ($object, $fieldlist) {
        //    $params = ['name' => $object, 'fieldlist' => $fieldlist];
        //    $objectlist = DataObjectFactory::getObjectList($params);
        //    $params = ['itemids' => $itemids];
        //    return $objectlist->getItems($params);
        //};
        //return $resolver;
        return null;
    }

    /**
     * Get item from the deferred list of items once they're all loaded
     * @param mixed $typename
     * @param mixed $id
     * @return mixed
     */
    public static function _xar_get_deferred($typename, $id): mixed
    {
        // support equivalent of overridden _xar_load_deferred in inheritance (e.g. usertype)
        return static::$_xar_deferred[$typename]->get($id);
    }
}
