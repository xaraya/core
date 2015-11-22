<?php
/**
 * Categories Module
 *
 * @package modules\categories
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.info/index.php/release/147.html
 *
 * @author Marc Lutolf <mfl@netspan.ch>
 */

sys::import('modules.dynamicdata.class.properties.base');
sys::import('modules.categories.xarproperties.categorytree');

/**
 * This property displays a series of 1 or more category selectors.
 * The selectors can be:
 * - a dropdown: the user can choose one category
 * - a multiselect box: the user can select one of more categories
 * - a set of 2 multiselect boxes: user moves one or more categories from one box to the other
 *
 * Each selector has as data a tree of categories whose parent is a base category
 * The property also references a module ID and an itemtype.
 * When bound to an object these are taken from the parent object.
 * Otherwise these can be added as attributes or the tag, or they take default values.
 *
 * Note: a base category -1 means show all the categories in a dropdown
 *
 */
class CategoriesProperty extends DataProperty
{
    public $id         = 100;
    public $name       = 'categories';
    public $desc       = 'Categories';
    public $reqmodules = array('categories');
    public static $deferto    = array('CategoryPickerProperty');

    public $include_reference   = 1;

    public $validation_single               = false;
    public $validation_allowempty           = false;
    public $validation_single_invalid; // CHECKME: is this a validation or something else?
    public $validation_allowempty_invalid;
    public $initialization_include_no_cat   = 0;
    public $initialization_include_all_cats = 0;
    // Four columns (0 - 3) on 1 line
    public $initialization_basecategories   = array(array('New Tree',array(array(1)),true,1));

    public $module_id      = 0;
    public $itemtype       = 0;
    public $property       = 0;
    public $itemid;
    public $categories     = array();
    public $basecategories = array();
        
    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->template       = 'categories';
        $this->tplmodule      = 'categories';
        $this->filepath       = 'modules/categories/xarproperties';

