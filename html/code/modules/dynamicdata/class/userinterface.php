<?php
/**
 * Dynamic Object User Interface
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

use Xaraya\DataObject\Handlers\DefaultHandler;

/**
 * Dynamic Object User Interface (work in progress)
 *
 * Sample usage in GUI functions:
 *
 * function mymodule_user_test($args = [])
 * {
 *     sys::import('modules.dynamicdata.class.userinterface');
 *
 *     // Add some extra arguments for the interface, e.g. for a non-standard method handler
 *     //$args['mapper'] = ['myname' => ['classname'  => 'MyMethodHandler',
 *     //                                'classfunc'  => 'run',
 *     //                                'importname' => 'modules.mymodule.class.myhandler'
 *     //                                'nextmethod' => 'display']];
 *     //$args['tplmodule'] = 'mymodule';
 *     //$args['linktype'] = 'user';
 *     //$args['linkfunc'] = 'test';
 *
 *     // Get the user interface
 *     $interface = new DataObjectUserInterface($args);
 *
 *     // Specify the method if it's not given in the arguments or URL parameters
 *     //$args['method'] = 'myname';
 *
 *     // Add some extra arguments to run the handler if you want
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
 * use Xaraya\DataObject\Handlers\DefaultHandler;
 *
 * class MyMethodHandler extends DefaultHandler
 * {
 *     ...
 * }
 *
 */
class DataObjectUserInterface extends xarObject
{
    // application framework we're working with
    public string $framework = 'xaraya';

    // method mapper
    /** @var array<string, mixed> */
    public array $mapper = [];

    // method aliases
    /** @var array<string, string> */
    public array $alias = [];

    // class namespace
    public string $namespace = 'Xaraya\DataObject\Handlers';

    // current arguments for the handler
    /** @var array<string, mixed> */
    public array $args = [];

    // current handler
    /** @var DefaultHandler|object|null */
    private $handler = null;

