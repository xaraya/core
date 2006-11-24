<?php
/**
 * DataObject List
 * Note : for performance reasons, we won't use an array of objects here,
 *        but a single object with an array of item values
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata module
 *
**/
sys::import('modules.dynamicdata.class.properties');

sys::import('modules.dynamicdata.class.objects.master');
class DataObjectList extends DataObjectMaster
{
    public $itemids  = array();           // the list of item ids used in data stores
    public $where    = '';
    public $sort;
    public $groupby;
    public $numitems = null;
    public $startnum = null;

    public $startstore = null; // the data store we should start with (for sort)

    public $items = array();             // the result array of itemid => (property name => value)

    // optional URL style for use in xarModURL() (defaults to itemtype=...&...)
    public $urlstyle = 'itemtype'; // TODO: table or object, or wrapper for all, or all in template, or...
    // optional display function for use in xarModURL() (defaults to 'display')
    public $linkfunc = 'display';

    /**
     * Inherits from DataObjectMaster and sets the requested item ids, sort, where, ...
     *
     * @param $args['itemids'] array of item ids to return
     * @param $args['sort'] sort field(s)
     * @param $args['where'] WHERE clause to be used as part of the selection
     * @param $args['numitems'] number of items to retrieve
     * @param $args['startnum'] start number
     */
    function __construct(DataObjectDescriptor $descriptor)
    {
        // get the object type information from our parent class
        $this->loader($descriptor);

        // see if we can access these objects, at least in overview
        if(!xarSecurityCheck('ViewDynamicDataItems',1,'Item',$this->moduleid.':'.$this->itemtype.':All')) return;

        // set the different arguments (item ids, sort, where, numitems, startnum, ...)
        $this->setArguments($descriptor->getArgs());
    }

    function setArguments($args)
    {
        // set the number of items to retrieve
        if(!empty($args['numitems']))
            $this->numitems = $args['numitems'];

        // set the start number to retrieve
        if(!empty($args['startnum']))
            $this->startnum = $args['startnum'];

        // set the list of requested item ids
        if(!empty($args['itemids']))
        {
            if(is_numeric($args['itemids']))
                $this->itemids = array($args['itemids']);
            elseif(is_string($args['itemids']))
                $this->itemids = explode(',',$args['itemids']);
            elseif(is_array($args['itemids']))
                $this->itemids = $args['itemids'];
        }
        if (!isset($this->itemids))
            $this->itemids = array();

        // reset fieldlist and datastores if necessary
        if(
            isset($args['fieldlist']) &&
            (!isset($this->fieldlist) || $args['fieldlist'] != $this->fieldlist)
        )
        {

            $this->fieldlist = $args['fieldlist'];
            $this->getDataStores(true);
        }
        else
        {
            if(
                isset($args['status']) &&
                (!isset($this->status) || $args['status'] != $this->status)
            )
            {
                $this->status = $args['status'];
                $this->fieldlist = array();
                foreach($this->properties as $name => $property)
                    if($property->status == $this->status)
                        $this->fieldlist[] = $name;

                $this->getDataStores(true);
            }
        }

        // add where clause if itemtype is one of the properties (e.g. articles)
        if(isset($this->secondary) && !empty($this->itemtype) && $this->objectid > 2)
        {
            if(empty($args['where']))
                $args['where'] = $this->secondary . ' eq ' . $this->itemtype;
            else
                $args['where'] .= ' and ' . $this->secondary . ' eq ' . $this->itemtype;
        }

        // Note: they can be empty here, which means overriding any previous criteria
        if(isset($args['sort']) || isset($args['where']) || isset($args['groupby']) || isset($args['cache']))
        {
            foreach(array_keys($this->datastores) as $name)
            {
                if(isset($args['sort']))
                    // make sure we don't have some left-over sort criteria
                    $this->datastores[$name]->cleanSort();
                if(isset($args['where']))
                    // make sure we don't have some left-over where clauses
                    $this->datastores[$name]->cleanWhere();
                if(isset($args['groupby']))
                    // make sure we don't have some left-over group by fields
                    $this->datastores[$name]->cleanGroupBy();
                if(isset($args['cache']))
                    // pass the cache value to the datastores
                    $this->datastores[$name]->cache = $args['cache'];
            }
        }

        // set the sort criteria
        if(!empty($args['sort']))
            $this->setSort($args['sort']);

        // set the where clauses
        if(!empty($args['where']))
            $this->setWhere($args['where']);

        // set the group by fields
        if(!empty($args['groupby']))
            $this->setGroupBy($args['groupby']);

        // set the categories
        if(!empty($args['catid']))
            $this->setCategories($args['catid']);
    }