        // In a bound property, get module and itemtype from the parent object
        if (!empty($this->objectref)) {
            $this->module_id = (int)$this->objectref->moduleid;
            $this->itemtype  = (int)$this->objectref->itemtype;
        }
    }

    public function checkInput($name = '', $value = null)
    {
        $name = empty($name) ? $this->propertyprefix . $this->id : $name;
        // store the fieldname for validations who need them (e.g. file uploads)
        $this->fieldname = $name;

        // Pull in local module and itemtype from the form and store for reuse
        if (!xarVarFetch($name . '["itemtype"]', 'int', $itemtype, 0, XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch($name . '["module_id"]', 'int', $module_id, 182, XARVAR_NOT_REQUIRED)) return;
        $this->module_id = $module_id;
        $this->itemtype = $itemtype;
       
        // Get the base categories from the form
        if (!xarVarFetch($name . '["base_category"]', 'array', $basecats, array(), XARVAR_NOT_REQUIRED)) return;
        $this->basecategories = $basecats;
        // Get the categories from the form
        // Select type of each tree can be different
        // CHECKME: only one tree and one basecategory per property
        /*
        foreach ($this->basecategories as $key => $base_category) {
            $select_type = 3;
            if ($select_type == 1) $select_type = 'dropdown';
            else $select_type = 'multiselect';
            if (!xarVarFetch($name . '["categories"]', 'array', $categories, array(), XARVAR_NOT_REQUIRED)) return;
        }
        */
        if (!xarVarFetch($name . '["categories"]', 'array', $categories, array(), XARVAR_NOT_REQUIRED)) return;
        return $this->validateValue($categories);
    }

    public function validateValue($value = null)
    {
        if (!parent::validateValue($value)) return false;

        // Make sure they are valid unless we can override
//        if (!$this->validation_override) {
        if (0) {
            if (count($value) > 0) {
                $checkcats= array();
                foreach ($value as $category) {
                    if (empty($category)) continue;
                    $catparts = explode('.',$category);
                    $category = (int)$catparts[0];
                    $validcat = xarMod::apiFunc('categories','user','getcatinfo',array('cid' => $category));
                    if (!$validcat) {
                        $this->invalid = xarML("The category #(1) is not valid", $category);
                        xarLog::message($this->invalid, XARLOG_LEVEL_ERROR);
                        $this->value = null;
                        return false;
                    }
                }
            }
        }
        
        // CHECKME: do we still need this?
        // Check the number of base categories against the number categories we have
        // Remark: some of the selected categories might be empty here !
        if (count($this->basecategories) != count($value)) {
            $this->invalid = xarML("The number of categories and their base categories is not the same");
            $this->value = null;
            return false;
        }
        
        // We passed the checks, set the categories, amking sure we have integers
        $this->categories = array();
        foreach ($value as $category_id) $this->categories[] = (int)current($category_id);
        
        // Keep a reference of the data of this property in $this->value, for saving or easy manipulation
        $this->value = $this->categories;
        return true;
    }

    /**
     * Create Value
     * 
     * @param int $itemid
     * @return boolean Returns true
     */
    public function createValue($itemid=0)
    {
        if (isset($this->objectref)) {
            // This property is bound
            return $this->updateLinks($itemid);
        } else {
            sys::import('xaraya.structures.query');
            xarMod::apiLoad('categories');
            $xartable =& xarDB::getTables();
        
            // This property is standalone
            // For both create and update we remove any existing links and create the new ones
            if (!empty($itemid)) {
                $q = new Query('DELETE', $xartable['categories_linkage']); 
                $q->eq('item_id', (int)$itemid);
                // CHRCKME: shouldn't we force a value for module_id and itemtype?
                if ($this->module_id) $q->eq('module_id', $this->module_id);
                if ($this->itemtype) $q->eq('itemtype', $this->itemtype);
                $q->run();
            }

            foreach ($this->basecategories as $key => $basecategory) {
                foreach ($this->categories[$key] as $category) {
                    // Ignore if no category was chosen (value = 0)
                    if (empty($category)) continue;
                
                    $q = new Query('INSERT', $xartable['categories_linkage']); 
                    $q->addfield('item_id', (int)$itemid);
                    $q->addfield('module_id', $this->module_id);
                    $q->addfield('itemtype', $this->itemtype);
//                    $q->addfield('basecategory', $key);
                    $q->addfield('category_id', $category);
                    $q->addfield('property_id', $this->id);
                    $q->run();
                }
            }
        }
        return true;
    }

    /**
     * Updates value for the given item id.
     * @param int $itemid ID of the item to be updated
     * @return boolean Returns true on success, false on failure
     *
     * This method also maintains integrity by updating module_id, itemtype etc. if these have changed
     */
    public function updateValue($itemid=0)
    {
        if (isset($this->objectref)) {
            return $this->updateLinks($itemid);
        } else {
            // This property is standalone
            return $this->createValue($itemid);
        }
        return $itemid;
    }

    /**
     * Deletes a value by item ID. Not implemented
     * 
     * @param int $itemid Item ID to be deleted
     * @return int Returns Item ID
     */
    public function deleteValue($itemid=0)
    {
        sys::import('xaraya.structures.query');
        xarMod::apiLoad('categories');
        $xartable =& xarDB::getTables();
        
        if (isset($this->objectref)) {
            // This property is bound
            $q = new Query('DELETE', $xartable['categories_linkage']); 
            $q->eq('item_id', (int)$itemid);
            $q->eq('property_id', $this->id);
            $q->run();
        } else {
            // This property is standalone
            // Not implemented
        }
        return $itemid;
    }

    public function showInput(Array $data = array())
    {
        if (isset($data['include_no_line'])) $this->initialization_include_no_cat = $data['include_no_line'];
        if (isset($data['include_all_line'])) $this->initialization_include_all_cats = $data['include_all_line'];
        if (isset($data['allowempty'])) $this->validation_allowempty = $data['allowempty'];
        if (isset($data['configuration'])) $this->configuration = $data['configuration'];

        // Set the module_id: case of a bound property
        if (isset($this->objectref)) $this->module_id = (int)$this->objectref->module_id;
        // Override for a standalone property
        if (isset($data['module'])) $this->module_id = xarMod::getID($data['module']);
        // No hint at all, take the current module
        if (!isset($this->module_id)) $this->module_id = xarMod::getID(xarModGetName());

        // Do the same for itemtypes
        if (isset($this->objectref)) $this->itemtype = (int)$this->objectref->itemtype;
        if (isset($data['itemtype'])) $this->itemtype = (int)$data['itemtype'];
        // No hint at all, assume all itemtypes
        if (!isset($this->itemtype)) $this->itemtype = 0;

        // Do the same for the property
        if (isset($this->objectref)) $this->property = (int)$this->id;

        // Get the itemid
        $itemid = $this->_itemid;
        if (isset($data['itemid'])) $itemid = (int)$data['itemid'];

        // Retrieve the configuration settings for this property
        if (!empty($this->configuration)) {
            try {
                $configuration = unserialize($this->configuration);
                $configuration = $configuration['initialization_basecategories'];
                $data['tree_name']    = array();
                $base_categories      = array();
                $data['include_self'] = array();
                $data['select_type']  = array();
                foreach ($configuration as $row) {
                    $data['tree_name'][]     = $row[0];
                    $base_categories[]       = $row[1];
                    $data['include_self'][]  = $row[2];
                    $data['select_type'][]   = $row[3];
                }
            } catch(Exception $e) {
                $data['tree_name']    = array(0 => 'New Tree');
                $base_categories      = array(0 => 1);
                $data['include_self'] = array(0 => true);
                $data['select_type']  = array(0 => 1);
            }
       } else {
            $data['tree_name']    = array(0 => 'New Tree');
            $base_categories      = array(0 => 1);
            $data['include_self'] = array(0 => true);
            $data['select_type']  = array(0 => 1);
        }
        // Get an array of category trees, each having a base category as its head
        // CHECKME: what is this again?
        $filter = array(
            'getchildren' => true,
            'maxdepth' => isset($data['maxdepth'])?$data['maxdepth']:null,
            'mindepth' => isset($data['mindepth'])?$data['mindepth']:null,
        );
        // The somewhat convoluted way of getting to the actual base category ids is a consequence of 
        // using the array property (categorypicker) to define them
        $data['base_category'] = array();
        foreach ($base_categories as $key => $trees) {
            // The base category is a single category (no multiselect), so get the category ID
            $tree = is_array($trees) ? reset($trees) : $trees;
            $id = is_array($tree) ? reset($tree) : $tree;
            $data['base_category'][$key] = (int)$id;
            $nodes = new BasicSet();
            $node = new CategoryTreeNode($id);
            $node->setfilter($filter);
            $tree = new CategoryTree($node);
            $nodes->addAll($node->depthfirstenumeration());
            if (!$data['include_self'][$key]) {
                $elements = $nodes->toArray();
                $nodes->clear();
                array_shift($elements);
                foreach($elements as $element)
                    $nodes->add($element);
            }
            $data['trees'][$key] = $nodes;
        }

        if (!empty($this->source)) {
            if (!isset($data['value'])) $data['value'] = array(1=>array(1 => $this->value));
        } else {
             // If we have no values passed, get an array of values (selected categories) for each tree
            if (!isset($data['value'])) {
                $data['value'] = array();
                xarMod::apiLoad('categories');
                $xartable =& xarDB::getTables();
                sys::import('xaraya.structures.query');
                foreach ($data['base_category'] as $key => $value) {
                    $q = new Query('SELECT', $xartable['categories_linkage']); 
                    $q->eq('basecategory', (int)$value);
                    $q->eq('item_id', (int)$itemid);
                    if ($this->module_id) $q->eq('module_id', $this->module_id);
                    if ($this->itemtype) $q->eq('itemtype', $this->itemtype);
                    if ($this->property) $q->eq('property_id', $this->property);
                    $q->addfield('category_id');
                    $q->run();
                    $result = $q->output();
                    $categories = array();
                    foreach ($result as $row) 
                        if (!empty($row['category_id'])) $categories[] = (int)$row['category_id'];
                    $data['value'][$key] = $categories;
                }
            }
       }

        // Prepare some variables we need for the template
        $data['categories_module_id'] = $this->module_id;
        $data['categories_itemtype'] = $this->itemtype;

        return parent::showInput($data);
    }

    public function showOutput(Array $data = array())
    {
        if (!empty($this->source)) {
            $this->tplmodule = 'dynamicdata';
            $this->template = '';
        } else {
            // Set the module_id: case of a bound property
            $itemid = 0;
            if (isset($this->objectref)) {
                $this->module_id = (int)$this->objectref->module_id;
                // Ignore itemid if this is an object list; need to get the ID from the corresponding attribute
                if (isset($this->objectref->itemid)) {
                    if (isset($this->objectref->properties['objectid'])) {
                        $itemid = $this->objectref->properties['objectid']->value;
                    } else {
                        $itemid = $this->objectref->properties['id']->value;
                    }
                }
            }
            // Override or a standalone property
            if (isset($data['module'])) $this->module_id = xarMod::getID($data['module']);
            // No hint at all, take the current module
            if (!isset($this->module_id)) $this->module_id = xarMod::getID(xarModGetName());
    
            // Do the same for itemtypes
            if (isset($this->objectref)) $this->itemtype = (int)$this->objectref->itemtype;
            if (isset($data['itemtype'])) $this->itemtype = (int)$data['itemtype'];
            // No hint at all, assume all itemtypes
            if (!isset($this->itemtype)) $this->itemtype = 0;
    
            // Do the same for the property
            if (isset($this->objectref)) $this->property = (int)$this->id;

            // Pick up an itemid if one was passed
            if (isset($data['itemid'])) $itemid = (int)$data['itemid'];

            $this->mountValue($itemid);
            $this->value = unserialize($this->value);
            $data['value'] = $this->value;
        }
        return parent::showOutput($data);
    }

    public function getValue()
    {    
        $unpacked = unserialize($this->value);
        return $unpacked;
    }

    public function setValue($value=null)
    {
        $this->value = serialize($value);
    }

    public function mountValue($itemid=0)
    {    
        sys::import('xaraya.structures.query');
        xarMod::apiLoad('categories');
        $xartable =& xarDB::getTables();
        $q = new Query('SELECT'); 
        $q->addtable( $xartable['categories'],'c');
        $q->addtable( $xartable['categories_linkage'],'cl');
        $q->join('c.id','cl.category_id');
        $q->eq('item_id', $itemid);
        if ($this->module_id) $q->eq('module_id', $this->module_id);
        if ($this->itemtype) $q->eq('itemtype', $this->itemtype);
        if ($this->property) $q->eq('property_id', $this->property);
        $q->run();
        $result = $q->output();
        $this->value = serialize($result);
    
        return true;
    }

    /**
     * Fetches items from the database
     * 
     * @param int $category Category ID of the items
     * @param object $object Object the property belongs to
     * @return array Array of fetched items
     * @throws Exception Thrown if no object was given.
     */
    public function getItems($category=0, $object=null)
    {
        if (empty($object)) $object = $this->objectref;
        if (empty($object)) throw new Exception(xarML('No object found for the getItems method'));
        if (empty($this->itemid)) $this->itemid = $object->properties[$object->primary]->value;
        $prinaryfield = $object->properties[$object->primary]->source;
        xarMod::load('categories');
        $q = $object->dataquery;
        $tables =& xarDB::getTables();
        $q->addtable($tables['categories'],'c');
        $q->addtable($tables['categories_linkage'],'l');
        $q->leftjoin('l.category_id','c.id');
        $q->leftjoin($prinaryfield,'l.item_id');
        if (!empty($category)) {
            if (is_array($category)) {
                $q->in('c.id', $category);
            } else {
                $q->eq('c.id', $category);
            }
        }
        $q->run();
//        $q->qecho();
        $items = $q->output();
        return $items;
    }

    public function updateConfiguration(Array $data = array())
    {
        // Array properties and their extensions have arrays as values
        // Use the property's checkInput method to get the value
        $arrayprop = DataPropertyMaster::getProperty(array('name' => 'categorypicker'));
        $arrayprop->checkInput($this->propertyprefix . $this->id . '["initialization_basecategories"]');
        
        // Assign the value to this configuration property for update
        $data['configuration']['initialization_basecategories'] = unserialize($arrayprop->value);

        // The other configuration properties need no special treatment
        return parent::updateConfiguration($data);
    }
    
    public function preList()
    {
        // Bail if there is no parent object
        if (empty($this->objectref)) return true;

        // Get the parent object's query;
        $q = $this->objectref->dataquery;
        
        // Get the primary propety of the parent object, and its source
        $primary = $this->objectref->primary;
        $primary_source = $this->objectref->properties[$primary]->source;
        
        // The tables of this property will be added with a special prefix
        // to make sure all tables are unique
        $tableprefix = $this->id . "_";

        // Assemble the links to the object's table
        xarMod::load('categories');
        $tables = xarDB::getTables();
        $q->addTable($tables['categories_linkage'], $tableprefix . 'linkage');
        $q->leftjoin($primary_source, $tableprefix . 'linkage.item_id');
        $q->addTable($tables['categories'], $tableprefix . 'categories');
        $q->leftjoin($tableprefix . 'categories.id', $tableprefix . 'linkage.category_id');
        // A zero means "all"
        // Itemtype & module ID = 0 means the objects listing
        if (!empty($this->module_id) && !empty($this->itemtype)) $q->eq($tableprefix . 'linkage.module_id', $this->module_id);
        if (!empty($this->itemtype)) $q->eq($tableprefix . 'linkage.itemtype', $this->itemtype);
        if (!empty($this->property)) $q->eq('linkage.property_id', $this->property);
        
        // Set the source of this property
        $this->source = $tableprefix . 'categories.name';

        // Debug display
        if (xarModVars::get('dynamicdata','debugmode') && 
        in_array(xarUser::getVar('id'),xarConfigVars::get(null, 'Site.User.DebugAdmins'))) {
            echo "Ref Object: " . $this->objectref->name . "<br/>";
            echo "Property: " . $this->name . "<br/>";
            echo "Query: " . $q->qecho() . "<br/>";
        }
        
        return true;
    }

    /**
     * Get the category links for this property
     * 
     * @param int $itemid
     * @return array category links
     */
    private function getLinks($itemid=0)
    {
        sys::import('xaraya.structures.query');
        xarMod::apiLoad('categories');
        $xartable =& xarDB::getTables();
        
        $q = new Query('SELECT', $xartable['categories_linkage']); 
        $q->eq('item_id', (int)$itemid);
        $q->eq('property_id', $this->id);
        $q->run();
        $links = array();
        foreach ($q->output() as $row) $links[(int)$row['category_id']] = $row;
        return $links;
    }
    
    /**
     * Updates category links for a given item id.
     * @param int $itemid ID of the item to be updated
     * @return boolean Returns true on success, false on failure
     *
     * A property can have 0 to many category links
     * The method compares categories to be added and removed, 
     * and then tries to reuse existing linkage entries that can be overwritten
     * This method also maintains integrity in the linkage table by 
     * updating module_id, itemtype etc. if these have changed
     */
    private function updateLinks($itemid=0)
    {
        sys::import('xaraya.structures.query');
        xarMod::apiLoad('categories');
        $xartable =& xarDB::getTables();
        
        // This property is bound
        // Get the base category:there is only one for each bound property
        $basecategory = (int)$this->initialization_basecategories[0][1][0][0];
        // Get the category links of this property and item
        $links = $this->getLinks($itemid);
        
        // Calculate what rows require what actions
        $previous_cats = array_keys($links);
        $current_cats  = $this->categories;
        $todelete = array_diff($previous_cats,$current_cats);
        $tocreate = array_diff($current_cats,$previous_cats);
        $toupdate = array_intersect($current_cats,$previous_cats);

        // Set up for updating rows we want to delete
        if (!empty($tocreate)) {
            $q = new Query('UPDATE', $xartable['categories_linkage']); 
        }

        // We need to delete and create a certain number of categories
        // Instead we update the deletes to the values of the ctegories we need to create
        $reusable = min(count($todelete), count($tocreate));
        if (!empty($reusable)) {
            for ($i=0;$i<$reusable;$i++) {
                // Get the of the row to delete we will overwrite
                $this_category = array_shift($todelete);
                $this_link = $links[$this_category];
                $id = $this_link['id'];
                
                // This will be row we overwrite
                $q->eq('id', $id);
                
                // Get the category we will insert into this row
                $new_category = array_shift($tocreate);
                $q->addfield('category_id', $new_category);
                
                // Check if any other items need updating
                if ($this_link['module_id'] != $this->module_id) {
                    $q->addfield('module_id', $this->module_id);
                }
                if ($this_link['itemtype'] != $this->itemtype) {
                    $q->addfield('itemtype', $this->itemtype);
                }
                if ($this_link['basecategory'] != $basecategory) {
                    $q->addfield('basecategory', $basecategory);
                }
                $q->run();
            }
        }
        unset($q);
        
        // Do the deletes
        if (!empty($todelete)) {
            $q = new Query('DELETE', $xartable['categories_linkage']); 
            $q->eq('item_id', (int)$itemid);
            $q->eq('property_id', $this->id);
            $q->eq('category_id', $todelete);
            $q->run();
        }
        unset($q);
    
        // Do the creates
        if (!empty($tocreate)) {
            $q = new Query('INSERT', $xartable['categories_linkage']); 
            $q->addfield('item_id', (int)$itemid);
            $q->addfield('module_id', $this->module_id);
            $q->addfield('itemtype', $this->itemtype);
            $q->addfield('basecategory', $basecategory);
            $q->addfield('property_id', $this->id);
            foreach ($tocreate as $category) {
                $q->addfield('category_id', $category);
                $q->run();
            }
        }
        unset($q);

        // Do the updates
        if (!empty($toupdate)) {
            $q = new Query('UPDATE', $xartable['categories_linkage']); 
            $q->eq('item_id', (int)$itemid);
            $q->eq('property_id', $this->id);
            foreach ($toupdate as $category) {
                // Only update if something has changed
                $this_link = $links[$category];
                $q->eq('id', $this_link['id']);
                $check_passed = true;
                if ($this_link['module_id'] != $this->module_id) {
                    $q->addfield('module_id', $this->module_id);
                    $check_passed = false;
                }
                if ($this_link['itemtype'] != $this->itemtype) {
                    $q->addfield('itemtype', $this->itemtype);
                    $check_passed = false;
                }
                if ($this_link['basecategory'] != $this->basecategory) {
                    $q->addfield('basecategory', $this->basecategory);
                    $check_passed = false;
                }
                if (!$check_passed) $q->run();
            }
        }
        unset($q);
        return true;
    }
}

class CategoriesPropertyInstall extends CategoriesProperty implements iDataPropertyInstall
{
    /**
     * Install method
     * 
     * @param array $data Parameter data array
     * @return boolean Returns true.
     */
    public function install(Array $data=array())
    {
        $files[] = sys::code() . 'modules/categories/xardata/categories_configurations-dat.xml';
        foreach ($files as $file) {
            try {
                $objectid = xarMod::apiFunc('dynamicdata','util','import', array('file' => $file));
            } catch (Exception $e) {}
        }
        return true;
    }    
}
?>