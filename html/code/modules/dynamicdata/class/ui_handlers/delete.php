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

sys::import('modules.dynamicdata.class.interface');
/**
 * Dynamic Object User Interface Handler
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata
 */
class DataObjectDeleteHandler extends DataObjectDefaultHandler
{
    public $method = 'delete';

    function run(array $args = array())
    {
        if(!xarVarFetch('cancel',  'isset', $args['cancel'],  NULL, XARVAR_DONT_SET)) 
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
        if(!empty($args['cancel'])) 
        {
            if(!xarVarFetch('return_url',  'isset', $args['return_url'], NULL, XARVAR_DONT_SET)) 
                return;
                
            if(!empty($args['return_url'])) 
                xarResponse::Redirect($args['return_url']);
            else 
                xarResponse::Redirect(xarModURL(
                    $this->tplmodule, $this->type, $this->func,
                    array('object' => $this->object->name))
                );
            // Return
            return true;
        }
        
        if(!xarSecurityCheck(
            'DeleteDynamicDataItem',1,'Item',
            $this->object->moduleid.':'.$this->object->itemtype.':'.$this->object->itemid)
        ) return;

        $itemid = $this->object->getItem();
        if(empty($itemid) || $itemid != $this->object->itemid) 
            throw new BadParameterException(null,'The itemid when deleting the object was found to be invalid');

        if(!empty($args['confirm'])) 
        {
            if (!xarSecConfirmAuthKey()) {
                return xarTplModule('privileges','user','errors',array('layout' => 'bad_author'));
            }        

            $itemid = $this->object->deleteItem();

            if(empty($itemid)) 
                return; // throw back

            if(!xarVarFetch('return_url',  'isset', $args['return_url'], NULL, XARVAR_DONT_SET)) 
                return;
                
            if(!empty($args['return_url'])) 
                xarResponse::Redirect($args['return_url']);
            else 
                xarResponse::Redirect(xarModURL(
                    $this->tplmodule, $this->type, $this->func,
                    array('object' => $this->object->name))
                );
            // Return
            return true;
        }

        $title = xarML('Delete #(1)', $this->object->label);
        xarTplSetPageTitle(xarVarPrepForDisplay($title));

        $this->object->viewfunc = $this->func;
        // TODO: have dedicated template for 'object' type
        return xarTplModule(
            $this->tplmodule,'admin','delete',
            array(
                  'object' => $this->object,
                  'authid' => xarSecGenAuthKey(),
                  'tplmodule' => $this->tplmodule,
            ),
            $this->object->template
        );
    }
}

?>
