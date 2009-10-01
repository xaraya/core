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

        sys::import('modules.dynamicdata.class.objects.master');

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

?>