    /**
     * Set up any initial parameters (all optional)
     *
     * @param array<string, mixed> $args
     * with
     *     $args['framework'] the framework we're running in (= 'xaraya')
     *     $args['mapper'] the method mapper we want to override
     *     $args['alias'] the method aliases we want to redefine
     *     $args['handler'] a specific handler instance we want to use
     *
     * And any other arguments we want to pass when creating the handler, e.g.
     *     $args['tplmodule'] module where the main templates for the GUI reside (defaults to the object module)
     *     $args['linktype'] main type of function handling all object method calls (= 'object' or 'user' [+ 'admin'] GUI)
     *     $args['linkfunc'] main function handling all object method calls (= if we're not using object URLs)
     *     $args['nextmethod'] default next method to redirect to after create/update/delete/yourstuff/etc. (defaults to 'view')
     *     $args any other arguments we want to pass to DataObjectFactory::getObject() or ::getObjectList() later on
     */
    public function __construct(array $args = [])
    {
        // set a specific framework
        if (!empty($args['framework'])) {
            $this->framework = $args['framework'];
        }
        if ($this->framework != 'xaraya') {
            // TODO: import something minimal ? :-)
        }

        // define the method mapper
        $this->mapper = [
            'create'  => ['classname'  => 'CreateHandler',
                          'classfunc'  => 'run',
                          'importname' => 'modules.dynamicdata.class.ui_handlers.create',
                          'nextmethod' => 'view'], // or e.g. 'display' (= with itemid)

            'update'  => ['classname'  => 'UpdateHandler',
                          'classfunc'  => 'run',
                          'importname' => 'modules.dynamicdata.class.ui_handlers.update',
                          'nextmethod' => 'view'],

            'delete'  => ['classname'  => 'DeleteHandler',
                          'classfunc'  => 'run',
                          'importname' => 'modules.dynamicdata.class.ui_handlers.delete',
                          'nextmethod' => 'view'],

            'display' => ['classname'  => 'DisplayHandler',
                          'classfunc'  => 'run',
                          'importname' => 'modules.dynamicdata.class.ui_handlers.display'],

            'view'    => ['classname'  => 'ViewHandler',
                          'classfunc'  => 'run',
                          'importname' => 'modules.dynamicdata.class.ui_handlers.view'],

            'search'  => ['classname'  => 'SearchHandler',
                          'classfunc'  => 'run',
                          'importname' => 'modules.dynamicdata.class.ui_handlers.search'],

            'config'  => ['classname'  => 'ConfigHandler',
                          'classfunc'  => 'run',
                          'importname' => 'modules.dynamicdata.class.ui_handlers.config'],

            'stats'   => ['classname'  => 'StatsHandler',
                          'classfunc'  => 'run',
                          'importname' => 'modules.dynamicdata.class.ui_handlers.stats'],
/*
            'access'  => ['classname'  => 'AccessHandler',
                          'classfunc'  => 'run',
                          'importname' => 'modules.dynamicdata.class.ui_handlers.access'],
*/
            'default' => ['classname'  => 'DefaultHandler',
                          'classfunc'  => 'run',
                          'importname' => 'modules.dynamicdata.class.ui_handlers.default'],
/*
            'myname'  => ['classname'  => 'MyMethodHandler',
                          'classfunc'  => 'run',
                          'importname' => 'modules.mymodule.class.myhandler',
                          'nextmethod' => 'display'],
*/
        ];

        // override and/or extend the method mapper if necessary
        if (!empty($args['mapper'])) {
            foreach ($args['mapper'] as $method => $methodmap) {
                $this->mapper[$method] = $methodmap;
            }
        }

        // specify any aliases for the methods (you can have several aliases for one method)
        $this->alias = [
            'new'      => 'create',
            'modify'   => 'update',
            'remove'   => 'delete', // we don't allow removing all items for an object here
            'show'     => 'display',
            'list'     => 'view',
            'query'    => 'search', // same handler, different private method called by run()
            'settings' => 'config',
            'report'   => 'stats',
            'other'    => 'default',
/*
            'mystuff'  => 'myname',
*/
        ];

        // override and/or extend the method aliases if necessary
        if (!empty($args['alias'])) {
            foreach ($args['alias'] as $alias => $realmethod) {
                $this->alias[$alias] = $realmethod;
            }
        }

        // sanity check on method aliases during setup
        foreach ($this->alias as $alias => $realmethod) {
            if (empty($this->mapper[$realmethod])) {
                throw new Exception(xarMLS::translate('Unknown method #(1) for alias #(2)', $realmethod, $alias));
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
     * @param array<string, mixed> $args
     * with
     *     $args['method'] the ui method we are handling here
     *     $args['itemid'] item id of the object to call the method for, if the method needs it
     *     $args any other arguments we want to pass to DataObjectFactory::getObject() or ::getObjectList() later on
     * @return string|void output of the handler->run() method
     */
    public function handle(array $args = [])
    {
        if (!xarVar::fetch('method', 'isset', $args['method'], null, xarVar::DONT_SET)) {
            return;
        }
        if (!xarVar::fetch('itemid', 'isset', $args['itemid'], null, xarVar::DONT_SET)) {
            return;
        }

        // default method is 'view' without itemid, or 'display' with an itemid
        if (empty($args['method'])) {
            if (empty($args['itemid'])) {
                $args['method'] = 'view';
            } else {
                $args['method'] = 'display';
            }
        }

        // get the right function to call in this handler class (default is 'run')
        $handlerfunc = 'run';

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
            $handlerclazz = $this->namespace . '\\' . $methodmap['classname'];

            // get the right function to call in this handler class (default is 'run')
            if (!empty($methodmap['classfunc'])) {
                $handlerfunc = $methodmap['classfunc'];
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
     * @return DefaultHandler|object|null
     */
    public function &getHandler()
    {
        return $this->handler;
    }

    /**
     * Return the current object in the handler, e.g. in case you want to access it afterwards
     * @return DataObjectList|DataObject|null
     */
    public function &getObject()
    {
        if (isset($this->handler)) {
            return $this->handler->object;
        }
        return null;
    }
}
