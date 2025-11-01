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
use sys;

sys::import('modules.dynamicdata.class.ui_handlers.default');

/**
 * Dynamic Object User Interface Handler
 *
 */
class ViewHandler extends DefaultHandler
{
    public string $method = 'view';

    /**
     * Run the ui 'view' method
     *
     * @param array<string, mixed> $args
     * with
     *     $args['method'] the ui method we are handling is 'view' here
     *     $args['catid'] optional category for the view
     *     $args['sort'] optional sort for the view
     *     $args['where'] optional where clause(s) for the view
     *     $args['startnum'] optional start number for the view
     * @return string|void output of data()->template() using 'ui_view'
     */
    public function run(array $args = [])
    {
        $this->var()->check('catid', $args['catid']);
        $this->var()->check('sort', $args['sort']);
        $this->var()->check('where', $args['where']);
        $this->var()->check('startnum', $args['startnum']);

        // Note: $args['where'] could be an array, e.g. index.php?object=sample&where[name]=Baby
        if (!empty($args['where']) && is_array($args['where'])) {
            $args['where'] = array_filter($args['where']);
        }

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

        // check if we want a subset of fields here (projection)
        $this->checkFieldList();

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

        $title = $this->mls()->translate('View #(1)', $this->object->label);
        $this->tpl()->setPageTitle($this->prep()->text($title));

        if (!$this->object->checkAccess('view')) {
            $msg = $this->mls()->translate('View #(1) is forbidden', $this->object->label);
            return $this->ctl()->forbidden($msg);
        }

        if (!empty($this->args['where']) && is_array($this->args['where']) && is_object($this->object->datastore)) {
            // key white-list filter - https://www.php.net/manual/en/function.array-intersect-key.php
            $allowed = array_flip(array_keys($this->object->properties));
            $this->args['where'] = array_intersect_key($this->args['where'], $allowed);
            // Need the database connection for quoting strings.
            $dbconn = $this->db()->getConn();
            if ($this->object->datastore->getClassName() === 'RelationalDataStore') {
                $wherelist = [];
                foreach ($this->args['where'] as $key => $value) {
                    if (is_numeric($value)) {
                        $wherelist[] = "$key eq $value";
                    } else {
                        $wherelist[] = "$key eq " . $dbconn->qstr($value);
                    }
                }
                $wherestring = implode(' and ', $wherelist);
                $conditions = $this->object->setWhere($wherestring);
                $this->object->dataquery->addconditions($conditions);
            } else {
                $join = '';
                foreach ($this->args['where'] as $key => $value) {
                    if (is_numeric($value)) {
                        $clause = "= $value";
                    } else {
                        $clause = "= " . $dbconn->qstr($value);
                    }
                    $this->object->addWhere($key, $clause, $join);
                    $join = 'and';
                }
            }
        }

        $this->object->countItems();

        // @checkme setArguments() is not applied without arguments
        if (!empty($this->args['sort']) && !is_array($this->object->sort)) {
            $this->object->setSort($this->args['sort']);
        }
        $this->object->getItems();

        $this->object->callHooks('view');

        // add data to original method args
        $data = array_replace($args, [
            'object'   => $this->object,
            'context'  => $this->getContext(),
            'tpltitle' => $this->tpltitle,
            'modtitle' => ucwords($this->object->tplmodule),
        ]);

        $output = $this->data()->template(
            'ui_view',
            $data
        );

        // Set the output of the object method in cache
        $this->cache()->setObject($cacheKey, $output);
        return $output;
    }
}
