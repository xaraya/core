<?php
/**
 * Dynamic Object User Interface Handler
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */

namespace Xaraya\DataObject\Handlers;

use xarVar;
use xarCache;
use xarObjectCache;
use xarMLS;
use xarMod;
use xarResponse;
use xarTpl;
use DataObjectMaster;
use DataPropertyMaster;
use sys;

sys::import('modules.dynamicdata.class.ui_handlers.default');

/**
 * Dynamic Object User Interface Handler
 *
 */
class SearchHandler extends DefaultHandler
{
    public string $method = 'search'; // or 'query'

    /**
     * Run the ui 'search' and 'query' methods
     *
     * @param array<string, mixed> $args
     * with
     *     $args['method'] the ui method we are handling is 'search' or 'query' here
     *     $args['catid'] optional category for the search
     *     $args['sort'] optional sort for the search
     *     $args['where'] optional where clause(s) for the search
     *     $args['startnum'] optional start number for the search
     *     $args['q'] optional query string for the search
     *     $args['field'] optional field selection for the search
     *     $args['match'] optional match type for the search
     * @return string|void output of xarTpl::object() using 'ui_search'
     */
    public function run(array $args = [])
    {
        if (!xarVar::fetch('catid', 'isset', $args['catid'], null, xarVar::DONT_SET)) {
            return;
        }
        if (!xarVar::fetch('sort', 'isset', $args['sort'], null, xarVar::DONT_SET)) {
            return;
        }
        if (!xarVar::fetch('where', 'isset', $args['where'], null, xarVar::DONT_SET)) {
            return;
        }
        if (!xarVar::fetch('startnum', 'isset', $args['startnum'], null, xarVar::DONT_SET)) {
            return;
        }

        // Note: $args['where'] could be an array, e.g. index.php?object=sample&where[name]=Baby

        if (!xarVar::fetch('q', 'isset', $args['q'], null, xarVar::DONT_SET)) {
            return;
        }
        if (!xarVar::fetch('field', 'isset', $args['field'], null, xarVar::DONT_SET)) {
            return;
        }
        if (!xarVar::fetch('match', 'isset', $args['match'], null, xarVar::DONT_SET)) {
            return;
        }

        if (!empty($args) && is_array($args) && count($args) > 0) {
            $this->args = array_merge($this->args, $args);
        }

        if (!empty($this->args['object']) && !empty($this->args['method'])) {
            // Get a cache key for this object method if it's suitable for object caching
            $cacheKey = xarCache::getObjectKey($this->args['object'], $this->args['method'], $this->args);
            // Check if the object method is cached
            if (!empty($cacheKey) && xarObjectCache::isCached($cacheKey)) {
                // Return the cached object method output
                return xarObjectCache::getCached($cacheKey);
            }
        }

        if ($this->args['method'] == 'query') {
            $output = $this->query();
        } else {
            $output = $this->search();
        }

        // Set the output of the object method in cache
        if (!empty($cacheKey)) {
            xarObjectCache::setCached($cacheKey, $output);
        }
        return $output;
    }

