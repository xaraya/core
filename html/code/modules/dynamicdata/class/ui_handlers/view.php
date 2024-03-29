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
use xarDB;
use xarTpl;
use DataObjectFactory;
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
     * @return string|void output of xarTpl::object() using 'ui_view'
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
        if (!empty($args['where']) && is_array($args['where'])) {
            $args['where'] = array_filter($args['where']);
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

        // check if we want a subset of fields here (projection)
        $this->checkFieldList();

        if (!isset($this->object)) {
            // set context if available in handler
            $this->object = DataObjectFactory::getObjectList($this->args, $this->getContext());
            if (empty($this->object) || (!empty($this->args['object']) && $this->args['object'] != $this->object->name)) {
                return xarResponse::NotFound(xarMLS::translate('Object #(1) seems to be unknown', $this->args['object']));
            }

            if (empty($this->tplmodule)) {
                $modname = xarMod::getName($this->object->moduleid);
                $this->tplmodule = $modname;
            }
        } else {
            // set context if available in handler
            $this->object->setContext($this->getContext());
        }

        $title = xarMLS::translate('View #(1)', $this->object->label);
        xarTpl::setPageTitle(xarVar::prepForDisplay($title));

        if (!$this->object->checkAccess('view')) {
            $this->getContext()?->setStatus(403);
            return xarResponse::Forbidden(xarMLS::translate('View #(1) is forbidden', $this->object->label));
        }

        if (!empty($this->args['where']) && is_array($this->args['where']) && is_object($this->object->datastore)) {
            // key white-list filter - https://www.php.net/manual/en/function.array-intersect-key.php
            $allowed = array_flip(array_keys($this->object->properties));
            $this->args['where'] = array_intersect_key($this->args['where'], $allowed);
            // Need the database connection for quoting strings.
            $dbconn = xarDB::getConn();
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

        $output = xarTpl::object(
            $this->tplmodule,
            $this->object->template,
            'ui_view',
            ['object'   => $this->object,
             'context'  => $this->getContext(),
             'tpltitle' => $this->tpltitle]
        );

        // Set the output of the object method in cache
        if (!empty($cacheKey)) {
            xarObjectCache::setCached($cacheKey, $output);
        }
        return $output;
    }
}
