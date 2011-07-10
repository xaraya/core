<?php
/**
 * Dynamic Object User Interface Handler
 * @package modules
 * @subpackage dynamicdata module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/182.html
 *
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
     * @return string output of xarTpl::object() using 'ui_delete'
     */
    function run(array $args = array())
    {
        if(!xarVarFetch('cancel',  'isset', $args['cancel'],  NULL, XARVAR_DONT_SET)) 
            return;
        if(!xarVarFetch('confirm', 'isset', $args['confirm'], NULL, XARVAR_DONT_SET)) 
            return;
        if(!xarVarFetch('return_url', 'isset', $args['return_url'], NULL, XARVAR_DONT_SET)) 
            return;

        if(!empty($args) && is_array($args) && count($args) > 0) 
            $this->args = array_merge($this->args, $args);

        if(!isset($this->object)) 
        {
            $this->object =& DataObjectMaster::getObject($this->args);
            if(empty($this->object) || (!empty($this->args['object']) && $this->args['object'] != $this->object->name)) 
                return xarController::$response->NotFound(xarML('Object #(1) seems to be unknown', $this->args['object']));

            if(empty($this->tplmodule)) 
            {
                $modname = xarMod::getName($this->object->moduleid);
                $this->tplmodule = $modname;
            }
        }
        if(!empty($args['cancel'])) 
        {
            if(empty($args['return_url'])) 
                $args['return_url'] = $this->getReturnURL();

            xarController::redirect($args['return_url']);
            // Return
            return true;
        }
        if (!$this->object->checkAccess('delete'))
            return xarController::$response->Forbidden(xarML('Delete Itemid #(1) of #(2) is forbidden', $this->args['itemid'], $this->object->label));

        $itemid = $this->object->getItem();
        if(empty($itemid) || $itemid != $this->object->itemid) 
            return xarController::$response->NotFound(xarML('Itemid #(1) of #(2) seems to be invalid', $this->args['itemid'], $this->object->label));

        if(!empty($args['confirm'])) 
        {
            if (!xarSecConfirmAuthKey()) {
                return xarTpl::module('privileges','user','errors',array('layout' => 'bad_author'));
            }        

            $itemid = $this->object->deleteItem();

            if(empty($itemid)) 
                return; // throw back

            if(empty($args['return_url'])) 
                $args['return_url'] = $this->getReturnURL();

            xarController::redirect($args['return_url']);
            // Return
            return true;
        }

        $title = xarML('Delete #(1)', $this->object->label);
        xarTpl::setPageTitle(xarVarPrepForDisplay($title));

        return xarTpl::object(
            $this->tplmodule, $this->object->template, 'ui_delete',
            array('object' => $this->object,
                  'authid' => xarSecGenAuthKey(),
                  'tpltitle' => $this->tpltitle,
                  'return_url' => $args['return_url'])
        );
    }
}

?>
