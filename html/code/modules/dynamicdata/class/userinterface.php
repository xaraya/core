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
 */

/**
 * Dynamic Object User Interface (work in progress)
 *
 * Sample usage in GUI functions:
 *
 * function mymodule_user_test($args = array())
 * {
 *     sys::import('modules.dynamicdata.class.userinterface');
 *
 *     // Add some default arguments for the interface
 *     //$args['mapper'] = array('myname' => array('classname'  => 'MyMethodHandler',
 *     //                                          'classfunc'  => 'run',
 *     //                                          'importname' => 'modules.mymodule.class.myhandler'
 *     //                                          'nextmethod' => 'display'));
 *     //$args['tplmodule'] = 'mymodule';
 *     //$args['type'] = 'user';
 *     //$args['func'] = 'test';
 *
 *     // Get the user interface
 *     $interface = new DataObjectUserInterface($args);
 *
 *     // Add some extra arguments to run the handler
 *     //$args['catid'] = 123;
 *
 *     // Handle the request of the user and return the output
 *     return $interface->handle($args);
 * }
 *
 * Sample usage for classes:
 *
 * Extend & override the default handler, and pass your own method mapper and/or handler to the interface
 *
 * class MyMethodHandler extends DataObjectDefaultHandler
 * {
 *     ...
 * }
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata
 */
class DataObjectUserInterface extends Object
{
    // application framework we're working with
    public $framework = 'xaraya';

    // method mapper
    public $mapper = array();

    // method aliases
    public $alias = array();

    // current arguments for the handler
    public $args = array();

    // current handler
    private $handler = null;

