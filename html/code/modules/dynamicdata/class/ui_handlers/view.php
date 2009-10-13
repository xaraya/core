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
 * @todo try to replace xarTplModule with xarTplObject
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
     * @param $args['startnum'] optional start number for the view
     * @return string output of xarTplObject() using 'ui_view'
     */
    function run(array $args = array())
    {
        if(!xarVarFetch('catid',    'isset', $args['catid'],    NULL, XARVAR_DONT_SET)) 
            return;
        if(!xarVarFetch('sort',     'isset', $args['sort'],     NULL, XARVAR_DONT_SET)) 
            return;
        if(!xarVarFetch('startnum', 'isset', $args['startnum'], NULL, XARVAR_DONT_SET)) 
            return;

        if(!empty($args) && is_array($args) && count($args) > 0) 
            $this->args = array_merge($this->args, $args);

        if(!isset($this->object)) 
        {
            $this->object =& DataObjectMaster::getObjectList($this->args);
            if(empty($this->object)) 
                return;

            if(empty($this->tplmodule)) 
            {
                $modname = xarMod::getName($this->object->moduleid);
                $this->tplmodule = $modname;
            }
        }
        $title = xarML('View #(1)', $this->object->label);
        xarTplSetPageTitle(xarVarPrepForDisplay($title));

        $this->object->countItems();

        $this->object->getItems();

$this->object->callHooks('view');

        $this->object->viewfunc = $this->func;
        // Specify link type here as well
        $this->object->linktype = $this->type;
        $this->object->linkfunc = $this->func;
        return xarTplObject(
            $this->tplmodule, $this->object->template, 'ui_view',
            array('object' => $this->object)
        );
    }
}

?>
