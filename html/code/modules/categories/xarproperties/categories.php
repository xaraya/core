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

    public $baselist   = 'all';
    public $cidlist    = array();
    public $itemid     = 0;
    public $showbase   = true;

    public $module;
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
        list($isvalid, $categories) = $this->fetchValue($name . '["categories"]');
        if ($categories == null) {
            if (!xarVarFetch($name . '["categories"]', 'isset', $categories, array(), XARVAR_NOT_REQUIRED)) return;
        }
        // Make sure we have an array of categories
        // CHECKME
        if (isset($categories) && !is_array($categories)) $categories = array($categories);
        $value = $categories;
        
        return $this->validateValue($value);
    }

    public function validateValue($value = null)
    {
        // The following passes for validateValue in this property. We do it this way because we have more than one "value"
        
        //Begin checks
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
        // Check the number of base categories against the number categories we have
        // Remark: some of the selected categories might be empty here !
        if (count($this->basecategories) != count($value)) {
            $this->invalid = xarML("The number of categories and their base categories is not the same");
            $this->value = null;
            return false;
        }
        // End checks
        
        // We passed the checks, set the categories
        $this->categories = $value;
        
        // Keep a reference of the data of this property in $this->value, for saving or easy manipulation
        $this->value = $value;        
        return true;
    }

    public function createValue($itemid=0)
    {
        // If there was no preceding checkInput, do nothing
        if (!isset($this->module)) return true;

        if (!empty($itemid)) {
            $result = xarMod::apiFunc('categories', 'admin', 'unlink',
                              array('iid' => $itemid,
                                    'itemtype' => $this->itemtype,
                                    'modid' => xarMod::getRegId($this->module)));
        }

        // Remark: some of the selected categories might be empty here !
        $cleancats = array();
        foreach ($this->categories as $category) {
            if (empty($category)) continue;
            $cleancats[] = $category;
        }

        if (count($cleancats) > 0) {
            $result = xarMod::apiFunc('categories', 'admin', 'linkcat',
                                  array('cids'        => $cleancats,
                                        'iids'        => array($itemid),
                                        'itemtype'    => $this->itemtype,
                                        'modid'       => xarMod::getRegId($this->module),
                                        'basecids'    => $this->basecategories,
                                        'check'       => false,
                                        'clean_first' => true));
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

    /* REMEMBERME: old code. remove at some point
    public function returnInput($name = '', $value = null)
    {
        $name = empty($name) ? 'dd_'.$this->id : $name;
        // store the fieldname for validations who need them (e.g. file uploads)
        $this->fieldname = $name;

        list($isvalid, $categories) = $this->fetchValue($name . '_categories');
        if ($isvalid) {
            if (!is_array($categories)) {
                $categories = array($categories);
            } else {
                if (!xarVarFetch($name . '_categories', 'array', $categories, array(), XARVAR_NOT_REQUIRED)) return;
            }
        } else {
            $categories = array();
        }
        return $categories;
    }

    public function saveInput($name = '', $value = null)
    {
        $name = empty($name) ? 'dd_'.$this->id : $name;
        // store the fieldname for validations who need them (e.g. file uploads)
        $this->fieldname = $name;

        if (!xarVarFetch($name . '_categories_module', 'str', $modname, '', XARVAR_NOT_REQUIRED)) return;
        if (empty($modname)) $modname = xarModGetName();
        if (!xarVarFetch($name . '_categories_itemtype', 'int', $itemtype, 0, XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch($name . '_categories_basecats', 'array', $basecats, array(), XARVAR_NOT_REQUIRED)) return;

        $categories = $this->returnInput($name, $value);

        if (!xarVarFetch($name . '_categories_itemid', 'int', $itemid, 0, XARVAR_NOT_REQUIRED)) return;
        if (!$itemid) $itemid = $value;

        $result = xarMod::apiFunc('categories', 'admin', 'unlink',
                          array('iid' => $itemid,
                                'itemtype' => $itemtype,
                                'modid' => xarMod::getRegId($modname)));
        if (count($categories) > 0) {
            $result = xarMod::apiFunc('categories', 'admin', 'linkcat',
                                array('cids'  => $categories,
                                      'iids'  => array($itemid),
                                      'itemtype' => $itemtype,
                                      'modid' => xarMod::getRegId($modname),
                                      'basecids'  => $basecats,
                                      'clean_first' => true));
        }
        return true;
    }
*/
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
        $filter = array(
            'getchildren' => true,
            'maxdepth' => isset($data['maxdepth'])?$data['maxdepth']:null,
            'mindepth' => isset($data['mindepth'])?$data['mindepth']:null,
            'cidlist'  => $this->cidlist,
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
        var_dump($this->itemtype);
        
        var_dump($data['value']);
//        $data['value'] = array(2,2,2,2);
/*        if (empty($data['module'])) {
            if (!empty($data['module'])) {
                $data['categories_module'] = $data['module'];
            } else {
                if (!empty($this->module)) {
                    $data['categories_module'] = $this->module;
                } else {
                    $data['categories_module'] = xarModGetName();
                }
            }
        } else {
            $data['categories_module'] = $data['module'];
            unset($data['module']);
        }
        
        if (!isset($data['itemtype'])) {
            if (!empty($this->itemtype)) {
                $data['categories_itemtype'] = $this->itemtype;
            } else {
                $data['categories_itemtype'] = 0;
            }
        } else {
            $data['categories_itemtype'] = $data['itemtype'];
        }

        if (isset($data['validation'])) $this->parseValidation($data['validation']);
        if (!isset($data['bases'])) $data['bases'] = $this->baselist;

        if (!is_array($data['bases'])) {
            // Return an array where each toplevel category is a base category
            if (strtolower($data['bases']) == 'all') {
                if (empty($data['categories_itemtype'])) {
                    $basecats = xarMod::apiFunc('categories','user','getallcatbases',array('module' => $data['categories_module']));
                } else {
                    $basecats = xarMod::apiFunc('categories','user','getallcatbases',array('module' => $data['categories_module'], 'itemtype' => $data['categories_itemtype']));
                }
                $data['basecids'] = array();
                foreach ($basecats as $basecat) $data['basecids'][] = $basecat['category_id'];

            // Return an array where the only base category is the parent all categories
            } elseif (strtolower($data['bases']) == 'single') {
                $data['basecids'] = array(0);

            // Return an array with no base categories
            } elseif (strtolower($data['bases']) == 'none') {
                $data['basecids'] = array();

            // Return an array of base categories we got from the tag
            } else {
                $data['basecids'] = explode(',',$data['bases']);
            }
        } else {
            // still todo: display manually entered basecat trees
            // right now works for 1 basecat
            $data['basecids'] = $data['bases'];
        }

        $filter = array(
            'getchildren' => true,
            'maxdepth' => isset($data['maxdepth'])?$data['maxdepth']:null,
            'mindepth' => isset($data['mindepth'])?$data['mindepth']:null,
            'cidlist'  => $this->cidlist,
        );
        $returnitself = (empty($data['returnitself'])) ? false : $data['returnitself'];
        $data['trees'] = array();
        if (empty($data['basecids'])) $data['basecids'] = array(0);
        if ($data['basecids'] == array(0) || empty($data['basecids'])) {
            $toplevel = xarMod::apiFunc('categories','user','getchildren',array('cid' => 0));
            $nodes = new BasicSet();
            foreach ($toplevel as $entry) {
                $node = new CategoryTreeNode($entry['id']);
                $node->setfilter($filter);
                $tree = new CategoryTree($node);
                $nodes->addAll($node->depthfirstenumeration());
            }
            $data['trees'][] = $nodes;
        } else {
            foreach ($data['basecids'] as $cid) {
                $nodes = new BasicSet();
                $node = new CategoryTreeNode($cid);
                $node->setfilter($filter);
                $tree = new CategoryTree($node);
                $nodes->addAll($node->depthfirstenumeration());
                $data['trees'][] = $nodes;
            }
        }
        if (!isset($data['name'])) $data['name'] = "dd_" . $this->id;
        if (!isset($data['javascript'])) $data['javascript'] = '';
        if (!isset($data['multiple'])) $data['multiple'] = 0;

        if (empty($data['show_edit']) || !empty($data['multiple'])) {
            $data['show_edit'] = 0;
        }

        // Now we need to figure out which categories are displayed
        $selectedcategories = array();
    
        if (!empty($data['itemid'])) {
            $data['categories_itemid'] = $data['itemid'];
        } elseif (isset($this->_itemid)) {
            $data['categories_itemid'] = $this->_itemid;
        } else {
            $data['categories_itemid'] = 0;
        }

        // We have a valid itemid, so get its linked categories
        // This is the case of a property attached to an object
        if (!empty($this->categories)) {
            // We are in displaying a preview, or checkInput for our object failed
            $selectedcategories = $this->categories;
        } elseif (!empty($data['categories_itemid'])) {           
            // No checkInput has run, we are in an existing object or a standalone with an itemid given
            $links = xarMod::apiFunc('categories', 'user', 'getlinkage',
                                   array('itemid' => $data['categories_itemid'],
                                         'itemtype' => $data['categories_itemtype'],
                                         'module' => $data['categories_module'],
                                          ));
            $catlink = array();
            foreach ($links as $link) {
                $fulllink = !empty($link['childid']) ? $link['id'] . "." . $link['childid'] : $link['id'];
                $catlink[$link['basecategory_id']] = $fulllink;
            }
            foreach ($data['basecids'] as $basecid)
                $selectedcategories[] = isset($catlink[$basecid]) ? $catlink[$basecid]: 0;
        }

        // We have a categories attribute
        // This is the case of a standalone property
        if (!empty($data['categories'])) $selectedcategories = $data['categories'];
        
        // This is just for backward compatibility in the template
        if (empty($selectedcategories) && isset($data['value']))  $selectedcategories = $data['value'];

        // CHECKME: are you sure you want to do that ?
        // No information passed, so just make the base categories the selected categories
        if (empty($selectedcategories))  $selectedcategories = $data['basecids'];

    // Note : $data['values'][$id] will be updated inside the template, so that when several
    //        select boxes are used with overlapping trees, categories will only be selected once
    // This requires that the values are passed by reference : $data['values'] =& $seencids;
//        if (isset($data['values'])) {
//            $GLOBALS['Categories_MakeSelect_Values'] =& $data['values'];
//        }

// FIXME: where was this itemid value supposed to come from ???
        // This is just for backward compatibility in the template
//        $data['categories_itemid'] = isset($data['value']) ? $data['value'] : 0;


        // Now make the value passed to the template the selected categories
        $data['value'] = $selectedcategories;

        // Make sure we have an array
        if (!empty($data['value']) && !is_array($data['value'])) $data['value'] = array($data['value']);

        $configuration = unserialize($this->configuration);
        $data['tree_name'] = $configuration['initialization_basecategories'][1][1];
        $data['include_self'] = $configuration['initialization_basecategories'][2][1];
        $data['select_type'] = $configuration['initialization_basecategories'][3][1];
        */
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