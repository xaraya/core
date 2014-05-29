<?php
/**
 * @package modules
 * @subpackage dynamicdata module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/182.html
 *
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
     *   TOCHECK: module.itemtype:xarMod::apiFunc(...)
     *       show some list of "item types" for that module via xarMod::apiFunc(...)
     *       and use itemtype to retrieve individual items via getitemlinks()
     *       E.g. "articles.1:xarMod::apiFunc('articles','user','dropdownlist',array('ptid' => 1, 'where' => ...))"
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
        if (count($this->options) > 0) {
            return $this->options;
        }
        
        if (empty($this->initialization_module)) return array();

        /*
        if (is_numeric($this->initialization_module)) {
            // we should have a regid here, if we don't get the module name
            $this->initialization_module = xarMod::getName($this->initialization_module);
        }
        */
        $options = array();
        if (empty($this->initialization_itemtype)) {
            // we're interested in the module itemtypes (= default behaviour)
            try {
                $itemtypes = xarMod::apiFunc($this->initialization_module,'user','getitemtypes');
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
                $itemlinks = xarMod::apiFunc($this->initialization_module,'user','getitemlinks',
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
}

?>
