<?php
/**
 * Twig extension to use Twig template engine for output in Xaraya
 */

namespace Xaraya\Bridge\TemplateEngine;

use Twig\TwigFunction;
use DataObjectFactory;
use DataPropertyMaster;
use AccessProperty;
use xarConfigVars;
use xarMod;
use xarModVars;
use xarTpl;
use xarUser;
use xarVar;
use Exception;

/**
 * DynamicData Tags
 * ```twig
 * {{ xar_data_view({object: objectlist}) }}
 * {{ xar_data_display({object: objectitem}) }}
 * {{ xar_data_output({property: property}) }}
 * {% set properties, items = xar_data_getitems({object: objectlist}) %}
 * {% set properties = xar_data_getitem({object: objectitem, itemid: 1}) %}
 * {% if xar_access(...) %}...{% endif %}
 * ```
 */
class DynamicDataTagExtension extends XarayaTwigExtension
{
    public function getFilters()
    {
        return [
        ];
    }

    public function getTests()
    {
        return [
        ];
    }

    public function getFunctions()
    {
        return [
             // <xar:data-view object="$object" newlink=""/>
            new TwigFunction('xar_data_view', [$this, 'xar_data_view'], ['is_safe' => ['html']]),
            // <xar:data-display object="$object"/>
            new TwigFunction('xar_data_display', [$this, 'xar_data_display'], ['is_safe' => ['html']]),
            new TwigFunction('xar_data_form', [$this, 'xar_data_form'], ['is_safe' => ['html']]),
            new TwigFunction('xar_data_filterform', [$this, 'xar_data_filterform'], ['is_safe' => ['html']]),
            // <xar:data-label property="$properties[$name]"/>
            new TwigFunction('xar_data_label', [$this, 'xar_data_label'], ['is_safe' => ['html']]),
            // <xar:data-output property="$properties[$name]" _itemid="$itemid" value="$fields[$name]"/>
            new TwigFunction('xar_data_output', [$this, 'xar_data_output'], ['is_safe' => ['html']]),
            new TwigFunction('xar_data_input', [$this, 'xar_data_input'], ['is_safe' => ['html']]),
            new TwigFunction('xar_data_filter', [$this, 'xar_data_filter'], ['is_safe' => ['html']]),
            new TwigFunction('xar_data_getitems', [$this, 'xar_data_getitems']),
            new TwigFunction('xar_data_getitem', [$this, 'xar_data_getitem']),
            new TwigFunction('xar_data_objectlist', [$this, 'xar_data_objectlist']),
            new TwigFunction('xar_data_object', [$this, 'xar_data_object']),
            new TwigFunction('xar_data_property', [$this, 'xar_data_property']),
            new TwigFunction('xar_access', [$this, 'xar_access']),
        ];
    }

    public function xar_data_view($args = [])
    {
        // Use the object attribute
        if (!empty($args['object'])) {
            $object = $args['object'];
            unset($args['object']);
            // @todo do we always overwrite the context or not?
            if (empty($object->getContext())) {
                $object->setContext($this->context);
            }
            return $object->showView($args);
        }
        // This a string. we assume it's an object name
        if (!empty($args['objectname'])) {
            $objectName = $args['objectname'];
            unset($args['objectname']);
            $object = DataObjectFactory::getObjectList(['name' => $objectName], $this->context);
            $object->getItems($args);
            return $object->showView($args);
        }
        // No object or objectname? Generate ourselves then
        return xarMod::apiFunc('dynamicdata', 'user', 'showview', $args, $this->context);
    }

    public function xar_data_display($args = [])
    {
        if (!empty($args['object'])) {
            $object = $args['object'];
            unset($args['object']);
            if (is_string($object)) {
                $objectName = $object;
                $object = DataObjectFactory::getObject(['name' => $objectName], $this->context);
            } else {
                // @todo do we always overwrite the context or not?
                if (empty($object->getContext())) {
                    $object->setContext($this->context);
                }
            }
            return $object->showDisplay($args);
        }
        // No object passed in
        if (!empty($args['definition'])) {
            return xarMod::apiFunc('dynamicdata', 'user', 'showdisplay', $args['definition'], $this->context);
        }
        // No direct definition, use the attributes
        return xarMod::apiFunc('dynamicdata', 'user', 'showdisplay', $args, $this->context);
    }

