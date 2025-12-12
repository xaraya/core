<?php

/**
 * Object handling subsystem (counterpart for modules on object-centric sites)
 *
 * @package core
 * @subpackage objects
 * @category Xaraya Web Applications Framework
 * @version 2.9.3
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author Michel Dalle <mikespub@xaraya.com>
 */

use Xaraya\Routing\DataObjectURL;
use Xaraya\Context\Context;
use Xaraya\Services\xar;

/**
 * Interface declaration for xarDDObject
 *
 * @package core\objects
 * @deprecated 2.9.3 no longer relevant
 */
interface ixarDDObject {}

/**
 * Preliminary class to model xarDDObject interface
 *
 * @package core\objects
 * @deprecated 2.9.3 use xar::data()->guiMethod() or DataObjectURL::getActionURL() instead
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
     * @deprecated 2.9.3 use xar::data()->guiMethod() instead
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
     * @deprecated 2.9.3 use DataObjectURL::getActionURL() instead
     */
    public static function getActionURL($object, $action = '', $itemid = null, $extra = [], $xar = null)
    {
        return DataObjectURL::getActionURL($object, $action, $itemid, $extra, $xar);
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
        return DataObjectURL::getModuleURL($object, $action, $itemid, $extra, $xar);
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
        return DataObjectURL::getObjectURL($object, $action, $itemid, $extra, $xar);
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
        return DataObjectURL::getCurrentURL($object, $action, $itemid, $xar);
    }

    /**
     * Generate Other URL for a specific action on an object (TBD)
     *
     * @param DataObject|DataObjectList $object the object or object list we want to create an URL for
     * @param string $action the action we want to take on this object (= method or func)
     * @param mixed $itemid the specific item id or null
     * @return string the generated URL
     */
    public static function getOtherURL($object, $action = '', $itemid = null, $xar = null)
    {
        return DataObjectURL::getOtherURL($object, $action, $itemid, $xar);
    }

    /**
     * Check access for a specific action on object level (see also xarMod and xarBlock)
     *
     * @param object $object the object or object list we want to check access for
     * @param string $action the action we want to take on this object (display/update/create/delete/config)
     * @param mixed $itemid the specific item id or null
     * @param mixed $roleid override the current user or null
     * @return bool true if access
     * @deprecated 2.9.3 not used
     */
    public static function checkAccess($object, $action, $itemid = null, $roleid = null)
    {
        return $object->checkAccess($action, $itemid, $roleid);
    }
}
