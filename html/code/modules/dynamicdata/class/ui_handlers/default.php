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
    public $type = 'object';
    // main function handling all object method calls (to be handled by the core someday ?)
    public $func = 'main';
    // default next method to redirect to after create/update/delete/yourstuff/etc. (defaults to 'view')
    public $nextmethod = 'view';

    // current arguments for the handler
    public $args = array();

    public $object = null;

    /**
     * Default constructor for all handlers - get common input arguments for objects
     *
     * @param $args['tplmodule'] module where the main templates for the GUI reside (defaults to the object module)
     * @param $args['type'] main type of function handling all object method calls (= 'object' or 'user' [+ 'admin'] GUI)
     * @param $args['func'] main function handling all object method calls (to be handled by the core someday ?)
     * @param $args['nextmethod'] default next method to redirect to after create/update/delete/yourstuff/etc. (defaults to 'view')
     * @param $args any other arguments we want to pass to DataObjectMaster::getObject() or ::getObjectList() later on
     */
    function __construct(array $args = array())
    {
        // set a specific GUI module for now
        if (!empty($args['tplmodule'])) {
            $this->tplmodule = $args['tplmodule'];
        }
        if (!empty($args['type'])) {
            $this->type = $args['type'];
        }
        if (!empty($args['func'])) {
            $this->func = $args['func'];
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

        // save the arguments for the handler
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
            if(empty($this->tplmodule)) 
            {
                $modname = xarMod::getName($this->object->moduleid);
                $this->tplmodule = $modname;
            }
        }

        if (empty($this->object)) 
            return xarML('Unknown object #(1)', $this->args['object']);

        if (!method_exists($this->object, $this->method)) {
            return xarML('Unknown method #(1) for #(2)', xarVarPrepForDisplay($this->method), $this->object->label);
        }

        // Pre-fetch item(s) for some standard dataobject methods
        if (empty($args['itemid']) && $this->method == 'showview') {
            $this->object->getItems();

        } elseif (!empty($args['itemid']) && ($this->method == 'showdisplay' || $this->method == 'showform')) {
            $this->object->getItem();
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

        // if we're working with object URLs, use $this->nextmethod (and pass along the itemid if any)
        if ($this->type == 'object') {
            if (empty($this->nextmethod) || $this->nextmethod == 'view') {
                // Note: we skip the itemid in this case
                $return_url = xarServer::getObjectURL($this->object->name, 'view');
            } elseif (isset($this->object->itemid)) {
                $return_url = xarServer::getObjectURL($this->object->name, $this->nextmethod, array('itemid' => $this->object->itemid));
            } else {
                $return_url = xarServer::getObjectURL($this->object->name, $this->nextmethod);
            }

        // if we're working with module URLs, use $this->type and $this->func
        } else {
            $return_url = xarServer::getModuleURL(
                $this->tplmodule, $this->type, $this->func,
                array('name' => $this->object->name));
        }

        return $return_url;
    }
}

?>
