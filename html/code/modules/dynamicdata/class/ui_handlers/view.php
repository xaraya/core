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

        if(!isset($this->list)) 
        {
            $this->list =& DataObjectMaster::getObjectList($this->args);
            if(empty($this->list)) 
                return;

            if(empty($this->tplmodule)) 
            {
                $modname = xarMod::getName($this->list->moduleid);
                $this->tplmodule = $modname;
            }
        }
        $title = xarML('View #(1)', $this->list->label);
        xarTplSetPageTitle(xarVarPrepForDisplay($title));

        $this->list->getItems();

        $this->list->viewfunc = $this->func;
        // Specify link type here as well
        $this->list->linktype = $this->type;
        $this->list->linkfunc = $this->func;
        // TODO: have dedicated template for 'object' type
        return xarTplModule(
            $this->tplmodule,'user','view',
            array('object' => $this->list,
                  'layout' => $this->list->layout),
            $this->list->template
        );
    }
}

?>
