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

sys::import('xaraya.objects');

/**
 * Dynamic Object User Interface Handler
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata
 */
class DataObjectDefaultHandler extends Object
{
    public $method = 'overridden in child classes';

    // module where the main templates for the GUI reside (defaults to the object module)
    public $tplmodule = null;
    // main type of function handling all object method calls (= 'object' or 'user' [+ 'admin'] GUI)
    public $linktype = 'object';
    // main function handling all object method calls (= if we're not using object URLs)
    public $linkfunc = 'main';
    // default next method to redirect to after create/update/delete/yourstuff/etc. (defaults to 'view')
    public $nextmethod = 'view';

    // current arguments for the handler
    public $args = array();

    public $object = null;

    /**
     * Default constructor for all handlers - get common input arguments for objects
     *
     * @param $args['tplmodule'] module where the main templates for the GUI reside (defaults to the object module)
     * @param $args['linktype'] main type of function handling all object method calls (= 'object' or 'user' [+ 'admin'] GUI)
     * @param $args['linkfunc'] main function handling all object method calls (= if we're not using object URLs)
     * @param $args['nextmethod'] default next method to redirect to after create/update/delete/yourstuff/etc. (defaults to 'view')
     * @param $args any other arguments we want to pass to DataObjectMaster::getObject() or ::getObjectList() later on
     */
    function __construct(array $args = array())
    {
        // set a specific GUI module for now
        if (!empty($args['tplmodule'])) {
            $this->tplmodule = $args['tplmodule'];
        }
        // specify the link type
        if (!empty($args['linktype'])) {
            $this->linktype = $args['linktype'];
        } else {
            $args['linktype'] = $this->linktype;
        }
        // specify the link function if relevant
        if (!empty($args['linkfunc'])) {
            $this->linkfunc = $args['linkfunc'];
        } else {
            $args['linkfunc'] = $this->linkfunc;
        }
        if (!empty($args['nextmethod'])) {
            $this->nextmethod = $args['nextmethod'];
        }

        // get some common URL parameters
        if (!xarVarFetch('object',   'isset', $args['object'],   NULL, XARVAR_DONT_SET)) {return;}
        if (!xarVarFetch('name',     'isset', $args['name'],     NULL, XARVAR_DONT_SET)) {return;}
        if (!xarVarFetch('module',   'isset', $args['module'],   NULL, XARVAR_DONT_SET)) {return;}
        if (!xarVarFetch('itemtype', 'isset', $args['itemtype'], NULL, XARVAR_DONT_SET)) {return;}
        if (!xarVarFetch('table',    'isset', $args['table'],    NULL, XARVAR_DONT_SET)) {return;}
        if (!xarVarFetch('layout',   'isset', $args['layout'],   NULL, XARVAR_DONT_SET)) {return;}
        if (!xarVarFetch('template', 'isset', $args['template'], NULL, XARVAR_DONT_SET)) {return;}
        if (!xarVarFetch('startnum', 'isset', $args['startnum'], NULL, XARVAR_DONT_SET)) {return;}
        if (!xarVarFetch('numitems', 'isset', $args['numitems'], NULL, XARVAR_DONT_SET)) {return;}

         if (!xarVarFetch('fieldlist', 'isset', $fieldlist, NULL, XARVAR_DONT_SET)) {return;}
        // make fieldlist an array, 
        // @todo should the object class do it?
        if (!empty($fieldlist)) {
            $args['fieldlist'] = explode(',',$fieldlist);
        }

        // support name=... parameter for DD if no object=... is found
        if (empty($args['object']) && !empty($args['name'])) {
            $args['object'] = $args['name'];
        }

        sys::import('modules.dynamicdata.class.objects.master');

        // retrieve the object information for this object
        if(!empty($args['object']))  {
            $info = DataObjectMaster::getObjectInfo(
                array('name' => $args['object'])
            );
            if (!empty($info)) $args = array_merge($args,$info);

        } elseif (!empty($args['module']) && empty($args['moduleid'])) { 
            $args['moduleid'] = xarMod::getRegID($args['module']);
        }

        if (empty($args['layout'])) $args['layout'] = 'default';

        // save the arguments for the handler (= used to initialize the object there)
        $this->args = $args;
    }

