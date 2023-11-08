<?php
/**
 * Object handling subsystem (experimental counterpart for modules on object-centric sites)
 *
 * @package core
 * @subpackage objects
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author Michel Dalle <mikespub@xaraya.com>
 */

/**
 * Interface declaration for xarDDObject
 *
 * @package core\objects
 * @todo this is very likely to change, it was created as baseline for refactoring
 */
interface IxarDDObject
{

}

/**
 * Preliminary class to model xarDDObject interface
 *
 * @package core\objects
 */
class xarDDObject extends xarObject implements IxarDDObject
{
    /**
     * Initialize
     *
     */
    static function init(array $args=array())
    {
        // Nothing to do here
        return true;
    }

    /**
     * Call a dataobject user interface method (maybe from index.php someday)
     *
     * @param string $objectName registered name of object
     * @param string $methodName specific method to run
     * @param array<string, mixed> $args arguments to pass to the method
     * @return mixed The output of the method, or raise an exception
     * @throws EmptyParameterException
     */
    static function guiMethod($objectName, $methodName = 'view', $args = array())
    {
        if (empty($objectName)) throw new EmptyParameterException('objectName');

        // Pass the object name and method to the userinterface class
        $args['object'] = $objectName;
        $args['method'] = $methodName;

        sys::import('modules.dynamicdata.class.userinterface');

        $interface = new DataObjectUserInterface($args);
        return $interface->handle($args);
    }

    /**
     * Call a dataobject class method directly - CHECKME: do we even want this here ???
     *
     * @param string $objectName registered name of object
     * @param string $methodName specific method to run
     * @param array<string, mixed> $args arguments to pass to the method
     * @param mixed $roleid override the current user or null
     * @return mixed The output of the method, or false on failure
     * @throws EmptyParameterException
     */
    static function classMethod($objectName, $methodName = 'showDisplay', $args = array(), $roleid = null)
    {
        if (empty($objectName)) throw new EmptyParameterException('objectName');

        // Pass the object name to the object class
        $args['name'] = $objectName;

        sys::import('modules.dynamicdata.class.objects.factory');

        switch (strtolower($methodName))
        {
            case 'countitems':
                $objectlist = DataObjectFactory::getObjectList($args);
                if (!$objectlist->checkAccess('view', null, $roleid)) {
                    return;
                }
                return $objectlist->countItems($args);

            case 'getitems':
                $objectlist = DataObjectFactory::getObjectList($args);
                if (!$objectlist->checkAccess('view', null, $roleid)) {
                    return;
                }
                return $objectlist->getItems($args);

            case 'showview':
            case 'getviewvalues':
                $objectlist = DataObjectFactory::getObjectList($args);
                if (!$objectlist->checkAccess('view', null, $roleid)) {
                    return;
                }
                // get the items first
                $objectlist->getItems($args);
                return $objectlist->{$methodName}($args);

            // CHECKME: what do we want to return here ?
            case 'getitem':
                $object = DataObjectFactory::getObject($args);
                if (!$object->checkAccess('display', $args['itemid'], $roleid)) {
                    return;
                }
                // get the item first
                if (!$object->getItem($args)) {
                    return;
                }
                return $object->getFieldValues($args);

            case 'getfieldvalues':
            case 'getdisplayvalues':
            case 'showform':
            case 'showdisplay':
                $object = DataObjectFactory::getObject($args);
                if (!$object->checkAccess('display', $args['itemid'], $roleid)) {
                    return;
                }
                // get the item first
                if (!$object->getItem($args)) {
                    return;
                }
                return $object->{$methodName}($args);

            case 'createitem':
            case 'updateitem':
            case 'deleteitem':
            default:
                $object = DataObjectFactory::getObject($args);
                if (!$object->checkAccess('delete', $args['itemid'], $roleid)) {
                    return;
                }
                // get the item first
                if (!empty($args['itemid']) && !$object->getItem($args)) {
                    return;
                }
                return $object->{$methodName}($args);
        }
    }

