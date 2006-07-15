<?php
/**
 * Simple Object Interface
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata module
 */
require_once 'modules/dynamicdata/class/interface.php';

class Simple_Object_Interface extends Dynamic_Object_Interface
{
    function __construct($args)
    {
        parent::__construct($args);
        if(!xarVarFetch('tplmodule',   'isset', $args['tplmodule'],   NULL, XARVAR_DONT_SET)) {return;}
        if (!empty($args) && is_array($args) && count($args) > 0) {
            $this->args = array_merge($this->args, $args);
        }
	}

    function handle($args = array())
    {
        if(!xarVarFetch('method', 'isset', $args['method'], NULL, XARVAR_DONT_SET)) {return;}
        if(!xarVarFetch('itemid', 'isset', $args['itemid'], NULL, XARVAR_DONT_SET)) {return;}
        if (empty($args['method'])) {
            if (empty($args['itemid'])) {
                $args['method'] = 'view';
            } else {
                $args['method'] = 'display';
            }
        }
        if (!empty($args) && is_array($args) && count($args) > 0) {
            $this->args = array_merge($this->args, $args);
        }
		$this->object =& Dynamic_Object_Master::getObject($this->args);
		if (empty($this->object)) return;
		return $this->object->{$this->args['method']}($this->args);
    }
}

?>
