<?php
/**
 * Dynamic Object User Interface 
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
 * Dynamic Object User Interface (work in progress)
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata
 */
class DataObjectUserInterface extends Object
{
    // application framework we're working with
    public $framework = 'xaraya';

    // current arguments for the handler
    public $args = array();

    /**
     * Set up any initial parameters
     */
    function __construct(array $args = array())
    {
        // set a specific framework
        if (!empty($args['framework'])) {
            $this->framework = $args['framework'];
        }
        if ($this->framework != 'xaraya') {
            // TODO: import something minimal ? :-)
        }

        // save the arguments for the handler
        $this->args = $args;
    }

    /**
     * Determine which handler to run based on input parameters 'method' and 'itemid'
     */
    function handle(array $args = array())
    {
        if(!xarVarFetch('method', 'isset', $args['method'], NULL, XARVAR_DONT_SET)) 
            return;
        if(!xarVarFetch('itemid', 'isset', $args['itemid'], NULL, XARVAR_DONT_SET)) 
            return;

        if(empty($args['method'])) 
        {
            if(empty($args['itemid'])) 
                $args['method'] = 'view';
            else 
                $args['method'] = 'display';
        }

        switch ($args['method']) 
        {
            case 'new':
            case 'create':
                $handlerclazz = 'DataObjectCreateHandler';
                sys::import('modules.dynamicdata.class.ui_handlers.create');
                break;
            case 'modify':
            case 'update':
                $handlerclazz = 'DataObjectUpdateHandler';
                sys::import('modules.dynamicdata.class.ui_handlers.update');
                break;
            case 'delete':
                $handlerclazz = 'DataObjectDeleteHandler';
                sys::import('modules.dynamicdata.class.ui_handlers.delete');
                break;
            case 'display':
                $handlerclazz = 'DataObjectDisplayHandler';
                sys::import('modules.dynamicdata.class.ui_handlers.display');
                break;
            case 'view':
            default:
                $handlerclazz = 'DataObjectViewHandler';
                sys::import('modules.dynamicdata.class.ui_handlers.view');
                break;
        }
        $handler = new $handlerclazz($this->args);

        // run the handler and return the output
        return $handler->run($args);
    }
}

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
    // main type of function handling all object method calls (= 'user' [+ 'admin'] or 'object' GUI)
    public $type = 'user';
    // main function handling all object method calls (to be handled by the core someday ?)
    public $func = 'main';

    // current arguments for the handler
    public $args = array();

    public $object = null;
    public $list   = null;

    /**
     * Get common input arguments for objects
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

        // get some common URL parameters
        if (!xarVarFetch('name',     'isset', $args['name'],     NULL, XARVAR_DONT_SET)) {return;}
        if (!xarVarFetch('object',   'isset', $args['object'],   NULL, XARVAR_DONT_SET)) {return;}
        if (!xarVarFetch('module',   'isset', $args['module'],   NULL, XARVAR_DONT_SET)) {return;}
        if (!xarVarFetch('itemtype', 'isset', $args['itemtype'], NULL, XARVAR_DONT_SET)) {return;}
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

        // support new-style name=... parameter for DD (replacing object=...)
        if (empty($args['object']) && !empty($args['name'])) {
            $args['object'] = $args['name'];
        }

        sys::import('modules.dynamicdata.class.objects');

        // retrieve the object information for this object
        if(!empty($args['object']))  {
            $info = DataObjectMaster::getObjectInfo(
                array('name' => $args['object'])
            );
            $args = array_merge($args, $info);
        } elseif (!empty($args['module']) && empty($args['moduleid'])) { 
            $args['moduleid'] = xarMod::getRegID($args['module']);
        }

        if (empty($args['layout'])) $args['layout'] = 'default';

        // save the arguments for the handler
        $this->args = $args;
    }

    /**
     * Do your thing
     */
    function run(array $args = array())
    {
        return "This method is overridden in a child class for each method";
    }
}

/**
 * Dynamic Object Interface (deprecated)
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata
 */
class DataObjectInterface extends DataObjectDefaultHandler
{
    // Provide the old __construct for SimpleObjectInterface (for now)
    function __construct(array $args = array())
    {
        parent::__construct($args);
        sys::import('modules.dynamicdata.class.objects');
    }

    function handle(array $args = array())
    {
        return "You're in the wrong class - try DataObjectUserInterface() instead";
    }
}

?>