    public function xar_data_form($args = [])
    {
        if (!empty($args['object'])) {
            // Use the object attribute
            $object = $args['object'];
            unset($args['object']);
            if (is_string($object)) {
                $objectName = $object;
                $object = DataObjectFactory::getObject(['name' => $objectName], $this->context);
            } else {
                // @todo do we always overwrite the context or not?
                if (empty($object->getContext())) {
                    $object->setContext($this->context);
                }
            }
            return $object->showForm($args);
        }
        // No object passed in
        if (!empty($args['definition'])) {
            return xarMod::apiFunc('dynamicdata', 'user', 'showform', $args['definition'], $this->context);
        }
        // No direct definition, use the attributes
        return xarMod::apiFunc('dynamicdata', 'user', 'showform', $args, $this->context);
    }

    public function xar_data_filterform($args = [])
    {
        if (!empty($args['object'])) {
            // Use the object attribute
            $object = $args['object'];
            unset($args['object']);
            if (is_string($object)) {
                $objectName = $object;
                $object = DataObjectFactory::getObject(['name' => $objectName], $this->context);
            } else {
                // @todo do we always overwrite the context or not?
                if (empty($object->getContext())) {
                    $object->setContext($this->context);
                }
            }
            return $object->showFilterForm($args);
        }
        // No object passed in
        if (!empty($args['definition'])) {
            return xarMod::apiFunc('dynamicdata', 'user', 'showfilterform', $args['definition'], $this->context);
        }
        // No direct definition, use the attributes
        return xarMod::apiFunc('dynamicdata', 'user', 'showfilterform', $args, $this->context);
    }

    public function xar_data_label($args = [])
    {
        // If we have an object, throw out its label
        if (!empty($args['object'])) {
            $object = $args['object'];
            return xarVar::prepForDisplay($object->label);
        }
        // We have a property
        if (!empty($args['property'])) {
            $property = $args['property'];
            unset($args['property']);
            if (empty($property->objectref)) {
                $property->objectref = new DummyObject($this->context);
            }
            return $property->showLabel($args);
        }
        // Ok, we have nothin, but a label
        if (!empty($args['label'])) {
            $args['context'] ??= $this->context;
            return xarTpl::property('dynamicdata', 'label', 'showoutput', $args, 'label');
        }
        return 'I need an object or a property or a label attribute';
    }

    public function xar_data_output($args = [])
    {
        if (empty($args['property'])) {
            // No prop, get one (the right one, preferably)
            $property = DataPropertyMaster::getProperty($args);
            $property->objectref = new DummyObject($this->context);
            // if we have a field attribute, use just that, otherwise use all attributes
            if (!empty($args['field'])) {
                return $property->showOutput($args['field']);
            }
            return $property->showOutput($args);
        }
        // We already had a property object, run its output method
        $property = $args['property'];
        unset($args['property']);
        if (empty($property->objectref)) {
            $property->objectref = new DummyObject($this->context);
        }
        // if we have a field attribute, use just that, otherwise use all attributes
        if (!empty($args['field'])) {
            return $property->showOutput($args['field']);
        }
        return $property->showOutput($args);
    }

    public function xar_data_input($args = [])
    {
        try {
            $params = $args;
            unset($params['hidden']);
            unset($params['preset']);
            if (empty($args['property'])) {
                // No property, gotta make one
                $property = DataPropertyMaster::getProperty($params);
                $property->objectref = new DummyObject($this->context);
            } else {
                // We do have a property in the attribute
                $property = $args['property'];
                unset($params['property']);
                if (empty($property->objectref)) {
                    $property->objectref = new DummyObject($this->context);
                }
            }
            if (!empty($args['preset']) && !isset($args['value'])) {
                return $property->_showPreset($params);
            }
            if (!empty($args['hidden'])) {
                return $property->showHidden($params);
            }
            return $property->showInput($params);
        } catch (Exception $e) {
            if (xarModVars::get('dynamicdata', 'debugmode') && in_array(xarUser::getVar('id'), xarConfigVars::get(null, 'Site.User.DebugAdmins'))) {
                return "<pre>" . $e->getMessage() . "</pre>";
            }
            return '';
        }
    }

