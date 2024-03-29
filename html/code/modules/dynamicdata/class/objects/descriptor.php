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

sys::import('xaraya.structures.descriptor');

/*
 * generate the variables necessary to instantiate a DataObject or DataProperty class
*/
class DataObjectDescriptor extends ObjectDescriptor
{
    public function __construct(array $args = [])
    {
        $args = self::getObjectID($args);
        parent::__construct($args);
    }

    public static function getModID(array $args = [])
    {
        foreach ($args as $key => &$value) {
            if (in_array($key, ['module','modid','module','moduleid'])) {
                if (empty($value)) {
                    $value = xarMod::getRegID(xarMod::getName());
                }
                if (is_numeric($value) || is_integer($value)) {
                    $args['moduleid'] = $value;
                } else {
                    //$info = xarMod::getInfo(xarMod::getRegID($value));
                    $args['moduleid'] = xarMod::getRegID($value);
                }
                break;
            }
        }
        // Still not found?
        if (!isset($args['moduleid'])) {
            if (isset($args['fallbackmodule']) && ($args['fallbackmodule'] == 'current')) {
                $args['fallbackmodule'] = xarMod::getName();
            } else {
                $args['fallbackmodule'] = 'dynamicdata';
            }
            //$info = xarMod::getInfo(xarMod::getRegID($args['fallbackmodule']));
            $args['moduleid'] = xarMod::getRegID($args['fallbackmodule']);
        }
        if (!isset($args['itemtype'])) {
            $args['itemtype'] = 0;
        }
        return $args;
    }

    /**
     * Get Object ID
     *
     * @return array<mixed> all parts necessary to describe a DataObject
     */
    public static function getObjectID(array $args = [])
    {
        // @todo remove overlap with DataObjectFactory::*getObjectInfo()
        $row = static::findObject($args);

        if (empty($row) || count($row) < 1) {
            $args['moduleid'] = isset($args['moduleid']) ? (int) $args['moduleid'] : null;
            $args['itemtype'] = isset($args['itemtype']) ? (int) $args['itemtype'] : null;
            $args['objectid'] = isset($args['objectid']) ? (int) $args['objectid'] : null;
            $args['name'] ??= null;
        } else {
            $args['moduleid'] = (int) $row['module_id'];
            $args['itemtype'] = (int) $row['itemtype'];
            $args['objectid'] = (int) $row['id'];
            $args['name'] = $row['name'];
        }
        // object property is called module_id now instead of moduleid for whatever reason !?
        $args['module_id'] = $args['moduleid'];
        if (xarCore::isLoaded(xarCore::SYSTEM_TEMPLATES) && empty($args['tplmodule'])) {
            $args['tplmodule'] = xarMod::getName($args['moduleid']);
        }
        if (empty($args['template'])) {
            $args['template'] = $args['name'];
        }
        return $args;
    }

    /**
     * Find object based on objectid, name or moduleid + itemtype
     * @todo remove overlap with DataObjectFactory::*getObjectInfo()
     *
     * @param array<string, mixed> $args
     * with
     *     $args['objectid'] the object id of the object, or
     *     $args['name'] the name of the object, or
     *     $args['moduleid'] the module id of the object +
     *     $args['itemtype'] the itemtype of the object, or
     *     some other module + itemtype variation supported by getModID()
     * @return array<mixed> minimal information about object or empty array
     */
    public static function findObject(array $args = [])
    {
        $cacheKey = 'DynamicData.FindObject';
        if (!empty($args['objectid']) && xarCoreCache::isCached($cacheKey, $args['objectid'])) {
            return xarCoreCache::getCached($cacheKey, $args['objectid']);
        }
        if (!empty($args['name']) && xarCoreCache::isCached($cacheKey, $args['name'])) {
            return xarCoreCache::getCached($cacheKey, $args['name']);
        }
        if (!empty($args['moduleid']) && isset($args['itemtype']) && xarCoreCache::isCached($cacheKey, $args['moduleid'] . ':' . $args['itemtype'])) {
            return xarCoreCache::getCached($cacheKey, $args['moduleid'] . ':' . $args['itemtype']);
        }
        xarMod::loadDbInfo('dynamicdata', 'dynamicdata');
        $xartable = xarDB::getTables();
        $dynamicobjects = $xartable['dynamic_objects'];

        $query = "SELECT id,
                         name,
                         module_id,
                         itemtype
                  FROM $dynamicobjects ";

        $bindvars = [];
        if (isset($args['name'])) {
            $query .= " WHERE name = ? ";
            $bindvars[] = $args['name'];
        } elseif (!empty($args['objectid'])) {
            $query .= " WHERE id = ? ";
            $bindvars[] = (int) $args['objectid'];
        } else {
            $args = self::getModID($args);
            $query .= " WHERE module_id = ?
                          AND itemtype = ? ";
            $bindvars[] = (int) $args['moduleid'];
            $bindvars[] = (int) $args['itemtype'];
        }

        $dbconn = xarDB::getConn();
        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery($bindvars, xarDB::FETCHMODE_ASSOC);
        if (!$result->first()) {
            $row = [];
        } else {
            $row = $result->getRow();
        }
        $result->close();

        if (!empty($row) && count($row) > 0) {
            $args['moduleid'] = $row['module_id'];
            $args['itemtype'] = $row['itemtype'];
            $args['objectid'] = $row['id'];
            $args['name'] = $row['name'];
        }
        if (!empty($args['objectid'])) {
            xarCoreCache::setCached($cacheKey, $args['objectid'], $row);
        }
        if (!empty($args['name'])) {
            xarCoreCache::setCached($cacheKey, $args['name'], $row);
        }
        if (!empty($args['moduleid']) && isset($args['itemtype'])) {
            xarCoreCache::setCached($cacheKey, $args['moduleid'] . ':' . $args['itemtype'], $row);
        }
        return $row;
    }
}