    /**
     * Summary of search
     * @return string
     */
    public function search()
    {
        // set search criteria
        $search = [];
        $criteria = ['q', 'field', 'match'];
        foreach ($criteria as $key) {
            if (isset($this->args[$key])) {
                $search[$key] = $this->args[$key];
            } else {
                $search[$key] = null;
            }
            unset($this->args[$key]);
        }
        // get the list of selected fields
        if (!empty($search['field'])) {
            $search['field'] = array_keys($search['field']);
        } else {
            $search['field'] = [];
        }
        // default match type is 'like' here
        if (empty($search['match'])) {
            $search['match'] = 'like';
        }
        $search['sort'] = null;
        if (isset($this->args['sort'])) {
            $search['sort'] = $this->args['sort'];
        }

        if (!isset($this->object)) {
            $this->object = DataObjectMaster::getObject($this->args);
            if (empty($this->object) || (!empty($this->args['object']) && $this->args['object'] != $this->object->name)) {
                return xarResponse::NotFound(xarMLS::translate('Object #(1) seems to be unknown', $this->args['object']));
            }

            if (empty($this->tplmodule)) {
                $modname = xarMod::getName($this->object->moduleid);
                $this->tplmodule = $modname;
            }
        }
        $title = xarMLS::translate('Search #(1)', $this->object->label);
        xarTpl::setPageTitle(xarVar::prepForDisplay($title));

        if (!$this->object->checkAccess('view')) {
            return xarResponse::Forbidden(xarMLS::translate('Search #(1) is forbidden', $this->object->label));
        }

        if (empty($search['field']) || count($search['field']) < 1) {
            // some common property types where the content is real text (as opposed to dropdown, object ref etc.)
            $is_text_type = [1,2,3,4,5,11,12,13,38];
            // preselect some fields here !?
            foreach ($this->object->properties as $name => $property) {
                if (in_array($property->type, $is_text_type)) {
                    array_push($search['field'], $name);
                }
            }
        }

        // get where clauses
        if (isset($search['q']) && $search['q'] !== '' && !empty($search['field'])) {
            // get the where clause for this value and match type
            $clause = $this->getwhereclause($search['q'], $search['match']);
            $wherelist = [];
            if (!empty($clause)) {
                foreach ($search['field'] as $field) {
                    if (!isset($this->object->properties[$field])) {
                        continue;
                    }
                    $wherelist[$field] = $clause;
                }
            }
        }

        if (empty($wherelist)) {
            $result = null;
        } else {
            // get result list
            $result = DataObjectMaster::getObjectList($this->args);
            if (empty($result) || (!empty($this->args['object']) && $this->args['object'] != $result->name)) {
                return xarResponse::NotFound(xarMLS::translate('Object #(1) seems to be unknown', $this->args['object']));
            }
            // add the where clauses directly here to avoid quoting issues
            $wherestring = '';
            if (!empty($result->where)) {
                //$wherestring = $result->where;
                $join = 'and';
                // TODO: wrap OR statements in (...) below
            } else {
                $join = '';
            }
            foreach ($wherelist as $name => $clause) {
                $result->addWhere($name, $clause, $join);
                $wherestring .= $join . ' ' . $name . ' ' . trim($clause);
                // CHECKME: use OR by default here !
                $join = 'or';
            }
            if ($wherestring != '' && is_object($result->datastore) && get_class($result->datastore) !== 'VariableTableDataStore') {
                $conditions = $result->setWhere($wherestring);
                $result->dataquery->addconditions($conditions);
            }
            // count the items
            $result->countItems();
            // @checkme setArguments() is not applied without arguments
            if (!empty($this->args['sort']) && !is_array($result->sort)) {
                $result->setSort($this->args['sort']);
            }
            // get the items
            $result->getItems();
            // call the view hooks
            $result->callHooks('view');
        }

        // prepare for output
        if (isset($search['q']) && $search['q'] !== '') {
            $search['q'] = xarVar::prepForDisplay($search['q']);
        }
        $search['options'] = ['like'  => '',
                              'start' => 'starts with',
                              'end'   => 'ends with',
                              'eq'    => 'exact match',
                              'in'    => 'in list a,b,c',
                              'gt'    => 'greater than',
                              'lt'    => 'less than',
                              'ne'    => 'not equal to'];

        return xarTpl::object(
            $this->tplmodule,
            $this->object->template,
            'ui_search',
            ['object' => $this->object,
             'search' => $search,
             'result' => $result,
             'tpltitle' => $this->tpltitle]
        );
    }

