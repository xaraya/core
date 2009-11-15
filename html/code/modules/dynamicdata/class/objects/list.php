<?php
/**
 * DataObject List
 * Note : for performance reasons, we won't use an array of objects here,
 *        but a single object with an array of item values
 *
 * @package modules
 * @subpackage dynamicdata
 *
 **/

sys::import('modules.dynamicdata.class.objects.master');
sys::import('modules.dynamicdata.class.objects.interfaces');

// FIXME: only needed for the DataPropertyMaster::DD_* constants - handle differently ?
//sys::import('modules.dynamicdata.class.properties.master');

class DataObjectList extends DataObjectMaster implements iDataObjectList
{
    public $itemids  = array();           // the list of item ids used in data stores
    public $where    = '';
    public $sort     = '';
    public $groupby  = array();
    public $numitems = null;
    public $startnum = null;
    public $count    = 0;           // specify if you want DD to count items before getting them (e.g. for the pager)

    public $startstore = null;      // the data store we should start with (for sort)

    public $items = array();       // the result array of itemid => (property name => value)
    public $itemcount = null;       // the number of items given by countItems()

// CHECKME: should exclude DISPLAYONLY here, as well as DISABLED (and IGNORED ?)
//    public $status      = 65;           // inital status is active and can add/modify

    public $fieldsummary = null;          // do we show a summary for numeric fields (sum, min, max, avg, ...) ?
    public $fieldsummarylabel = null;     // what label should we use in the options for this summary ?

    /**
     * Inherits from DataObjectMaster and sets the requested item ids, sort, where, ...
     *
     * @param $args['itemids'] array of item ids to return
     * @param $args['sort'] sort field(s)
     * @param $args['where'] WHERE clause to be used as part of the selection
     * @param $args['numitems'] number of items to retrieve
     * @param $args['startnum'] start number
     * @param $args['count'] count items first before you get them (on demand only)
     */
    public function __construct(DataObjectDescriptor $descriptor)
    {
        // get the object type information from our parent class
        $this->loader($descriptor);

        // Set the configuration parameters
        $args = $descriptor->getArgs();
        if (!empty($args['config'])) {
            try {
                $configargs = unserialize($args['config']);
                foreach ($configargs as $key => $value) $this->{$key} = $value;
                $this->configuration = $configargs;
            } catch (Exception $e) {}
        }

        // Set the arguments passed via the constructor. These override the configurations settings
        $this->setArguments($args);

        // Get a reference to each property's value
        foreach ($this->properties as $property) {
            $this->configuration['property_' . $property->name] = array('type' => &$property->type, 'value' => &$property->value);
        }

        // Get a reference to each property's value
        $this->configuration['items'] =& $this->items;
    }

    /**
     * Set arguments for the DataObjectList class
     *
     * @param array
     */
    public function setArguments(Array $args = array())
    {
        if (empty($args)) return true;
        foreach ($args as $key => $value) $this->{$key} = $value;
        // Make sure we have an array for itemids and groupings
        if (!is_array($this->itemids)) {
            if(is_numeric($this->itemids)) {
                $this->itemids = array($this->itemids);
            } elseif(is_string($this->itemids)) {
                $this->itemids = explode(',',$this->itemids);
            }
        }
        if (!is_array($this->groupby)) $this->groupby = explode(',',$this->groupby);

// CHECKME: this should filter the fieldlist based on the status as well - cfr. master.php
        // If a fieldlist was passed, only get the appropriate datastores
        if (isset($args['fieldlist'])) $this->getDataStores(true);

        // REMOVEME: secondary is now always false
        // add where clause if itemtype is one of the properties (e.g. articles)
        if(isset($this->secondary) && !empty($this->itemtype) && $this->objectid > 2 && $this->filter) {
            if(empty($this->where)) {
                $this->where = $this->secondary . ' eq ' . $this->itemtype;
            } else {
                $this->where .= ' and ' . $this->secondary . ' eq ' . $this->itemtype;
            }
        }
        // Note: they can be empty here, which means overriding any previous criteria
        foreach(array_keys($this->datastores) as $name) {
            // make sure we don't have some left-over sort criteria
            $this->datastores[$name]->cleanSort();
            // make sure we don't have some left-over where clauses
            $this->datastores[$name]->cleanWhere();
            // make sure we don't have some left-over group by fields
            $this->datastores[$name]->cleanGroupBy();
            if(isset($args['cache']))
                // pass the cache value to the datastores
                $this->datastores[$name]->cache = $args['cache'];
        }
        $this->setSort($this->sort);
        $this->setWhere($this->where);
        $this->setGroupBy($this->groupby);
//        $this->setCategories($this->catid);

    }

