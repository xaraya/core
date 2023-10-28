<?php
/**
 * DataObject List
 * Note : for performance reasons, we won't use an array of objects here,
 *        but a single object with an array of item values
 *
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 **/

sys::import('modules.dynamicdata.class.objects.master');
sys::import('modules.dynamicdata.class.objects.interfaces');
sys::import('xaraya.datastores.sql');
use Xaraya\DataObject\DataStores\SQLDataStore;

class DataObjectList extends DataObjectMaster implements iDataObjectList
{
    public $prelist  = true;           // Run preList methods or not
    public $itemids  = [];        // The list of item ids used in data stores
    public $sort     = [];
    public $groupby  = [];        // The list of property names to group by (if any) - see also isgrouped
    public $numitems = null;
    public $startnum = null;
    public $count    = 0;              // Specify if you want DD to count items before getting them (e.g. for the pager)

    public $items = [];           // The result array of itemid => (property name => value)
    public $itemcount = 0;             // The number of items given by countItems()

    public $fieldsummary = null;       // Do we show a summary for numeric fields (sum, min, max, avg, ...) ?
    public $fieldsummarylabel = null;  // What label should we use in the options for this summary ?

    public $ddwhere;
    public $ddsort;
    public $filters;
    private $cached_allow;
    public $pagerurl;

    /**
     * Inherits from DataObjectMaster and sets the requested item ids, sort, where, ...
     *
     * @param DataObjectDescriptor $descriptor
     * with
     *     $args['itemids'] array of item ids to return
     *     $args['sort'] sort field(s)
     *     $args['where'] WHERE clause to be used as part of the selection
     *     $args['numitems'] number of items to retrieve
     *     $args['startnum'] start number
     *     $args['count'] count items first before you get them (on demand only)
     */
    public function __construct(DataObjectDescriptor $descriptor)
    {
        // get the object type information from our parent class
        $this->loader($descriptor);

        // Set limits if required
        if (isset($this->numitems) && is_numeric($this->numitems)) {
            $this->dataquery->rowstodo = $this->numitems;
        }
        if (isset($this->startnum) && is_numeric($this->startnum)) {
            $this->dataquery->startat = $this->startnum;
        }

        if (!is_array($this->configuration)) {
            $this->configuration = [];
        }
        // Get a reference to each property's value
        foreach ($this->properties as $property) {
            $this->configuration['property_' . $property->name] = ['type' => &$property->type, 'value' => &$property->value];
        }

        // Get a reference to each property's value
        $this->configuration['items'] = & $this->items;

        // Run the preList methods of some properties, if called for
        // By convention we run this when instantiating, rather than in one of the methods
        if ($this->prelist) {
            $this->runPreList();
        }
    }

    /**
     * Apply as set of filter values to an object's query
     */

    private function addFilterCondition($name, $filter, $value)
    {
        try {
            switch ($filter) {
                case '=':
                    $this->dataquery->eq($this->properties[$name]->source, $value);
                    break;
                case '!=':
                    $this->dataquery->ne($this->properties[$name]->source, $value);
                    break;
                case '>':
                    $this->dataquery->gt($this->properties[$name]->source, $value);
                    break;
                case '<':
                    $this->dataquery->lt($this->properties[$name]->source, $value);
                    break;
                case '>=':
                    $this->dataquery->ge($this->properties[$name]->source, $value);
                    break;
                case '<=':
                    $this->dataquery->le($this->properties[$name]->source, $value);
                    break;
                case 'like':
                    $this->dataquery->like($this->properties[$name]->source, $value);
                    break;
                case 'notlike':
                    $this->dataquery->notlike($this->properties[$name]->source, $value);
                    break;
                case 'null':
                    $this->dataquery->eq($this->properties[$name]->source, null);
                    break;
                case 'notnull':
                    $this->dataquery->ne($this->properties[$name]->source, null);
                    break;
                case 'regex':
                    $this->dataquery->regex($this->properties[$name]->source, $value);
                    break;
            }
        } catch (Exception $e) {
            return false;
        }
        return true;
    }

    public function applyFilters(array $args = [])
    {
        $properties = $this->getProperties($args);
        foreach ($args as $key => $value) {
            $this->addFilterCondition($key, $value['filter'], $value['value']);
        }
        return true;
    }

