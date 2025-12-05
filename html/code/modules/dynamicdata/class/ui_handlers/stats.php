<?php

/**
 * Dynamic Object User Interface Handler
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */

namespace Xaraya\DataObject\Handlers;

use DataObjectList;

/**
 * Dynamic Object User Interface Handler
 * @todo add context
 */
class StatsHandler extends DefaultHandler
{
    public string $method = 'stats';

    /**
     * Run the ui 'stats' method
     *
     * @param array<string, mixed> $args
     * with
     *     $args['method'] the ui method we are handling is 'stats' here
     *     $args['catid'] optional category for the view
     *     $args['sort'] optional sort for the view
     *     $args['where'] optional where clause(s) for the view
     *     $args['startnum'] optional start number for the view
     * @return string|void output of $this->template() using 'ui_stats'
     */
    public function run(array $args = [])
    {
        $this->var()->check('catid', $args['catid']);
        $this->var()->check('sort', $args['sort']);
        $this->var()->check('where', $args['where']);
        $this->var()->check('startnum', $args['startnum']);

        // Note: $args['where'] could be an array, e.g. index.php?object=sample&where[name]=Baby

        $this->var()->check('group', $args['group']);
        $this->var()->check('field', $args['field']);
        $this->var()->check('match', $args['match']);
        $this->var()->check('report', $args['report']);

        if (!empty($args) && is_array($args) && count($args) > 0) {
            $this->args = array_merge($this->args, $args);
        }

        // override numitems for groups !?
        $this->args['numitems'] = 0;

        $cacheKey = null;
        if (!empty($this->args['object']) && !empty($this->args['method'])) {
            // Get a cache key for this object method if it's suitable for object caching
            $cacheKey = $this->cache()->getObjectKey($this->args['object'], $this->args['method'], $this->args);
            // Check if the object method is cached
            if ($this->cache()->hasObject($cacheKey)) {
                // Return the cached object method output
                return $this->cache()->getObject($cacheKey);
            }
        }

        if ($this->args['method'] == 'report') {
            $output = $this->report($args);
        } else {
            $output = $this->stats($args);
        }

        // Set the output of the object method in cache
        $this->cache()->setObject($cacheKey, $output);
        return $output;
    }

    /**
     * Summary of stats
     * @param array<string, mixed> $args
     * @return bool|string
     */
    public function stats(array $args = [])
    {
        // set stats criteria
        $stats = [];
        $criteria = ['group', 'field', 'match', 'report'];
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
            $stats['group'] = [];
        }
        $newgroup = [];
        foreach ($stats['group'] as $name) {
            if (empty($name)) {
                continue;
            }
            $newgroup[] = $name;
        }
        $stats['group'] = $newgroup;
        // initialize field values if necessary
        if (empty($stats['field'])) {
            $stats['field'] = [];
        }
        // initialize match types if necessary
        if (empty($stats['match'])) {
            $stats['match'] = [];
        }
        // initialize report if necessary
        if (empty($stats['report'])) {
            $stats['report'] = 'Default Report';
        }
        // prepare for output now
        $stats['report'] = $this->prep()->text($stats['report']);

        if (!isset($this->object)) {
            // set context if available in handler
            $this->object = $this->data()->getObjectList($this->args);
            if (empty($this->object) || (!empty($this->args['object']) && $this->args['object'] != $this->object->name)) {
                $msg = $this->mls()->translate('Object #(1) seems to be unknown', $this->args['object']);
                return $this->ctl()->notFound($msg);
            }

            if (empty($this->tplmodule)) {
                // set in DataObjectDescriptor::getObjectID()
                $this->tplmodule = $this->object->tplmodule;
            }
        } else {
            // set context if available in handler
            $this->object->setContext($this->getContext());
        }
        assert($this->object instanceof DataObjectList);

        $title = $this->mls()->translate('Statistics for #(1)', $this->object->label);
        $this->tpl()->setPageTitle($this->prep()->text($title));

        if (!$this->object->checkAccess('view')) {
            $msg = $this->mls()->translate('View #(1) is forbidden', $this->object->label);
            return $this->ctl()->forbidden($msg);
        }

        // load previously defined report if available
        if (!empty($stats['report']) && empty($stats['group']) && empty($stats['field']) && empty($stats['match'])) {
            $info = $this->getReport($stats['report']);
            if (!empty($info) && !empty($info['stats'])) {
                $stats = $info['stats'];
            }
        }

