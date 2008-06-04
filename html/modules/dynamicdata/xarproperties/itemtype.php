<?php
/**
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */

    /**
     * Possible formats
     *
     *   DONE: module
     *       show the list of itemtypes for that module via getitemtypes()
     *       E.g. "articles" = the list of publication types in articles
     *
     *   TOCHECK: module.itemtype
     *       show the list of items for that module+itemtype via getitemlinks()
     *       E.g. "articles.1" = the list of articles in publication type 1 News Articles
     *
     *   TOCHECK: module.itemtype:xarModAPIFunc(...)
     *       show some list of "item types" for that module via xarModAPIFunc(...)
     *       and use itemtype to retrieve individual items via getitemlinks()
     *       E.g. "articles.1:xarModAPIFunc('articles','user','dropdownlist',array('ptid' => 1, 'where' => ...))"
     *       = some filtered list of articles in publication type 1 News Articles
     *
     *   TODO: support 2nd API call to retrieve the item in case getitemlinks() isn't supported
     */


/**
 * Include the base class
 */
sys::import('modules.base.xarproperties.combobox');

/**
 * Handle the item type property
 */
class ItemTypeProperty extends ComboProperty
{
    public $id         = 20;
    public $name       = 'itemtype';
    public $desc       = 'Item Type';
    public $reqmodules = array('dynamicdata');

    public $initialization_module   = '';         // get itemtypes for this module with getitemtypes()
    public $initialization_itemtype = 0;          // get items for this module+itemtype with getitemlinks()

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->filepath   = 'modules/dynamicdata/xarproperties';
    }

    public function showInput(Array $data = array())
    {
        if (!empty($data['module'])) $this->initialization_module = $data['module'];
        if (!empty($data['itemtype'])) $this->initialization_itemtype = $data['itemtype'];

        return parent::showInput($data);
    }

    /**
     * Retrieve the list of options on demand
     */
    function getOptions()
    {
        $options = $this->getFirstline();
        if (count($this->options) > 0) {
            if (!empty($firstline)) $this->options = array_merge($options,$this->options);
            return $this->options;
        }
        
        if (empty($this->initialization_module)) return array();

        if (empty($this->initialization_itemtype)) {
            // we're interested in the module itemtypes (= default behaviour)
            try {
                $itemtypes = xarModAPIFunc($this->initialization_module,'user','getitemtypes');
                if (!empty($itemtypes)) {
                    foreach ($itemtypes as $typeid => $typeinfo) {
                        if (isset($typeid) && isset($typeinfo['label'])) {
                            $options[] = array('id' => $typeid, 'name' => $typeinfo['label']);
                        }
                    }
                }
            } catch (Exception $e) {}

        } elseif (empty($this->initialization_func)) {
            // we're interested in the items for module+itemtype
            try {
                $itemlinks = xarModAPIFunc($this->initialization_module,'user','getitemlinks',
                                           array('itemtype' => $this->itemtype,
                                                 'itemids'  => null));
                if (!empty($itemlinks)) {
                    foreach ($itemlinks as $itemid => $linkinfo) {
                        if (isset($itemid) && isset($linkinfo['label'])) {
                            $options[] = array('id' => $itemid, 'name' => $linkinfo['label']);
                        }
                    }
                }
            } catch (Exception $e) {}

        } else {
            // we have some specific function to retrieve the items here
            try {
                eval('$items = ' . $this->func .';');
                if (isset($items) && count($items) > 0) {
                    foreach ($items as $id => $name) {
                        // skip empty items from e.g. dropdownlist() API
                        if (empty($id) && empty($name)) continue;
                        $options[] = array('id' => $id, 'name' => $name);
                    }
                }
            } catch (Exception $e) {}
        }

        return $options;
    }

    /**
     * Retrieve or check an individual option on demand
     */
    /* Use the parent method for now
    function getOption($check = false)
    {
        if (!isset($this->value)) {
             if ($check) return true;
             return null;
        }
        // we don't want to check empty values for items
        if (empty($this->value)) {
             if ($check) return true;
             return $this->value;
        }

        if (empty($this->initialization_module)) {
            if ($check) return true;
            return $this->value;
        }
        if (!isset($this->itemtype)) {
            // we're interested in one of the module itemtypes (= default behaviour)
            $options = $this->getOptions();
            foreach ($options as $option) {
                if ($option['id'] == $this->value) {
                    if ($check) return true;
                    return $option['name'];
                }
            }
            if ($check) return false;
            return $this->value;
        }

        // we're interested in one of the items for module+itemtype
        try {
            $itemlinks = xarModAPIFunc($this->module,'user','getitemlinks',
                                       array('itemtype' => $this->itemtype,
                                             'itemids' => array($this->value)));
            if (!empty($itemlinks) && !empty($itemlinks[$this->value])) {
                if ($check) return true;
                return $itemlinks[$this->value]['label'];
            }
        } catch (Exception $e) {}
        if ($check) return false;
        return $this->value;
    }
    */
}

?>
