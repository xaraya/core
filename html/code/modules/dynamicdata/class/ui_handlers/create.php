<?php
/**
 * Dynamic Object User Interface Handler
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

sys::import('modules.dynamicdata.class.ui_handlers.default');
/**
 * Dynamic Object User Interface Handler
 *
 */
class DataObjectCreateHandler extends DataObjectDefaultHandler
{
    public $method = 'create';

    /**
     * Run the ui 'create' method
     *
     * @param $args['method'] the ui method we are handling is 'create' here
     * @param $args['preview'] true if you want dd to call checkInput() = standard dd preview using GET/POST params, or
     * @param $args['values'] array of predefined field values to use = ui-specific preview using arguments in your call
     * @param $args['confirm'] true if the user confirms
     * @param $args['return_url'] the url to return to when finished (defaults to the object view / module)
     * @return string output of xarTpl::object() using 'ui_create'
     */
    function run(array $args = array())
    {
        if(!xarVarFetch('preview', 'isset', $args['preview'], NULL, XARVAR_DONT_SET)) 
            return;
        if(!xarVarFetch('confirm', 'isset', $args['confirm'], NULL, XARVAR_DONT_SET)) 
            return;
        if(!xarVarFetch('values', 'isset', $args['values'], NULL, XARVAR_DONT_SET)) 
            return;
        if(!xarVarFetch('return_url', 'isset', $args['return_url'], NULL, XARVAR_DONT_SET)) 
            return;

        if(!empty($args) && is_array($args) && count($args) > 0) 
            $this->args = array_merge($this->args, $args);

        if(!isset($this->object)) 
        {
            $this->object = DataObjectMaster::getObject($this->args);
            if(empty($this->object) || (!empty($this->args['object']) && $this->args['object'] != $this->object->name)) 
                return xarController::$response->NotFound(xarML('Object #(1) seems to be unknown', $this->args['object']));

            if(empty($this->tplmodule)) 
            {
                $modname = xarMod::getName($this->object->moduleid);
                $this->tplmodule = $modname;
            }
        }
        if (!$this->object->checkAccess('create'))
            return xarController::$response->Forbidden(xarML('Create #(1) is forbidden', $this->object->label));

        // there's no item to get here yet
        //$this->object->getItem();

        if (!empty($this->args['values'])) {
            // always set the properties based on the given values !?
            //$this->object->setFieldValues($this->args['values']);
            // check any given input values but suppress errors for now
            $this->object->checkInput($this->args['values'], 1);
        }

        if(!empty($args['preview']) || !empty($args['confirm'])) 
        {
            if (!empty($args['confirm']) && !xarSecConfirmAuthKey()) {
                return xarTpl::module('privileges','user','errors',array('layout' => 'bad_author'));
            }

            $isvalid = $this->object->checkInput();

            if($isvalid && !empty($args['confirm'])) 
            {
                $itemid = $this->object->createItem();

                if(empty($itemid)) 
                    return; // throw back

                if(empty($args['return_url'])) 
                    $args['return_url'] = $this->getReturnURL();

                xarController::redirect($args['return_url']);
                // Return
                return true;
            }
            $args['preview'] = true;
        }

        $title = xarML('New #(1)', $this->object->label);
        xarTpl::setPageTitle(xarVarPrepForDisplay($title));

        // call item new hooks for this item
        $this->object->callHooks('new');

        return xarTpl::object(
            $this->tplmodule, $this->object->template, 'ui_create',
            array('object'  => $this->object,
                  'preview' => $args['preview'],
                  'authid'  => xarSecGenAuthKey(),
                  'hooks'   => $this->object->hookoutput,
                  'tpltitle' => $this->tpltitle,
                  'return_url' => $args['return_url'])
        );
    }
}

?>