    /**
     * Set sort portion of query
     *
     * @param string sort
     */
    public function setSort($sort)
    {
        if(is_array($sort)) {
            $this->sort = $sort;
        } else {
            $this->sort = explode(',',$sort);
        }
        foreach($this->sort as $criteria) {
            // split off trailing ASC or DESC
            if(preg_match('/^(.+)\s+(ASC|DESC)\s*$/',$criteria,$matches)) {
                $criteria = trim($matches[1]);
                $sortorder = $matches[2];
            } else {
                $sortorder = 'ASC';
            }

            if(isset($this->properties[$criteria])) {
                // pass the sort criteria to the right data store
                $datastore = $this->properties[$criteria]->datastore;
                // assign property to datastore if necessary
                if(empty($datastore)) {
                    list($storename, $storetype) = $this->properties[$criteria]->getDataStore();
                    if(!isset($this->datastores[$storename]))
                        $this->addDataStore($storename, $storetype);

                    $this->properties[$criteria]->datastore = $storename;
                    $this->datastores[$storename]->addField($this->properties[$criteria]); // use reference to original property
                    $datastore = $storename;
                }
                elseif($this->properties[$criteria]->type == 21)
                    $this->datastores[$datastore]->addField($this->properties[$criteria]); // use reference to original property

                $this->datastores[$datastore]->addSort($this->properties[$criteria],$sortorder);

                // if we're sorting on some field, we should start querying by the data store that holds it
                if (!isset($this->startstore))
                   $this->startstore = $datastore;
            }
        }
    }

    /**
     * Add where clause for a property
     *
     * @param string $name property name
     * @param string $clause SQL clause, e.g. = 123, IN ('this', 'that'),  LIKE '%something%', etc.
     * @param string $join '' for the first, 'and' or 'or' for the next
     * @param string $pre optional pre (
     * @param string $post optional post )
     */
    public function addWhere($name, $clause, $join='', $pre='', $post='')
    {
        if (!isset($this->properties[$name])) return;

        // pass the where clause to the right data store
        $datastore = $this->properties[$name]->datastore;
        // assign property to datastore if necessary
        if(empty($datastore)) {
            list($storename, $storetype) = $this->properties[$name]->getDataStore();
            if(!isset($this->datastores[$storename]))
                $this->addDataStore($storename, $storetype);

            $this->properties[$name]->datastore = $storename;
            $this->datastores[$storename]->addField($this->properties[$name]); // use reference to original property
            $datastore = $storename;
        } elseif($this->properties[$name]->type == 21)
            $this->datastores[$datastore]->addField($this->properties[$name]); // use reference to original property

        if ($datastore == '_dummy_') {
            // CHECKME: could the dummy datastore actually do something here ?
            return;
        }

        $this->datastores[$datastore]->addWhere(
            $this->properties[$name],
            $clause,
            $join,
            $pre,
            $post
        );
    }

