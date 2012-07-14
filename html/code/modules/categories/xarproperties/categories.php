<?php
/**
 * Categories Module
 *
 * @package modules
 * @subpackage categories module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/147.html
 *
 * @author Marc Lutolf <mfl@netspan.ch>
 */

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
 */

sys::import('modules.dynamicdata.class.properties.base');
sys::import('modules.categories.xarproperties.categorytree');

class CategoriesProperty extends DataProperty
{
    public $id         = 100;
    public $name       = 'categories';
    public $desc       = 'Categories';
    public $reqmodules = array('categories');
    public static $deferto    = array('CategoryPickerProperty');

    public $include_reference   = 1;

    public $validation_single = false;
    public $validation_allowempty = false;
    public $validation_single_invalid; // CHECKME: is this a validation or something else?
    public $validation_allowempty_invalid;
    public $initialization_include_no_cat   = 1;
    public $initialization_include_all_cats = 0;
    public $initialization_basecategories;

    public $module_id;
    public $itemtype;
    public $itemid;
    public $categories = array();
    public $basecategories = array();
    
//    public $validation_categories;

    
    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->template       = 'categories';
        $this->tplmodule      = 'categories';
        $this->filepath       = 'modules/categories/xarproperties';
        $this->prepostprocess = 2;
    }

    public function checkInput($name = '', $value = null)
    {
        $name = empty($name) ? 'dd_'.$this->id : $name;
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
        // Select type of each tree can be diferent
        foreach ($this->basecategories as $key => $base_category) {
            $select_type = 3;
            if ($select_type == 1) $select_type = 'dropdown';
            else $select_type = 'multiselect';
                if (!xarVarFetch($name . '["categories"]', 'isset', $categories, array(), XARVAR_NOT_REQUIRED)) return;
        }
        $value = $categories;
        return $this->validateValue($value);
    }

    public function validateValue($value = null)
    {
        // Make sure they are valid unless we can override
//        if (!$this->validation_override) {
        if (0) {
            if (count($value) > 0) {
                $checkcats= array();
                foreach ($value as $category) {
                    if (empty($category)) continue;
                    $catparts = explode('.',$category);
                    $category = $catparts[0];
                    $validcat = xarMod::apiFunc('categories','user','getcatinfo',array('cid' => $category));
                    if (!$validcat) {
                        $this->invalid = xarML("The category #(1) is not valid", $category);
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
        
        // We passed the checks, set the categories
        $this->categories = $value;
        
        // Keep a reference of the data of this property in $this->value, for saving or easy manipulation
        $this->value = $value;        
        return true;
    }

    public function createValue($itemid=0)
    {
        // For both create and update we remove any existing links and create the new ones
        sys::import('xaraya.structures.query');
        xarMod::apiLoad('categories');
        $xartable = xarDB::getTables();
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
                $q->addfield('basecategory', $basecategory);
                $q->addfield('category_id', $category);
                $q->run();
            }
        }
        return true;
    }

    public function updateValue($itemid=0)
    {
        return $this->createValue($itemid);
    }

    public function deleteValue($itemid=0)
    {
        // TODO make this work, but do we need it?
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
        // Override or a standalone property
        if (isset($data['module'])) $this->module_id = xarMod::getID($data['module']);
        // No hint at all, take the current module
        if (!isset($this->module_id)) $this->module_id = xarMod::getID(xarModGetName());

        // Do the same for itemtypes
        if (isset($this->objectref)) $this->itemtype = (int)$this->objectref->itemtype;
        if (isset($data['itemtype'])) $this->itemtype = (int)$data['itemtype'];
        // No hint at all, assume all itemtypes
        if (!isset($this->itemtype)) $this->itemtype = 0;

        // Get the itemid
        $itemid = $this->_itemid;
        if (isset($data['itemid'])) $itemid = (int)$data['itemid'];

        // Retrieve the configuration settings for this property
        if (!empty($this->configuration)) {
            $configuration = unserialize($this->configuration);
            $configuration = $configuration['initialization_basecategories'];
            $data['tree_name'] = $configuration[0];
            $base_categories = $configuration[1];
            $data['include_self'] = $configuration[2];
            $data['select_type'] = $configuration[3];
       } else {
            $data['tree_name'] = array(1 => 'New Tree');
            $base_categories = array(1 => 1);
            $data['include_self'] = array(1 => 1);
            $data['select_type'] = array(1 => 1);
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
            $data['base_category'][$key] = $id;
            $nodes = new BasicSet();
            if ($id == -1) {
                // We want all the categories
                // This is not really a tree because we can have many top level categories,
                // so we'll have to create a virtual root category as parent of them all
                $node = new CategoryTreeNode();
            } else {
                // We want a specific tree of which the base is $id
                $node = new CategoryTreeNode($id);
            }
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
        
        // Get an array of values (selected categories) for each tree
        if (!isset($data['value'])) {
            $data['value'] = array();
            xarMod::apiLoad('categories');
            $xartable = xarDB::getTables();
            sys::import('xaraya.structures.query');
            foreach ($data['base_category'] as $key => $value) {
                $q = new Query('SELECT', $xartable['categories_linkage']); 
                $q->eq('basecategory', (int)$value);
                $q->eq('item_id', (int)$itemid);
                if ($this->module_id) $q->eq('module_id', $this->module_id);
                if ($this->itemtype) $q->eq('itemtype', $this->itemtype);
                $q->addfield('category_id');
                $q->run();
                $result = $q->output();
                $categories = array();
                foreach ($result as $row) 
                    if (!empty($row['category_id'])) $categories[] = $row['category_id'];
                $data['value'][$key] = $categories;
            }
        }

        // Prepare some variables we need for the template
        $data['categories_module_id'] = $this->module_id;
        $data['categories_itemtype'] = $this->itemtype;
        
        return parent::showInput($data);
    }

    public function showOutput(Array $data = array())
    {
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

        // Pick up an itemid if one was passed
        if (isset($data['itemid'])) $itemid = (int)$data['itemid'];

        sys::import('xaraya.structures.query');
        xarMod::apiLoad('categories');
        $xartable = xarDB::getTables();
        $q = new Query('SELECT'); 
        $q->addtable( $xartable['categories'],'c');
        $q->addtable( $xartable['categories_linkage'],'cl');
        $q->join('c.id','cl.category_id');
        $q->eq('item_id', $itemid);
        if ($this->module_id) $q->eq('module_id', $this->module_id);
        if ($this->itemtype) $q->eq('itemtype', $this->itemtype);
        $q->run();
        $this->value = $q->output();

        $data['value'] = $this->value;
        return parent::showOutput($data);
    }

    public function updateConfiguration(Array $data = array())
    {
        // Array properties and their extensions have arrays as values
        // Use the property's checkInput method to get the value
        $arrayprop = DataPropertyMaster::getProperty(array('name' => 'categorypicker'));
        $arrayprop->checkInput('dd_' . $this->id . '["initialization_basecategories"]');
        // Assign the value to this configuration property for update
        $data['configuration']['initialization_basecategories'] = $arrayprop->getValue();
        
        // The other configuration properties need no special treatment
        return parent::updateConfiguration($data);
    }
}

class CategoriesPropertyInstall extends CategoriesProperty implements iDataPropertyInstall
{
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