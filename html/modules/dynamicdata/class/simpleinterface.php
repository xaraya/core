<?php
/**
 * Simple Object Interface
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundatio
 * @subpackage dynamicdata
 * @author Marc Lutolf
 * @author Johnny Robeson <johnny@localmomentum.net>
 */
sys::import('modules.dynamicdata.class.interface');

/**
  * Simple Object Interface
  */
class Simple_Object_Interface extends DataObjectInterface
{
    function __construct(array $args = array())
    {
        parent::__construct($args);
        if (!xarVarFetch('tplmodule',   'isset', $args['tplmodule'], 'dynamicdata', XARVAR_NOT_REQUIRED)) 
            return;
            
        if (!empty($args) && is_array($args) && count($args) > 0) { 
            $this->args = array_merge($this->args, $args);
        }
    }

    function handle(array $args = array())
    {                                
        if (!xarVarFetch('method', 'str', $args['method'], 'showDisplay', XARVAR_NOT_REQUIRED)) 
            return;
        if (!xarVarFetch('itemid', 'id', $args['itemid'], NULL, XARVAR_DONT_SET)) 
            return;
        // @todo maybe this should be done somewhere else ?
        if (!xarVarFetch('qparam', 'str', $qparam, NULL, XARVAR_DONT_SET)) 
           return;
        if (!xarVarFetch('qstring', 'str', $qstring, NULL, XARVAR_DONT_SET)) 
           return;

        if (!empty($qparam) && !empty ($qstring)) {
            $args['where'] = "$qparam LIKE '$qstring%'";
        }
        if (!empty($args) && is_array($args) && count($args) > 0) { 
            $this->args = array_merge($this->args, $args);
        }
        $this->object =& DataObjectMaster::getObjectList($this->args);
        $this->object->getItems();

        if (empty($this->object)) return;

        return $this->object->{$this->args['method']}($this->args);
    }
}
?>