    public function checkInput(array $args = [], $suppress = 0)
    {
        xarLog::message("DataObjectList::checkInput: Checking items of object " . $this->name, xarLog::LEVEL_INFO);

        $data = ['id' => []];  // = $args; // @checkme is that what we want here?
        // First get the itemids
        if (!xarVar::fetch($this->primary, 'array', $data['id'], [], xarVar::NOT_REQUIRED)) {
            return;
        }
        if (empty($data['id'])) {
            return true;
        }

        // Clean the itemids found
        foreach ($data['id'] as $k => $v) {
            $data['id'][$k] = (int)$v;
        }

        // Get the data from the form
        $formitems = [];
        foreach ($data['id'] as $id) {
            $formitem = [];
            foreach ($this->properties as $name => $property) {
                // Only active or list properties will be checked
                if(!in_array($property->getDisplayStatus(), [DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE,DataPropertyMaster::DD_DISPLAYSTATE_VIEWONLY])) {
                    continue;
                }
                $isvalid = $this->properties[$name]->checkInput($name . "[" . $id . "]");
                if ($isvalid) {
                    $formitem[$name] = $this->properties[$name]->value;
                }
            }
            $formitems[$id] = $formitem;
        }
        // Save the items for reuse
        $this->items = $formitems;
        return $formitems;
    }

    public function updateItems(array $args = [])
    {
        xarLog::message("DataObjectList::updateItems: Updating items of object " . $this->name, xarLog::LEVEL_INFO);

        // Get the items to be updated
        if (isset($args['items'])) {
            $items_to_update = $args['items'];
        } else {
            $items_to_update = $this->items;
        }

        // Next, get the items corresponding to the itemids we have to updateq
        $q = $this->dataquery;
        $primarysource = $this->properties[$this->primary]->source;
        $q->in($primarysource, array_keys($items_to_update));
        $db_items = $this->getItems();

        // Replace the DB data with the data to be updated
        $single_object = DataObjectMaster::getObject(['name' => $this->name]);
        foreach ($db_items as $key => $db_item) {
            // Check if the data changed
            $unchanged = true;
            foreach ($items_to_update[$key] as $field_key => $field_value) {
                if (!isset($db_item[$field_key])) {
                    $msg = xarML("The property '#(1)' could not be updated", $field_key);
                    throw new Exception($msg);
                }
                if ($db_item[$field_key] != $field_value) {
                    $unchanged = false;
                    break;
                }
            }

            // If the data changed, save it
            if (!$unchanged) {
                $db_items[$key] = $items_to_update[$key] + $db_item;
                $single_object->setFieldValues($db_items[$key], 1);
                $single_object->updateItem(['itemid' => $key]);
            }
        }
        return true;
    }

    /**
     * Set arguments for the DataObjectList class
     *
     * @param array<string, mixed> $args
     */
    public function setArguments(array $args = [])
    {
        if (empty($args)) {
            return true;
        }
        foreach ($args as $key => $value) {
            $this->{$key} = $value;
        }
        // Make sure we have an array for itemids, groupings and fieldlist
        if (!is_array($this->itemids)) {
            if(is_numeric($this->itemids)) {
                $this->itemids = [$this->itemids];
            } elseif(is_string($this->itemids)) {
                $this->itemids = explode(',', $this->itemids);
            }
        }
        if (!is_array($this->sort)) {
            $this->sort = explode(',', (string) $this->sort);
        }
        if (!is_array($this->groupby)) {
            $this->groupby = explode(',', (string) $this->groupby);
        }
        if (!is_array($this->fieldlist)) {
            $this->fieldlist = explode(',', (string) $this->fieldlist);
        }
        // Clean up arrays by removing false values (= empty, false, null, 0)
        if (!empty($this->itemids)) {
            $this->itemids = array_filter($this->itemids);
        }
        if (!empty($this->sort)) {
            $this->sort = array_filter($this->sort);
        }
        if (!empty($this->groupby)) {
            $this->groupby = array_filter($this->groupby);
        }
        if (!empty($this->fieldlist)) {
            $this->fieldlist = array_filter($this->fieldlist);
        }

        $this->getDataStore(true);

        // REMOVEME: secondary is now always false
        // add where clause if itemtype is one of the properties (e.g. articles)
        /*
        if(isset($this->secondary) && !empty($this->itemtype) && $this->objectid > 2 && $this->filter) {
            if(empty($this->where)) {
                $this->where = $this->secondary . ' eq ' . $this->itemtype;
            } else {
                $this->where .= ' and ' . $this->secondary . ' eq ' . $this->itemtype;
            }
        }
        */
        // Make sure we don't have an empty datastore
        if (is_object($this->datastore) && $this->datastore instanceof SQLDataStore) {
            // Note: they can be empty here, which means overriding any previous criteria
            // make sure we don't have some left-over sort criteria
            $this->datastore->cleanSort();
            // make sure we don't have some left-over where clauses
            $this->datastore->cleanWhere();
            // make sure we don't have some left-over group by fields
            $this->datastore->cleanGroupBy();
        }
        // @deprecated not actually used in datastores
        if(isset($args['cache'])) {
            // pass the cache value to the datastores
            $this->datastore->cache = $args['cache'];
        }
        $this->setSort($this->sort);
        // add content filters before setWhere()
        $this->addFilters();
        // add selecting on itemids again for relational tables - moved to sql/relational.php
        //if (!empty($this->primary) && !empty($this->itemids)) {
        //    $primarysource = $this->properties[$this->primary]->source;
        //    $this->dataquery->in($primarysource, $this->itemids);
        //}
        $conditions = $this->setWhere($this->where);
        $this->dataquery->addconditions($conditions);
        $this->setGroupBy($this->groupby);

    }