    /**
     * Run a dataobject class method via simpleinterface - CHECKME: do we even want this here ???
     *
     * @param string $objectName registered name of object
     * @param string $methodName specific method to run
     * @param array<string, mixed> $args arguments to pass to the method
     * @return mixed The output of the method, or false on failure
     * @throws EmptyParameterException
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

    /**
     * Generate URL for a specific action on an object - the format will depend on the linktype
     *
     * @param DataObject|DataObjectList $object the object or object list we want to create an URL for
     * @param string $action the action we want to take on this object (= method or func)
     * @param mixed $itemid the specific item id or null
     * @param array<string, mixed> $extra extra arguments to pass to the URL - CHECKME: we should only need itemid here !?
     * @return string the generated URL
     */
    static function getActionURL($object, $action = '', $itemid = null, $extra = array())
    {
        // special case when dealing with objectid 1 = objects
        if ($action == 'modifyprop' || $action == 'viewitems') {
            return self::getModuleURL($object, $action, $itemid);
        }

        // CHECKME: the linktype is set by the object user interface when we work with object URLs - make this depend on current request, config, ... ?
        switch ($object->linktype)
        {
            case 'object':
                $link = self::getObjectURL($object, $action, $itemid, $extra);
                break;

            case 'current':
                $link = self::getCurrentURL($object, $action, $itemid);
                break;

            case 'other':
                //$link = self::getOtherURL($object, $action, $itemid, $extra);
                if (!empty($object->linkfunc) && is_callable($object->linkfunc)) {
                    $link = call_user_func($object->linkfunc, $object->name, $action, $itemid, $extra);
                } else {
                    $link = self::getObjectURL($object, $action, $itemid, $extra);
                }
                break;

            case 'user':
            case 'admin':
            default:
                $link = self::getModuleURL($object, $action, $itemid, $extra);
                break;
        }

        return $link;
    }

    /**
     * Generate Module URL for a specific action on an object
     * e.g. use module URLs via the dynamicdata or dyn_example module
     *
     * @param DataObject|DataObjectList $object the object or object list we want to create an URL for
     * @param string $action the action we want to take on this object (= method or func)
     * @param mixed $itemid the specific item id or null
     * @param array<string, mixed> $extra extra arguments to pass to the URL - CHECKME: we should only need itemid here !?
     * @return string the generated URL
     */
    static function getModuleURL($object, $action = '', $itemid = null, $extra=array())
    {
        $urlargs = $extra;
        if (!empty($object->table)) {
            $urlargs['table'] = $object->table;
        }
        $urlargs['name'] = $object->name;
        if (!empty($itemid)) {
            $urlargs[$object->urlparam] = $itemid;
        }
        // TODO: do we need the concept of tplmodule at all? Good question :-)
        $urlargs['tplmodule'] = $object->tplmodule;

        switch ($action)
        {
            case 'display':
                $tplmodule = xarMod::checkModuleFunction($object->tplmodule, $object->linktype, $object->linkfunc);
                $link = xarServer::getModuleURL($tplmodule, $object->linktype, $object->linkfunc, $urlargs);
                break;

            case 'view':
                unset($urlargs['itemid']);
                $tplmodule = xarMod::checkModuleFunction($object->tplmodule, $object->linktype, 'view');
                $link = xarServer::getModuleURL($tplmodule, $object->linktype, 'view', $urlargs);
                break;

            // special case when dealing with objectid 1 = objects
            case 'modifyprop':
                $tplmodule = xarMod::checkModuleFunction($object->tplmodule, 'admin', 'modifyprop');
                $link = xarServer::getModuleURL($tplmodule, 'admin', 'modifyprop', $urlargs);
                break;

            // special case when dealing with objectid 1 = objects
            case 'viewitems':
                $link = xarServer::getModuleURL('dynamicdata','admin','view',
                                                array('itemid' => $itemid));
                break;

            case 'new':
                unset($urlargs['itemid']);
                // fall through
            case 'modify':
            case 'delete':
            default:
                $tplmodule = xarMod::checkModuleFunction($object->tplmodule, 'admin', $action);
                $link = xarServer::getModuleURL($tplmodule, 'admin', $action, $urlargs);
                break;
        }

        return $link;
    }