    /**
     * Summary of query
     * @return string
     */
    public function query()
    {
        // set query criteria
        $query = [];
        $criteria = ['field', 'match'];
        foreach ($criteria as $key) {
            if (isset($this->args[$key])) {
                $query[$key] = $this->args[$key];
            } else {
                $query[$key] = null;
            }
            unset($this->args[$key]);
        }
        // initialize field values if necessary
        if (empty($query['field'])) {
            $query['field'] = [];
        }
        // initialize match types if necessary
        if (empty($query['match'])) {
            $query['match'] = [];
        }
        $query['sort'] = null;
        if (isset($this->args['sort'])) {
            $query['sort'] = $this->args['sort'];
        }

        if (!isset($this->object)) {
            $this->object = DataObjectMaster::getObject($this->args);
            if (empty($this->object) || (!empty($this->args['object']) && $this->args['object'] != $this->object->name)) {
                return xarResponse::NotFound(xarMLS::translate('Object #(1) seems to be unknown', $this->args['object']));
            }

            if (empty($this->tplmodule)) {
                $modname = xarMod::getName($this->object->moduleid);
                $this->tplmodule = $modname;
            }
        }
        $title = xarMLS::translate('Query #(1)', $this->object->label);
        xarTpl::setPageTitle(xarVar::prepForDisplay($title));

        if (!$this->object->checkAccess('view')) {
            return xarResponse::Forbidden(xarMLS::translate('Query #(1) is forbidden', $this->object->label));
        }

        // get where clauses
        $wherelist = [];
        $extralist = [];
        foreach ($query['field'] as $field => $value) {
            if (!isset($this->object->properties[$field])) {
                continue;
            }
            // default match type is 'like' here
            if (empty($query['match'][$field])) {
                $query['match'][$field] = 'like';
            }
            // CHECKME: special treatment of 'range' for numbers and dates ?
            if ($query['match'][$field] == 'range') {
                $field2 = $field . '2';
                if (isset($query['field'][$field2])) {
                    $value2 = $query['field'][$field2];
                } else {
                    $value2 = null;
                }
                // check the range for these two values
                $clauses = $this->checkrange($value, $value2);
                foreach ($clauses as $clause) {
                    if (!empty($clause)) {
                        // we can have several clauses for the same field here
                        $extralist[] = [$field, $clause];
                    }
                }
                continue;
            }
            if (!isset($value) || $value === '') {
                continue;
            }
            // get the where clause for this value and match type
            $clause = $this->getwhereclause($value, $query['match'][$field]);
            if (!empty($clause)) {
                $wherelist[$field] = $clause;
            }
        }

        if (empty($wherelist) && empty($extralist)) {
            $result = null;
        } else {
            // get result list
            $result = DataObjectMaster::getObjectList($this->args);
            if (empty($result) || (!empty($this->args['object']) && $this->args['object'] != $result->name)) {
                return xarResponse::NotFound(xarMLS::translate('Object #(1) seems to be unknown', $this->args['object']));
            }
            // add the where clauses directly here to avoid quoting issues
            $wherestring = '';
            if (!empty($result->where)) {
                $join = 'and';
            } else {
                $join = '';
            }
            foreach ($wherelist as $name => $clause) {
                $result->addWhere($name, $clause, $join);
                $wherestring .= $join . ' ' . $name . ' ' . trim($clause);
                // CHECKME: use AND by default here !
                $join = 'and';
            }
            foreach ($extralist as $extra) {
                $result->addWhere($extra[0], $extra[1], $join);
                $wherestring .= $join . ' ' . $extra[0] . ' ' . trim($extra[1]);
                // CHECKME: use AND by default here !
                $join = 'and';
            }
            if ($wherestring != '' && is_object($result->datastore) && get_class($result->datastore) !== 'VariableTableDataStore') {
                $conditions = $result->setWhere($wherestring);
                $result->dataquery->addconditions($conditions);
            }
            // count the items
            $result->countItems();
            // @checkme setArguments() is not applied without arguments
            if (!empty($this->args['sort']) && !is_array($result->sort)) {
                $result->setSort($this->args['sort']);
            }
            // get the items
            $result->getItems();
            // call the view hooks
            $result->callHooks('view');
        }

        // prepare for output
        foreach (array_keys($query['field']) as $field) {
            if (isset($query['field'][$field]) && $query['field'][$field] !== '') {
                if (!is_array($query['field'][$field])) {
                    $query['field'][$field] = xarVar::prepForDisplay($query['field'][$field]);
                }
            }
        }
        $query['options'] = ['like'  => '',
                             'start' => 'starts with',
                             'end'   => 'ends with',
                             'eq'    => 'exact match',
                             'in'    => 'in list a,b,c',
                             'gt'    => 'greater than',
                             'lt'    => 'less than',
                             'ne'    => 'not equal to'];
        // get the property types in case we want to do more than check the parent class
        $query['proptypes'] = DataPropertyMaster::getPropertyTypes();

        return xarTpl::object(
            $this->tplmodule,
            $this->object->template,
            'ui_query',
            ['object' => $this->object,
             'query'  => $query,
             'result' => $result,
             'tpltitle' => $this->tpltitle]
        );
    }