    /**
     * Set where clause
     *
     * @param mixed where string or array of name => value pairs
     */
    public function setWhere($where)
    {
        if (empty($where)) {
            return;

        } elseif (is_array($where)) {
            $join = '';
            foreach ($where as $name => $val) {
                if (empty($name) || !isset($val) || $val === '') continue;
                if (!isset($this->properties[$name])) continue;
                if (is_numeric($val)) {
                    $mywhere = " = " . $val;
                } elseif (is_string($val)) {
                    $val = str_replace("'","\\'",$val);
                    $mywhere = " = '" . $val . "'";
                } elseif (is_array($val) && count($val) > 0) {
                    if (is_numeric($val[0])) {
                        $mywhere = " IN (" . implode(", ", $val) . ")";
                    } elseif (is_string($val[0])) {
                        $val = str_replace("'","\\'",$val);
                        $mywhere = " IN ('" . implode("', '", $val) . "')";
                    } else {
                        continue;
                    }
                } else {
                    continue;
                }
                $this->addWhere($name, $mywhere, $join);

                // default AND when using array format
                $join = 'and';
            }
            return;
        }

        // find all single-quoted pieces of text with and/or and replace them first, to
        // allow where clauses like : title eq 'this and that' and body eq 'here or there'
        $idx = 0;
        $found = array();
        if(preg_match_all("/'(.*?)'/",$where,$matches)) {
            foreach($matches[1] as $match) {
                // skip if it doesn't contain and/or
                if(!preg_match('/\s+(and|or)\s+/',$match))
                    continue;

                $found[$idx] = $match;
                $match = preg_quote($match);

                $match = str_replace("#","\#",$match);

                $where = trim(preg_replace("#'$match'#","'~$idx~'",$where));
                $idx++;
            }
        }

        // cfr. BL compiler - adapt as needed (I don't think == and === are accepted in SQL)
        $findLogic      = array(' eq ', ' ne ', ' lt ', ' gt ', ' id ', ' nd ', ' le ', ' ge ');
        $replaceLogic   = array( ' = ', ' != ',  ' < ',  ' > ',  ' = ', ' != ', ' <= ', ' >= ');

        $where = str_replace($findLogic, $replaceLogic, $where);

        // TODO: reject multi-source WHERE clauses :-)
        if (empty($where)) {
            $parts = array();
        } else {
            $parts = preg_split('/\s+(and|or)\s+/',$where,-1,PREG_SPLIT_DELIM_CAPTURE);
            $join = '';
        }
        foreach($parts as $part) {
            if($part == 'and' || $part == 'or') {
                $join = $part;
                continue;
            }

            $pieces = preg_split('/\s+/',$part);
            $pre = '';
            $post = '';
            $name = array_shift($pieces);
            if($name == '(') {
                $pre = '(';
                $name = array_shift($pieces);
            }

            $last = count($pieces) - 1;
            if($pieces[$last] == ')') {
                $post = ')';
                array_pop($pieces);
            }

            // sanity check on SQL
            if(count($pieces) < 2) {
                $msg = 'Invalid #(1) for #(2) function #(3)() in module #(4)';
                $vars = array('query ' . $where, 'DataObjectList', 'getWhere', 'DynamicData');
                throw new BadParameterException($vars,$msg);
            }

            if(isset($this->properties[$name])) {
                if(empty($idx)) {
                    $mywhere = join(' ',$pieces);
                } else {
                    $mywhere = '';
                    foreach($pieces as $piece) {
                        // replace the pieces again if necessary
                        if(preg_match("#'~(\d+)~'#",$piece,$matches) && isset($found[$matches[1]])) {
                            $original = $found[$matches[1]];
                            $piece = preg_replace("#'~(\d+)~'#","'$original'",$piece);
                        }
                        $mywhere .= $piece . ' ';
                    }
                }
                $this->addWhere($name, $mywhere, $join, $pre, $post);
            }
        }
    }

    /**
     * Set Group By
     *
     * @param mixed groupby
     * @todo make param not mixed
     */
    public function setGroupBy($groupby)
    {
        foreach($this->groupby as $name) {
            if(isset($this->properties[$name])) {
                // pass the sort criteria to the right data store
                $datastore = $this->properties[$name]->datastore;
                // assign property to datastore if necessary
                if(empty($datastore)) {
                    list($storename, $storetype) = $this->properties[$name]->getDataStore();
                    if(!isset($this->datastores[$storename])) {
                        $this->addDataStore($storename, $storetype);
                    }

                    $this->properties[$name]->datastore = $storename;
                    $this->datastores[$storename]->addField($this->properties[$name]); // use reference to original property
                    $datastore = $storename;
                }
                elseif($this->properties[$name]->type == 21) {
                    $this->datastores[$datastore]->addField($this->properties[$name]); // use reference to original property
                }
                $this->datastores[$datastore]->addGroupBy($this->properties[$name]);
                // if we're grouping by some field, we should start querying by the data store that holds it
                if(!isset($this->startstore)) {
                   $this->startstore = $datastore;
                }
            }
        }
    }