        // get the property types in case we want to do more than check the type
        $proptypes = $this->prop()->getPropertyTypes();

        $stats['grouplist'] = [];
        foreach ($this->object->properties as $name => $property) {
            if (empty($proptypes[$property->type])) {
                continue;
            }
            $proptype = $proptypes[$property->type]['name'];
            switch ($proptype) {
                case 'itemid':
                    // preset to count the items
                    if (empty($stats['field'][$name])) {
                        $stats['field'][$name] = 'count';
                    }
                    break;
                case 'calendar':
                    $stats['grouplist'][$name . ':year']  = $property->label . ' Year';
                    $stats['grouplist'][$name . ':month'] = $property->label . ' Month';
                    $stats['grouplist'][$name . ':day']   = $property->label . ' Day';
                    break;
                default:
                    $stats['grouplist'][$name] = $property->label;
                    break;
            }
            if (empty($stats['field'][$name])) {
                $stats['field'][$name] = 'hide';
            }
        }

        $groupby = [];
        $sort = [];
        $fieldlist = [];
        foreach ($stats['group'] as $name) {
            if (empty($stats['grouplist'][$name])) {
                continue;
            }
            if (!empty($this->object->properties[$name])) {
                $fieldlist[] = $name;
                $groupby[] = $name;
                $sort[] = $name;
            } elseif (strpos($name, ':')) {
                // TODO: calendar field by year, month or day
                [$name, $format] = explode(':', $name);
                if (empty($this->object->properties[$name])) {
                    continue;
                }
                $property = $this->object->properties[$name];
                $proptype = $proptypes[$property->type]['name'];
                $field = '';
                if ($proptype == 'calendar' && empty($property->configuration)) {
                    $field = $this->getTimestampField($name, $format);
                } else {
                    $field = $this->getDateField($name, $format);
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
            if (empty($this->object->properties[$name])) {
                continue;
            }
            // fields that are already used for grouping can't be used in other operations
            if (in_array($name, $groupby)) {
                continue;
            }
            // property operation is used for xar_dynamic_data
            // @todo check what to do for relational tables
            switch ($operation) {
                case 'hide':
                    break;
                case 'show':
                    $fieldlist[] = $name;
                    break;
                case 'count':
                    $fieldlist[] = "COUNT($name)";
                    $this->object->properties[$name]->operation = 'COUNT';
                    break;
                case 'min':
                    $fieldlist[] = "MIN($name)";
                    $this->object->properties[$name]->operation = 'MIN';
                    break;
                case 'max':
                    $fieldlist[] = "MAX($name)";
                    $this->object->properties[$name]->operation = 'MAX';
                    break;
                case 'avg':
                    $fieldlist[] = "AVG($name)";
                    $this->object->properties[$name]->operation = 'AVG';
                    break;
                case 'sum':
                    $fieldlist[] = "SUM($name)";
                    $this->object->properties[$name]->operation = 'SUM';
                    break;
                    // We use a custom operation here that gets translated to a database-specific one by the datastore
                case 'distinct':
                    $fieldlist[] = "COUNT_DISTINCT($name)"; // CHECKME in datastores
                    $this->object->properties[$name]->operation = 'COUNT_DISTINCT';
                    break;
                default:
                    break;
            }
        }

        $info = ['fieldlist' => $fieldlist,
            'groupby'   => $groupby,
            'sort'      => $sort];

        // check if we need to save this report
        $save = null;
        $this->var()->check('save', $save);

        // nothing to show here
        if (empty($fieldlist)) {
            $result = 0;

            // save the report and redirect
        } elseif (!empty($save) && !empty($stats['report']) && $this->object->checkAccess('config')) {
            $this->saveReport($stats['report'], $stats, $info);
            $this->ctl()->redirect($this->ctl()->getObjectUrl(
                $this->object->name,
                'report',
                ['report' => $stats['report']]
            ));
            return true;

            // get the result
        } else {
            // FIXME: support addFilters() when not grouping
            $this->object->getItems($info);
            $result = 1;
        }

        $stats['options'] = ['hide'     => '',
            //'show'     => 'Show', // can't be mixed with group by etc.
            'count'    => 'Count',
            'distinct' => 'Distinct', // CHECKME in datastores
            'min'      => 'Minimum',
            'max'      => 'Maximum',
            'sum'      => 'Sum',
            'avg'      => 'Average'];

        // add data to original method args
        $data = array_replace($args, [
            'object' => $this->object,
            'context' => $this->getContext(),
            'stats'  => $stats,
            'result' => $result,
            'tpltitle' => $this->tpltitle,
            'modtitle' => ucwords($this->object->tplmodule),
        ]);

        $output = $this->template(
            'ui_stats',
            $data
        );

        return $output;
    }

    /**
     * Summary of report
     * @param array<string, mixed> $args
     * @return string
     */
    public function report(array $args = [])
    {
        // set report criteria
        $report = [];
        $criteria = ['report'];
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
        $report['report'] = $this->prep()->text($report['report']);

        if (!isset($this->object)) {
            // set context if available in handler
            $this->object = $this->data()->getObjectList($this->args);
            if (empty($this->object) || (!empty($this->args['object']) && $this->args['object'] != $this->object->name)) {
                $msg = $this->mls()->translate('Object #(1) seems to be unknown', $this->args['object']);
                return $this->ctl()->notFound($msg);
            }

            if (empty($this->tplmodule)) {
                // set in DataObjectDescriptor::getObjectID()
                $this->tplmodule = $this->object->tplmodule;
            }
        } else {
            // set context if available in handler
            $this->object->setContext($this->getContext());
        }
        assert($this->object instanceof DataObjectList);

        $title = $this->mls()->translate('Report for #(1)', $this->object->label);
        $this->tpl()->setPageTitle($this->prep()->text($title));

        if (!$this->object->checkAccess('view')) {
            $msg = $this->mls()->translate('View #(1) is forbidden', $this->object->label);
            return $this->ctl()->forbidden($msg);
        }

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
            // FIXME: support addFilters() when not grouping
            $this->object->getItems($info);
            $result = 1;
        }

        // add data to original method args
        $data = array_replace($args, [
            'object' => $this->object,
            'context' => $this->getContext(),
            'report' => $report,
            'result' => $result,
            'tpltitle' => $this->tpltitle,
            'modtitle' => ucwords($this->object->tplmodule),
        ]);

        $output = $this->template(
            'ui_report',
            $data
        );

        return $output;
    }

