<?php

namespace Xaraya\Routing;

use Xaraya\Context\Context;
use Xaraya\Services\xar;
use DataObject;
use DataObjectList;

/**
 * Generate URL for a specific action on an object - the format will depend on the linktype
 * Note: moved from xarDDObject for use in xar::ctl()->getActionURL() and Routing (future)
 */
class DataObjectURL
{
    /**
     * Generate URL for a specific action on an object - the format will depend on the linktype
     *
     * @param DataObject|DataObjectList $object the object or object list we want to create an URL for
     * @param string $action the action we want to take on this object (= method or func)
     * @param mixed $itemid the specific item id or null
     * @param array<string, mixed> $extra extra arguments to pass to the URL - e.g. title slug
     * @return string the generated URL
     * @see \Xaraya\Bridge\Requests\DataObjectRequestHandler::handleObjectRequest()
     */
    public static function getActionURL($object, $action = '', $itemid = null, $extra = [], $xar = null)
    {
        // special case when dealing with objectid 1 = objects
        if ($action == 'modifyprop' || $action == 'viewitems') {
            return self::getModuleURL($object, $action, $itemid, [], $xar);
        }

        // CHECKME: the linktype is set by the object user interface when we work with object URLs - make this depend on current request, config, ... ?
        switch ($object->linktype) {
            case 'object':
                $link = self::getObjectURL($object, $action, $itemid, $extra, $xar);
                break;

            case 'route':
                $link = self::getRouteURL($object, $action, $itemid, $extra, $xar);
                break;

            case 'current':
                $link = self::getCurrentURL($object, $action, $itemid, $xar);
                break;

            case 'other':
                //$link = self::getOtherURL($object, $action, $itemid, $extra, $xar);
                if (!empty($object->linkfunc) && is_callable($object->linkfunc)) {
                    $link = call_user_func($object->linkfunc, $object->name, $action, $itemid, $extra);
                } else {
                    $link = self::getObjectURL($object, $action, $itemid, $extra, $xar);
                }
                break;

            case 'user':
            case 'admin':
            default:
                $link = self::getModuleURL($object, $action, $itemid, $extra, $xar);
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
    public static function getModuleURL($object, $action = '', $itemid = null, $extra = [], $xar = null)
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

        $xar ??= xar::getServicesClass();
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
    public static function getObjectURL($object, $action = '', $itemid = null, $extra = [], $xar = null)
    {
        $urlargs = $extra;
        if (!empty($object->table)) {
            $urlargs['table'] = $object->table;
        }
        if (!empty($itemid)) {
            $urlargs[$object->urlparam] = $itemid;
        }

        $xar ??= xar::getServicesClass();
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
     * Generate Route URL for a specific action on an object
     * e.g. use route URLs via index.php/object/sample
     *
     * @param DataObject|DataObjectList $object the object or object list we want to create an URL for
     * @param string $action the action we want to take on this object (= method or func)
     * @param mixed $itemid the specific item id or null
     * @param array<string, mixed> $extra extra arguments to pass to the URL - e.g. title slug
     * @return string the generated URL
     */
    public static function getRouteURL($object, $action = '', $itemid = null, $extra = [], $xar = null)
    {
        // set entity to get an object route with findRoute(), not a module route
        $extra['entity'] ??= $object->name;
        if (!empty($action)) {
            $extra['action'] ??= $action;
        }
        if (isset($itemid)) {
            $extra['itemid'] ??= $itemid;
        }
        // use tplmodule or dynamicdata as module for findRoute()
        $extra['module'] ??= $object->getModName();

        $xar ??= xar::getServicesClass();
        if (!empty($extra[RouterInterface::ROUTE_PARAM])) {
            $route = $extra[RouterInterface::ROUTE_PARAM];
            unset($extra[RouterInterface::ROUTE_PARAM]);
        } else {
            // @todo build route based on object, action, itemid and extra here?
            $route = '';
        }
        $link = $xar->ctl()->getRouteURL($route, $extra);

        return $link;
    }

    /**
     * Generate Current URL for a specific action on an object
     *
     * @param DataObject|DataObjectList $object the object or object list we want to create an URL for
     * @param string $action the action we want to take on this object (= method or func)
     * @param mixed $itemid the specific item id or null
     * @return string the generated URL
     */
    public static function getCurrentURL($object, $action = '', $itemid = null, $xar = null)
    {
        $xar ??= xar::getServicesClass();
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
     * Generate Other URL for a specific action on an object (using linkfunc)
     *
     * @param DataObject|DataObjectList $object the object or object list we want to create an URL for
     * @param string $action the action we want to take on this object (= method or func)
     * @param mixed $itemid the specific item id or null
     * @param array<string, mixed> $extra extra arguments to pass to the URL - e.g. title slug
     * @return string the generated URL
     * @see \Xaraya\Bridge\Middleware\DataObjectMiddleware::process()
     * @see \Xaraya\Bridge\Requests\DataObjectBridgeTrait::handleObjectRequest()
     */
    public static function getOtherURL($object, $action = '', $itemid = null, $extra = [], $xar = null)
    {
        if (!empty($object->linkfunc) && is_callable($object->linkfunc)) {
            return call_user_func($object->linkfunc, $object->name, $action, $itemid, $extra);
        }
        return self::getObjectURL($object, $action, $itemid, $extra, $xar);
    }
}
