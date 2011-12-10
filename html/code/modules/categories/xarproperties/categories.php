<?php
/**
 * Categories Module
 *
 * @package modules
 * @subpackage categories module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
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

    public $include_reference   = 1;

    public $module_id;
    public $itemtype;
    public $categories = array();
    public $basecategories = array();
    
//    public $validation_categories;
    public $validation_override = true;

    public $initialization_basecategories;
    
    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->template       = 'categories';
        $this->tplmodule      = 'categories';
        $this->filepath       = 'modules/categories/xarproperties';
        $this->prepostprocess = 2;
        $this->include_reference = 1;
        
        // Set some variables we need
        // Case of a bound property
        var_dump($this->objectref);
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
    }

    public function checkInput($name = '', $value = null)
    {
        $name = empty($name) ? 'dd_'.$this->id : $name;
        // store the fieldname for validations who need them (e.g. file uploads)
        $this->fieldname = $name;

        // Pull in local module and itemtype from the form and store for reuse
        if (!xarVarFetch($name . '["itemtype"]', 'int', $itemtype, 0, XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch($name . '["module"]', 'str', $modname, "", XARVAR_NOT_REQUIRED)) return;
        if (empty($modname)) $modname = xarModGetName();
        $this->itemtype = $itemtype;
        $this->module_id = xarMod::getID($modname);
        
        // Get the base categories from the form
        if (!xarVarFetch($name . '["base_category"]', 'array', $basecats, array(), XARVAR_NOT_REQUIRED)) return;
        $this->basecategories = $basecats;

        // Get the categories from the form
        if (!xarVarFetch($name . '["categories"]', 'isset', $tempcategories, array(), XARVAR_NOT_REQUIRED)) return;

        // Make sure we have the categories array has the proper form
        $categories = array();
        foreach ($tempcategories as $key => $category) {
            if (!is_array($category)) $category = array($category);
            $categories[$key] = $category;
            
        }

        $value = $categories;
        return $this->validateValue($value);
    }

    public function validateValue($value = null)
    {
        // Make sure they are valid unless we can override
        if (!$this->validation_override) {
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
        sys::import('xaraya.structures.query');
        xarMod::apiLOad('categories');
        $xartable = xarDB::getTables();
        if (!empty($itemid)) {
            $q = new Query('DELETE', $xartable['categories_linkage']); 
            $q->eq('item_id', (int)$itemid);
            if ($this->module_id) $q->eq('module_id', $this->module_id);
            if ($this->itemtype) $q->eq('itemtype', $this->itemtype);
            $q->run();
        }

        foreach ($this->basecategories as $key => $basecategory) {
            foreach ($this->categories[$key] as $category) {
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
        // Retrieve the configuration settings for this property
        if (!empty($this->configuration)) {
            $configuration = unserialize($this->configuration);
            $configuration = $configuration['initialization_basecategories'];
            $data['tree_name'] = $configuration[0];
            $data['base_category'] = $configuration[1];
            $data['include_self'] = $configuration[2];
            $data['select_type'] = $configuration[3];
       } else {
            $data['tree_name'] = array(1 => 'dork');
            $data['base_category'] = array(1 => 1);
            $data['include_self'] = array(1 => 1);
            $data['select_type'] = array(1 => 1);
        }
        
        // Get an array of category trees, each havig a base category as its head
        // CHECKME:
        $filter = array(
            'getchildren' => true,
            'maxdepth' => isset($data['maxdepth'])?$data['maxdepth']:null,
            'mindepth' => isset($data['mindepth'])?$data['mindepth']:null,
        );
        foreach ($data['base_category'] as $id) {
            $nodes = new BasicSet();
            $node = new CategoryTreeNode($id);
            $node->setfilter($filter);
            $tree = new CategoryTree($node);
            $nodes->addAll($node->depthfirstenumeration());
            $data['trees'][] = $nodes;
        }
        
        // Get an array of values (selected items) for each tree
        $data['value'] = array();
        xarMod::apiLOad('categories');
        $xartable = xarDB::getTables();
        sys::import('xaraya.structures.query');
        foreach ($data['base_category'] as $base) {
            $q = new Query('SELECT', $xartable['categories_basecategories']); 
            $q->eq('id', (int)$base);
            if ($this->module_id) $q->eq('module_id', $this->module_id);
            if ($this->itemtype) $q->eq('itemtype', $this->itemtype);
            $q->addfield('category_id');
            $q->run();
            $result = $q->output();
            $data['value'][$base] = !empty($result['category_id']) ? $result : array();        
        }
        
        // Prepare some variables we need for the template
        $data['categories_module_id'] = $this->module_id;
        $data['categories_itemtype'] = $this->itemtype;
        
//        $data['value'] = array(2,2,2,2);

        return parent::showInput($data);
    }

    public function showOutput(Array $data = array())
    {/*
        if (!empty($data['itemid'])) $this->itemid = $data['itemid'];
        $links = xarMod::apiFunc('categories', 'user', 'getlinkage',
                               array('itemid' => $this->itemid,
                                     'itemtype' => 3,
                                     'module' => 'dynamicdata',
                                     ));
*/
/*if (empty($data['module'])) {
            if (!empty($data['module'])) {
                $data['categories_module'] = $data['module'];
            } else {
                $data['categories_module'] = xarModGetName();
            }
        } else {
            $data['categories_module'] = $data['module'];
            unset($data['module']);
        }
        if (empty($data['itemtype'])) {
            $data['categories_itemtype'] = 0;
        } else {
            $data['categories_itemtype'] = $data['itemtype'];
        }

        if (isset($data['validation'])) $this->parseValidation($data['validation']);
        if (!isset($data['showbase'])) $data['showbase'] = $this->showbase;

        if (!isset($data['name'])) $data['name'] = "dd_" . $this->id;

        if (!empty($data['itemid'])) {
            $data['categories_itemid'] = $data['itemid'];
        } elseif (isset($this->_itemid)) {
            $data['categories_itemid'] = $this->_itemid;
        } else {
            $data['categories_itemid'] = 0;
        }

        // We have a valid itemid, so get its linked categories
        // This is the case of a property attached to an object
        $selectedcategories = array();
        if (!empty($this->categories)) {
            // We are in displaying a preview, or checkInput for our object failed
            $selectedcategories = $this->categories;
        } elseif (!empty($data['categories_itemid'])) {
            // No checkInput has run, we are in an existing object or a standalone with an itemid given
            if (empty($this->value)) {
                $data['value'] = array();
                $links = xarMod::apiFunc('categories', 'user', 'getlinkage',
                                       array('itemid' => $data['categories_itemid'],
                                             'itemtype' => $data['categories_itemtype'],
                                             'module' => $data['categories_module'],
                                             ));
                foreach ($links as $link) 
                    $selectedcategories[] = $link['id'];
            }
        }

        // We have a categories attribute
        // This is the case of a standalone property
        if (!empty($data['categories'])) $selectedcategories = $data['categories'];

        // Now make the value passed to the template the selected categories
        $data['value'] = $selectedcategories;

        // Make sure we have an array
        if (!empty($data['value']) && !is_array($data['value'])) $data['value'] = array($data['value']);
*/
        $data['value'] = $this->value;
        return parent::showOutput($data);
    }
/*
    function getOption($check = false)
    {
        if (!isset($this->value)) {
             if ($check) return true;
             return null;
        }
        $result = xarMod::apiFunc('categories','user','getcatinfo',array('cid' => $this->value));
        if (!empty($result)) {
            if ($check) return true;
            return $result['name'];
        }
        if ($check) return false;
        return $this->value;
    }
*/
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

?>