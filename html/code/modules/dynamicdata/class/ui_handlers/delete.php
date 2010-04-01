<?php
/**
 * Dynamic Object User Interface Handler
 * @package modules
 * @copyright see the html/credits.html file in this release
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
 * @package modules
 * @subpackage dynamicdata
 */
class DataObjectDeleteHandler extends DataObjectDefaultHandler
{
    public $method = 'delete';

    /**
     * Run the ui 'delete' method
     *
     * @param $args['method'] the ui method we are handling is 'delete' here
     * @param $args['itemid'] item id of the object to delete (required here)
     * @param $args['cancel'] true if the user cancels
     * @param $args['confirm'] true if the user confirms
     * @param $args['return_url'] the url to return to when finished (defaults to the object view / module)
     * @return string output of xarTplObject() using 'ui_delete'
     */
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
            if(empty($this->object) || (!empty($this->args['object']) && $this->args['object'] != $this->object->name)) 
                return xarResponse::NotFound(xarML('Object #(1) seems to be unknown', $this->args['object']));

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

            if(empty($args['return_url'])) 
                $args['return_url'] = $this->getReturnURL();

            xarResponse::redirect($args['return_url']);
            // Return
            return true;
        }
        if(!empty($this->object->table) && !xarSecurityCheck('AdminDynamicData',0))
            return xarResponse::Forbidden(xarML('Delete Table #(1) is forbidden', $this->object->table));

        if(!xarSecurityCheck('DeleteDynamicDataItem',0,'Item',$this->object->moduleid.':'.$this->object->itemtype.':'.$this->args['itemid']))
            return xarResponse::Forbidden(xarML('Delete Itemid #(1) of #(2) is forbidden', $this->args['itemid'], $this->object->label));

        $itemid = $this->object->getItem();
        if(empty($itemid) || $itemid != $this->object->itemid) 
            return xarResponse::NotFound(xarML('Itemid #(1) of #(2) seems to be invalid', $this->args['itemid'], $this->object->label));

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
                
            if(empty($args['return_url'])) 
                $args['return_url'] = $this->getReturnURL();

            xarResponse::redirect($args['return_url']);
            // Return
            return true;
        }

        $title = xarML('Delete #(1)', $this->object->label);
        xarTplSetPageTitle(xarVarPrepForDisplay($title));

        return xarTplObject(
            $this->tplmodule, $this->object->template, 'ui_delete',
            array('object' => $this->object,
                  'authid' => xarSecGenAuthKey())
        );
    }
}

?>
