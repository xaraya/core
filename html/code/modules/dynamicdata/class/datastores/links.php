<?php
/**
 * @package modules
 * @subpackage dynamicdata module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.info/index.php/release/182.html
 */

sys::import('modules.dynamicdata.class.datastores.master');

/**
 * DataStoreLinks class
 */
class DataStoreLinks extends Object
{
    static $linktypes = array(
        'children'   => 'is parent of (one-to-many)',
        'parents'    => 'is child of (many-to-one)',
        'linkedto'   => 'is linked to (one-to-one)',
        'linkedfrom' => 'is linked from (one-to-one)',
    );
    static $reverselinktypes = array(
        'parents'    => 'children',
        'children'   => 'parents',
        'linkedfrom' => 'linkedto',
        'linkedto'   => 'linkedfrom',
    );
    static $directions = array(
        'bi'   => 'two-way',
        'uni'  => 'one-way',
        'info' => 'info',
        'fk'   => 'foreign key',
    );

    /**
     * Initialize DataStoreLinks by importing the necessary xml files if necessary
     */
    static function initLinks()
    {
        $linklist = DataObjectMaster::getObjectList(array('name' => 'dynamic_table_links'));
        if (empty($linklist) || empty($linklist->objectid)) {
            $def_file = sys::code() . 'modules/dynamicdata/xardata/dynamic_table_links-def.xml';
            $dat_file = sys::code() . 'modules/dynamicdata/xardata/dynamic_table_links-dat.xml';
            if (file_exists($def_file)) {
                $objectid = xarMod::apiFunc('dynamicdata','util','import',
                                            array('file' => $def_file));
                if (empty($objectid)) return;
            }
            if (file_exists($dat_file)) {
                $objectid = xarMod::apiFunc('dynamicdata','util','import',
                                            array('file' => $dat_file));
                if (empty($objectid)) return;
            } else {
                // add foreign keys to table links
                $foreignkeys = self::getForeignKeys();
                foreach ($foreignkeys as $info) {
                    DataStoreLinks::addLink($info['source'], $info['from'], $info['target'], $info['to'], 'parents', 'fk');
                }
            }
            $linklist = DataObjectMaster::getObjectList(array('name' => 'dynamic_table_links'));
        }
        return $linklist;
    }

    /**
     * Get links for a datastore
     *
     * @param $source the table we want to get the links for (tablename)
     * @param $linktype the type of links we're looking for (default, parents, children, linkedto, linkedfrom, info, all)
     */
    static function getLinks($source = '', $linktype = '')
    {
        $linklist = self::initLinks();
        if (empty($linklist) || empty($linklist->objectid)) return array();

        $source = self::getName($source);

        $where = array();

        // CHECKME: do we support getting the links for all tables here ?
        if (!empty($source)) {
            $where[] = "source eq '$source'";
        }

        // check what link_type we need to filter on (except 'info' and 'all')
        if (!empty($linktype) && !empty(self::$linktypes[$linktype])) {
            $where[] = "link_type eq '" . $linktype . "'";
        }

        // we'll skip the 'info' links here, unless explicitly asked for by 'info' or 'all'
        if ($linktype == 'info') {
            $where[] = "direction eq 'info'";
        } elseif ($linktype != 'all') {
            $where[] = "direction ne 'info'";
        }

        // get the links for this source, link_type and direction
        if (!empty($where)) {
            $items = $linklist->getItems(array('where' => implode(' and ', $where)));
        } else {
            $items = $linklist->getItems();
        }

        // return as source => links array
        $links = array();
        foreach ($items as $link) {
            if (empty($links[$link['source']])) {
                $links[$link['source']] = array();
            }
            $links[$link['source']][] = $link;
        }
        return $links;
    }

    /**
     * Add a link between a source table and a target table
     *
     * @param $from_table the table we want to add a link from (object, objectlist, objectname or objectid)
     * @param $from_field the field we want to add a link from
     * @param $to_table the table we want to add a link to (table, objectlist, objectname or objectid)
     * @param $to_field the field we want to add a link to
     * @param $link_type the type of link we want to add
     * @param $direction the direction of the link we want to add (bi, uni or info)
     * @param $extra additional constraints for this link
     * @param $add_reverse if we want to add a reverse link from target to source too (default is always true)
     */
    static function addLink($from_table, $from_field, $to_table, $to_field, $link_type, $direction, $extra = '', $add_reverse = true)
    {
        $linkobject = DataObjectMaster::getObject(array('name' => 'dynamic_table_links'));
        if (empty($linkobject) || empty($linkobject->objectid)) return;

        $from_table = self::getName($from_table);
        $to_table = self::getName($to_table);
        if (empty($from_table) || empty($to_table)) return;

        $link = array('source'    => $from_table,
                      'from_prop' => $from_field,
                      'target'    => $to_table,
                      'to_prop'   => $to_field,
                      'link_type' => $link_type,
                      'direction' => $direction,
                      'extra'     => $extra);
        $link_id = $linkobject->createItem($link);
        if (empty($link_id)) return;

        // see if we need to create a reverse link too
        if (empty($add_reverse) || empty(self::$reverselinktypes[$link_type])) {
            // nothing more to add
            return $link_id;
        }

        // determine the link_type for the reverse link
        $reversetype = self::$reverselinktypes[$link_type];

        // for uni-directional links from source to target, we'll store an 'info' link back from target to source, just so we know it exists
        if ($direction == 'uni') {
            $reversedir = 'info';
        } elseif ($direction == 'info') {
            $reversedir = 'uni';
        } elseif ($direction == 'fk') {
            $reversedir = 'fk';
        } else {
            $reversedir = 'bi';
        }

        $link = array('source'    => $to_table,
                      'from_prop' => $to_field,
                      'target'    => $from_table,
                      'to_prop'   => $from_field,
                      'link_type' => $reversetype,
                      'direction' => $reversedir,
                      // CHECKME: probably not the right syntax in reverse !
                      'extra'     => $extra);

        $link_id = $linkobject->createItem($link);
        return $link_id;
    }

