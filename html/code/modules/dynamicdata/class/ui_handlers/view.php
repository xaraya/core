<?php
/**
 * Dynamic Object User Interface Handler
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */

sys::import('modules.dynamicdata.class.ui_handlers.default');
/**
 * Dynamic Object User Interface Handler
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata
 */
class DataObjectViewHandler extends DataObjectDefaultHandler
{
    public $method = 'view';

    /**
     * Run the ui 'view' method
     *
     * @param $args['method'] the ui method we are handling is 'view' here
     * @param $args['catid'] optional category for the view
     * @param $args['sort'] optional sort for the view
     * @param $args['where'] optional where clause(s) for the view
     * @param $args['startnum'] optional start number for the view
     * @return string output of xarTplObject() using 'ui_view'
     */
    function run(array $args = array())
    {
        if(!xarVarFetch('catid',    'isset', $args['catid'],    NULL, XARVAR_DONT_SET)) 
            return;
        if(!xarVarFetch('sort',     'isset', $args['sort'],     NULL, XARVAR_DONT_SET)) 
            return;
        if(!xarVarFetch('where',    'isset', $args['where'],    NULL, XARVAR_DONT_SET)) 
            return;
        if(!xarVarFetch('startnum', 'isset', $args['startnum'], NULL, XARVAR_DONT_SET)) 
            return;

        // TODO: support array in objectlist->setWhere()
        if (!empty($args['where']) && is_array($args['where'])) {
            $whereparts = array();
            foreach ($args['where'] as $key => $val) {
                if (empty($key) || !isset($val) || $val === '') continue;
                if (is_numeric($val)) {
                    $whereparts[] = $key . " eq " . $val;
                } else {
                    $whereparts[] = $key . " eq '" . $val . "'";
                }
            }
            if (count($whereparts) > 0) {
                $args['where'] = implode(' and ', $whereparts);
            } else {
                unset($args['where']);
            }
        }

        if(!empty($args) && is_array($args) && count($args) > 0) 
            $this->args = array_merge($this->args, $args);

        if (xarCache::$outputCacheIsEnabled && xarOutputCache::$objectCacheIsEnabled) {
            // we'll let xarObjectCache determine the cacheKey here
            $cacheKey = xarObjectCache::checkCachingRules(null, $this->args);
            if ($cacheKey && xarObjectCache::isCached($cacheKey, $this->args)) {
        // CHECKME: save & get page title here too ?
                return xarObjectCache::getCached($cacheKey);
            }
        }

        if(!isset($this->object)) 
        {
            $this->object =& DataObjectMaster::getObjectList($this->args);
            if(empty($this->object) || (!empty($this->args['object']) && $this->args['object'] != $this->object->name)) 
                return xarResponse::NotFound(xarML('Object #(1) seems to be unknown', $this->args['object']));

            if(empty($this->tplmodule)) 
            {
                $modname = xarMod::getName($this->object->moduleid);
                $this->tplmodule = $modname;
            }
        }
        $title = xarML('View #(1)', $this->object->label);
        xarTplSetPageTitle(xarVarPrepForDisplay($title));

        if(!empty($this->object->table) && !xarSecurityCheck('AdminDynamicData'))
            return xarResponse::Forbidden(xarML('View Table #(1) is forbidden', $this->object->table));

        if(!xarSecurityCheck('ViewDynamicDataItems',1,'Item',$this->object->moduleid.':'.$this->object->itemtype.':All'))
            return xarResponse::Forbidden(xarML('View #(1) is forbidden', $this->object->label));

        $this->object->countItems();

        $this->object->getItems();

$this->object->callHooks('view');

        $output = xarTplObject(
            $this->tplmodule, $this->object->template, 'ui_view',
            array('object' => $this->object)
        );

        if (!empty($cacheKey)) {
            xarObjectCache::setCached($cacheKey, $output);
        }
        return $output;
    }
}

?>
