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
        if(!xarVarFetch('report',   'isset', $args['report'],   NULL, XARVAR_DONT_SET)) 
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

        if ($this->args['method'] == 'report') {
            $output = $this->report();
        } else {
            $output = $this->stats();
        }

        // Set the output of the object method in cache
        if (!empty($cacheKey)) {
            xarObjectCache::setCached($cacheKey, $output);
        }
        return $output;
    }

    function stats()
    {
        // set stats criteria
        $stats = array();
        $criteria = array('group', 'field', 'match', 'report');
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
        // initialize report if necessary
        if (empty($stats['report'])) {
            $stats['report'] = 'Default Report';
        }
        // prepare for output now
        $stats['report'] = xarVarPrepForDisplay($stats['report']);

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
/*
        // Set page template
        if (xarTplGetPageTemplateName() == 'default') {
             // Use the admin-$modName.xt page if available when $modType is admin
            // falling back on admin.xt if the former isn't available
            if (!xarTplSetPageTemplateName('admin-'.$this->tplmodule)) {
                xarTplSetPageTemplateName('admin');
            }
        }
*/
        if(!empty($this->object->table) && !xarSecurityCheck('AdminDynamicData'))
            return xarResponse::Forbidden(xarML('View Table #(1) is forbidden', $this->object->table));

        if(!xarSecurityCheck('ViewDynamicDataItems',1,'Item',$this->object->moduleid.':'.$this->object->itemtype.':All'))
            return xarResponse::Forbidden(xarML('View #(1) is forbidden', $this->object->label));

        // load previously defined report if available
        if (!empty($stats['report']) && empty($stats['group']) && empty($stats['field']) && empty($stats['match'])) {
            $info = $this->getReport($stats['report']);
            if (!empty($info) && !empty($info['stats'])) {
                $stats = $info['stats'];
            }
        }

        // get the property types in case we want to do more than check the type
        $proptypes = DataPropertyMaster::getPropertyTypes();

        $stats['grouplist'] = array();
        foreach ($this->object->properties as $name => $property) {
            if (empty($proptypes[$property->type])) continue;
            $proptype = $proptypes[$property->type]['name'];
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
        foreach ($stats['group'] as $name) {
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
                $proptype = $proptypes[$property->type]['name'];
                $field = '';
                if ($proptype == 'calendar' && empty($property->configuration)) {
                    $field = $this->getTimestampField($name,$format);
                } else {
                    $field = $this->getDateField($name,$format);
                }
                if (!empty($field)) {
                    // add the custom operation to the fieldlist
                    $fieldlist[] = $field;
                    // add the property to the groupby and sort list
                    $groupby[] = $name;
                    $sort[] = $name;
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
                // We use a custom operation here that gets translated to a database-specific one by the datastore
                case 'distinct':
                    $fieldlist[] = "COUNT_DISTINCT($name)"; // CHECKME in datastores
                    break;
                default:
                    break;
            }
        }

        $info = array('fieldlist' => $fieldlist,
                      'groupby'   => $groupby,
                      'sort'      => $sort);

        // check if we need to save this report
        if (!xarVarFetch('save', 'isset', $save, NULL, XARVAR_DONT_SET)) 
            return;

        // nothing to show here
        if (empty($fieldlist)) {
            $result = 0;

        // save the report and redirect
        } elseif (!empty($save) && !empty($stats['report']) && xarSecurityCheck('DeleteDynamicDataItem',0,'Item',$this->object->moduleid.':'.$this->object->itemtype.':All')) {
            $this->saveReport($stats['report'], $stats, $info);
            xarController::Rredirect(xarServer::getObjectURL($this->object->name, 'report', array('report' => $stats['report'])));
            return true;

        // get the result
        } else {
            $this->object->getItems($info);
            $result = 1;
        }

        $stats['options'] = array('hide'     => '',
                                  //'show'     => 'Show', // can't be mixed with group by etc.
                                  'count'    => 'Count',
                                  'distinct' => 'Distinct', // CHECKME in datastores
                                  'min'      => 'Minimum',
                                  'max'      => 'Maximum',
                                  'sum'      => 'Sum',
                                  'avg'      => 'Average');

        $output = xarTplObject(
            $this->tplmodule, $this->object->template, 'ui_stats',
            array('object' => $this->object,
                  'stats'  => $stats,
                  'result' => $result)
        );

        return $output;
    }

    function report()
    {
        // set report criteria
        $report = array();
        $criteria = array('report');
        foreach ($criteria as $key) {
            if (isset($this->args[$key])) {
                $report[$key] = $this->args[$key];
            } else {
                $report[$key] = null;
            }
            unset($this->args[$key]);
        }
        // initialize report if necessary
        if (empty($report['report'])) {
            $report['report'] = 'Default Report';
        }
        // prepare for output now
        $report['report'] = xarVarPrepForDisplay($report['report']);

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
        $title = xarML('Report for #(1)', $this->object->label);
        xarTplSetPageTitle(xarVarPrepForDisplay($title));

        if(!empty($this->object->table) && !xarSecurityCheck('AdminDynamicData'))
            return xarResponse::Forbidden(xarML('View Table #(1) is forbidden', $this->object->table));

        if(!xarSecurityCheck('ViewDynamicDataItems',1,'Item',$this->object->moduleid.':'.$this->object->itemtype.':All'))
            return xarResponse::Forbidden(xarML('View #(1) is forbidden', $this->object->label));

        $report['reportlist'] = $this->getReportList();

        if (!empty($report['reportlist']) && in_array($report['report'], $report['reportlist'])) {
            $info = $this->getReport($report['report']);
        }

        if (empty($info) || empty($info['fieldlist'])) {
            $this->object->countItems();
            $result = 0;
        } else {
            // remove stats info
            unset($info['stats']);
            $this->object->getItems($info);
            $result = 1;
        }

        $output = xarTplObject(
            $this->tplmodule, $this->object->template, 'ui_report',
            array('object' => $this->object,
                  'report' => $report,
                  'result' => $result)
        );

        return $output;
    }

    /**
     * We use a custom operation here that gets translated to a database-specific one by the datastore
     */
    function getTimestampField($field,$format) // CHECKME for all database types
    {
        $newfield = '';
        if ($format == 'year') {
            $newfield = "UNIXTIME_BY_YEAR($field)";
        } elseif ($format == 'month') {
            $newfield = "UNIXTIME_BY_MONTH($field)";
        } elseif ($format == 'day') {
            $newfield = "UNIXTIME_BY_DAY($field)";
        }
        return $newfield;
    }

    /**
     * We use a custom operation here that gets translated to a database-specific one by the datastore
     */
    function getDateField($field,$format) // CHECKME for all database types
    {
        $newfield = '';
        if ($format == 'year') {
            $newfield = "DATETIME_BY_YEAR($field)";
        } elseif ($format == 'month') {
            $newfield = "DATETIME_BY_MONTH($field)";
        } elseif ($format == 'day') {
            $newfield = "DATETIME_BY_DAY($field)";
        }
        return $newfield;
    }

    function getReportList()
    {
        $serialreports = xarModVars::get('dynamicdata','reportlist.'.$this->object->name);
        if (!empty($serialreports)) {
            $reportlist = unserialize($serialreports);
        } else {
            $reportlist = array();
        }
        return $reportlist;
    }

    function getReport($report)
    {
        $key = 'report.'.$this->object->name.'.'.$report;
        if (strlen($key) > 64) {
            $key = 'report.' . md5($key);
        }
        $serialinfo = xarModVars::get('dynamicdata',$key);
        if (!empty($serialinfo)) {
            $info = unserialize($serialinfo);
        } else {
            $info = array();
        }
        return $info;
    }

    function saveReport($report, $stats, $info)
    {
        $reportlist = $this->getReportList();
        if (empty($reportlist) || !in_array($report, $reportlist)) {
            // only keep the last 20 reports per object
            if (count($reportlist) > 20) {
                $oldreport = array_pop($reportlist);
                $this->deleteReport($oldreport);
            }
            // add the new report at the front of the list
            array_unshift($reportlist, $report);
            xarModVars::set('dynamicdata','reportlist.'.$this->object->name,serialize($reportlist));
        }
        // add stats to info so we can edit it afterwards
        $info['stats'] = $stats;
        $key = 'report.'.$this->object->name.'.'.$report;
        if (strlen($key) > 64) {
            $key = 'report.' . md5($key);
        }
        xarModVars::set('dynamicdata',$key,serialize($info));
    }

    function deleteReport($report)
    {
        $key = 'report.'.$this->object->name.'.'.$report;
        if (strlen($key) > 64) {
            $key = 'report.' . md5($key);
        }
        xarModVars::delete('dynamicdata',$key);
    }
}

?>