    /**
     * Set sort portion of query
     *
     * @param mixed $sort array or comma-separated string
     */
    public function setSort($sort)
    {
        $this->sort = [];  // FIXME: this should not be necessary

        // Make sure we have an array
        if(is_array($sort)) {
            $this->sort = $sort;
        } elseif (!empty($sort)) {
            $this->sort = explode(',', $sort);
        }

        foreach($this->sort as $criteria) {
            if (empty($criteria)) {
                return true;
            }

            // split off trailing ASC or DESC
            if(preg_match('/^(.+)\s+(ASC|DESC)\s*$/i', $criteria, $matches)) {
                $criteria = trim($matches[1]);
                $sortorder = strtoupper($matches[2]);
            } elseif (substr($criteria, 0, 1) === '-') {
                // reverse sort order if criteria starts with '-'
                $criteria = substr($criteria, 1);
                $sortorder = 'DESC';
            } else {
                $sortorder = 'ASC';
            }

            // Add the field's order clause
            $this->dataquery->addorder($this->properties[$criteria]->source, $sortorder);
            // @todo fix setSort() and/or dataquery to support other datastores than relational ones
            if (is_object($this->datastore) && $this->datastore->getClassName() !== 'RelationalDataStore') {
                $this->addSort($criteria, $sortorder);
            }
        }
    }

    /**
     * Add where clauses for dynamic_data objects in object search ui or deferlink property
     * @todo fix setWhere() and/or dataquery to support other datastores than relational ones
     */
    public function addWhere($name, $clause, $join = '', $pre = '', $post = '')
    {
        if (!array_key_exists($name, $this->properties)) {
            return;
        }
        if (!property_exists($this, 'ddwhere')) {
            $this->ddwhere = [];
        }
        // Result in variabletable:
        // $query .= $whereitem['join'] . ' ' . $whereitem['pre'] . 'dd_' . $whereitem['field'] . ' ' . $whereitem['clause'] . $whereitem['post'] . ' ';
        $whereitem = [
            'join' => $join,
            'pre' => $pre,
            'field' => $this->properties[$name]->id,
            'clause' => $clause,
            'post' => $post,
            // add name for external datastores
            'name' => $name,
        ];
        $this->ddwhere[] = $whereitem;
    }

    /**
     * Add sort order for dynamic_data objects in object lists
     * @todo fix setSort() and/or dataquery to support other datastores than relational ones
     */
    public function addSort($name, $sortorder = 'ASC')
    {
        if (!array_key_exists($name, $this->properties)) {
            return;
        }
        if (!property_exists($this, 'ddsort')) {
            $this->ddsort = [];
        }
        if (!empty($sortorder) && strtoupper($sortorder) == 'DESC') {
            $sortorder = 'DESC';
        } else {
            $sortorder = 'ASC';
        }
        // Result in variabletable:
        //  $query .= $join . 'dd_' . $sortitem['field'] . ' ' . $sortitem['sortorder'];
        $sortitem = [
            'field' => $this->properties[$name]->id,
            'sortorder' => $sortorder,
            // add name for external datastores
            'name' => $name,
        ];
        $this->ddsort[] = $sortitem;
    }

