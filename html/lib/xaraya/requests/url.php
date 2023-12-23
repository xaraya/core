<?php
/**
 * @package core\requests
 * @category Xaraya Web Applications Framework
 * @version 2.4.2
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
**/

namespace Xaraya\Requests;

use xarObject;

/**
 * Convenience classes
 *
**/
class RequestURL extends xarObject
{
    /**
     * Encode parts of a URL.
     * This will encode the path parts, the and GET parameter names
     * and data. It cannot encode a complete URL yet.
     *
     * @param string $data the data to be encoded (see todo)
     * @param string $type the type of string to be encoded ('getname', 'getvalue', 'path', 'url', 'domain')
     * @return string the encoded URL parts
     * @todo support arrays and encode the complete array (keys and values)
    **/
    public static function encode($data, $type = 'getname')
    {
        // Different parts of a URL are encoded in different ways.
        // e.g. a '?' and '/' are allowed in GET parameters, but
        // '?' must be encoded when in a path, and '/' is not
        // allowed in a path at all except as the path-part
        // separators.
        // The aim is to encode as little as possible, so that URLs
        // remain as human-readable as we can allow.

        static $decode = [
            'path' => [
                ['%2C', '%24', '%21', '%2A', '%28', '%29', '%3D'],
                [',', '$', '!', '*', '(', ')', '='],
            ],
            'getname' => [
                ['%2C', '%24', '%21', '%2A', '%28', '%29', '%3D', '%27', '%5B', '%5D'],
                [',', '$', '!', '*', '(', ')', '=', '\'', '[', ']'],
            ],
            'getvalue' => [
                ['%2C', '%24', '%21', '%2A', '%28', '%29', '%3D', '%27', '%5B', '%5D', '%3A', '%2F', '%3F', '%3D'],
                [',', '$', '!', '*', '(', ')', '=', '\'', '[', ']', ':', '/', '?', '='],
            ],
        ];

        // We will encode everything first, then restore a select few
        // characters.
        // TODO: tackle it the other way around, i.e. have rules for
        // what to encode, rather than undoing some ecoded characters.
        $data = rawurlencode($data);

        // TODO: check what automatic ML settings have on this.
        // I suspect none, as all multi-byte characters have ASCII values
        // of their parts > 127.
        if (isset($decode[$type])) {
            $data = str_replace($decode[$type][0], $decode[$type][1], $data);
        }
        return $data;
    }

    /**
     * Format GET parameters formed by nested arrays, to support xarController::URL().
     * This function will recurse for each level to the arrays.
     *
     * @param array<string, mixed> $args the array to be expanded as a GET parameter
     * @param string $prefix the prefix for the GET parameter
     * @return string the expanded GET parameter(s)
     **/
    public static function nested($args, $prefix)
    {
        $path = '';
        foreach ($args as $key => $arg) {
            if (is_array($arg)) {
                $path .= self::nested($arg, $prefix . '[' . self::encode($key, 'getname') . ']');
            } else {
                $path .= $prefix . '[' . self::encode($key, 'getname') . ']' . '=' . self::encode($arg, 'getvalue');
            }
        }
        return $path;
    }

    /**
     * Add further parameters to the path, ensuring each value is encoded correctly.
     *
     * @param array<string, mixed> $args the array to be encoded
     * @param string $path the current path to append parameters to
     * @param string $pini the initial path seperator to use
     * @param string $psep the path seperator to use
     * @return string the path with encoded parameters
     */
    public static function addParametersToPath($args, $path, $pini, $psep)
    {
        if (count($args) > 0) {
            $params = '';

            foreach ($args as $k => $v) {
                if (is_array($v)) {
                    // Recursively walk the array tree to as many levels as necessary
                    // e.g. ...&foo[bar][dee][doo]=value&...
                    $params .= self::nested($v, $psep . $k);
                } elseif (isset($v)) {
                    // TODO: rather than rawurlencode, use a xar function to encode
                    $params .= (!empty($params) ? $psep : '') . self::encode($k, 'getname') . '=' . self::encode($v, 'getvalue');
                }
            }

            // Join to the path with the appropriate character,
            // depending on whether there are already GET parameters.
            $path .= (strpos($path, $pini) === false ? $pini : $psep) . $params;
        }

        return $path;
    }
}
