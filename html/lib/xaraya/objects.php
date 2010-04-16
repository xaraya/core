<?php
/**
 * Object handling subsystem (experimental counterpart for modules on object-centric sites)
 *
 * @package objects
 * @copyright see the html/credits.html file in this release
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

    /**
     * Generate URL for a specific action on an object - the format will depend on the linktype
     *
     * @access public
     * @param object object the object or object list we want to create an URL for
     * @param action string the action we want to take on this object (= method or func)
     * @param itemid mixed the specific item id or null
     * @param extra array extra arguments to pass to the URL - CHECKME: we should only need itemid here !?
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
/*
            case 'other':
                $link = self::getOtherURL($object, $action, $itemid, $extra);
                break;
*/
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
     * e.g. use current URLs by putting #xarObject::guiMethod('sample', null, array('linktype' => 'current'))# in some page template
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
     */
    static function getOtherURL($object, $action = '', $itemid = null)
    {
        return 'http://www.xaraya.com/to_be_defined';
    }

    /**
     * Check access for a specific action on object level (see also xarMod and xarBlock)
     *
     * @access public
     * @param object object the object or object list we want to check access for
     * @param action string the action we want to take on this object (display/update/create/delete/config)
     * @param itemid mixed the specific item id or null
     * @param roleid mixed override the current user or null
     * @return bool true if access
     */
    static function checkAccess($object, $action, $itemid = null, $roleid = null)
    {
        return $object->checkAccess($action, $itemid, $roleid);
    }
}

?>