    /**
     * Generate Object URL for a specific action on an object
     * e.g. use object URLs via index.php?object=sample
     *
     * @param DataObject|DataObjectList $object the object or object list we want to create an URL for
     * @param string $action the action we want to take on this object (= method or func)
     * @param mixed $itemid the specific item id or null
     * @param array<string, mixed> $extra extra arguments to pass to the URL - CHECKME: we should only need itemid here !?
     * @return string the generated URL
     */
    static function getObjectURL($object, $action = '', $itemid = null ,$extra=array())
    {
        $urlargs = $extra;
        if (!empty($object->table)) {
            $urlargs['table'] = $object->table;
        }
        if (!empty($itemid)) {
            $urlargs[$object->urlparam] = $itemid;
        }

        switch ($action)
        {
            case 'new':
                unset($urlargs['itemid']);
                $link = xarServer::getObjectURL($object->name, 'create', $urlargs);
                break;

            case 'modify':
                $link = xarServer::getObjectURL($object->name, 'update', $urlargs);
                break;

            case 'view':
                $link = xarServer::getObjectURL($object->name, 'view');
                break;

            // all other actions should correspond to some gui method
            case 'display':
            default:
                $link = xarServer::getObjectURL($object->name, $action, $urlargs);
                break;
        }

        return $link;
    }

    /**
     * Generate Current URL for a specific action on an object
     * e.g. use current URLs by putting #xarDDObject::guiMethod('sample', null, array('linktype' => 'current'))# in some page template
     *
     * @param DataObject|DataObjectList $object the object or object list we want to create an URL for
     * @param string $action the action we want to take on this object (= method or func)
     * @param mixed $itemid the specific item id or null
     * @return string the generated URL
     */
    static function getCurrentURL($object, $action = '', $itemid = null)
    {
        switch ($action)
        {
            case 'display':
                // CHECKME: reset method in the current URL ?
                $link = xarServer::getCurrentURL(array('method' => null, 'itemid' => $itemid));
                break;

            case 'new':
                // CHECKME: reset itemid in the current URL ?
                $link = xarServer::getCurrentURL(array('method' => 'create', 'itemid' => null));
                break;

            case 'modify':
                // CHECKME: pass method and itemid to the current URL ?
                $link = xarServer::getCurrentURL(array('method' => 'update', 'itemid' => $itemid));
                break;

            case 'delete':
                // CHECKME: pass method and itemid to the current URL ?
                $link = xarServer::getCurrentURL(array('method' => 'delete', 'itemid' => $itemid));
                break;

            case 'view':
                // CHECKME: reset method and itemid in the current URL ?
                $link = xarServer::getCurrentURL(array('method' => null, 'itemid' => null));
                break;

            default:
                // CHECKME: pass method and itemid to the current URL ?
                $link = xarServer::getCurrentURL(array('method' => $action, 'itemid' => $itemid));
                break;
        }

        return $link;
    }

    /**
     * Generate Other URL for a specific action on an object (TBD)
     *
     * @param DataObject|DataObjectList $object the object or object list we want to create an URL for
     * @param string $action the action we want to take on this object (= method or func)
     * @param mixed $itemid the specific item id or null
     * @return string the generated URL
     */
    static function getOtherURL($object, $action = '', $itemid = null)
    {
        return 'http://www.xaraya.com/to_be_defined';
    }

    /**
     * Check access for a specific action on object level (see also xarMod and xarBlock)
     *
     * @param object $object the object or object list we want to check access for
     * @param string $action the action we want to take on this object (display/update/create/delete/config)
     * @param mixed $itemid the specific item id or null
     * @param mixed $roleid override the current user or null
     * @return boolean true if access
     */
    static function checkAccess($object, $action, $itemid = null, $roleid = null)
    {
        return $object->checkAccess($action, $itemid, $roleid);
    }
}