    /**
     * Add content filters to where clauses - do not call directly for now...
     */
    private function addFilters()
    {
        if (!empty($this->filters) && is_string($this->filters)) {
            try {
                $this->filters = unserialize($this->filters);
            } catch (Exception $e) {
                $this->filters = null;
            }
        }
        if (empty($this->filters)) {
            return;
        }

        if (!empty(xarSession::getAnonId()) && xarUser::isLoggedIn()) {
            // get the direct parents of the current user (no ancestors)
            $grouplist = xarCache::getParents();
        } else {
            // check anonymous visitors by themselves
            $anonid = xarConfigVars::get(null, 'Site.User.AnonymousUID');
            $grouplist = [$anonid];
        }

        foreach ($grouplist as $groupid) {
            if (empty($this->filters[$groupid])) {
                continue;
            }
            foreach ($this->filters[$groupid] as $filter) {
                if (!isset($this->properties[$filter[0]])) {
                    // skip filters on unknown properties
                    continue;
                }
                $whereclause = '';
                // TODO: cfr. getwhereclause in search ui
                if ($filter != 'in' && !is_numeric($filter[2])) {
                    // escape single quotes
                    $filter[2] = str_replace("'", "\\'", $filter[2]);
                    $filter[2] = "'"  . $filter[2] . "'";
                }
                switch ($filter[1]) {
                    case 'in':
                        $whereclause = ' IN (' . $filter[2] . ')';
                        break;
                    case 'eq':
                    case 'gt':
                    case 'lt':
                    case 'ne':
                    default:
                        $whereclause = ' ' . $filter[1] . ' ' . $filter[2];
                        break;
                }
                if (!empty($this->where)) {
                    // CHECKME: how about when $this->where is an array ?
                    $this->where .= ' and ' . $filter[0] . $whereclause;
                } else {
                    $this->where = $filter[0] . $whereclause;
                }
            }
            // one group having filters is enough here !?
            return;
        }
    }

    /**
     * Set Group By
     *
     * @param mixed $groupby
     * @todo make param not mixed
     */
    public function setGroupBy($groupby)
    {
        foreach($this->groupby as $name) {
            // If it fails, just ignore it
            try {
                $this->dataquery->addgroup($this->properties[$name]->source);
            } catch (Exception $e) {
            }
        }
    }

    /**
     * Set categories for an object (work in progress - do not use)
     *
     * @param array<int>|int $cids array of category ids
     * @param bool $join_by_and get items assigned to all the cids (AND = true) or any of the cids (OR = false)
     */
    public function setCategories($cids, $join_by_and = false)
    {
        // Support both a single category ID and an array of same
        if (!empty($cids) && is_numeric($cids)) {
            $cids = [$cids];
        }

        // Sanity check: bail if we don't have an array at this point
        if (!is_array($cids) || count($cids) == 0) {
            return true;
        }

        // Check the properties to see if any of them are categories
        // If so, set up conjunction clauses for the object's dataquery
        $conjunctions = [];
        foreach ($this->properties as $property) {
            // If this property is not a category, move on
            if ($property->type != 100) {
                continue;
            }

            $category_idfield = $property->id . "_categories.id";
            if (!$join_by_and) {
                $conjunctions[] = $this->dataquery->pin($category_idfield, $cids);
            } else {
                // Not yet supported
                // The assembly is a bit more complicated, but doable
            }
        }

        // If we found something, then add the conjunctions to the object's dataquery
        if (!empty($conjunctions)) {
            if (!$join_by_and) {
                $this->dataquery->qor($conjunctions);
            } else {
                // Not yet supported
                // The assembly is a bit more complicated, but doable
            }
        }
        return true;
    }

    /**
     * Get Items
     *
     * @return array<mixed>
     */
    public function &getItems(array $args = [])
    {
        xarLog::message("DataObjectList::getItems: Retrieving items of object " . $this->name, xarLog::LEVEL_INFO);

        // Set/override the different arguments (item ids, sort, where, numitems, startnum, ...)
        $this->setArguments($args);

        // CHECKME: this should filter the fieldlist based on the status as well - cfr. master.php

        if(isset($args['count']) && (($args['count'] == 'count') || ($args['count'] == 1))) {
            $itemcount = $this->countitems($args);
        }
        if(empty($args['numitems'])) {
            $args['numitems'] = $this->numitems;
        }
        if(empty($args['startnum'])) {
            $args['startnum'] = $this->startnum;
        }

        // Replace the fieldlist with the fields passed
        $fields = [];
        if(!empty($args['fieldlist'])) {
            $fields = $this->getFieldList();
            $this->setFieldList($args['fieldlist']);
        }

        $this->items = [];
        $this->datastore->getItems($args);

        // For now always show the values of properties with virtual datastore
        // CHECKME: the preList method is probably a better solution
        // $args['getvirtuals'] = true;
        if (!empty($args['getvirtuals'])) {
            // Get the values of properties with virtual datastore and add them to the items array
            foreach ($this->getFieldList() as $fieldname) {
                if (empty($this->properties[$fieldname]->source) || !empty($this->properties[$fieldname]->is_virtual)) {
                    //                    if (method_exists($this->properties[$fieldname],'getItemValue')) {echo "<pre>";echo $fieldname;var_dump($this->items);//exit;
                    foreach ($this->items as $key => $value) {
                        try {
                            $this->items[$key][$fieldname] = $this->properties[$fieldname]->getItemValue($key);
                        } catch (Exception $e) {
                            $this->items[$key][$fieldname] = null;
                        }
                    }
                    //                }
                }
            }
        }

        // Reinstate the original fieldlist
        if(!empty($args['fieldlist'])) {
            $this->setFieldList($fields);
        }

        return $this->items;
    }

