<?php
/**
 * Dynamic Object User Interface Handler
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */

sys::import('modules.dynamicdata.class.ui_handlers.default');
/**
 * Dynamic Object User Interface Handler
 *
 * @package modules
 * @subpackage dynamicdata
 */
class DataObjectStatsHandler extends DataObjectDefaultHandler
{
    public $method = 'stats';

    /**
     * Run the ui 'stats' method
     *
     * @param $args['method'] the ui method we are handling is 'stats' here
     * @param $args['catid'] optional category for the view
     * @param $args['sort'] optional sort for the view
     * @param $args['where'] optional where clause(s) for the view
     * @param $args['startnum'] optional start number for the view
     * @return string output of xarTplObject() using 'ui_stats'
     */
    function run(array $args = array())
    {
        if(!xarVarFetch('catid',    'isset', $args['catid'],    NULL, XARVAR_DONT_SET)) 
            return;
        if(!xarVarFetch('sort',     'isset', $args['sort'],     NULL, XARVAR_DONT_SET)) 
            return;
        if(!xarVarFetch('where',    'isset', $args['where'],    NULL, XARVAR_DONT_SET)) 
            return;
        if(!xarVarFetch('startnum', 'isset', $args['startnum'], NULL, XARVAR_DONT_SET)) 
            return;

        // Note: $args['where'] could be an array, e.g. index.php?object=sample&where[name]=Baby

        if(!xarVarFetch('group',    'isset', $args['group'],    NULL, XARVAR_DONT_SET)) 
            return;
        if(!xarVarFetch('field',    'isset', $args['field'],    NULL, XARVAR_DONT_SET)) 
            return;
        if(!xarVarFetch('match',    'isset', $args['match'],    NULL, XARVAR_DONT_SET)) 
            return;

        if(!empty($args) && is_array($args) && count($args) > 0) 
            $this->args = array_merge($this->args, $args);

        // override numitems for groups !?
        $this->args['numitems'] = 0;

        if (!empty($this->args['object']) && !empty($this->args['method'])) {
            // Get a cache key for this object method if it's suitable for object caching
            $cacheKey = xarCache::getObjectKey($this->args['object'], $this->args['method'], $this->args);
            // Check if the object method is cached
            if (!empty($cacheKey) && xarObjectCache::isCached($cacheKey)) {
                // Return the cached object method output
                return xarObjectCache::getCached($cacheKey);
            }
        }

        // set stats criteria
        $stats = array();
        $criteria = array('group', 'field', 'match');
        foreach ($criteria as $key) {
            if (isset($this->args[$key])) {
                $stats[$key] = $this->args[$key];
            } else {
                $stats[$key] = null;
            }
            unset($this->args[$key]);
        }
        // initialize group values if necessary
        if (empty($stats['group'])) {
            $stats['group'] = array();
        }
        $newgroup = array();
        foreach ($stats['group'] as $name) {
            if (empty($name)) continue;
            $newgroup[] = $name;
        }
        $stats['group'] = $newgroup;
        // initialize field values if necessary
        if (empty($stats['field'])) {
            $stats['field'] = array();
        }
        // initialize match types if necessary
        if (empty($stats['match'])) {
            $stats['match'] = array();
        }

        if(!isset($this->object)) 
        {
            $this->object =& DataObjectMaster::getObjectList($this->args);
            if(empty($this->object) || (!empty($this->args['object']) && $this->args['object'] != $this->object->name)) 
                return xarResponse::NotFound(xarML('Object #(1) seems to be unknown', $this->args['object']));

            if(empty($this->tplmodule)) 
            {
                $modname = xarMod::getName($this->object->moduleid);
                $this->tplmodule = $modname;
            }
        }
        $title = xarML('Statistics for #(1)', $this->object->label);
        xarTplSetPageTitle(xarVarPrepForDisplay($title));

        if(!empty($this->object->table) && !xarSecurityCheck('AdminDynamicData'))
            return xarResponse::Forbidden(xarML('View Table #(1) is forbidden', $this->object->table));

        if(!xarSecurityCheck('ViewDynamicDataItems',1,'Item',$this->object->moduleid.':'.$this->object->itemtype.':All'))
            return xarResponse::Forbidden(xarML('View #(1) is forbidden', $this->object->label));

        // get the property types in case we want to do more than check the type
        $stats['proptypes'] = DataPropertyMaster::getPropertyTypes();

        $stats['grouplist'] = array();
        foreach ($this->object->properties as $name => $property) {
            if (empty($stats['proptypes'][$property->type])) continue;
            $proptype = $stats['proptypes'][$property->type]['name'];
            switch ($proptype)
            {
                case 'itemid':
                    // preset to count the items
                    if (empty($stats['field'][$name])) {
                        $stats['field'][$name] = 'count';
                    }
                    break;
                case 'calendar':
                    $stats['grouplist'][$name.':year']  = $property->label . ' Year';
                    $stats['grouplist'][$name.':month'] = $property->label . ' Month';
                    $stats['grouplist'][$name.':day']   = $property->label . ' Day';
                    break;
                default:
                    $stats['grouplist'][$name] = $property->label;
                    break;
            }
            if (empty($stats['field'][$name])) {
                $stats['field'][$name] = 'hide';
            }
        }

        $groupby = array();
        $sort = array();
        $fieldlist = array();
        foreach ($newgroup as $name) {
            if (empty($stats['grouplist'][$name])) continue;
            if (!empty($this->object->properties[$name])) {
                $fieldlist[] = $name;
                $groupby[] = $name;
                $sort[] = $name;
            } elseif (strpos($name,':')) {
                // TODO: calendar field by year, month or day
                list($name,$format) = explode(':',$name);
                if (empty($this->object->properties[$name])) continue;
                $property = $this->object->properties[$name];
                $proptype = $stats['proptypes'][$property->type]['name'];
                if ($proptype == 'calendar' && empty($property->configuration)) {
                    list($field,$group) = $this->getTimestamp($name,$format);
                } else {
                    list($field,$group) = $this->getDate($name,$format);
                }
                if (!empty($field) && !empty($group)) {
                    $fieldlist[] = $field;
                    $groupby[] = $group;
                    $sort[] = $group;
                }
            }
        }

        foreach ($stats['field'] as $name => $operation) {
            if (empty($this->object->properties[$name])) continue;
            // fields that are already used for grouping can't be used in other operations
            if (in_array($name, $groupby)) continue;
            switch ($operation) {
                case 'hide':
                    break;
                case 'show':
                    $fieldlist[] = $name;
                    break;
                case 'count':
                    $fieldlist[] = "COUNT($name)";
                    break;
                case 'distinct':
                    $fieldlist[] = "COUNT(DISTINCT $name)"; // FIXME for getDataStores in master.php
/* FIXME:
For sqlite, we need something like
SELECT COUNT(*)
FROM ( SELECT DISTINCT field FROM table )
*/
                    break;
                case 'min':
                    $fieldlist[] = "MIN($name)";
                    break;
                case 'max':
                    $fieldlist[] = "MAX($name)";
                    break;
                case 'avg':
                    $fieldlist[] = "AVG($name)";
                    break;
                case 'sum':
                    $fieldlist[] = "SUM($name)";
                    break;
                default:
                    break;
            }
        }

        if (empty($fieldlist)) {
            $this->object->countItems();
            $result = 0;
        } else {
            $this->object->getItems(array('fieldlist' => $fieldlist,
                                          'groupby'   => $groupby,
                                          'sort'      => $sort));
            $result = 1;
        }

        $stats['options'] = array('hide'     => '',
                                  //'show'     => 'Show', // can't be mixed with group by etc.
                                  'count'    => 'Count',
                                  //'distinct' => 'Distinct', // SELECT COUNT(DISTINCT ...) ? FIXME for getDataStores in master.php
                                  'min'      => 'Minimum',
                                  'max'      => 'Maximum',
                                  'avg'      => 'Average',
                                  'sum'      => 'Sum');

        $output = xarTplObject(
            $this->tplmodule, $this->object->template, 'ui_stats',
            array('object' => $this->object,
                  'stats'  => $stats,
                  'result' => $result)
        );

        // Set the output of the object method in cache
        if (!empty($cacheKey)) {
            xarObjectCache::setCached($cacheKey, $output);
        }
        return $output;
    }

