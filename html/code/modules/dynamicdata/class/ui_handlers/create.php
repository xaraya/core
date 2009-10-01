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
class DataObjectCreateHandler extends DataObjectDefaultHandler
{
    public $method = 'create';

    function run(array $args = array())
    {
        if(!xarVarFetch('preview', 'isset', $args['preview'], NULL, XARVAR_DONT_SET)) 
            return;
        if(!xarVarFetch('confirm', 'isset', $args['confirm'], NULL, XARVAR_DONT_SET)) 
            return;

        if(!empty($args) && is_array($args) && count($args) > 0) 
            $this->args = array_merge($this->args, $args);

        if(!isset($this->object)) 
        {
            $this->object =& DataObjectMaster::getObject($this->args);
            if(empty($this->object)) 
                return;
            if(empty($this->tplmodule)) 
            {
                $modname = xarMod::getName($this->object->moduleid);
                $this->tplmodule = $modname;
            }
        }
        if(!xarSecurityCheck(
            'AddDynamicDataItem',1,'Item',
            $this->object->moduleid.':'.$this->object->itemtype.':All')
        )   return;

        //$this->object->getItem();

        if(!empty($args['preview']) || !empty($args['confirm'])) 
        {
            if (!xarSecConfirmAuthKey()) {
                return xarTplModule('privileges','user','errors',array('layout' => 'bad_author'));
            }        

            $isvalid = $this->object->checkInput();

            if($isvalid && !empty($args['confirm'])) 
            {
                $itemid = $this->object->createItem();

                if(empty($itemid)) 
                    return; // throw back

                if(!xarVarFetch('return_url',  'isset', $args['return_url'], NULL, XARVAR_DONT_SET)) 
                    return;

                if(!empty($args['return_url'])) 
                {
                    xarResponse::Redirect($args['return_url']);
                } 
                else 
                {
                    xarResponse::Redirect(xarModURL(
                        $this->tplmodule, $this->type, $this->func,
                        array('object' => $this->object->name))
                    );
                }
                // Return
                return true;
            }
        }

        $title = xarML('New #(1)', $this->object->label);
        xarTplSetPageTitle(xarVarPrepForDisplay($title));

        // call item new hooks for this item
        $item = array();
        foreach(array_keys($this->object->properties) as $name) 
            $item[$name] = $this->object->properties[$name]->value;

        if(!isset($modname)) 
            $modname = xarMod::getName($this->object->moduleid);

        $item['module'] = $modname;
        $item['itemtype'] = $this->object->itemtype;
        $item['itemid'] = $this->object->itemid;
        $hooks = xarModCallHooks('item', 'new', $this->object->itemid, $item, $modname);

        $this->object->viewfunc = $this->func;
        // TODO: have dedicated template for 'object' type
        return xarTplModule(
            $this->tplmodule,'admin','new',
            array(
                'object' => $this->object,
                'preview' => $args['preview'],
                'authid' => xarSecGenAuthKey(),
                'tplmodule' => $this->tplmodule,
                'hookoutput' => $hooks
            ),
            $this->object->template
        );
    }
}

?>