    /**
     * Count the number of items that match the selection criteria
     *
     * Note : this must be called *before* getItems() if you're using numitems !
     */
    public function countItems(array $args = [])
    {
        // set/override the different arguments (item ids, sort, where, numitems, startnum, ...)
        $this->setArguments($args);
        $this->itemcount = $this->datastore->countItems($args);
        return $this->itemcount;
    }

    /**
     * Show a view of an object
     *
     * @return string xarTpl::object
     */
    public function showView(array $args = [])
    {
        xarLog::message("DataObjectList::showView: Listing items of object " . $this->name, xarLog::LEVEL_INFO);

        $args = $this->toArray($args);
        // Note: we do NOT retrieve the items again here
        //$this->getItems($args);

        if(!empty($this->status)) {
            $state = $this->status;
        } else {
            $state = DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE;
        }
        $args['properties'] = [];
        if (!empty($args['fieldlist']) && !is_array($args['fieldlist'])) {
            $args['fieldlist'] = explode(',', $args['fieldlist']);
            if (!is_array($args['fieldlist'])) {
                throw new Exception('Badly formed fieldlist attribute');
            }
        }
        if(count($args['fieldlist']) > 0) {
            foreach($args['fieldlist'] as $field) {
                $name = trim($field);
                if(isset($this->properties[$name])) {
                    if(($this->properties[$name]->getDisplayStatus() == ($state & DataPropertyMaster::DD_DISPLAYMASK))
                    || ($this->properties[$name]->getDisplayStatus() == DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE)
                    || ($this->properties[$name]->getDisplayStatus() == DataPropertyMaster::DD_DISPLAYSTATE_VIEWONLY)
                    || ($this->properties[$name]->getDisplayStatus() == DataPropertyMaster::DD_INPUTSTATE_IGNORED)
                    ) {
                        $args['properties'][$name] = & $this->properties[$name];
                    }
                }
            }
        } else {
            foreach($this->properties as $name => $property) {
                if(($this->properties[$name]->getDisplayStatus() == ($state & DataPropertyMaster::DD_DISPLAYMASK))
                || ($this->properties[$name]->getDisplayStatus() == DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE)
                || ($this->properties[$name]->getDisplayStatus() == DataPropertyMaster::DD_DISPLAYSTATE_VIEWONLY)
                || ($this->properties[$name]->getDisplayStatus() == DataPropertyMaster::DD_INPUTSTATE_IGNORED)
                ) {
                    $args['properties'][$name] = & $this->properties[$name];
                }
            }

            // Order the fields if this is an extended object
            if (!empty($this->fieldorder)) {
                $tempprops = [];
                foreach ($this->fieldorder as $field) {
                    if (isset($args['properties'][$field])) {
                        $tempprops[$field] = $args['properties'][$field];
                    }
                }
                $args['properties'] = $tempprops;
            }
        }

        // If we have an items parameter, take it as valid
        if (isset($args['items'])) {
            $this->items = $args['items'];
        } else {
            $args['items'] = & $this->items;
        }

        // add link to display the item
        if(empty($args['linktype'])) {
            $args['linktype'] = $this->linktype;
        }
        if(empty($args['linkfunc'])) {
            $args['linkfunc'] = $this->linkfunc;
        }
        if(empty($args['linklabel'])) {
            $args['linklabel'] = xarML('Display');
        }
        if(empty($args['param'])) {
            $args['param'] = $this->urlparam;
        }
        if(empty($args['linkfield'])) {
            $args['linkfield'] = '';
        }

        // pass some extra template variables for use in BL tags, API calls etc.
        $args['moduleid'] = $this->moduleid;

        $modname = xarMod::getName($this->moduleid);
        $itemtype = $this->itemtype;

        // override for viewing dynamic objects
        if($modname == 'dynamicdata' && $this->itemtype == 0 && empty($this->table)) {
            $args['linktype'] = 'user';
            $args['linkfunc'] = 'display';
            // Don't show link to view items that don't belong to the DD module
        }

        if(empty($itemtype)) {
            $itemtype = 0;
        } // don't add to URL
        $args['table'] = !empty($this->table) ? $this->table : null;
        $args['objectname'] = !empty($this->name) ? $this->name : null;
        $args['objectlabel'] = !empty($this->label) ? $this->label : null;
        $args['modname'] = $modname;
        $args['itemtype'] = $itemtype;
        $args['objectid'] = $this->objectid;
        $args['links'] = [];

        if (empty($args['template']) && !empty($args['objectname'])) {
            $args['template'] = $args['objectname'];
        }
        if(empty($args['tplmodule'])) {
            if(!empty($this->tplmodule)) {
                $args['tplmodule'] = $this->tplmodule;
            } else {
                $args['tplmodule'] = $modname;
            }
        }
        // update current tplmodule, linktype, linkfunc and urlparam if necessary
        $this->tplmodule = $args['tplmodule'];
        $this->linktype = $args['linktype'];
        $this->linkfunc = $args['linkfunc'];
        $this->urlparam = $args['param'];

        sys::import('xaraya.objects');

        // get view options for each item
        if(empty($this->groupby)) {
            // reset cached urls
            $this->cached_urls = [];
            foreach(array_keys($this->items) as $itemid) {
                $args['links'][$itemid] = $this->getViewOptions($itemid);
            }
        }

        // calculate field summary for items
        if (!empty($this->fieldsummary)) {
            $summary = $this->getFieldSummary();
            if (!empty($summary)) {
                // add a dummy item to hold the summary information
                $itemid = 0;
                if (!in_array($itemid, $this->itemids)) {
                    $this->itemids[] = $itemid;
                }
                $this->items[$itemid] = $summary;
                // add view options for the dummy item - last label wins :-)
                $args['links'][$itemid] = ['display' => ['otitle' => $this->fieldsummarylabel,
                                                                   'olink'  => '',
                                                                   'ojoin'  => '']];
            }
        }

        if(!empty($this->groupby)) {
            foreach(array_keys($args['properties']) as $name) {
                if(!empty($this->properties[$name]->operation)) {
                    $this->properties[$name]->label = $this->properties[$name]->operation . '(' . $this->properties[$name]->label . ')';
                }
            }
            $args['linkfield'] = 'N/A';
        }

        if (isset($args['newlink'])) {
            // use pre-defined newlink (if this is an empty string, no link will be shown)
        } else {
            $args['newlink'] = $this->getActionURL('new');
        }

        $args['isprimary'] = !empty($this->primary);
        $args['catid'] = !empty($this->catid) ? $this->catid : null;

        // see if we received an itemcount we can use for the pager
        if (!empty($args['itemcount'])) {
            // the item count was passed to showView() e.g. by dynamicdata_userapi_showview() when setting count="1" in xar:data-view
            $this->itemcount = $args['itemcount'];
        }

        if(empty($args['pagerurl'])) {
            $args['pagerurl'] = '';
        }
        $this->pagerurl = $args['pagerurl'];
        $args['sorturl'] = $this->getSortURL($this->pagerurl);
        if (!isset($this->startnum)) {
            $this->startnum = 1;
        }

        $args['object'] = $this;
        return xarTpl::object($args['tplmodule'], $args['template'], 'showview', $args);
    }