    function getTimestamp($field,$format)
    {
        $newfield = '';
        $newgroup = '';
        if ($format == 'year') {
            $dbtype = xarDB::getType();
            switch ($dbtype) {
                case 'mysql':
                    $newfield = "LEFT(FROM_UNIXTIME($field),4) AS $field" . "_year";
                    $newgroup = $field . "_year";
                    break;
                case 'postgres':
                    $newfield = "TO_CHAR(ABSTIME($field),'YYYY') AS $field" . "_year";
                // CHECKME: do we need to use TO_CHAR(...) for the group field too ?
                    $newgroup = $field . "_year";
                    break;
                case 'mssql':
                    $newfield = "LEFT(CONVERT(VARCHAR,DATEADD(ss,$field,'1/1/1970'),120),4) as $field" . "_year";
                    $newgroup = "LEFT(CONVERT(VARCHAR,DATEADD(ss,$field,'1/1/1970'),120),4)";
                    break;
                // TODO:  Add SQL queries for Oracle, etc.
                default:
                    break;
            }
        } elseif ($format == 'month') {
            $dbtype = xarDB::getType();
            switch ($dbtype) {
                case 'mysql':
                    $newfield = "LEFT(FROM_UNIXTIME($field),7) AS $field" . "_month";
                    $newgroup = $field . "_month";
                    break;
                case 'postgres':
                    $newfield = "TO_CHAR(ABSTIME($field),'YYYY-MM') AS $field" . "_month";
                // CHECKME: do we need to use TO_CHAR(...) for the group field too ?
                    $newgroup = $field . "_month";
                    break;
                case 'mssql':
                    $newfield = "LEFT(CONVERT(VARCHAR,DATEADD(ss,$field,'1/1/1970'),120),7) as $field" . "_month";
                    $newgroup = "LEFT(CONVERT(VARCHAR,DATEADD(ss,$field,'1/1/1970'),120),7)";
                    break;
                // TODO:  Add SQL queries for Oracle, etc.
                default:
                    break;
            }
        } elseif ($format == 'day') {
            $dbtype = xarDB::getType();
            switch ($dbtype) {
                case 'mysql':
                    $newfield = "LEFT(FROM_UNIXTIME($field),10) AS $field" . "_day";
                    $newgroup = $field . "_day";
                    break;
                case 'postgres':
                    $newfield = "TO_CHAR(ABSTIME($field),'YYYY-MM-DD') AS $field" . "_day";
                // CHECKME: do we need to use TO_CHAR(...) for the group field too ?
                    $newgroup = $field . "_day";
                    break;
                case 'mssql':
                    $newfield = "LEFT(CONVERT(VARCHAR,DATEADD(ss,$field,'1/1/1970'),120),10) as $field" . "_day";
                    $newgroup = "LEFT(CONVERT(VARCHAR,DATEADD(ss,$field,'1/1/1970'),120),10)";
                    break;
                // TODO:  Add SQL queries for Oracle, etc.
                default:
                    break;
            }
        }
        return array($newfield,$newgroup);
    }