    /**
     * We use a custom operation here that gets translated to a database-specific one by the datastore
     * @param string $field
     * @param string $format
     * @return string
     */
    public function getTimestampField($field, $format) // CHECKME for all database types
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
     * @param string $field
     * @param string $format
     * @return string
     */
    public function getDateField($field, $format) // CHECKME for all database types
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

    /**
     * Summary of getReportList
     * @return array<mixed>
     */
    public function getReportList()
    {
        $serialreports = $this->mod()->getVar('reportlist.' . $this->object->name);
        if (!empty($serialreports)) {
            $reportlist = unserialize($serialreports);
        } else {
            $reportlist = [];
        }
        return $reportlist;
    }

    /**
     * Summary of getReport
     * @param string $report
     * @return array<mixed>
     */
    public function getReport($report)
    {
        $key = 'report.' . $this->object->name . '.' . $report;
        if (strlen($key) > 64) {
            $key = 'report.' . md5($key);
        }
        $serialinfo = $this->mod()->getVar($key);
        if (!empty($serialinfo)) {
            $info = unserialize($serialinfo);
        } else {
            $info = [];
        }
        return $info;
    }

    /**
     * Summary of saveReport
     * @param string $report
     * @param array<mixed> $stats
     * @param array<mixed> $info
     * @return void
     */
    public function saveReport($report, $stats, $info)
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
            $this->mod()->setVar('reportlist.' . $this->object->name, serialize($reportlist));
        }
        // add stats to info so we can edit it afterwards
        $info['stats'] = $stats;
        $key = 'report.' . $this->object->name . '.' . $report;
        if (strlen($key) > 64) {
            $key = 'report.' . md5($key);
        }
        $this->mod()->setVar($key, serialize($info));
    }

    /**
     * Summary of deleteReport
     * @param string $report
     * @return void
     */
    public function deleteReport($report)
    {
        $key = 'report.' . $this->object->name . '.' . $report;
        if (strlen($key) > 64) {
            $key = 'report.' . md5($key);
        }
        $this->mod()->setVar($key, null);
    }
}
