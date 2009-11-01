<?php
/**
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata
 * @link http://xaraya.com/index.php/release/182.html
 */

sys::import('xaraya.structures.descriptor');

/*
 * generate the variables necessary to instantiate a DataObject or DataProperty class
*/
class DataObjectDescriptor extends ObjectDescriptor
{
    function __construct(Array $args=array())
    {
        $args = self::getObjectID($args);
        parent::__construct($args);
    }

    static function getModID(Array $args=array())
    {
        foreach ($args as $key => &$value) {
            if (in_array($key, array('module','modid','module','moduleid'))) {
                if (empty($value)) $value = xarMod::getRegID(xarMod::getName());
                if (is_numeric($value) || is_integer($value)) {
                    $args['moduleid'] = $value;
                } else {
                    //$info = xarMod::getInfo(xarMod::getRegID($value));
                    $args['moduleid'] = xarMod::getRegID($value); //$info['systemid']; FIXME
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
            $args['moduleid'] = xarMod::getRegID($args['fallbackmodule']); // $info['systemid'];  FIXME change id
        }
        if (!isset($args['itemtype'])) $args['itemtype'] = 0;
        return $args;
    }

    /**
     * Get Object ID
     *
     * @return array all parts necessary to describe a DataObject
     */
    static function getObjectID(Array $args=array())
    {
        xarMod::loadDbInfo('dynamicdata','dynamicdata');
        $xartable = xarDB::getTables();
        $dynamicobjects = $xartable['dynamic_objects'];

        $query = "SELECT id,
                         name,
                         module_id,
                         itemtype
                  FROM $dynamicobjects ";

        $bindvars = array();
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
        $result = $stmt->executeQuery($bindvars, ResultSet::FETCHMODE_ASSOC);
        if (!$result->first()) {
            $row = array();
        } else {
            $row = $result->getRow();
        }
        $result->close();

        if (empty($row) || count($row) < 1) {
            $args['moduleid'] = isset($args['moduleid']) ? (int)$args['moduleid'] : null;
            $args['itemtype'] = isset($args['itemtype']) ? (int)$args['itemtype'] : null;
            $args['objectid'] = isset($args['objectid']) ? (int)$args['objectid'] : null;
            $args['name'] = isset($args['name']) ? $args['name'] : null;
        } else {
            $args['moduleid'] = (int)$row['module_id'];
            $args['itemtype'] = (int)$row['itemtype'];
            $args['objectid'] = (int)$row['id'];
            $args['name'] = $row['name'];
        }
        if (empty($args['tplmodule'])) $args['tplmodule'] = xarMod::getName($args['moduleid']); //FIXME: go to systemid
        if (empty($args['template'])) $args['template'] = $args['name'];
        return $args;
    }
}

?>