    function getDate($field,$format) // CHECKME for all database types
    {
        $newfield = '';
        $newgroup = '';
        if ($format == 'year') {
            $dbtype = xarDB::getType();
            switch ($dbtype) {
                case 'mysql':
                    $newfield = "LEFT($field,4) AS $field" . "_year";
                    $newgroup = $field . "_year";
                    break;
                case 'postgres':
                    $newfield = "LEFT($field,4) AS $field" . "_year";
                // CHECKME: do we need to use TO_CHAR(...) for the group field too ?
                    $newgroup = $field . "_year";
                    break;
                case 'mssql':
                    $newfield = "LEFT($field,4) as $field" . "_year";
                    $newgroup = "LEFT($field,4)";
                    break;
                // TODO:  Add SQL queries for Oracle, etc.
                default:
                    break;
            }
        } elseif ($format == 'month') {
            $dbtype = xarDB::getType();
            switch ($dbtype) {
                case 'mysql':
                    $newfield = "LEFT($field,7) AS $field" . "_month";
                    $newgroup = $field . "_month";
                    break;
                case 'postgres':
                    $newfield = "LEFT($field,7) AS $field" . "_month";
                // CHECKME: do we need to use TO_CHAR(...) for the group field too ?
                    $newgroup = $field . "_month";
                    break;
                case 'mssql':
                    $newfield = "LEFT($field,7) AS $field" . "_month";
                    $newgroup = "LEFT($field,7)";
                    break;
                // TODO:  Add SQL queries for Oracle, etc.
                default:
                    break;
            }
        } elseif ($format == 'day') {
            $dbtype = xarDB::getType();
            switch ($dbtype) {
                case 'mysql':
                    $newfield = "LEFT($field,10) AS $field" . "_day";
                    $newgroup = $field . "_day";
                    break;
                case 'postgres':
                    $newfield = "LEFT($field,10) AS $field" . "_day";
                // CHECKME: do we need to use TO_CHAR(...) for the group field too ?
                    $newgroup = $field . "_day";
                    break;
                case 'mssql':
                    $newfield = "LEFT($field,10) AS $field" . "_day";
                    $newgroup = "LEFT($field,10)";
                    break;
                // TODO:  Add SQL queries for Oracle, etc.
                default:
                    break;
            }
        }
        return array($newfield,$newgroup);
    }
}

?>