    public function getSortURL($currenturl = null)
    {
        if (empty($currenturl)) {
            $currenturl = xarServer::getCurrentURL(['startnum' => null, 'sort' => null]);
        } else {
            $currenturl = preg_replace('/&amp;(startnum|sort)=(.*)?(&amp;|$)/', '$3', $currenturl);
            $currenturl = preg_replace('/\?(startnum|sort)=(.*)?&amp;/', '?', $currenturl);
            $currenturl = preg_replace('/\?(startnum|sort)=(.*)?$/', '', $currenturl);
        }
        $currenturl .= preg_match('/\?/', $currenturl) ? '&amp;sort' : '?sort';

        return $currenturl;
    }

    /**
      * Get List to fill showView template options
      *
      * @return array<mixed>
      *
      * @todo make this smarter
      */
    public function getViewOptions($itemid = null)
    {
        $options = [];

        $is_user = 1;

        // Work with specific access rules for this object (= valid for all itemids)
        if (!empty($this->access)) {
            // initialize the access options
            if (empty($this->cached_allow)) {
                $this->cached_allow = [];
                $this->cached_allow['display'] = $this->checkAccess('display');
                $this->cached_allow['update'] = $this->checkAccess('update');
                $this->cached_allow['create'] = $this->checkAccess('create');
                $this->cached_allow['delete'] = $this->checkAccess('delete');
            }
            // get the access options
            $allow_delete = $this->cached_allow['delete'];
            $allow_add = $this->cached_allow['create'];
            $allow_edit = $this->cached_allow['update'];
            $allow_read = $this->cached_allow['display'];

            // Assume normal rules for access control, i.e. Delete > Edit > Read
        } elseif ($is_user && $this->checkAccess('delete', $itemid)) {
            $allow_delete = 1;
            $allow_add = 1;
            $allow_edit = 1;
            $allow_read = 1;
        } elseif ($is_user && $this->checkAccess('create', $itemid)) {
            $allow_delete = 0;
            $allow_add = 1;
            $allow_edit = 1;
            $allow_read = 1;
        } elseif ($is_user && $this->checkAccess('update', $itemid)) {
            $allow_delete = 0;
            $allow_add = 0;
            $allow_edit = 1;
            $allow_read = 1;
        } elseif ($this->checkAccess('display', $itemid)) {
            $allow_delete = 0;
            $allow_add = 0;
            $allow_edit = 0;
            $allow_read = 1;
        } else {
            return $options;
        }

        // Limit this to the dynamicdata module and maybe remove it altogether
        // This should be done in the templates
        // It is creating unnecessary shorturl encodes
        $modname = xarMod::getName();
        if ($modname == 'dynamicdata' || $modname == 'object') {
            if ($allow_read) {
                $options['display'] = ['otitle' => xarML('Display'),
                                            'oicon'  => 'display.png',
                                            'olink'  => $this->getActionURL('display', $itemid),
                                            'ojoin'  => ''];
            }
            if ($allow_edit) {
                $options['modify'] = ['otitle' => xarML('Edit'),
                                           'oicon'  => 'modify.png',
                                           'olink'  => $this->getActionURL('modify', $itemid),
                                           'ojoin'  => '|'];
            }
            // extra options when showing the dynamic objects themselves
            if ($allow_edit && $this->objectid == 1) {
                // CHECKME: access should be based on the objects themselves here (but probably too heavy) ?
                $options['modifyprops'] = ['otitle' => xarML('Properties'),
                                                'oicon'  => 'modify-config.png',
                                                'olink'  => $this->getActionURL('modifyprop', $itemid),
                                                'ojoin'  => '|'];
                $options['access'] = ['otitle' => xarML('Access'),
                                                'oicon'  => 'privileges.png',
                                                'olink'  => $this->getActionURL('access', $itemid),
                                                'ojoin'  => '|'];
                $options['viewitems'] = ['otitle' => xarML('Items'),
                                              'oicon'  => 'item-list.png',
                                              'olink'  => $this->getActionURL('viewitems', $itemid),
                                              'ojoin'  => '|',
                                             ];
            }
            //if ($allow_add)  {
            // CHECKME: and/or skip cloning in object interface ?
            //if ($allow_add && $this->linktype != 'object')  {
            // CHECKME: allow cloning only for the dynamic objects themselves ?
            if ($allow_add && $this->objectid == 1) {
                // TODO: define 'clone' as a standard action for objects if we want it, instead of overloading 'modify' action
                $options['clone'] = ['otitle' => xarML('Clone'),
                                           'oicon'  => 'add.png',
                                           'olink'  => $this->getActionURL('modify', $itemid, ['tab' => 'clone']),
                                           'ojoin'  => '|'];
            }
            if ($allow_delete) {
                $options['delete'] = ['otitle' => xarML('Delete'),
                                           'oicon'  => 'delete.png',
                                           'olink'  => $this->getActionURL('delete', $itemid),
                                           'ojoin'  => '|'];
            }
        }
        return $options;
    }