    function setSort($sort)
    {
        if(is_array($sort))
            $this->sort = $sort;
        else
            $this->sort = explode(',',$sort);

        foreach($this->sort as $criteria)
        {
            // split off trailing ASC or DESC
            if(preg_match('/^(.+)\s+(ASC|DESC)\s*$/',$criteria,$matches))
            {
                $criteria = trim($matches[1]);
                $sortorder = $matches[2];
            }
            else
                $sortorder = 'ASC';

            if(isset($this->properties[$criteria]))
            {
                // pass the sort criteria to the right data store
                $datastore = $this->properties[$criteria]->datastore;
                // assign property to datastore if necessary
                if(empty($datastore))
                {
                    list($storename, $storetype) = $this->property2datastore($this->properties[$criteria]);
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

    function setWhere($where)
    {
        // find all single-quoted pieces of text with and/or and replace them first, to
        // allow where clauses like : title eq 'this and that' and body eq 'here or there'
        $idx = 0;
        $found = array();
        if(preg_match_all("/'(.*?)'/",$where,$matches))
        {
            foreach($matches[1] as $match)
            {
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
        $parts = preg_split('/\s+(and|or)\s+/',$where,-1,PREG_SPLIT_DELIM_CAPTURE);
        $join = '';
        foreach($parts as $part)
        {
            if($part == 'and' || $part == 'or')
            {
                $join = $part;
                continue;
            }

            $pieces = preg_split('/\s+/',$part);
            $pre = '';
            $post = '';
            $name = array_shift($pieces);
            if($name == '(')
            {
                $pre = '(';
                $name = array_shift($pieces);
            }

            $last = count($pieces) - 1;
            if($pieces[$last] == ')')
            {
                $post = ')';
                array_pop($pieces);
            }

            // sanity check on SQL
            if(count($pieces) < 2)
            {
                $msg = 'Invalid #(1) for #(2) function #(3)() in module #(4)';
                $vars = array('query ' . $where, 'DataObjectList', 'getWhere', 'DynamicData');
                throw new BadParameterException($vars,$msg);
            }

            if(isset($this->properties[$name]))
            {
                // pass the where clause to the right data store
                $datastore = $this->properties[$name]->datastore;
                // assign property to datastore if necessary
                if(empty($datastore))
                {
                    list($storename, $storetype) = $this->property2datastore($this->properties[$name]);
                    if(!isset($this->datastores[$storename]))
                        $this->addDataStore($storename, $storetype);

                    $this->properties[$name]->datastore = $storename;
                    $this->datastores[$storename]->addField($this->properties[$name]); // use reference to original property
                    $datastore = $storename;
                }
                elseif($this->properties[$name]->type == 21)
                    $this->datastores[$datastore]->addField($this->properties[$name]); // use reference to original property

                if(empty($idx))
                    $mywhere = join(' ',$pieces);
                else
                {
                    $mywhere = '';
                    foreach($pieces as $piece)
                    {
                        // replace the pieces again if necessary
                        if(preg_match("#'~(\d+)~'#",$piece,$matches) && isset($found[$matches[1]]))
                        {
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

    function setGroupBy($groupby)
    {
        if(is_array($groupby))
            $this->groupby = $groupby;
        else
            $this->groupby = explode(',',$groupby);

        $this->isgrouped = 1;

        foreach($this->groupby as $name)
        {
            if(isset($this->properties[$name]))
            {
                // pass the sort criteria to the right data store
                $datastore = $this->properties[$name]->datastore;
                // assign property to datastore if necessary
                if(empty($datastore))
                {
                    list($storename, $storetype) = $this->property2datastore($this->properties[$name]);
                    if(!isset($this->datastores[$storename]))
                        $this->addDataStore($storename, $storetype);

                    $this->properties[$name]->datastore = $storename;
                    $this->datastores[$storename]->addField($this->properties[$name]); // use reference to original property
                    $datastore = $storename;
                }
                elseif($this->properties[$name]->type == 21)
                    $this->datastores[$datastore]->addField($this->properties[$name]); // use reference to original property

                $this->datastores[$datastore]->addGroupBy($this->properties[$name]);
                // if we're grouping by some field, we should start querying by the data store that holds it
                if(!isset($this->startstore))
                   $this->startstore = $datastore;
            }
        }
    }

    function setCategories($catid)
    {
        if(!xarModIsAvailable('categories'))
            return;

        $categoriesdef = xarModAPIFunc(
            'categories','user','leftjoin',
            array(
                'modid' => $this->moduleid,
                'itemtype' => $this->itemtype,
                'catid' => $catid
            )
        );

        foreach(array_keys($this->datastores) as $name)
        {
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

    function &getItems($args = array())
    {
        // initialize the items array
        $this->items = array();

        // set/override the different arguments (item ids, sort, where, numitems, startnum, ...)
        $this->setArguments($args);

        if(empty($args['numitems']))
            $args['numitems'] = $this->numitems;

        if(empty($args['startnum']))
            $args['startnum'] = $this->startnum;

        // if we don't have a start store yet, but we do have a primary datastore, we'll start there
        if(empty($this->startstore) && !empty($this->primary))
            $this->startstore = $this->properties[$this->primary]->datastore;

       // first get the items from the start store (if any)
        if(!empty($this->startstore))
        {
            $this->datastores[$this->startstore]->getItems($args);

            // check if we found something - if not, no sense looking further
            if(count($this->itemids) == 0)
                return $this->items;
        }
        // then retrieve the other info about those items
        foreach(array_keys($this->datastores) as $name)
        {
            if(!empty($this->startstore) && $name == $this->startstore)
                continue;

            $this->datastores[$name]->getItems($args);
        }
        return $this->items;
    }

    /**
     * Count the number of items that match the selection criteria
     * Note : this must be called *before* getItems() if you're using numitems !
     */
    function countItems($args = array())
    {
        // set/override the different arguments (item ids, sort, where, numitems, startnum, ...)
        $this->setArguments($args);

        // if we don't have a start store yet, but we do have a primary datastore, we'll count there
        if(empty($this->startstore) && !empty($this->primary))
            $this->startstore = $this->properties[$this->primary]->datastore;

        // try to count the items in the start store (if any)
        if(!empty($this->startstore))
            return $this->datastores[$this->startstore]->countItems($args);
        else
        {
            // If we don't have a start store, we're probably stuck,
            // but we'll try the first one anyway :)
            // TODO: find some better way to determine which data store to count in
            foreach(array_keys($this->datastores) as $name)
            {
                // this looks like a loop but it isnt :-) (yet)
                return $this->datastores[$name]->countItems($args);
            }
        }
    }

    function showView($args = array())
    {
        $args = $this->toArray($args);
/*        if(empty($args['layout']))      $args['layout']         = $this->layout;
        if(empty($args['template']))   $args['template']   = $this->template;
        if(empty($args['tplmodule'])) $args['tplmodule']  = $this->tplmodule;
        if(empty($args['viewfunc']))   $args['viewfunc']    = $this->viewfunc;
        if(empty($args['fieldprefix'])) $args['fieldprefix'] = $this->fieldprefix;
        if(empty($args['fieldlist']))     $args['fieldlist']      = $this->fieldlist;
        if(!empty($args['extend']))    $this->extend();
*/
        if(!empty($this->status))
            $state = $this->status;
        else
            $state = DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE;

        $args['properties'] = array();
        if(count($args['fieldlist']) > 0)
        {
            foreach($args['fieldlist'] as $name) {
                if(isset($this->properties[$name])) {
                    if(($this->properties[$name]->status & DataPropertyMaster::DD_DISPLAYMASK) == ($state & DataPropertyMaster::DD_DISPLAYMASK))
                        $args['properties'][$name] =& $this->properties[$name];
                }
            }
        }
        else
        {
            foreach($this->properties as $property)
                if(($property->status & DataPropertyMaster::DD_DISPLAYMASK) == ($state & DataPropertyMaster::DD_DISPLAYMASK))
                    $args['properties'][$property->name] = $property;
        }

        $args['items'] =& $this->items;

        // add link to display the item
        if(empty($args['linkfunc']))  $args['linkfunc'] = $this->linkfunc;
        if(empty($args['linklabel'])) $args['linklabel'] = xarML('Display');
        if(empty($args['param']))     $args['param'] = $this->urlparam;
        if(empty($args['linkfield'])) $args['linkfield'] = '';

        // pass some extra template variables for use in BL tags, API calls etc.
        $args['moduleid'] = $this->moduleid;

        $modinfo = xarModGetInfo($this->moduleid);
        $modname = $modinfo['name'];
        $itemtype = $this->itemtype;

        // override for viewing dynamic objects
        $args['dummymode'] = 0; // Set to 0 when interested in viewing them anyway...
        if($modname == 'dynamicdata' && $this->itemtype == 0 && empty($this->table))
        {
            $linktype = 'user';
            $linkfunc = 'view';
            // Don't show link to view items that don't belong to the DD module
        }
        else
        {
            $linktype = 'user';
            $linkfunc = $args['linkfunc'];
        }
        $args['linktype'] = $linktype;

        if(empty($itemtype))
            $itemtype = null; // don't add to URL
        $args['table'] = !empty($this->table) ? $this->table : null;
        $args['objectname'] = !empty($this->name) ? $this->name : null;
        $args['modname'] = $modname;
        $args['itemtype'] = $itemtype;
        $args['objectid'] = $this->objectid;
        $args['links'] = array();
        if(empty($args['urlmodule'])) {
            if(!empty($this->urlmodule)) {
                $args['urlmodule'] = $this->urlmodule;
            } else {
                $info = DataObjectMaster::getObjectInfo(
                    array(
                        'moduleid' => $args['moduleid'],
                        'itemtype' => $args['itemtype']
                    )
                );
                $base = DataObjectMaster::getBaseAncestor(
                    array('objectid' => $info['objectid'])
                );
                $args['urlmodule'] = $modname;
            }
        }
        foreach(array_keys($this->items) as $itemid) {
            // TODO: improve this + SECURITY !!!
            $options = array();
            if(!empty($this->isgrouped)) {
                $args['links'][$itemid] = $options;
                continue;
            }
             $args['itemid'] = $itemid;
            // @todo let's be a lil more explicit in handling these options
            $args['links'][$itemid] = $this->getViewOptions($args);
        }
        if(!empty($this->isgrouped))
        {
            foreach(array_keys($args['properties']) as $name)
            {
                if(!empty($this->properties[$name]->operation))
                    $this->properties[$name]->label = $this->properties[$name]->operation . '(' . $this->properties[$name]->label . ')';
            }
            $args['linkfield'] = 'N/A';
        }

        $args['isprimary'] = !empty($this->primary);
        $args['catid'] = !empty($this->catid) ? $this->catid : null;
        if(isset($args['newlink']))
        {
            // TODO: improve this + SECURITY !!!
        }
        elseif(xarSecurityCheck('AddDynamicDataItem',0,'Item',$this->moduleid.':'.$this->itemtype.':All'))
        {
            $args['newlink'] = xarModURL(
                $args['urlmodule'],'admin','new',
                array(
                    'itemtype' => $itemtype,
                    'table'    => $args['table']
                )
            );
        }
        else
            $args['newlink'] = '';

        if(empty($args['pagerurl']))
            $args['pagerurl'] = '';

        list(
            $args['prevurl'],
            $args['nexturl'],
            $args['sorturl']) = $this->getPager($args['pagerurl']);

        // $current = xarModAPIFunc('dynamicdata','user','setcontext',$args);

        return xarTplObject($args['tplmodule'],$args['template'],'showview',$args);
    }

    /**
      * Get List to fill showView template options
      *
      * @return array
      *
      * @todo make this smarter
      * @todo can we use this for the newlink too?
      */
    function getViewOptions($args)
    {
        extract($args);

        if ($dummymode && $this->items[$itemid]['moduleid'] != 182) {
            $dummyoption = array(
               'otitle' => xarML('View'),
               'olink'  => '',
               'ojoin'  => ''
            );
        }

        $urlargs = array();
        $urlargs['itemtype'] =$itemtype;
        $urlargs['table'] = $table;
        $urlargs[$args['param']] = $itemid;
        $urlargs['template'] = $args['template'];

        $options = array();
        if (xarSecurityCheck('DeleteDynamicDataItem',0,'Item',$this->moduleid.':'.$this->itemtype.':'.$itemid))  {
            if( isset($dummyoption))  {
                    $options['dummy'] = $dummyoption;
            } else {
                $options['view'] = array('otitle' => xarML('View'),
                                       'olink'  => xarModURL($args['urlmodule'],$linktype,$linkfunc,
                                                   $urlargs),
                                       'ojoin'  => '');
            }
            $options['modify'] = array('otitle' => xarML('Edit'),
                                   'olink'  => xarModURL($args['urlmodule'],'admin','modify',
                                               $urlargs),
                                   'ojoin'  => '|');
            $options['delete'] = array('otitle' => xarML('Delete'),
                                   'olink'  => xarModURL($args['urlmodule'],'admin','delete',
                                               $urlargs),
                                   'ojoin'  => '|');
        } elseif(xarSecurityCheck('EditDynamicDataItem',0,'Item',$this->moduleid.':'.$this->itemtype.':'.$itemid)) {
            if (isset($dummyoption)) {
                $options['dummy'] = $dummyoption;
            } else {
                $options['view'] = array(
                        'otitle' => xarML('View'),
                        'olink'  => xarModURL(
                            $args['urlmodule'],$linktype,$linkfunc,
                             $urlargs
                        ),
                        'ojoin'  => ''
                    );
            }
            $options['edit'] = array(
                    'otitle' => xarML('Edit'),
                    'olink'  => xarModURL(
                        $args['urlmodule'],'admin','modify',
                        $urlargs
                    ),
                    'ojoin'  => '|'
                );
        } elseif(xarSecurityCheck('ReadDynamicDataItem',0,'Item',$this->moduleid.':'.$this->itemtype.':'.$itemid)) {
            if (isset($dummyoption)) {
                $options['dummy'] = $dummyoption;
            } else {
                $options['view'] = array(
                        'otitle' => xarML('View'),
                        'olink'  => xarModURL(
                            $args['urlmodule'],$linktype,$linkfunc,
                            $urlargs
                        ),
                        'ojoin'  => ''
                    );
            }
        }
        return $options;
    }

    /**
     * Get the labels and values to include in some output view for these items
     */
    function &getViewValues($args = array())
    {
        if(empty($args['fieldlist']))
            $args['fieldlist'] = $this->fieldlist;
        if(count($args['fieldlist']) == 0 && empty($this->status))
            $args['fieldlist'] = array_keys($this->properties);

        $viewvalues = array();
        foreach($this->itemids as $itemid)
        {
            $viewvalues[$itemid] = array();
            foreach($args['fieldlist'] as $name)
            {
                if(isset($this->properties[$name]))
                {
                    $label = xarVarPrepForDisplay($this->properties[$name]->label);
                    if(isset($this->items[$itemid][$name]))
                        $value = $this->properties[$name]->showOutput(array('value' => $this->items[$itemid][$name]));
                    else
                        $value = '';
                    $viewvalues[$itemid][$label] = $value;
                }
            }
        }
        return $viewvalues;
    }

    function getPager($currenturl = '')
    {
        $prevurl = '';
        $nexturl = '';
        $sorturl = '';

        if(empty($this->startnum))
            $this->startnum = 1;

        // TODO: count items before calling getItems() if we want some better pager

        // Get current URL (this uses &amp; by default now)
        if(empty($currenturl))
            $currenturl = xarServerGetCurrentURL();

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
        if(preg_match('/\?/',$sorturl))
            $sorturl = $sorturl . '&amp;sort';
        else
            $sorturl = $sorturl . '?sort';

        if(empty($this->numitems) || ( (count($this->items) < $this->numitems) && $this->startnum == 1 ))
        {
            return array($prevurl,$nexturl,$sorturl);
        }

        if(preg_match('/startnum=\d+/',$currenturl))
        {
            if(count($this->items) == $this->numitems)
            {
                $next = $this->startnum + $this->numitems;
                $nexturl = preg_replace('/startnum=\d+/',"startnum=$next",$currenturl);
            }
            if($this->startnum > 1)
            {
                $prev = $this->startnum - $this->numitems;
                $prevurl = preg_replace('/startnum=\d+/',"startnum=$prev",$currenturl);
            }
        }
        elseif(preg_match('/\?/',$currenturl))
        {
            if(count($this->items) == $this->numitems)
            {
                $next = $this->startnum + $this->numitems;
                $nexturl = $currenturl . '&amp;startnum=' . $next;
            }
            if($this->startnum > 1)
            {
                $prev = $this->startnum - $this->numitems;
                $prevurl = $currenturl . '&amp;startnum=' . $prev;
            }
        }
        else
        {
            if(count($this->items) == $this->numitems)
            {
                $next = $this->startnum + $this->numitems;
                $nexturl = $currenturl . '?startnum=' . $next;
            }
            if($this->startnum > 1)
            {
                $prev = $this->startnum - $this->numitems;
                $prevurl = $currenturl . '?startnum=' . $prev;
            }
        }
        return array($prevurl,$nexturl,$sorturl);
    }

    /**
     * Get items one at a time, instead of storing everything in $this->items
     */
    function getNext($args = array())
    {
        static $start = true;

        if($start)
        {
            // set/override the different arguments (item ids, sort, where, numitems, startnum, ...)
            $this->setArguments($args);

            if(empty($args['numitems']))
                $args['numitems'] = $this->numitems;
            if(empty($args['startnum']))
                $args['startnum'] = $this->startnum;

            // if we don't have a start store yet, but we do have a primary datastore, we'll start there
            if(empty($this->startstore) && !empty($this->primary))
                $this->startstore = $this->properties[$this->primary]->datastore;

            $start = false;
        }

        $itemid = null;
        // first get the items from the start store (if any)
        if(!empty($this->startstore))
        {
            $itemid = $this->datastores[$this->startstore]->getNext($args);

            // check if we found something - if not, no sense looking further
            if(empty($itemid))
                return;
        }
        return $itemid;
    }
}
?>
