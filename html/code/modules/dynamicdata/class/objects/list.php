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

    public $startstore = null;      // the data store we should start with (for sort)

    public $items = array();       // the result array of itemid => (property name => value)

    // optional URL style for use in xarModURL() (defaults to itemtype=...&...)
    public $urlstyle = 'itemtype'; // TODO: table or object, or wrapper for all, or all in template, or...
    // optional link type for use in xarModURL() (defaults to 'user', could be 'object')
    public $linktype = 'user';
    // optional link function for use in xarModURL() (defaults to 'display', could be 'main')
    public $linkfunc = 'display';

// CHECKME: should exclude DISPLAYONLY here, as well as DISABLED (and IGNORED ?)
//    public $status      = 65;           // inital status is active and can add/modify

    /**
     * Inherits from DataObjectMaster and sets the requested item ids, sort, where, ...
     *
     * @param $args['itemids'] array of item ids to return
     * @param $args['sort'] sort field(s)
     * @param $args['where'] WHERE clause to be used as part of the selection
     * @param $args['numitems'] number of items to retrieve
     * @param $args['startnum'] start number
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
     * Set where clause
     *
     * @param string where
     */
    public function setWhere($where)
    {
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
                $this->datastores[$datastore]->addWhere(
                    $this->properties[$name],
                    $mywhere,
                    $join,
                    $pre,
                    $post
                );
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
     * Set categories for an object
     *
     */
    public function setCategories($catid)
    {
        if(!xarModIsAvailable('categories')) return;

        $categoriesdef = xarMod::apiFunc(
            'categories','user','leftjoin',
            array(
                'modid' => $this->moduleid,
                'itemtype' => $this->itemtype,
                'catid' => $catid
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

        // if we don't have a start store yet, but we do have a primary datastore, we'll count there
        if(empty($this->startstore) && !empty($this->primary)) {
            $this->startstore = $this->properties[$this->primary]->datastore;
        }
        // try to count the items in the start store (if any)
        if(!empty($this->startstore)) {
            return $this->datastores[$this->startstore]->countItems($args);
        } else {
            // If we don't have a start store, we're probably stuck,
            // but we'll try the first one anyway :)
            // TODO: find some better way to determine which data store to count in
            foreach(array_keys($this->datastores) as $name) {
                // this looks like a loop but it isnt :-) (yet)
                return $this->datastores[$name]->countItems($args);
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
            foreach($args['fieldlist'] as $name) {
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
            $linktype = 'user';
            $linkfunc = 'view';
            $args['linktype'] = $linktype;
            $args['linkfunc'] = $linkfunc;
            // Don't show link to view items that don't belong to the DD module
        } else {
            $linktype = $args['linktype'];
            $linkfunc = $args['linkfunc'];
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
        foreach(array_keys($this->items) as $itemid) {
            // TODO: improve this + SECURITY !!!
            $options = array();
            if(!empty($this->groupby)) {
                $args['links'][$itemid] = $options;
                continue;
            }
             $args['itemid'] = $itemid;
            // @todo let's be a lil more explicit in handling these options
            $args['links'][$itemid] = $this->getViewOptions($args);
        }
        if(!empty($this->groupby)) {
            foreach(array_keys($args['properties']) as $name) {
                if(!empty($this->properties[$name]->operation))
                    $this->properties[$name]->label = $this->properties[$name]->operation . '(' . $this->properties[$name]->label . ')';
            }
            $args['linkfield'] = 'N/A';
        }

        $args['isprimary'] = !empty($this->primary);
        $args['catid'] = !empty($this->catid) ? $this->catid : null;

        if(empty($args['pagerurl'])) {
            $args['pagerurl'] = '';
        }
        list(
            $args['prevurl'],
            $args['nexturl'],
            $args['sorturl']) = $this->getPager($args['pagerurl']);

        $args['object'] = $this;
        return xarTplObject($args['tplmodule'],$args['template'],'showview',$args);
    }

    /**
      * Get List to fill showView template options
      *
      * @return array
      *
      * @todo make this smarter
      */
    public function getViewOptions(Array $args = array())
    {
        extract($args);

        $urlargs = array();
        $urlargs['table'] = $table;
        $urlargs[$args['param']] = $itemid;
        $urlargs['tplmodule'] = $args['tplmodule'];
        // The next 3 lines make the DD modify/display routines work for overlay objects
        // TODO: do we need the concept of tplmodule at all? Good question :-)
/* passed by showView() $args above
        $info = DataObjectMaster::getObjectInfo($args);
        $urlargs['name'] = $info['name'];
*/
        $urlargs['name'] = $args['objectname'];

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
            $tplmodule = $this->checkModuleFunction($args['tplmodule'], $linktype, $linkfunc);
// CHECKME: who was using 'view' instead of 'display' for links directly in templates (besides DD itself) ?
            $options['display'] = array('otitle' => xarML('Display'),
                                        'olink'  => xarModURL($tplmodule,$linktype,$linkfunc,$urlargs),
                                        'ojoin'  => '');
        }
        if ($allow_edit) {
            // if $linktype == 'object' use function 'main' everywhere and set $urlargs['method']
            if ($args['tplmodule'] == 'dynamicdata' && $linktype == 'object' && $linkfunc == 'main') {
                // prepend method to urlargs
                $urlargs2 = array('method' => 'update') + $urlargs;
                unset($urlargs2['tplmodule']);
                $options['modify'] = array('otitle' => xarML('Edit'),
                                           'olink'  => xarModURL($tplmodule,$linktype,$linkfunc,$urlargs2),
                                           'ojoin'  => '|');
            } else {
                $tplmodule = $this->checkModuleFunction($args['tplmodule'], 'admin', 'modify');
                $options['modify'] = array('otitle' => xarML('Edit'),
                                           'olink'  => xarModURL($tplmodule,'admin','modify',$urlargs),
                                           'ojoin'  => '|');
            }
            // extra options when showing the dynamic objects themselves
            if ($this->objectid == 1) {
                $options['viewitems'] = array('otitle' => xarML('Items'),
                                              'olink'  => xarModURL('dynamicdata','admin','view',
                                                                    array('itemid' => $itemid)),
                                              'ojoin'  => '|'
                                             );
                $tplmodule = $this->checkModuleFunction($args['tplmodule'], 'admin', 'modifyprop');
                $options['modifyprops'] = array('otitle' => xarML('Properties'),
                                     'olink'  => xarModURL($tplmodule,'admin','modifyprop',$urlargs),
                                     'ojoin'  => '|');
            }
        }
        if ($allow_delete)  {
            // if $linktype == 'object' use function 'main' everywhere and set $urlargs['method']
            if ($args['tplmodule'] == 'dynamicdata' && $linktype == 'object' && $linkfunc == 'main') {
                // prepend method to urlargs
                $urlargs2 = array('method' => 'delete') + $urlargs;
                unset($urlargs2['tplmodule']);
                $options['delete'] = array('otitle' => xarML('Delete'),
                                           'olink'  => xarModURL($tplmodule,$linktype,$linkfunc,$urlargs2),
                                           'ojoin'  => '|');
            } else {
                $tplmodule = $this->checkModuleFunction($args['tplmodule'], 'admin', 'delete');
                $options['delete'] = array('otitle' => xarML('Delete'),
                                           'olink'  => xarModURL($tplmodule,'admin','delete', $urlargs),
                                           'ojoin'  => '|');
            }
        }

        return $options;
    }

    /**
     * Check if a particular module function exists, or default back to 'dynamicdata'
     *
     * @todo use some core function to verify that a module function exists ?
     * @return string tplmodule or 'dynamicdata'
     */
    private function checkModuleFunction($tplmodule = 'dynamicdata', $type = 'user', $func = 'display')
    {
        static $tplmodule_cache = array();

        $key = "$tplmodule:$type:$func";
        if (!isset($tplmodule_cache[$key])) {
            $file = sys::code() . 'modules/' . $tplmodule . '/xar' . $type . '/' . $func . '.php';
            if (file_exists($file)) {
                $tplmodule_cache[$key] = $tplmodule;
            } else {
                $tplmodule_cache[$key] = 'dynamicdata';
            }
        }
        return $tplmodule_cache[$key];
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

    public function getPager($currenturl=null)
    {
        $currenturl = isset($currenturl) ? $currenturl : "";
        $prevurl = '';
        $nexturl = '';
        $sorturl = '';

        if(empty($this->startnum)) $this->startnum = 1;

        // TODO: count items before calling getItems() if we want some better pager

        // Get current URL (this uses &amp; by default now)
        if(empty($currenturl)) $currenturl = xarServer::getCurrentURL();

        // TODO: clean up generation of sort URL

        // get rid of current startnum and sort params
        $sorturl = $currenturl;
        $sorturl = preg_replace('/&amp;startnum=\d+/','',$sorturl);
        $sorturl = preg_replace('/\?startnum=\d+&amp;/','?',$sorturl);
        $sorturl = preg_replace('/\?startnum=\d+$/','',$sorturl);
        $sorturl = preg_replace('/&amp;sort=\w+/','',$sorturl);
        $sorturl = preg_replace('/\?sort=\w+&amp;/','?',$sorturl);
        $sorturl = preg_replace('/\?sort=\w+$/','',$sorturl);
        // add sort param at the end of the URL
        if(preg_match('/\?/',$sorturl)) {
            $sorturl = $sorturl . '&amp;sort';
        } else {
            $sorturl = $sorturl . '?sort';
        }
        if(empty($this->numitems) || ( (count($this->items) < $this->numitems) && $this->startnum == 1 )) {
            return array($prevurl,$nexturl,$sorturl);
        }

        if(preg_match('/startnum=\d+/',$currenturl)) {
            if(count($this->items) == $this->numitems) {
                $next = $this->startnum + $this->numitems;
                $nexturl = preg_replace('/startnum=\d+/',"startnum=$next",$currenturl);
            }
            if($this->startnum > 1) {
                $prev = $this->startnum - $this->numitems;
                $prevurl = preg_replace('/startnum=\d+/',"startnum=$prev",$currenturl);
            }
        }
        elseif(preg_match('/\?/',$currenturl)) {
            if(count($this->items) == $this->numitems) {
                $next = $this->startnum + $this->numitems;
                $nexturl = $currenturl . '&amp;startnum=' . $next;
            }
            if($this->startnum > 1) {
                $prev = $this->startnum - $this->numitems;
                $prevurl = $currenturl . '&amp;startnum=' . $prev;
            }
        } else {
            if(count($this->items) == $this->numitems) {
                $next = $this->startnum + $this->numitems;
                $nexturl = $currenturl . '?startnum=' . $next;
            }
            if($this->startnum > 1) {
                $prev = $this->startnum - $this->numitems;
                $prevurl = $currenturl . '?startnum=' . $prev;
            }
        }
        return array($prevurl,$nexturl,$sorturl);
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
