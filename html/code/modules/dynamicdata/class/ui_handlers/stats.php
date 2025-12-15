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
     * @return string|void output of $this->render() using 'ui_stats'
     */
    public function run(array $args = [])
    {
        $xar = $this->getStaticServices();
        $xar->var()->check('catid', $args['catid']);
        $xar->var()->check('sort', $args['sort']);
        $xar->var()->check('where', $args['where']);
        $xar->var()->check('startnum', $args['startnum']);

        // Note: $args['where'] could be an array, e.g. index.php?object=sample&where[name]=Baby

        $xar->var()->check('group', $args['group']);
        $xar->var()->check('field', $args['field']);
        $xar->var()->check('match', $args['match']);
        $xar->var()->check('report', $args['report']);

        if (!empty($args) && is_array($args) && count($args) > 0) {
            $this->args = array_merge($this->args, $args);
        }

        // override numitems for groups !?
        $this->args['numitems'] = 0;

        $cacheKey = null;
        if (!empty($this->args['object']) && !empty($this->args['method'])) {
            // Get a cache key for this object method if it's suitable for object caching
            $cacheKey = $xar->cache()->getObjectKey($this->args['object'], $this->args['method'], $this->args);
            // Check if the object method is cached
            if ($xar->cache()->hasObject($cacheKey)) {
                // Return the cached object method output
                return $xar->cache()->getObject($cacheKey);
            }
        }

        if ($this->args['method'] == 'report') {
            $output = $this->report($args);
        } else {
            $output = $this->stats($args);
        }

        // Set the output of the object method in cache
        $xar->cache()->setObject($cacheKey, $output);
        return $output;
    }

    /**
     * Summary of stats
     * @param array<string, mixed> $args
     * @return bool|string
     */
    public function stats(array $args = [])
    {
        $xar = $this->getStaticServices();
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
        $stats['report'] = $xar->prep()->text($stats['report']);

        if (!isset($this->object)) {
            // set context if available in handler
            $this->object = $xar->data()->getObjectList($this->args);
            if (empty($this->object) || (!empty($this->args['object']) && $this->args['object'] != $this->object->name)) {
                $msg = $xar->mls()->translate('Object #(1) seems to be unknown', $this->args['object']);
                return $xar->ctl()->notFound($msg);
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

        $title = $xar->mls()->translate('Statistics for #(1)', $this->object->label);
        $xar->tpl()->setPageTitle($xar->prep()->text($title));

        if (!$this->object->checkAccess('view')) {
            $msg = $xar->mls()->translate('View #(1) is forbidden', $this->object->label);
            return $xar->ctl()->forbidden($msg);
        }

        $modName = $this->getModName();
        $reports = new StatsReports($modName, $this->object->name, $xar);

        // load previously defined report if available
        if (!empty($stats['report']) && empty($stats['group']) && empty($stats['field']) && empty($stats['match'])) {
            $info = $reports->getReport($stats['report']);
            if (!empty($info) && !empty($info['stats'])) {
                $stats = $info['stats'];
            }
        }

        // get the property types in case we want to do more than check the type
        $proptypes = $xar->prop()->getPropertyTypes();

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

        // add field operations
        $operations = $this->setFieldOperations($stats, $groupby);
        $fieldlist = array_merge($fieldlist, $operations);

        $info = ['fieldlist' => $fieldlist,
            'groupby'   => $groupby,
            'sort'      => $sort];

        // check if we need to save this report
        $save = null;
        $xar->var()->check('save', $save);

        // nothing to show here
        if (empty($fieldlist)) {
            $result = 0;

            // save the report and redirect
        } elseif (!empty($save) && !empty($stats['report']) && $this->object->checkAccess('config')) {
            $reports->saveReport($stats['report'], $stats, $info);
            $xar->ctl()->redirect($xar->ctl()->getObjectUrl(
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
            'stats'  => $stats,
            'result' => $result,
            'tpltitle' => $this->tpltitle,
            'modtitle' => ucwords($this->object->tplmodule),
        ]);

        $output = $this->render(
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
        $xar = $this->getStaticServices();
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
        $report['report'] = $xar->prep()->text($report['report']);

        if (!isset($this->object)) {
            // set context if available in handler
            $this->object = $xar->data()->getObjectList($this->args);
            if (empty($this->object) || (!empty($this->args['object']) && $this->args['object'] != $this->object->name)) {
                $msg = $xar->mls()->translate('Object #(1) seems to be unknown', $this->args['object']);
                return $xar->ctl()->notFound($msg);
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

        $title = $xar->mls()->translate('Report for #(1)', $this->object->label);
        $xar->tpl()->setPageTitle($xar->prep()->text($title));

        if (!$this->object->checkAccess('view')) {
            $msg = $xar->mls()->translate('View #(1) is forbidden', $this->object->label);
            return $xar->ctl()->forbidden($msg);
        }

        $modName = $this->getModName();
        $reports = new StatsReports($modName, $this->object->name, $xar);

        $report['reportlist'] = $reports->getReportList();

        if (!empty($report['reportlist']) && in_array($report['report'], $report['reportlist'])) {
            $info = $reports->getReport($report['report']);
            if (!empty($info['stats']) && !empty($info['groupby'])) {
                $this->setFieldOperations($info['stats'], $info['groupby']);
            }
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
            'report' => $report,
            'result' => $result,
            'tpltitle' => $this->tpltitle,
            'modtitle' => ucwords($this->object->tplmodule),
        ]);

        $output = $this->render(
            'ui_report',
            $data
        );

        return $output;
    }

    /**
     * Summary of setFieldOperations
     * @param array<string, mixed> $stats
     * @param list<string> $groupby
     * @return list<string>
     */
    public function setFieldOperations($stats, $groupby)
    {
        $fieldlist = [];
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
        return $fieldlist;
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
}
