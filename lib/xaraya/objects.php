<?php
/**
 * Object handling subsystem (experimental counterpart for modules on object-centric sites)
 *
 * @package objects
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @author Michel Dalle <mikespub@xaraya.com>
 * @todo Investigate possible use ;-)
 */

/**
 * Interface declaration for xarObject
 *
 * @todo this is very likely to change, it was created as baseline for refactoring
 */
interface IxarObject
{

}

/**
 * Preliminary class to model xarObject interface
 *
 */
class xarObject extends Object implements IxarObject
{
    /**
     * Initialize
     *
     */
    static function init($args)
    {
        // Nothing to do here
        return true;
    }

    /**
     * Call a dataobject user interface method (maybe from index.php someday)
     *
     * @access public
     * @param objectName string registered name of object
     * @param methodName string specific method to run
     * @param args array arguments to pass to the method
     * @return mixed The output of the method, or raise an exception
     * @throws BAD_PARAM
     */
    static function guiMethod($objectName, $methodName = 'view', $args = array())
    {
        if (empty($objectName)) throw new EmptyParameterException('objectName');

        // Pass the object name and method to the userinterface class
        $args['object'] = $objectName;
        $args['method'] = $methodName;
        // we'll use the 'object' GUI function type here, instead of the default 'user' (+ 'admin')
        $args['type'] = 'object';

        sys::import('modules.dynamicdata.class.userinterface');

        $interface = new DataObjectUserInterface($args);

        return $interface->handle($args);
    }

    /**
     * Call a dataobject class method directly - CHECKME: do we even want this here ???
     *
     * @access public
     * @param objectName string registered name of object
     * @param methodName string specific method to run
     * @param args array arguments to pass to the method
     * @return mixed The output of the method, or false on failure
     * @throws BAD_PARAM
     */
    static function classMethod($objectName, $methodName = 'showDisplay', $args = array())
    {
        if (empty($objectName)) throw new EmptyParameterException('objectName');

        // Pass the object name to the object class
        $args['name'] = $objectName;

        sys::import('modules.dynamicdata.class.objects.master');

        switch (strtolower($methodName))
        {
            case 'countitems':
                $objectlist = DataObjectMaster::getObjectList($args);
                return $objectlist->countItems($args);
                break;

            case 'getitems':
                $objectlist = DataObjectMaster::getObjectList($args);
                return $objectlist->getItems($args);
                break;

            case 'showview':
            case 'getviewvalues':
                $objectlist = DataObjectMaster::getObjectList($args);
                // get the items first
                $objectlist->getItems($args);
                return $objectlist->{$methodName}($args);
                break;

            // CHECKME: what do we want to return here ?
            case 'getitem':
                $object = DataObjectMaster::getObject($args);
                // get the item first
                $object->getItem($args);
                return $object->getFieldValues($args);
                break;

            case 'getfieldvalues':
            case 'getdisplayvalues':
            case 'showform':
            case 'showdisplay':
            case 'createitem':
            case 'updateitem':
            case 'deleteitem':
            default:
                $object = DataObjectMaster::getObject($args);
                if (!empty($args['itemid'])) {
                    // get the item first
                    $object->getItem($args);
                }
                return $object->{$methodName}($args);
                break;
        }
    }

    /**
     * Run a dataobject class method via simpleinterface - CHECKME: do we even want this here ???
     *
     * @access public
     * @param objectName string registered name of object
     * @param methodName string specific method to run
     * @param args array arguments to pass to the method
     * @return mixed The output of the method, or false on failure
     * @throws BAD_PARAM
     */
    static function simpleMethod($objectName, $methodName = 'showDisplay', $args = array())
    {
        if (empty($objectName)) throw new EmptyParameterException('objectName');

        // Pass the object name and method to the simpleinterface class
        $args['name'] = $objectName;
        $args['method'] = $methodName;

        sys::import('modules.dynamicdata.class.simpleinterface');

        $interface = new SimpleObjectInterface($args);

        return $interface->handle($args);
    }
}

?>
