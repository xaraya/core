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
class DataObjectDisplayHandler extends DataObjectDefaultHandler
{
    public $method = 'display';

    function run(array $args = array())
    {
        if(!xarVarFetch('preview', 'isset', $args['preview'], NULL, XARVAR_DONT_SET)) 
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
                $modinfo = xarModGetInfo($this->object->moduleid);
                $this->tplmodule = $modinfo['name'];
            }
        }
        $title = xarML('Display #(1)', $this->object->label);
        xarTplSetPageTitle(xarVarPrepForDisplay($title));

        $itemid = $this->object->getItem();
        if(empty($itemid) || $itemid != $this->object->itemid) 
            throw new BadParameterException(
                null,
                'The itemid when displaying the object was found to be invalid'
            );

        // call item display hooks for this item
        $item = array();
        foreach(array_keys($this->object->properties) as $name) 
            $item[$name] = $this->object->properties[$name]->value;

        if(!isset($modinfo)) 
            $modinfo = xarModGetInfo($this->object->moduleid);

        $item['module'] = $modinfo['name'];
        $item['itemtype'] = $this->object->itemtype;
        $item['itemid'] = $this->object->itemid;
        $item['returnurl'] = xarModURL(
            $this->tplmodule,$this->type,$this->func,
            array(
                'object' => $this->object->name,
                'itemid'   => $this->object->itemid
            )
        );
        $hooks = xarModCallHooks(
            'item', 'display', $this->object->itemid, $item, $modinfo['name']
        );

        $this->object->viewfunc = $this->func;
        // TODO: have dedicated template for 'object' type
        return xarTplModule(
            $this->tplmodule,'user','display',
            array(
                'object' => $this->object,
                'hookoutput' => $hooks
            ),
            $this->object->template
        );
    }
}

?>
