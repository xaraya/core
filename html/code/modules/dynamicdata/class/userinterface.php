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

    // current handler
    private $handler = null;

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
                $handlerclazz = 'DataObjectViewHandler';
                sys::import('modules.dynamicdata.class.ui_handlers.view');
                break;
            // run any unknown gui method via the default handler
            default:
                $handlerclazz = 'DataObjectDefaultHandler';
                sys::import('modules.dynamicdata.class.ui_handlers.default');
                break;
        }
        $this->handler = new $handlerclazz($this->args);

        // run the handler and return the output
        return $this->handler->run($args);
    }

    /**
     * Return the current handler, e.g. in case you want to access the object afterwards
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