    public function xar_data_filter($args = [])
    {
        try {
            $params = $args;
            unset($params['hidden']);
            unset($params['preset']);
            if (empty($args['property'])) {
                // No property, gotta make one
                $property = DataPropertyMaster::getProperty($params);
                $property->objectref = new DummyObject($this->context);
            } else {
                // We do have a property in the attribute
                $property = $args['property'];
                unset($params['property']);
                if (empty($property->objectref)) {
                    $property->objectref = new DummyObject($this->context);
                }
            }
            if (!empty($args['hidden'])) {
                return $property->showHidden($params);
            }
            return $property->showFilter($params);
        } catch (Exception $e) {
            if (xarModVars::get('dynamicdata', 'debugmode') && in_array(xarUser::getVar('id'), xarConfigVars::get(null, 'Site.User.DebugAdmins'))) {
                return "<pre>" . $e->getMessage() . "</pre>";
            }
            return '';
        }
    }

    public function xar_data_getitems($args = [])
    {
        // take a copy of the arguments if we're passing variables we want to re-use!?
        $properties = $args['properties'] ?? [];
        $values = $args['values'] ?? [];
        $params = $args;
        unset($params['properties']);
        unset($params['values']);
        // Use the object attribute
        if (!empty($args['object'])) {
            $object = $args['object'];
            unset($params['object']);
            // @todo do we always overwrite the context or not?
            if (empty($object->getContext())) {
                $object->setContext($this->context);
            }
            $values = $object->getItems($params);
            $properties = $object->getProperties();
            return [$properties, $values];
        }
        // This a string. we assume it's an object name
        if (!empty($args['objectname'])) {
            $objectName = $args['objectname'];
            unset($params['objectname']);
            $object = DataObjectFactory::getObjectList(['name' => $objectName], $this->context);
            $values = $object->getItems($params);
            $properties = $object->getProperties();
            return [$properties, $values];
        }
        [$properties, $values] = xarMod::apiFunc('dynamicdata', 'user', 'getitemsforview', $params, $this->context);
        return [$properties, $values];
    }

    public function xar_data_getitem($args = [])
    {
        // take a copy of the arguments if we're passing variables we want to re-use!?
        $properties = $args['properties'] ?? [];
        $params = $args;
        unset($params['properties']);
        if (!empty($args['object'])) {
            $object = $args['object'];
            unset($params['object']);
            if (is_string($object)) {
                $objectName = $object;
                $object = DataObjectFactory::getObject(['name' => $objectName], $this->context);
            } else {
                // @todo do we always overwrite the context or not?
                if (empty($object->getContext())) {
                    $object->setContext($this->context);
                }
            }
        } else {
            $params = array_merge(['getobject' => 1], $params);
            $object = xarMod::apiFunc('dynamicdata', 'user', 'getitem', $params, $this->context);
        }
        $object->getItem($params);
        // @todo not sure this will help unless we change template too
        $properties = $object->getProperties($params);
        return $properties;
    }

    public function xar_data_objectlist($args)
    {
        return DataObjectFactory::getObjectList($args, $this->context);
    }

    public function xar_data_object($args)
    {
        return DataObjectFactory::getObject($args, $this->context);
    }

    public function xar_data_property($args, $objectref = null)
    {
        $property = DataPropertyMaster::getProperty($args);
        $property->objectref = $objectref ?? new DummyObject($this->context);
        return $property;
    }

    public function xar_access($args = [], $exclusive = 1)
    {
        /** @var AccessProperty $access */
        $access = DataPropertyMaster::getProperty(['type' => 'access']);
        return $access->checkAccessTag($args, $exclusive);
    }
}
