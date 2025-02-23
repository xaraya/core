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

use DataObject;
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
     * @return string|void output of data()->template() using 'ui_search'
     */
    public function run(array $args = [])
    {
        $this->var()->check('catid', $args['catid']);
        $this->var()->check('sort', $args['sort']);
        $this->var()->check('where', $args['where']);
        $this->var()->check('startnum', $args['startnum']);

        // Note: $args['where'] could be an array, e.g. index.php?object=sample&where[name]=Baby

        $this->var()->check('q', $args['q']);
        $this->var()->check('field', $args['field']);
        $this->var()->check('match', $args['match']);

        if (!empty($args) && is_array($args) && count($args) > 0) {
            $this->args = array_merge($this->args, $args);
        }

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

        if ($this->args['method'] == 'query') {
            $output = $this->query($args);
        } else {
            $output = $this->search($args);
        }

        // Set the output of the object method in cache
        $this->cache()->setObject($cacheKey, $output);
        return $output;
    }

    /**
     * Summary of search
     * @param array<string, mixed> $args
     * @return string
     */
    public function search(array $args = [])
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
            // set context if available in handler
            $this->object = $this->data()->getObject($this->args);
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
        assert($this->object instanceof DataObject);

        $title = $this->mls()->translate('Search #(1)', $this->object->label);
        $this->tpl()->setPageTitle($this->var()->prep($title));

        if (!$this->object->checkAccess('view')) {
            $msg = $this->mls()->translate('Search #(1) is forbidden', $this->object->label);
            return $this->ctl()->forbidden($msg);
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
            // set context if available in handler
            $result = $this->data()->getObjectList($this->args);
            if (empty($result) || (!empty($this->args['object']) && $this->args['object'] != $result->name)) {
                $msg = $this->mls()->translate('Object #(1) seems to be unknown', $this->args['object']);
                return $this->ctl()->notFound($msg);
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
            if ($wherestring != '' && is_object($result->datastore) && $result->datastore->getClassName() === 'RelationalDataStore') {
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
            $search['q'] = $this->var()->prep($search['q']);
        }
        $search['options'] = ['like'  => '',
                              'start' => 'starts with',
                              'end'   => 'ends with',
                              'eq'    => 'exact match',
                              'in'    => 'in list a,b,c',
                              'gt'    => 'greater than',
                              'lt'    => 'less than',
                              'ne'    => 'not equal to'];

        // add data to original method args
        $data = array_replace($args, [
            'object' => $this->object,
            'context' => $this->getContext(),
            'search' => $search,
            'result' => $result,
            'tpltitle' => $this->tpltitle,
        ]);

        return $this->data()->template(
            'ui_search',
            $data
        );
    }

    /**
     * Summary of query
     * @param array<string, mixed> $args
     * @return string
     */
    public function query(array $args = [])
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
            // set context if available in handler
            $this->object = $this->data()->getObject($this->args);
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
        assert($this->object instanceof DataObject);

        $title = $this->mls()->translate('Query #(1)', $this->object->label);
        $this->tpl()->setPageTitle($this->var()->prep($title));

        if (!$this->object->checkAccess('view')) {
            $msg = $this->mls()->translate('Query #(1) is forbidden', $this->object->label);
            return $this->ctl()->forbidden($msg);
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
            // set context if available in handler
            $result = $this->data()->getObjectList($this->args);
            if (empty($result) || (!empty($this->args['object']) && $this->args['object'] != $result->name)) {
                $msg = $this->mls()->translate('Object #(1) seems to be unknown', $this->args['object']);
                return $this->ctl()->notFound($msg);
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
            if ($wherestring != '' && is_object($result->datastore) && $result->datastore->getClassName() === 'RelationalDataStore') {
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
                    $query['field'][$field] = $this->var()->prep($query['field'][$field]);
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
        $query['proptypes'] = $this->prop()->getPropertyTypes();

        // add data to original method args
        $data = array_replace($args, [
            'object' => $this->object,
            'context' => $this->getContext(),
            'query'  => $query,
            'result' => $result,
            'tpltitle' => $this->tpltitle,
        ]);

        return $this->data()->template(
            'ui_query',
            $data
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