    /**
     * Run some other unknown ui method, or call some object/objectlist method directly
     *
     * @param $args['method'] the ui method we are handling here
     * @param $args['itemid'] item id of the object to call the method for, if the method needs it
     * @param $args any other arguments we want to pass to DataObjectMaster::getObject() or ::getObjectList()
     * @return string output of xarTplObject() using 'ui_default'
     */
    function run(array $args = array())
    {
        // This method is overridden in a child class for standard GUI methods

        if(!empty($args) && is_array($args) && count($args) > 0) 
            $this->args = array_merge($this->args, $args);

        $this->method = $this->args['method'];

        if (!isset($this->object)) {
            if (!empty($this->args['itemid'])) {
                $this->object =& DataObjectMaster::getObject($this->args);
            } else {
                $this->object =& DataObjectMaster::getObjectList($this->args);
            }
            if(empty($this->object) || (!empty($this->args['object']) && $this->args['object'] != $this->object->name)) 
                return xarResponse::NotFound(xarML('Object #(1) seems to be unknown', $this->args['object']));

            if(empty($this->tplmodule)) 
            {
                $modname = xarMod::getName($this->object->moduleid);
                $this->tplmodule = $modname;
            }
        }

        if (!method_exists($this->object, $this->method)) {
            return xarML('Unknown method #(1) for #(2)', xarVarPrepForDisplay($this->method), $this->object->label);
        }

        // Pre-fetch item(s) for some standard dataobject methods
        if (empty($args['itemid']) && $this->method == 'showview') {
            if(!xarSecurityCheck('ViewDynamicDataItems',1,'Item',$this->object->moduleid.':'.$this->object->itemtype.':All'))
                return xarResponse::Forbidden(xarML('View #(1) is forbidden', $this->object->label));

            $this->object->getItems();

        } elseif (!empty($args['itemid']) && ($this->method == 'showdisplay' || $this->method == 'showform')) {
            if(!xarSecurityCheck('ReadDynamicDataItem',1,'Item',$this->object->moduleid.':'.$this->object->itemtype.':'.$this->args['itemid']))
                return xarResponse::Forbidden(xarML('Display Itemid #(1) of #(2) is forbidden', $this->args['itemid'], $this->object->label));

            // get the requested item
            $itemid = $this->object->getItem();
            if(empty($itemid) || $itemid != $this->object->itemid) 
                return xarResponse::NotFound(xarML('Itemid #(1) of #(2) seems to be invalid', $this->args['itemid'], $this->object->label));
        }

        $title = $this->object->label;
        xarTplSetPageTitle(xarVarPrepForDisplay($title));

        // Here we try to run the requested method directly
        $output = $this->object->{$this->method}($this->args);

       // CHECKME: do we redirect to return_url or nextmethod in some cases here too ?

        return xarTplObject(
            $this->tplmodule, $this->object->template, 'ui_default',
            array('object' => $this->object,
                  'output' => $output)
        );

    }

    /**
     * Get the return URL (based on argument or handler settings)
     *
     * @param $url any $args['return_url'] given by the method
     * @return string the return url
     */
    function getReturnURL($return_url = '')
    {
        // if we already have a return_url, use that
        if (!empty($return_url)) {
            return $return_url;
        }

        if (isset($this->object->itemid)) {
            $return_url = xarObject::getActionURL($this->object, $this->nextmethod, $this->object->itemid);
        } else {
            $return_url = xarObject::getActionURL($this->object, $this->nextmethod);
        }

        return $return_url;
    }
}

?>