    /**
     * Get the WHERE clause for a value based on match type
     *
     * @param string $value original value
     * @param string $match match type to apply
     * @return string where clause
     */
    public function getwhereclause($value, $match = 'like')
    {
        // default match type is 'like' here
        if (empty($match)) {
            $match = 'like';
        }
        // escape single quotes
        $value = str_replace("'", "\\'", $value);
        $clause = '';
        switch ($match) {
            case 'start':
                // escape LIKE wildcards
                $value = str_replace('%', '\%', $value);
                $value = str_replace('_', '\_', $value);
                $clause = " LIKE '" . $value . "%'";
                break;

            case 'end':
                // escape LIKE wildcards
                $value = str_replace('%', '\%', $value);
                $value = str_replace('_', '\_', $value);
                $clause = " LIKE '%" . $value . "'";
                break;

            case 'eq':
                if (is_numeric($value)) {
                    $clause = ' = ' . $value;
                } elseif (is_string($value)) {
                    $clause = " = '" . $value . "'";
                }
                break;

            case 'gt':
                if (is_numeric($value)) {
                    $clause = ' > ' . $value;
                } elseif (is_string($value)) {
                    $clause = " > '" . $value . "'";
                }
                break;

            case 'ge':
                if (is_numeric($value)) {
                    $clause = ' >= ' . $value;
                } elseif (is_string($value)) {
                    $clause = " >= '" . $value . "'";
                }
                break;

            case 'lt':
                if (is_numeric($value)) {
                    $clause = ' < ' . $value;
                } elseif (is_string($value)) {
                    $clause = " < '" . $value . "'";
                }
                break;

            case 'le':
                if (is_numeric($value)) {
                    $clause = ' <= ' . $value;
                } elseif (is_string($value)) {
                    $clause = " <= '" . $value . "'";
                }
                break;

            case 'ne':
                if (is_numeric($value)) {
                    $clause = ' != ' . $value;
                } elseif (is_string($value)) {
                    $clause = " != '" . $value . "'";
                }
                break;

            case 'in':
                if (is_string($value)) {
                    $value = explode(',', $value);
                }
                if (count($value) > 0) {
                    if (is_numeric($value[0])) {
                        $clause = ' IN (' . implode(', ', $value) . ')';
                    } elseif (is_string($value[0])) {
                        $clause = " IN ('" . implode("', '", $value) . "')";
                    }
                }
                break;

            case 'like':
            default:
                // escape LIKE wildcards
                $value = str_replace('%', '\%', $value);
                $value = str_replace('_', '\_', $value);
                $clause = " LIKE '%" . $value . "%'";
                break;
        }
        return $clause;
    }

    /**
     * Check the range for two values and return the WHERE clause(s)
     *
     * @param ?string $value1 first value
     * @param ?string $value2 second value
     * @return array<mixed> where clause(s)
     */
    public function checkrange($value1, $value2)
    {
        $clauses = [];
        if (isset($value1) && $value1 !== '' && isset($value2) && $value2 !== '') {
            if ($value1 !== $value2) {
                // greater than or equal to the first value
                $clause = $this->getwhereclause($value1, 'ge');
                if (!empty($clause)) {
                    $clauses[] = $clause;
                }
                // less than or equal to the second value
                $clause = $this->getwhereclause($value2, 'le');
                if (!empty($clause)) {
                    $clauses[] = $clause;
                }
            } else {
                // equal to the value
                $clause = $this->getwhereclause($value1, 'eq');
                if (!empty($clause)) {
                    $clauses[] = $clause;
                }
            }
        } elseif (isset($value1) && $value1 !== '') {
            // greater than or equal to the first value
            $clause = $this->getwhereclause($value1, 'ge');
            if (!empty($clause)) {
                $clauses[] = $clause;
            }
        } elseif (isset($value2) && $value2 !== '') {
            // less than or equal to the second value
            $clause = $this->getwhereclause($value2, 'le');
            if (!empty($clause)) {
                $clauses[] = $clause;
            }
        } else {
        }
        return $clauses;
    }
}
