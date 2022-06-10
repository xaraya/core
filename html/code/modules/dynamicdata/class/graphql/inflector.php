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

/**
 * Class to handle singular/plural conversion of names
 */
class xarGraphQLInflector
{
    /**
     * Sanitize name, type, object, list and item based on given name, e.g.:
     * name=Object, type=object, object=objects, list=objects, item=object
     * name=Property, type=property, object=properties, list=properties, item=property
     */
    public static function sanitize($name, $type = null, $object = null)
    {
        // Object -> object / Property -> property
        if (!isset($type)) {
            $type = self::normalize($name);
        }
        // object -> objects / property -> properties
        if (!isset($object)) {
            // Basic pluralize for most common case(s)
            $object = self::pluralize($type);
        }
        // objects -> objects / properties -> properties
        //if (!isset($list)) {
        //    $list = $object;
        //}
        //object -> object / property-> property
        //if (!isset($item)) {
        //    $item = $type;
        //}
        // samples_page -> Samples_Page / sample_input -> Sample_Input
        if ($name === $type) {
            $name = ucwords($name, '_');
        }
        return [$name, $type, $object];
    }

    /**
     * Basic normalize for most common case(s):
     * samples_page -> samples / sample_input -> sample
     */
    public static function normalize($value)
    {
        $value = strtolower($value);
        $extensions = ['_page', '_input'];
        foreach ($extensions as $ext) {
            if (substr($value, -strlen($ext)) === $ext) {
                $value = substr($value, 0, strlen($value) - strlen($ext));
            }
        }
        return $value;
    }

    /**
     * Basic pluralize for most common case(s): typename -> objectname or listname
     * object -> objects / property -> properties
     */
    public static function pluralize($type)
    {
        $type = self::normalize($type);
        if (substr($type, -1) === "y") {
            $object = substr($type, 0, strlen($type) - 1) . "ies";
        } elseif ($type === "user") {
            $object = "roles_users";
        } elseif (!in_array($type, ["sample", "api_people", "api_species", "deferchildren", "deferparentchild", "cdcollection"])) {
            $object = $type . "s";
        } else {
            $object = $type;
        }
        return $object;
    }

    /**
     * Basic singularize for most common case(s): objectname or listname -> typename
     * objects -> object / properties -> property
     */
    public static function singularize($name)
    {
        $name = self::normalize($name);
        if ($name === "api_species") {
            $type = $name;
        } elseif ($name === "roles_users") {
            $type = "user";
        } elseif (substr($name, -3) === "ies") {
            $type = substr($name, 0, strlen($name) - 3) . "y";
        } elseif (substr($name, -1) === "s") {
            $type = substr($name, 0, strlen($name) - 1);
        } else {
            $type = $name;
        }
        return $type;
    }
}