    /**
     * Remove a link between a source table and a target table
     */
    static function removeLink($link_id, $remove_reverse = true)
    {
        $linkobject = DataObjectMaster::getObject(array('name' => 'dynamic_table_links'));
        if (empty($linkobject) || empty($linkobject->objectid)) return;

        $link_id = $linkobject->getItem(array('itemid' => $link_id));
        if (empty($link_id)) return;

        $linkfields = $linkobject->getFieldValues();

        $link_id = $linkobject->deleteItem();
        if (empty($remove_reverse)) {
            // nothing more to remove
            return $link_id;
        }

        // get all links from the target (= including 'info')
        $links = self::getLinks($linkfields['target'], 'all');
        if (empty($links[$linkfields['target']])) return $link_id;

        // determine the link_type for the reverse link
        $reversetype = self::$reverselinktypes[$linkfields['link_type']];

        foreach ($links[$linkfields['target']] as $link) {
            // find the corresponding link from target to source
            if ($link['target'] == $linkfields['source'] &&
                $link['to_prop'] == $linkfields['from_prop'] &&
                $link['from_prop'] == $linkfields['to_prop'] &&
                $link['link_type'] == $reversetype) {

                $link_id = $linkobject->getItem(array('itemid' => $link['id']));
                if (empty($link_id) || $link_id != $link['id']) continue;
                $link_id = $linkobject->deleteItem();
            }
        }
        return $link_id;
    }

    /**
     * Get the name of table arguments (tablename)
     */
    static function getName($object)
    {
        return $object;
    }

    /**
     * Get mapping of datastores to objects by looking at the property sources
     *
     * @return array of [datastore][objectid] = number of properties
     */
    static function getMapping()
    {
        // load tables for 'dynamic_data'
        xarMod::loadDbInfo('dynamicdata','dynamicdata');
        $xartables =& xarDB::getTables();
    
        $mapping = array();
        $properties = xarMod::apiFunc('dynamicdata','user','getobjectlist',
                                      array('name' => 'properties',
                                            'fieldlist' => array('name','objectid','source')));
        $properties->getItems();
        foreach ($properties->items as $item) {
            if (strpos($item['source'], '.') !== false) {
                list($store,$name) = explode('.', $item['source']);
            } elseif ($item['source'] == 'dynamic_data') {
                $store = $xartables['dynamic_data'];
            } else {
                $store = $item['source'];
            }
            if (!isset($mapping[$store])) {
                $mapping[$store] = array();
            }
            if (!isset($mapping[$store][$item['objectid']])) {
                $mapping[$store][$item['objectid']] = 0;
            }
            $mapping[$store][$item['objectid']] += 1;
        }
    
        return $mapping;
    }

    /**
     * Get mapping of datasource fields to properties
     *
     * @return array of [datasource] = property info
     */
    static function getSourceFieldMapping()
    {
        // load tables for 'dynamic_data'
        xarMod::loadDbInfo('dynamicdata','dynamicdata');
        $xartables =& xarDB::getTables();
    
        $sourcemapping = array();
        $properties = xarMod::apiFunc('dynamicdata','user','getobjectlist',
                                      array('name' => 'properties',
                                            'fieldlist' => array('name','objectid','source')));
        $properties->getItems();
        foreach ($properties->items as $item) {
            if (strpos($item['source'], '.') !== false) {
                // keep track of where each source field is used
                $sourcemapping[$item['source']] = $item;
            } elseif ($item['source'] == 'dynamic_data') {
                // not interested ?
            } else {
                // not interested
            }
        }
    
        return $sourcemapping;
    }

    /**
     * Get the foreign keys for all tables in the database
     *
     * @return array of foreign keys
     */
    static function getForeignKeys()
    {
        // get tables
        $dbconn = xarDB::getConn();
        $dbInfo = $dbconn->getDatabaseInfo();
        $tables = $dbInfo->getTables();

        $keylist = array();
        foreach ($tables as $tableInfo) {
            $foreignkeys = $tableInfo->getForeignKeys();
            if (empty($foreignkeys)) continue;
            foreach ($foreignkeys as $foreignkey) {
                $references = $foreignkey->getReferences();
                if (empty($references)) continue;
                foreach ($references as $reference) {
                    $local = $reference[0];
                    $foreign = $reference[1];
                    $ondelete = $reference[2];
                    $onupdate = $reference[3];

                    $source = $local->getTable()->getName();
                    $fromfield = $local->getName();
                    $target = $foreign->getTable()->getName();
                    $tofield = $foreign->getName();
    
                    $keylist[] = array('source'   => $source,
                                       'from'     => $fromfield,
                                       'target'   => $target,
                                       'to'       => $tofield,
                                       'onupdate' => $onupdate,
                                       'ondelete' => $ondelete);
                }
            }
        }
        return $keylist;
    }
}
?>