    /**
     * Get the labels and values to include in some output view for these items
     *
     * @return array<mixed>
     */
    public function &getViewValues(array $args = [])
    {
        if(empty($args['fieldlist'])) {
            $args['fieldlist'] = $this->fieldlist;
        }
        if (!is_array($args['fieldlist'])) {
            $args['fieldlist'] = explode(',', $args['fieldlist']);
            if (!is_array($args['fieldlist'])) {
                throw new Exception('Badly formed fieldlist attribute');
            }
        }

        if(count($args['fieldlist']) == 0 && empty($this->status)) {
            $args['fieldlist'] = $this->getFieldList();
        }
        $viewvalues = [];
        foreach($this->itemids as $itemid) {
            $viewvalues[$itemid] = [];
            foreach($args['fieldlist'] as $name) {
                if(isset($this->properties[$name])) {
                    $label = xarVar::prepForDisplay($this->properties[$name]->label);
                    if(isset($this->items[$itemid][$name])) {
                        $value = $this->properties[$name]->showOutput(['value' => $this->items[$itemid][$name]]);
                    } else {
                        $value = '';
                    }
                    $viewvalues[$itemid][$label] = $value;
                }
            }
        }
        return $viewvalues;
    }

    /**
     * Get field summary based on requested operation per field (sum, min, max, avg, ...)
     *
     * @return array<mixed>
     */
    public function getFieldSummary(array $fieldsummary = [])
    {
        if (!empty($fieldsummary)) {
            $this->fieldsummary = $fieldsummary;
        }
        if (empty($this->fieldsummary)) {
            return [];
        }

        // standardize operations to upper-case
        foreach (array_keys($this->fieldsummary) as $field) {
            $this->fieldsummary[$field] = strtoupper($this->fieldsummary[$field]);
        }

        // calculate the field summary
        $fieldvalues = [];
        $fieldcount  = [];
        foreach(array_keys($this->items) as $itemid) {
            foreach ($this->fieldsummary as $field => $operation) {
                if (!isset($this->items[$itemid][$field])) {
                    continue;
                }
                if (!isset($fieldvalues[$field])) {
                    $fieldvalues[$field] = $this->items[$itemid][$field];
                    $fieldcount[$field] = 1;
                    continue;
                }
                switch ($operation) {
                    case 'AVG':
                        $fieldcount[$field] += 1;
                        $fieldvalues[$field] += $this->items[$itemid][$field];
                        break;
                    case 'SUM':
                        $fieldvalues[$field] += $this->items[$itemid][$field];
                        break;
                    case 'MAX':
                        if ($fieldvalues[$field] < $this->items[$itemid][$field]) {
                            $fieldvalues[$field] = $this->items[$itemid][$field];
                        }
                        break;
                    case 'MIN':
                        if ($fieldvalues[$field] > $this->items[$itemid][$field]) {
                            $fieldvalues[$field] = $this->items[$itemid][$field];
                        }
                        break;
                }
            }
        }

        // fill in the summary item
        $item = [];
        $label = xarML('Summary');
        foreach ($this->fieldsummary as $field => $operation) {
            switch ($operation) {
                case 'AVG':
                    if (isset($fieldvalues[$field]) && !empty($fieldcount[$field])) {
                        $item[$field] = $fieldvalues[$field] / $fieldcount[$field];
                    }
                    $label = xarML('Current Average');
                    break;
                case 'SUM':
                    if (isset($fieldvalues[$field])) {
                        $item[$field] = $fieldvalues[$field];
                    }
                    $label = xarML('Current Total');
                    break;
                case 'MAX':
                    if (isset($fieldvalues[$field])) {
                        $item[$field] = $fieldvalues[$field];
                    }
                    $label = xarML('Current Maximum');
                    break;
                case 'MIN':
                    if (isset($fieldvalues[$field])) {
                        $item[$field] = $fieldvalues[$field];
                    }
                    $label = xarML('Current Minimum');
                    break;
            }
        }
        // set label for the view options of the field summary item - last label wins :-)
        if (!isset($this->fieldsummarylabel)) {
            $this->fieldsummarylabel = $label;
        }
        return $item;
    }

    /**
     * Get items one at a time, instead of storing everything in $this->items
     *
     * @return integer
     * @deprecated 2.2.0 relies on old datastore fields instead of object properties
     */
    public function getNext(array $args = [])
    {
        static $start = true;

        if($start) {
            // set/override the different arguments (item ids, sort, where, numitems, startnum, ...)
            $this->setArguments($args);

            if(empty($args['numitems'])) {
                $args['numitems'] = $this->numitems;
            }
            if(empty($args['startnum'])) {
                $args['startnum'] = $this->startnum;
            }
        }

        $itemid = $this->datastore->getNext($args);
        return $itemid;
    }

    private function runPreList()
    {
        // Run the preList methods of some properties
        $i = 0;
        foreach ($this->getFieldList() as $fieldname) {
            // Only properties that are configured to display in lists
            $display_status = $this->properties[$fieldname]->getDisplayStatus();
            if (!in_array($display_status, [DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE,
                                                 DataPropertyMaster::DD_DISPLAYSTATE_VIEWONLY,
                                                 DataPropertyMaster::DD_DISPLAYSTATE_HIDDEN])) {
                continue;
            }
            $this->properties[$fieldname]->preList();
        }
    }
}
