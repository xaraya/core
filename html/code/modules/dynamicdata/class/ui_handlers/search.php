<?php
/**
 * Dynamic Object User Interface Handler
 * @package modules
 * @copyright see the html/credits.html file in this release
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
class DataObjectSearchHandler extends DataObjectDefaultHandler
{
    public $method = 'search'; // or 'query'

    /**
     * Run the ui 'search' and 'query' methods
     *
     * @param $args['method'] the ui method we are handling is 'search' or 'query' here
     * @param $args['catid'] optional category for the search
     * @param $args['sort'] optional sort for the search
     * @param $args['where'] optional where clause(s) for the search
     * @param $args['startnum'] optional start number for the search
     * @param $args['q'] optional query string for the search
     * @param $args['field'] optional field selection for the search
     * @param $args['match'] optional match type for the search
     * @return string output of xarTplObject() using 'ui_search'
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

        if(!xarVarFetch('q',        'isset', $args['q'],        NULL, XARVAR_DONT_SET)) 
            return;
        if(!xarVarFetch('field',    'isset', $args['field'],    NULL, XARVAR_DONT_SET)) 
            return;
        if(!xarVarFetch('match',    'isset', $args['match'],    NULL, XARVAR_DONT_SET)) 
            return;

        if(!empty($args) && is_array($args) && count($args) > 0) 
            $this->args = array_merge($this->args, $args);

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

    function search()
    {
        // set search criteria
        $search = array();
        $criteria = array('q', 'field', 'match');
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
            $search['field'] = array();
        }
        // default match type is 'like' here
        if (empty($search['match'])) {
            $search['match'] = 'like';
        }

        if(!isset($this->object)) 
        {
            $this->object =& DataObjectMaster::getObject($this->args);
            if(empty($this->object) || (!empty($this->args['object']) && $this->args['object'] != $this->object->name)) 
                return xarResponse::NotFound(xarML('Object #(1) seems to be unknown', $this->args['object']));

            if(empty($this->tplmodule)) 
            {
                $modname = xarMod::getName($this->object->moduleid);
                $this->tplmodule = $modname;
            }
        }
        $title = xarML('Search #(1)', $this->object->label);
        xarTplSetPageTitle(xarVarPrepForDisplay($title));

        if (!$this->object->checkAccess('view'))
            return xarResponse::Forbidden(xarML('Search #(1) is forbidden', $this->object->label));

        if (empty($search['field']) || count($search['field']) < 1) {
            // some common property types where the content is real text (as opposed to dropdown, object ref etc.)
            $is_text_type = array(1,2,3,4,5,11,12,13,38);
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
            $wherelist = array();
            if (!empty($clause)) {
                foreach ($search['field'] as $field) {
                    if (!isset($this->object->properties[$field])) continue;
                    $wherelist[$field] = $clause;
                }
            }
        }

        if (empty($wherelist)) {
            $result = null;
        } else {
            // get result list
            $result =& DataObjectMaster::getObjectList($this->args);
            if(empty($result) || (!empty($this->args['object']) && $this->args['object'] != $result->name)) 
                return xarResponse::NotFound(xarML('Object #(1) seems to be unknown', $this->args['object']));
            // add the where clauses directly here to avoid quoting issues
            if (!empty($result->where)) {
                $join = 'and';
                // TODO: wrap OR statements in (...) below
            } else {
                $join = '';
            }
            foreach ($wherelist as $name => $clause) {
                $result->addWhere($name, $clause, $join);
                // CHECKME: use OR by default here !
                $join = 'or';
            }
            // count the items
            $result->countItems();
            // get the items
            $result->getItems();
            // call the view hooks
            $result->callHooks('view');
        }

        // prepare for output
        if (isset($search['q']) && $search['q'] !== '') {
            $search['q'] = xarVarPrepForDisplay($search['q']);
        }
        $search['options'] = array('like'  => '',
                                   'start' => 'starts with',
                                   'end'   => 'ends with',
                                   'eq'    => 'exact match',
                                   'in'    => 'in list a,b,c',
                                   'gt'    => 'greater than',
                                   'lt'    => 'less than',
                                   'ne'    => 'not equal to');

        return xarTplObject(
            $this->tplmodule, $this->object->template, 'ui_search',
            array('object' => $this->object,
                  'search' => $search,
                  'result' => $result,
                  'tpltitle' => $this->tpltitle)
        );
    }

    function query()
    {
        // set query criteria
        $query = array();
        $criteria = array('field', 'match');
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
            $query['field'] = array();
        }
        // initialize match types if necessary
        if (empty($query['match'])) {
            $query['match'] = array();
        }

        if(!isset($this->object)) 
        {
            $this->object =& DataObjectMaster::getObject($this->args);
            if(empty($this->object) || (!empty($this->args['object']) && $this->args['object'] != $this->object->name)) 
                return xarResponse::NotFound(xarML('Object #(1) seems to be unknown', $this->args['object']));

            if(empty($this->tplmodule)) 
            {
                $modname = xarMod::getName($this->object->moduleid);
                $this->tplmodule = $modname;
            }
        }
        $title = xarML('Query #(1)', $this->object->label);
        xarTplSetPageTitle(xarVarPrepForDisplay($title));

        if (!$this->object->checkAccess('view'))
            return xarResponse::Forbidden(xarML('Query #(1) is forbidden', $this->object->label));

        // get where clauses
        $wherelist = array();
        $extralist = array();
        foreach ($query['field'] as $field => $value) {
            if (!isset($this->object->properties[$field])) continue;
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
                        $extralist[] = array($field, $clause);
                    }
                }
                continue;
            }
            if (!isset($value) || $value === '') continue;
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
            $result =& DataObjectMaster::getObjectList($this->args);
            if(empty($result) || (!empty($this->args['object']) && $this->args['object'] != $result->name)) 
                return xarResponse::NotFound(xarML('Object #(1) seems to be unknown', $this->args['object']));
            // add the where clauses directly here to avoid quoting issues
            if (!empty($result->where)) {
                $join = 'and';
            } else {
                $join = '';
            }
            foreach ($wherelist as $name => $clause) {
                $result->addWhere($name, $clause, $join);
                // CHECKME: use AND by default here !
                $join = 'and';
            }
            foreach ($extralist as $extra) {
                $result->addWhere($extra[0], $extra[1], $join);
                // CHECKME: use AND by default here !
                $join = 'and';
            }
            // count the items
            $result->countItems();
            // get the items
            $result->getItems();
            // call the view hooks
            $result->callHooks('view');
        }

        // prepare for output
        foreach (array_keys($query['field']) as $field) {
            if (isset($query['field'][$field]) && $query['field'][$field] !== '') {
                if (!is_array($query['field'][$field])) {
                    $query['field'][$field] = xarVarPrepForDisplay($query['field'][$field]);
                }
            }
        }
        $query['options'] = array('like'  => '',
                                   'start' => 'starts with',
                                   'end'   => 'ends with',
                                   'eq'    => 'exact match',
                                   'in'    => 'in list a,b,c',
                                   'gt'    => 'greater than',
                                   'lt'    => 'less than',
                                   'ne'    => 'not equal to');
        // get the property types in case we want to do more than check the parent class
        $query['proptypes'] = DataPropertyMaster::getPropertyTypes();

        return xarTplObject(
            $this->tplmodule, $this->object->template, 'ui_query',
            array('object' => $this->object,
                  'query'  => $query,
                  'result' => $result,
                  'tpltitle' => $this->tpltitle)
        );
    }

    /**
     * Get the WHERE clause for a value based on match type
     *
     * @param string $value original value
     * @param string $match match type to apply
     * @return string where clause
     */
    function getwhereclause($value, $match = 'like')
    {
        // default match type is 'like' here
        if (empty($match)) {
            $match = 'like';
        }
        // escape single quotes
        $value = str_replace("'", "\\'", $value);
        $clause = '';
        switch ($match)
        {
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
     * @param string $value1 first value
     * @param string $value2 second value
     * @return array where clause(s)
     */
    function checkrange($value1,$value2)
    {
        $clauses = array();
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

?>
