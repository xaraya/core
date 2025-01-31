<?php

/**
 * Classes for handling GraphQL queries on Dynamic Data Objects (POC)
 *
 * Note: this assumes you install graphql-php with composer
 * and use composer autoload in the entrypoint, see e.g. gql.php
 *
 * $ composer require webonyx/graphql-php
 * $ head html/gql.php
 * <?php
 * ...
 * require_once dirname(__DIR__).'/vendor/autoload.php';
 * ...
 *
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.6.2
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
**/

/**
 * See xardocs/graphql.txt for class structure
 * @uses \sys::autoload()
 */
class xarGraphQLObjects
{
    /** @var array<string, string> */
    protected static $objectType = [];
    /** @var array<string, mixed> */
    protected static $objectSecurity = [];
    /** @var array<string, mixed> */
    protected static $objectFieldSpecs = [];
    /** @var array<string, mixed> */
    protected static $objectRef = [];

    /**
     * Summary of getTypes
     * @return array<string, string>
     */
    public static function getTypes()
    {
        return self::$objectType;
    }

    /**
     * Summary of getType
     * @param string $objectName
     * @return string|false
     */
    public static function getType($objectName)
    {
        return self::$objectType[$objectName] ?? false;
    }

    /**
     * Summary of setType
     * @param string $objectName
     * @param string $typeName
     * @return void
     */
    public static function setType($objectName, $typeName)
    {
        self::$objectType[$objectName] = $typeName;
    }

    /**
     * Summary of clearTypes
     * @return void
     */
    public static function clearTypes()
    {
        self::$objectType = [];
    }

    /**
     * Summary of hasSecurity
     * @param string $objectName
     * @param ?string $method
     * @return bool
     */
    public static function hasSecurity($objectName, $method = null)
    {
        return !empty(self::$objectSecurity[$objectName]) ? true : false;
    }

    /**
     * Summary of clearSecurity
     * @return void
     */
    public static function clearSecurity()
    {
        self::$objectSecurity = [];
    }

    /**
     * Summary of getFieldSpecs
     * @param string $objectName
     * @return array<mixed>|false
     */
    public static function getFieldSpecs($objectName)
    {
        return self::$objectFieldSpecs[$objectName] ?? false;
    }

    /**
     * Summary of setFieldSpecs
     * @param string $objectName
     * @param array<mixed>|false $fieldSpecs
     * @return void
     */
    public static function setFieldSpecs($objectName, $fieldSpecs)
    {
        self::$objectFieldSpecs[$objectName] = $fieldSpecs;
    }

    /**
     * Summary of clearFieldSpecs
     * @return void
     */
    public static function clearFieldSpecs()
    {
        self::$objectFieldSpecs = [];
    }

    /**
     * Summary of hasObjectRef
     * @param string $objectName
     * @return bool
     */
    public static function hasObjectRef($objectName)
    {
        return !empty(self::$objectRef[$objectName]);
    }

    /**
     * Summary of getObjectRef
     * @param string $objectName
     * @return object|false
     */
    public static function getObjectRef($objectName)
    {
        return self::$objectRef[$objectName] ?? false;
    }

    /**
     * Summary of setObjectRef
     * @param string $objectName
     * @param object $objectRef data object by reference
     * @return void
     */
    public static function setObjectRef($objectName, &$objectRef)
    {
        self::$objectRef[$objectName] = $objectRef;
    }

    /**
     * Summary of mapObjects
     * @return void
     */
    public static function mapObjects()
    {
        if (!empty(self::$objectType)) {
            return;
        }
        foreach (xarGraphQLTypes::getTypeMapper() as $name => $type) {
            $clazz = xarGraphQLTypes::getTypeClass($type);
            if (property_exists($clazz, '_xar_object') && !empty($clazz::$_xar_object)) {
                self::$objectType[$clazz::$_xar_object] = $name;
                if (property_exists($clazz, '_xar_security') && isset($clazz::$_xar_security)) {
                    self::$objectSecurity[$clazz::$_xar_object] = $clazz::$_xar_security;
                }
            }
        }
        foreach (xarGraphQLTypes::getExtraTypes() as $type) {
            [$name, $type, $object] = xarGraphQLInflector::sanitize($type);
            self::$objectType[$object] = $name;
        }
    }

    /**
     * Summary of loadObjects
     * @param array<string, mixed> $objects
     * @return void
     */
    public static function loadObjects($objects = [])
    {
        foreach ($objects as $object => $info) {
            self::$objectType[$object] = $info['name'];
            self::$objectSecurity[$object] = $info['security'];
            self::$objectFieldSpecs[$object] = $info['fieldspecs'] ?? false;
        }
    }

    /**
     * Summary of dumpObjects
     * @return array<string, mixed>
     */
    public static function dumpObjects()
    {
        self::clearTypes();
        self::clearSecurity();
        self::clearFieldSpecs();
        self::mapObjects();
        $typeMapper = xarGraphQLTypes::getTypeMapper();

        $info = [];
        foreach (self::getTypes() as $object => $name) {
            $info[$object] = [];
            $info[$object]['name'] = $name;
            $name = strtolower($name);
            $type = $typeMapper[$name] ?? $name;
            $info[$object]['type'] = $type;
            $info[$object]['security'] = self::hasSecurity($object);
            $info[$object]['class'] = xarGraphQLTypes::getTypeClass($type);
            if (!empty($typeMapper[$name])) {
                $info[$object]['fieldspecs'] = [];
                $objectType = xarGraphQLTypes::loadLazyType($name);
                foreach ($objectType->getFields() as $field) {
                    $info[$object]['fieldspecs'][$field->getName()] = ['fieldtype', $field->getType()->toString()];
                }
                $fieldspecs = xarGraphQLBuildType::find_object_fieldspecs($object, true);
                foreach ($fieldspecs as $prop_name => $fieldspec) {
                    if (array_key_exists($prop_name, $info[$object]['fieldspecs'])) {
                        $info[$object]['fieldspecs'][$prop_name] = array_merge($info[$object]['fieldspecs'][$prop_name], $fieldspec);
                    } else {
                        $info[$object]['fieldspecs'][$prop_name] = $fieldspec;
                    }
                }
            } else {
                $info[$object]['maketype'] = true;
            }
        }

        $fieldspecs = [];
        foreach (xarGraphQLTypes::getExtraTypes() as $type) {
            [$name, $type, $object] = xarGraphQLInflector::sanitize($type);
            $fieldspecs[$object] = xarGraphQLBuildType::find_object_fieldspecs($object, true);
        }
        foreach ($fieldspecs as $object => $fieldspec) {
            $info[$object]['fieldspecs'] = $fieldspec;
        }

        return $info;
    }
}
