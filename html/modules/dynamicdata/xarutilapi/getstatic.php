<?php
/**
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * (try to) get the "static" properties, corresponding to fields in dedicated
 * tables for this module + item type
 *
 * @param $args['module'] module name of table you're looking for, or
 * @param $args['modid'] module id of table you're looking for
 * @param $args['itemtype'] item type of table you're looking for
 * @param $args['table']  table name of table you're looking for (better)
 * @return mixed value of the field, or false on failure
 * @throws BAD_PARAM, DATABASE_ERROR, NO_PERMISSION
 * @todo split off the common parts which are also in getmeta
 */
function dynamicdata_utilapi_getstatic($args)
{
    static $propertybag = array();

    extract($args);

    if (empty($modid) && !empty($module)) {
        $modid = xarModGetIDFromName($module);
    }
    if (empty($modid)) {
        $modid = xarModGetIDFromName(xarModGetName());
    }
    $modinfo = xarModGetInfo($modid);
    if (empty($itemtype)) {
        $itemtype = 0;
    }

    $invalid = array();
    if (!isset($modid) || !is_numeric($modid) || empty($modinfo['name'])) {
        $invalid[] = 'module id ' . xarVarPrepForDisplay($modid);
    }
    if (!isset($itemtype) || !is_numeric($itemtype)) {
        $invalid[] = 'item type';
    }
    if (count($invalid) > 0) {
        $msg = 'Invalid #(1) for #(2) function #(3)() in module #(4)';
        $vars = array(join(', ',$invalid), 'util', 'getstatic', 'DynamicData');
        throw new BadParameterException($vars,$msg);
    }
    if (empty($table)) {
        $table = '';
    }
    if (isset($propertybag["$modid:$itemtype:$table"])) {
        return $propertybag["$modid:$itemtype:$table"];
    }

    $dbconn = xarDB::getConn();
    $xartable = xarDB::getTables();

    $dbInfo = $dbconn->getDatabaseInfo();
    $dbTables = array();

    if(!empty($table)) {
        // it's easy if the table name is known
        $dbTables[] = $dbInfo->getTable($table);
    } else {
        $dbTables = $dbInfo->getTables();
    }

    // TODO: we lost the linkage with modules here
    $static = array(); $order = 1; $seq=1;
    foreach($dbTables as $tblInfo) {
        $tblColumns = $tblInfo->getColumns();
        $table = $tblInfo->getName();
        foreach($tblColumns as $colInfo) {
            $name = $colInfo->getName();
            $id = $seq++;
            $default = $colInfo->getDefaultValue();
            // Construct name and label from the columnname
            $label = strtr($name,'_',' ');
            $label = ucwords($label);
            if(isset($static[$name])) {
                $i = 1;
                while(isset($static[$name . '_' . $i])) {
                    $i++;
                }
                $name = $name . '_' . $i;
                $label = $label . '_' . $i;
            }
            $status = DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE;

            // assign some default configuration for now
            $datatype = strtolower(CreoleTypes::getCreoleName($colInfo->getType()));
            //            $datatype = $colInfo->getNativeType();
            $size = $colInfo->getSize();

            // TODO: improve this based on property type configurations
            $configuration = $datatype;
            $configuration .= empty($size) ? '' : ' (' . $size . ')';

            // (try to) assign some default property type for now
            // = obviously limited to basic data types in this case
            // cfr. creole/CreoleTypes.php
            switch ($datatype) {
            case 'char':
            case 'varchar':
                $proptype = 2; // Text Box
                if (!empty($size)) {
                    $configuration = "0:$size";
                }
                break;
            case 'smallint':
            case 'tinyint':
            case 'bigint':
            case 'integer':
            case 'year':
                $proptype = 15; // Number Box
                break;
            case 'numeric':
            case 'decimal':
            case 'double':
            case 'float':
                $proptype = 17; // Number Box (float)
                break;
            case 'boolean':
                $proptype = 14; // Checkbox
                break;
            case 'date':
            case 'time':
            case 'timestamp':
                $proptype = 8; // Calendar
                break;
            case 'longvarchar':
            case 'text':
            case 'clob':
                $proptype = 4; // Medium Text Area
                $status = DataPropertyMaster::DD_DISPLAYSTATE_DISPLAYONLY;
                $configuration ='';
                break;
            case 'longvarbinary':
            case 'varbinary':
            case 'binary':
            case 'blob':       // caution, could be binary too !
                $proptype = 4; // Medium Text Area
                $status = DataPropertyMaster::DD_DISPLAYSTATE_DISPLAYONLY;
                break;
            default:
                $proptype = 1; // Static Text
                break;
            }

            // try to figure out if it's the item id
            // TODO: let modules define this
            //debug($colInfo);
            if ($colInfo->isAutoIncrement()) {
                // not allowed to modify primary key !
                $proptype = 21; // Item ID
                $configuration ='';
            }

            $static[$name] = array('name' => $name,
                                   'label' => $label,
                                   'type' => $proptype,
                                   'id' => $id,
                                   'defaultvalue' => $default,
                                   'source' => $table . '.' . $name,
                                   'status' => $status,
                                   'seq' => $order,
                                   'configuration' => $configuration);
            $order++;
        } // next column
    } // next table
    $propertybag["$modid:$itemtype:$table"] = $static;
    return $static;
}

?>
