<?php
/**
 * Include the base class
 */
sys::import('modules.base.xarproperties.combobox');
/**
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
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
 * This property displays a dropdown/textbox of dynamicdata itemtypes
 */
class ItemTypeProperty extends ComboProperty
{
    public $id         = 20;
    public $name       = 'itemtype';
    public $desc       = 'Item Type';
    public $reqmodules = ['dynamicdata'];

    public $initialization_module   = '';         // get itemtypes for this module with getitemtypes()
    public $initialization_itemtype = 0;          // get items for this module+itemtype with getitemlinks()

    public $itemtype;
    public $func;

    public function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->filepath   = 'modules/dynamicdata/xarproperties';
    }

    /**
     * Display a Dropdown/textbox for input
     *
     * @param array<string, mixed> $data An array of input parameters
     * @return string     HTML markup to display the property for input on a web page
     */
    public function showInput(array $data = [])
    {
        if (!empty($data['module'])) {
            $this->initialization_module = $data['module'];
        }
        if (!empty($data['itemtype'])) {
            $this->initialization_itemtype = $data['itemtype'];
        }

        return parent::showInput($data);
    }

    /**
     * Retrieve the list of options on demand
     *
     */
    public function getOptions()
    {
        if (count($this->options) > 0) {
            return $this->options;
        }

        if (empty($this->initialization_module)) {
            return [];
        }

        /*
        if (is_numeric($this->initialization_module)) {
            // we should have a regid here, if we don't get the module name
            $this->initialization_module = xarMod::getName($this->initialization_module);
        }
        */
        $options = [];
        if (empty($this->initialization_itemtype)) {
            // we're interested in the module itemtypes (= default behaviour)
            try {
                $itemtypes = xarMod::apiFunc($this->initialization_module, 'user', 'getitemtypes');
                if (!empty($itemtypes)) {
                    foreach ($itemtypes as $typeid => $typeinfo) {
                        if (isset($typeid) && isset($typeinfo['label'])) {
                            $options[] = ['id' => $typeid, 'name' => $typeinfo['label']];
                        }
                    }
                }
            } catch (Exception $e) {
            }

        } elseif (empty($this->initialization_func)) {
            // we're interested in the items for module+itemtype
            try {
                $itemlinks = xarMod::apiFunc(
                    $this->initialization_module,
                    'user',
                    'getitemlinks',
                    ['itemtype' => $this->itemtype,
                                                 'itemids'  => null]
                );
                if (!empty($itemlinks)) {
                    foreach ($itemlinks as $itemid => $linkinfo) {
                        if (isset($itemid) && isset($linkinfo['label'])) {
                            $options[] = ['id' => $itemid, 'name' => $linkinfo['label']];
                        }
                    }
                }
            } catch (Exception $e) {
            }

        } else {
            // @todo re-align with initialization_function in combobox
            // we have some specific function to retrieve the items here
            try {
                eval('$items = ' . $this->func .';');
                /** @var array<mixed> $items */
                if (isset($items) && count($items) > 0) {
                    foreach ($items as $id => $name) {
                        // skip empty items from e.g. dropdownlist() API
                        if (empty($id) && empty($name)) {
                            continue;
                        }
                        $options[] = ['id' => $id, 'name' => $name];
                    }
                }
            } catch (Exception $e) {
            }
        }

        return $options;
    }
}