    /**
     * Set up any initial parameters (all optional)
     *
     * @param $args['framework'] the framework we're running in (= 'xaraya')
     * @param $args['mapper'] the method mapper we want to override
     * @param $args['alias'] the method aliases we want to redefine
     * @param $args['handler'] a specific handler instance we want to use
     *
     * And any other arguments we want to pass when creating the handler, e.g.
     * @param $args['tplmodule'] module where the main templates for the GUI reside (defaults to the object module)
     * @param $args['type'] main type of function handling all object method calls (= 'object' or 'user' [+ 'admin'] GUI)
     * @param $args['func'] main function handling all object method calls (to be handled by the core someday ?)
     * @param $args['nextmethod'] default next method to redirect to after create/update/delete/yourstuff/etc. (defaults to 'view')
     * @param $args any other arguments we want to pass to DataObjectMaster::getObject() or ::getObjectList() later on
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

        // define the method mapper
        $this->mapper = array(
            'create'  => array('classname'  => 'DataObjectCreateHandler',
                               'classfunc'  => 'run',
                               'importname' => 'modules.dynamicdata.class.ui_handlers.create',
                               'nextmethod' => 'view'), // or e.g. 'display' (= with itemid)

            'update'  => array('classname'  => 'DataObjectUpdateHandler',
                               'classfunc'  => 'run',
                               'importname' => 'modules.dynamicdata.class.ui_handlers.update',
                               'nextmethod' => 'view'),

            'delete'  => array('classname'  => 'DataObjectDeleteHandler',
                               'classfunc'  => 'run',
                               'importname' => 'modules.dynamicdata.class.ui_handlers.delete',
                               'nextmethod' => 'view'),

            'display' => array('classname'  => 'DataObjectDisplayHandler',
                               'classfunc'  => 'run',
                               'importname' => 'modules.dynamicdata.class.ui_handlers.display'),

            'view'    => array('classname'  => 'DataObjectViewHandler',
                               'classfunc'  => 'run',
                               'importname' => 'modules.dynamicdata.class.ui_handlers.view'),

            'default' => array('classname'  => 'DataObjectDefaultHandler',
                               'classfunc'  => 'run',
                               'importname' => 'modules.dynamicdata.class.ui_handlers.default'),
/*
            'myname'  => array('classname'  => 'MyMethodHandler',
                               'classfunc'  => 'run',
                               'importname' => 'modules.mymodule.class.myhandler',
                               'nextmethod' => 'display'),
*/
        );

        // override and/or extend the method mapper if necessary
        if (!empty($args['mapper'])) {
            foreach ($args['mapper'] as $method => $methodmap) {
                $this->mapper[$method] = $methodmap;
            }
        }

        // specify any aliases for the methods (you can have several aliases for one method)
        $this->alias = array(
            'new'    => 'create',
            'modify' => 'update',
            'remove' => 'delete', // we don't allow removing all items for an object here
            'show'   => 'display',
            'list'   => 'view',
            'other'  => 'default',
/*
            'mystuff'=> 'myname',
*/
        );

        // override and/or extend the method aliases if necessary
        if (!empty($args['alias'])) {
            foreach ($args['alias'] as $alias => $realmethod) {
                $this->alias[$alias] = $realmethod;
            }
        }

        // sanity check on method aliases during setup
        foreach ($this->alias as $alias => $realmethod) {
            if (empty($this->mapper[$realmethod])) {
                return xarML('Unknown method #(1) for alias #(2)', $realmethod, $alias);
            }
        }

        // pass a specific handler instance to run
        if (!empty($args['handler'])) {
            $this->handler = $args['handler'];
        }

        // save the arguments for the handler
        $this->args = $args;
    }

    /**
     * Determine which handler to run based on input parameters 'method' and 'itemid'
     *
     * @param $args['method'] the ui method we are handling here
     * @param $args['itemid'] item id of the object to call the method for, if the method needs it
     * @param $args any other arguments we want to pass to DataObjectMaster::getObject() or ::getObjectList() later on
     * @return string output of the handler->run() method
     */
    function handle(array $args = array())
    {
        if(!xarVarFetch('method', 'isset', $args['method'], NULL, XARVAR_DONT_SET)) 
            return;
        if(!xarVarFetch('itemid', 'isset', $args['itemid'], NULL, XARVAR_DONT_SET)) 
            return;

        // default method is 'view' without itemid, or 'display' with an itemid
        if(empty($args['method'])) 
        {
            if(empty($args['itemid'])) 
                $args['method'] = 'view';
            else 
                $args['method'] = 'display';
        }

        // get the right handler based on the method mapper above
        if (empty($this->handler)) {
            // see if we're dealing with a method alias first
            if (empty($this->mapper[$args['method']]) && !empty($this->alias[$args['method']])) {
                $realmethod = $this->alias[$args['method']];
                $methodmap = $this->mapper[$realmethod];

            // see if the method is defined
            } elseif (!empty($this->mapper[$args['method']])) {
                $methodmap = $this->mapper[$args['method']];

            // run any unknown gui method via the default handler
            } else {
                $methodmap = $this->mapper['default'];
            }

            // get the right handler class for this method
            $handlerclazz = $methodmap['classname'];

            // get the right function to call in this handler class (default is 'run')
            if (!empty($methodmap['classfunc'])) {
                $handlerfunc = $methodmap['classfunc'];
            } else {
                $handlerfunc = 'run';
            }

            // import something extra for the class definition if specified
            if (!empty($methodmap['importname'])) {
                sys::import($methodmap['importname']);
            }

            // set the default nextmethod for the handler if specified (for simple workflows)
            if (!empty($args['nextmethod'])) {
                $this->args['nextmethod'] = $args['nextmethod'];
            } elseif (!empty($methodmap['nextmethod'])) {
                $this->args['nextmethod'] = $methodmap['nextmethod'];
            }

            // create the new handler with the initial arguments
            $this->handler = new $handlerclazz($this->args);
        }

        // run the handler with any additional arguments and return the output
        return $this->handler->$handlerfunc($args);
    }

    /**
     * Return the current handler, e.g. in case you want to access something or run another time
     */
    function &getHandler()
    {
        return $this->handler;
    }

    /**
     * Return the current object in the handler, e.g. in case you want to access it afterwards
     */
    function &getObject()
    {
        if (isset($this->handler)) {
            return $this->handler->object;
        }
    }
}

/**
 * Dynamic Object Interface (deprecated)
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata
 */
class DataObjectInterface extends DataObjectUserInterface
{
}

?>