    /**
     * Set categories for an object (work in progress - do not use)
     *
     * @param cids array of category ids
     * @param andcids bool get items assigned to all the cids (AND = true) or any of the cids (OR = false)
     */
    public function setCategories($cids, $andcids = false)
    {
        if(!xarModIsAvailable('categories')) return;

        if (!empty($cids) && is_numeric($cids)) {
            $cids = array($cids);
        }

        if (!is_array($cids) || count($cids) == 0) return;

        $categoriesdef = xarMod::apiFunc(
            'categories','user','leftjoin',
            array(
                'modid' => $this->moduleid,
                'itemtype' => $this->itemtype,
                'cids' => $cids,
                'andcids' => $andcids,
                // unused options - do they have any benefit for dd lists ?
                //'iids' => array(),    // only for these items - too early for dd here ?
                //'cidtree' => array(), // match any category in the tree(s) below the cid(s)
                //'groupcids' => null,  // group categories by 2 (typically) to show the items per combination in a category matrix
            )
        );

        foreach(array_keys($this->datastores) as $name) {
            $this->datastores[$name]->addJoin(
                $categoriesdef['table'],
                $categoriesdef['field'],
                array(),
                $categoriesdef['where'],
                'and',
                $categoriesdef['more']
            );
        }
    }

    /**
     * Get Items
     *
     * @return array
     */
    public function &getItems(Array $args = array())
    {
        // initialize the items array
        $this->items = array();

        // set/override the different arguments (item ids, sort, where, numitems, startnum, ...)
        $this->setArguments($args);

// CHECKME: this should filter the fieldlist based on the status as well - cfr. master.php
        //echo var_dump($this->fieldlist);

        if(empty($args['numitems'])) {
            $args['numitems'] = $this->numitems;
        }
        if(empty($args['startnum'])) {
            $args['startnum'] = $this->startnum;
        }
        // if we don't have a start store yet, but we do have a primary datastore, we'll start there
        if(empty($this->startstore) && !empty($this->primary)) {
            $this->startstore = $this->properties[$this->primary]->datastore;
        }

        // count the items first if we haven't done so yet, but only on demand (args['count'] = 1)
        if (!empty($this->count) && !isset($this->itemcount)) {
            $this->countItems();
        }

       // first get the items from the start store (if any)
        if(!empty($this->startstore)) {
            $this->datastores[$this->startstore]->getItems($args);

            // check if we found something - if not, no sense looking further
            if(count($this->itemids) == 0) return $this->items;
        }
        // then retrieve the other info about those items
        foreach(array_keys($this->datastores) as $name) {
            if(!empty($this->startstore) && $name == $this->startstore) {
                continue;
            }

            $this->datastores[$name]->getItems($args);
        }
        return $this->items;
    }

    /**
     * Count the number of items that match the selection criteria
     *
     * Note : this must be called *before* getItems() if you're using numitems !
     */
    public function countItems(Array $args = array())
    {
        // set/override the different arguments (item ids, sort, where, numitems, startnum, ...)
        $this->setArguments($args);

        // initialize the itemcount
        $this->itemcount = null;

        // if we don't have a start store yet, but we do have a primary datastore, we'll count there
        if(empty($this->startstore) && !empty($this->primary)) {
            $this->startstore = $this->properties[$this->primary]->datastore;
        }
        // try to count the items in the start store (if any)
        if(!empty($this->startstore)) {
            $this->itemcount = $this->datastores[$this->startstore]->countItems($args);
            return $this->itemcount;
        } else {
            // If we don't have a start store, we're probably stuck,
            // but we'll try the first one anyway :)
            // TODO: find some better way to determine which data store to count in
            foreach(array_keys($this->datastores) as $name) {
                // this looks like a loop but it isnt :-) (yet)
                $this->itemcount = $this->datastores[$name]->countItems($args);
                return $this->itemcount;
            }
        }
    }

