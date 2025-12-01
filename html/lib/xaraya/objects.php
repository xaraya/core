<?php

/**
 * Object handling subsystem (counterpart for modules on object-centric sites)
 *
 * @package core
 * @subpackage objects
 * @category Xaraya Web Applications Framework
 * @version 2.6.2
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author Michel Dalle <mikespub@xaraya.com>
 */

use Xaraya\Context\Context;
use Xaraya\Services\xar;

/**
 * Interface declaration for xarDDObject
 *
 * @package core\objects
 * @todo this is very likely to change, it was created as baseline for refactoring
 */
interface ixarDDObject {}

/**
 * Preliminary class to model xarDDObject interface
 *
 * @package core\objects
 */
class xarDDObject extends xarObject implements ixarDDObject
{
    /**
     * Initialize
     *
     */
    public static function init(array $args = [])
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
     * @param ?Context<string, mixed> $context optional context for the method call (default = none)
     * @return string The output of the method, or raise an exception
     * @throws EmptyParameterException
     */
    public static function guiMethod($objectName, $methodName = 'view', $args = [], $context = null, $xar = null)
    {
        if (empty($objectName)) {
            throw new EmptyParameterException('objectName');
        }
        $xar ??= xar::getServicesClass();

        // Pass the object name and method to the userinterface class
        $args['object'] = $objectName;
        $args['method'] = $methodName;
        if (!isset($context)) {
            // $context = new Context(['source' => __METHOD__]);
            // Use context from static services class here
            $context = $xar->getContext();
        }
        // Set module name and type in context if needed (dummy)
        $context['module'] ??= 'object';
        $context['modtype'] ??= $objectName;

        // @todo refine configuration elsewhere later
        $twig_support = $xar->mod('dynamicdata')->getVar('twig_support');
        if (!empty($twig_support)) {
            if (empty($context['twig'])) {
                $context['twig'] = true;
            }
        }


        $interface = new DataObjectUserInterface($args, $context, $xar);
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
     * @see \Xaraya\Bridge\Requests\DataObjectRequestHandler::handleObjectRequest()
     */
    public static function getActionURL($object, $action = '', $itemid = null, $extra = [])
    {
        // special case when dealing with objectid 1 = objects
        if ($action == 'modifyprop' || $action == 'viewitems') {
            return self::getModuleURL($object, $action, $itemid);
        }

        // CHECKME: the linktype is set by the object user interface when we work with object URLs - make this depend on current request, config, ... ?
        switch ($object->linktype) {
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
    public static function getModuleURL($object, $action = '', $itemid = null, $extra = [])
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

        $xar = xar::getServicesClass();
        switch ($action) {
            case 'display':
                $tplmodule = $xar->mod()->checkModuleFunction($object->tplmodule, $object->linktype, $object->linkfunc);
                $link = $xar->ctl()->getModuleURL($tplmodule, $object->linktype, $object->linkfunc, $urlargs);
                break;

            case 'view':
                unset($urlargs['itemid']);
                $tplmodule = $xar->mod()->checkModuleFunction($object->tplmodule, $object->linktype, 'view');
                $link = $xar->ctl()->getModuleURL($tplmodule, $object->linktype, 'view', $urlargs);
                break;

                // special case when dealing with objectid 1 = objects
            case 'modifyprop':
                $tplmodule = $xar->mod()->checkModuleFunction($object->tplmodule, 'admin', 'modifyprop');
                $link = $xar->ctl()->getModuleURL($tplmodule, 'admin', 'modifyprop', $urlargs);
                break;

                // special case when dealing with objectid 1 = objects
            case 'viewitems':
                $link = $xar->ctl()->getModuleURL(
                    'dynamicdata',
                    'admin',
                    'view',
                    ['itemid' => $itemid]
                );
                break;

            case 'new':
                unset($urlargs['itemid']);
                // fall through
                // no break
            case 'modify':
            case 'delete':
            default:
                $tplmodule = $xar->mod()->checkModuleFunction($object->tplmodule, 'admin', $action);
                $link = $xar->ctl()->getModuleURL($tplmodule, 'admin', $action, $urlargs);
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
    public static function getObjectURL($object, $action = '', $itemid = null, $extra = [])
    {
        $urlargs = $extra;
        if (!empty($object->table)) {
            $urlargs['table'] = $object->table;
        }
        if (!empty($itemid)) {
            $urlargs[$object->urlparam] = $itemid;
        }

        $xar = xar::getServicesClass();
        switch ($action) {
            case 'new':
                unset($urlargs['itemid']);
                $link = $xar->ctl()->getObjectURL($object->name, 'create', $urlargs);
                break;

            case 'modify':
                $link = $xar->ctl()->getObjectURL($object->name, 'update', $urlargs);
                break;

            case 'view':
                $link = $xar->ctl()->getObjectURL($object->name, 'view');
                break;

                // all other actions should correspond to some gui method
            case 'display':
            default:
                $link = $xar->ctl()->getObjectURL($object->name, $action, $urlargs);
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
    public static function getCurrentURL($object, $action = '', $itemid = null)
    {
        $xar = xar::getServicesClass();
        switch ($action) {
            case 'display':
                // CHECKME: reset method in the current URL ?
                $link = $xar->ctl()->getCurrentURL(['method' => null, 'itemid' => $itemid]);
                break;

            case 'new':
                // CHECKME: reset itemid in the current URL ?
                $link = $xar->ctl()->getCurrentURL(['method' => 'create', 'itemid' => null]);
                break;

            case 'modify':
                // CHECKME: pass method and itemid to the current URL ?
                $link = $xar->ctl()->getCurrentURL(['method' => 'update', 'itemid' => $itemid]);
                break;

            case 'delete':
                // CHECKME: pass method and itemid to the current URL ?
                $link = $xar->ctl()->getCurrentURL(['method' => 'delete', 'itemid' => $itemid]);
                break;

            case 'view':
                // CHECKME: reset method and itemid in the current URL ?
                $link = $xar->ctl()->getCurrentURL(['method' => null, 'itemid' => null]);
                break;

            default:
                // CHECKME: pass method and itemid to the current URL ?
                $link = $xar->ctl()->getCurrentURL(['method' => $action, 'itemid' => $itemid]);
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
    public static function getOtherURL($object, $action = '', $itemid = null)
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
     * @return bool true if access
     */
    public static function checkAccess($object, $action, $itemid = null, $roleid = null)
    {
        return $object->checkAccess($action, $itemid, $roleid);
    }
}
