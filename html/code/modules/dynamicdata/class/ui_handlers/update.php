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
class DataObjectUpdateHandler extends DataObjectDefaultHandler
{
    public $method = 'update';

    /**
     * Run the ui 'update' method
     *
     * @param $args['method'] the ui method we are handling is 'update' here
     * @param $args['itemid'] item id of the object to update (required here), and
     * @param $args['preview'] true if you want dd to call checkInput() = standard dd preview using GET/POST params, or
     * @param $args['values'] array of predefined field values to use = ui-specific preview using arguments in your call
     * @param $args['confirm'] true if the user confirms
     * @param $args['return_url'] the url to return to when finished (defaults to the object view / module)
     * @return string output of xarTplObject() using 'ui_update'
     */
    function run(array $args = array())
    {
        if(!xarVarFetch('preview', 'isset', $args['preview'], NULL, XARVAR_DONT_SET)) 
            return;
        if(!xarVarFetch('confirm', 'isset', $args['confirm'], NULL, XARVAR_DONT_SET)) 
            return;
        if(!xarVarFetch('values', 'isset', $args['values'], NULL, XARVAR_DONT_SET)) 
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
        if (!$this->object->checkAccess('update'))
            return xarResponse::Forbidden(xarML('Update Itemid #(1) of #(2) is forbidden', $this->args['itemid'], $this->object->label));

        $itemid = $this->object->getItem();
        if(empty($itemid) || $itemid != $this->object->itemid) 
            return xarResponse::NotFound(xarML('Itemid #(1) of #(2) seems to be invalid', $this->args['itemid'], $this->object->label));

        if (!empty($this->args['values'])) {
            // always set the properties based on the given values !?
            //$this->object->setFieldValues($this->args['values']);
            // check any given input values but suppress errors for now
            $this->object->checkInput($this->args['values'], 1);
        }

        if(!empty($args['preview']) || !empty($args['confirm'])) 
        {
            if (!empty($args['confirm']) && !xarSecConfirmAuthKey()) {
                return xarTplModule('privileges','user','errors',array('layout' => 'bad_author'));
            }

            $isvalid = $this->object->checkInput();

            if($isvalid && !empty($args['confirm'])) 
            {
                $itemid = $this->object->updateItem();

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
            $args['preview'] = true;
        }

        $title = xarML('Modify #(1)', $this->object->label);
        xarTplSetPageTitle(xarVarPrepForDisplay($title));

        // call item modify hooks for this item
        $this->object->callHooks('modify');

        return xarTplObject(
            $this->tplmodule, $this->object->template, 'ui_update',
            array('object'  => $this->object,
                  'preview' => $args['preview'],
                  'authid'  => xarSecGenAuthKey(),
                  'hooks'   => $this->object->hookoutput)
        );
    }
}

?>