    /**
     * Show a view of an object
     *
     * @return xarTplObject
     */
    public function showView(Array $args = array())
    {
        $args = $this->toArray($args);
        // Note: we do NOT retrieve the items again here
        //$this->getItems($args);

        if(!empty($this->status)) {
            $state = $this->status;
        } else {
            $state = DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE;
        }
        $args['properties'] = array();
        if (!empty($args['fieldlist']) && !is_array($args['fieldlist'])) {
            $args['fieldlist'] = explode(',',$args['fieldlist']);
            if (!is_array($args['fieldlist'])) throw new Exception('Badly formed fieldlist attribute');
        }
        if(count($args['fieldlist']) > 0) {
            foreach($args['fieldlist'] as $field) {
                $name = trim($field);
                if(isset($this->properties[$name])) {
                    if(($this->properties[$name]->getDisplayStatus() == ($state & DataPropertyMaster::DD_DISPLAYMASK))
                    || ($this->properties[$name]->getDisplayStatus() == DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE)
                    || ($this->properties[$name]->getDisplayStatus() == DataPropertyMaster::DD_DISPLAYSTATE_VIEWONLY)
                    || ($this->properties[$name]->getDisplayStatus() == DataPropertyMaster::DD_DISPLAYSTATE_IGNORED)
                    ) {
                        $args['properties'][$name] =& $this->properties[$name];
                    }
                }
            }
        } else {
            foreach($this->properties as $name => $property)
                if(($this->properties[$name]->getDisplayStatus() == ($state & DataPropertyMaster::DD_DISPLAYMASK))
                || ($this->properties[$name]->getDisplayStatus() == DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE)
                || ($this->properties[$name]->getDisplayStatus() == DataPropertyMaster::DD_DISPLAYSTATE_VIEWONLY)
                || ($this->properties[$name]->getDisplayStatus() == DataPropertyMaster::DD_DISPLAYSTATE_IGNORED)
                ) {
                        $args['properties'][$name] =& $this->properties[$name];
                }

            // Order the fields if this is an extended object
            if (!empty($this->fieldorder)) {
                $tempprops = array();
                foreach ($this->fieldorder as $field)
                    if (isset($args['properties'][$field]))
                        $tempprops[$field] = $args['properties'][$field];
                $args['properties'] = $tempprops;
            }
        }

        $args['items'] =& $this->items;

        // add link to display the item
        if(empty($args['linktype']))  $args['linktype'] = $this->linktype;
        if(empty($args['linkfunc']))  $args['linkfunc'] = $this->linkfunc;
        if(empty($args['linklabel'])) $args['linklabel'] = xarML('Display');
        if(empty($args['param']))     $args['param'] = $this->urlparam;
        if(empty($args['linkfield'])) $args['linkfield'] = '';

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

        if(empty($itemtype)) $itemtype = 0; // don't add to URL
        $args['table'] = !empty($this->table) ? $this->table : null;
        $args['objectname'] = !empty($this->name) ? $this->name : null;
        $args['objectlabel'] = !empty($this->label) ? $this->label : null;
        $args['modname'] = $modname;
        $args['itemtype'] = $itemtype;
        $args['objectid'] = $this->objectid;
        $args['links'] = array();

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
            $this->cached_urls = array();
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
                $args['links'][$itemid] = array('display' => array('otitle' => $this->fieldsummarylabel,
                                                                   'olink'  => '',
                                                                   'ojoin'  => ''));
            }
        }

        if(!empty($this->groupby)) {
            foreach(array_keys($args['properties']) as $name) {
                if(!empty($this->properties[$name]->operation))
                    $this->properties[$name]->label = $this->properties[$name]->operation . '(' . $this->properties[$name]->label . ')';
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
        if (!isset($this->startnum)) $this->startnum = 1;

        $args['object'] = $this;
        return xarTplObject($args['tplmodule'],$args['template'],'showview',$args);
    }

    public function getSortURL($currenturl = null)
    {
        if (empty($currenturl)) {
            $currenturl = xarServer::getCurrentURL(array('startnum' => null, 'sort' => null));
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
      * @return array
      *
      * @todo make this smarter
      */
    public function getViewOptions($itemid = null)
    {
        $options = array();

        $is_user = 1;
/*
// CHECKME: further optimise for anonymous access by assuming they can't delete (or edit) ?
        if (xarUserIsLoggedIn()) {
            $is_user = 1;
        } else {
            $is_user = 0;
        }
*/

        // Assume normal rules for access control, i.e. Delete > Edit > Read
        if ($is_user && xarSecurityCheck('DeleteDynamicDataItem',0,'Item',$this->moduleid.':'.$this->itemtype.':'.$itemid))  {
            $allow_delete = 1;
            $allow_edit = 1;
            $allow_read = 1;
        } elseif ($is_user && xarSecurityCheck('EditDynamicDataItem',0,'Item',$this->moduleid.':'.$this->itemtype.':'.$itemid)) {
            $allow_delete = 0;
            $allow_edit = 1;
            $allow_read = 1;
        } elseif (xarSecurityCheck('ReadDynamicDataItem',0,'Item',$this->moduleid.':'.$this->itemtype.':'.$itemid)) {
            $allow_delete = 0;
            $allow_edit = 0;
            $allow_read = 1;
        } else {
            return $options;
        }

        if ($allow_read) {
            $options['display'] = array('otitle' => xarML('Display'),
                                        'oicon'  => 'display.png',
                                        'olink'  => $this->getActionURL('display', $itemid),
                                        'ojoin'  => '');
        }
        if ($allow_edit) {
            $options['modify'] = array('otitle' => xarML('Edit'),
                                       'oicon'  => 'modify.png',
                                       'olink'  => $this->getActionURL('modify', $itemid),
                                       'ojoin'  => '|');
        }
        // extra options when showing the dynamic objects themselves
        if ($allow_edit && $this->objectid == 1) {
            $options['modifyprops'] = array('otitle' => xarML('Properties'),
                                            'oicon'  => 'modify-config.png',
                                            'olink'  => $this->getActionURL('modifyprop', $itemid),
                                            'ojoin'  => '|');
            $options['viewitems'] = array('otitle' => xarML('Items'),
                                          'oicon'  => 'item-list.png',
                                          'olink'  => $this->getActionURL('viewitems', $itemid),
                                          'ojoin'  => '|'
                                         );
        }
        if ($allow_delete)  {
            $options['delete'] = array('otitle' => xarML('Delete'),
                                       'oicon'  => 'delete.png',
                                       'olink'  => $this->getActionURL('delete', $itemid),
                                       'ojoin'  => '|');
        }

        return $options;
    }

    /**
     * Get the labels and values to include in some output view for these items
     *
     * @return array
     */
    public function &getViewValues(Array $args = array())
    {
        if(empty($args['fieldlist'])) {
            $args['fieldlist'] = $this->fieldlist;
        }
        if(count($args['fieldlist']) == 0 && empty($this->status)) {
            $args['fieldlist'] = array_keys($this->properties);
        }
        $viewvalues = array();
        foreach($this->itemids as $itemid) {
            $viewvalues[$itemid] = array();
            foreach($args['fieldlist'] as $name) {
                if(isset($this->properties[$name])) {
                    $label = xarVarPrepForDisplay($this->properties[$name]->label);
                    if(isset($this->items[$itemid][$name])) {
                        $value = $this->properties[$name]->showOutput(array('value' => $this->items[$itemid][$name]));
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
     * @return array
     */
    public function getFieldSummary(Array $fieldsummary = array())
    {
        if (!empty($fieldsummary)) {
            $this->fieldsummary = $fieldsummary;
        }
        if (empty($this->fieldsummary)) {
            return array();
        }

        // standardize operations to upper-case
        foreach (array_keys($this->fieldsummary) as $field) {
            $this->fieldsummary[$field] = strtoupper($this->fieldsummary[$field]);
        }

        // calculate the field summary
        $fieldvalues = array();
        $fieldcount  = array();
        foreach(array_keys($this->items) as $itemid) {
            foreach ($this->fieldsummary as $field => $operation) {
                if (!isset($this->items[$itemid][$field])) continue;
                if (!isset($fieldvalues[$field])) {
                    $fieldvalues[$field] = $this->items[$itemid][$field];
                    $fieldcount[$field] = 1;
                    continue;
                }
                switch ($operation)
                {
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
        $item = array();
        $label = xarML('Summary');
        foreach ($this->fieldsummary as $field => $operation) {
            switch ($operation)
            {
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
     * @return int
     */
    public function getNext(Array $args = array())
    {
        static $start = true;

        if($start) {
            // set/override the different arguments (item ids, sort, where, numitems, startnum, ...)
            $this->setArguments($args);

            if(empty($args['numitems']))
                $args['numitems'] = $this->numitems;
            if(empty($args['startnum']))
                $args['startnum'] = $this->startnum;

            // if we don't have a start store yet, but we do have a primary datastore, we'll start there
            if(empty($this->startstore) && !empty($this->primary)) {
                $this->startstore = $this->properties[$this->primary]->datastore;
            }
            $start = false;
        }

        $itemid = null;
        // first get the items from the start store (if any)
        if(!empty($this->startstore)) {
            $itemid = $this->datastores[$this->startstore]->getNext($args);

            // check if we found something - if not, no sense looking further
            if(empty($itemid)) return;
        }
        return $itemid;
    }
}
?>
