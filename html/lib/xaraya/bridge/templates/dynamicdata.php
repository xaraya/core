<?php

/**
 * Twig extension to use Twig template engine for output in Xaraya
 */

namespace Xaraya\Bridge\TemplateEngine;

use Twig\TwigFunction;
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
            new TwigFunction('xar_data_view', $this->xar_data_view(...), ['is_safe' => ['html']]),
            // <xar:data-display object="$object"/>
            new TwigFunction('xar_data_display', $this->xar_data_display(...), ['is_safe' => ['html']]),
            new TwigFunction('xar_data_form', $this->xar_data_form(...), ['is_safe' => ['html']]),
            new TwigFunction('xar_data_filterform', $this->xar_data_filterform(...), ['is_safe' => ['html']]),
            // <xar:data-label property="$properties[$name]"/>
            new TwigFunction('xar_data_label', $this->xar_data_label(...), ['is_safe' => ['html']]),
            // <xar:data-output property="$properties[$name]" _itemid="$itemid" value="$fields[$name]"/>
            new TwigFunction('xar_data_output', $this->xar_data_output(...), ['is_safe' => ['html']]),
            new TwigFunction('xar_data_input', $this->xar_data_input(...), ['is_safe' => ['html']]),
            new TwigFunction('xar_data_filter', $this->xar_data_filter(...), ['is_safe' => ['html']]),
            new TwigFunction('xar_data_getitems', $this->xar_data_getitems(...)),
            new TwigFunction('xar_data_getitem', $this->xar_data_getitem(...)),
            new TwigFunction('xar_data_objectlist', $this->xar_data_objectlist(...)),
            new TwigFunction('xar_data_object', $this->xar_data_object(...)),
            new TwigFunction('xar_data_property', $this->xar_data_property(...)),
            new TwigFunction('xar_access', $this->xar_access(...)),
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
            $object = $this->data()->getObjectList(['name' => $objectName]);
            $object->getItems($args);
            return $object->showView($args);
        }
        // No object or objectname? Generate ourselves then
        return $this->mod()->apiFunc('dynamicdata', 'user', 'showview', $args);
    }

    public function xar_data_display($args = [])
    {
        if (!empty($args['object'])) {
            $object = $args['object'];
            unset($args['object']);
            if (is_string($object)) {
                $objectName = $object;
                $object = $this->data()->getObject(['name' => $objectName]);
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
            return $this->mod()->apiFunc('dynamicdata', 'user', 'showdisplay', $args['definition']);
        }
        // No direct definition, use the attributes
        return $this->mod()->apiFunc('dynamicdata', 'user', 'showdisplay', $args);
    }

    public function xar_data_form($args = [])
    {
        if (!empty($args['object'])) {
            // Use the object attribute
            $object = $args['object'];
            unset($args['object']);
            if (is_string($object)) {
                $objectName = $object;
                $object = $this->data()->getObject(['name' => $objectName]);
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
            return $this->mod()->apiFunc('dynamicdata', 'user', 'showform', $args['definition']);
        }
        // No direct definition, use the attributes
        return $this->mod()->apiFunc('dynamicdata', 'user', 'showform', $args);
    }

    public function xar_data_filterform($args = [])
    {
        if (!empty($args['object'])) {
            // Use the object attribute
            $object = $args['object'];
            unset($args['object']);
            if (is_string($object)) {
                $objectName = $object;
                $object = $this->data()->getObject(['name' => $objectName]);
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
            return $this->mod()->apiFunc('dynamicdata', 'user', 'showfilterform', $args['definition']);
        }
        // No direct definition, use the attributes
        return $this->mod()->apiFunc('dynamicdata', 'user', 'showfilterform', $args);
    }

    public function xar_data_label($args = [])
    {
        // If we have an object, throw out its label
        if (!empty($args['object'])) {
            $object = $args['object'];
            return $this->var()->prep($object->label);
        }
        // We have a property
        if (!empty($args['property'])) {
            $property = $args['property'];
            unset($args['property']);
            if (empty($property->objectref)) {
                $property->objectref = DummyObjectFactory::getDummyObject($this->context);
            }
            return $property->showLabel($args);
        }
        // Ok, we have nothin, but a label
        if (!empty($args['label'])) {
            $args['context'] ??= $this->context;
            // @todo why are we using this here instead of changing showoutput directly? - see xar:data-label
            return $this->tpl()->property('dynamicdata', 'label', 'showoutput', $args, 'label');
        }
        return 'I need an object or a property or a label attribute';
    }

    public function xar_data_output($args = [])
    {
        if (empty($args['property'])) {
            // No prop, get one (the right one, preferably)
            $property = $this->prop()->getProperty($args);
            if (empty($property->objectref)) {
                $property->objectref = DummyObjectFactory::getDummyObject($this->context);
            }
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
            $property->objectref = DummyObjectFactory::getDummyObject($this->context);
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
                $property = $this->prop()->getProperty($params);
                if (empty($property->objectref)) {
                    $property->objectref = DummyObjectFactory::getDummyObject($this->context);
                }
            } else {
                // We do have a property in the attribute
                $property = $args['property'];
                unset($params['property']);
                if (empty($property->objectref)) {
                    $property->objectref = DummyObjectFactory::getDummyObject($this->context);
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
            if ($this->mod('dynamicdata')->getVar('debugmode') && $this->user()->isDebugAdmin()) {
                return "<pre>" . $e->getMessage() . "</pre>";
            }
            return '<pre>' . $e . '</pre>';
            //return '';
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
                $property = $this->prop()->getProperty($params);
                if (empty($property->objectref)) {
                    $property->objectref = DummyObjectFactory::getDummyObject($this->context);
                }
            } else {
                // We do have a property in the attribute
                $property = $args['property'];
                unset($params['property']);
                if (empty($property->objectref)) {
                    $property->objectref = DummyObjectFactory::getDummyObject($this->context);
                }
            }
            if (!empty($args['hidden'])) {
                return $property->showHidden($params);
            }
            return $property->showFilter($params);
        } catch (Exception $e) {
            if ($this->mod('dynamicdata')->getVar('debugmode') && $this->user()->isDebugAdmin()) {
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
            $object = $this->data()->getObjectList(['name' => $objectName]);
            $values = $object->getItems($params);
            $properties = $object->getProperties();
            return [$properties, $values];
        }
        [$properties, $values] = $this->mod()->apiFunc('dynamicdata', 'user', 'getitemsforview', $params);
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
                $object = $this->data()->getObject(['name' => $objectName]);
            } else {
                // @todo do we always overwrite the context or not?
                if (empty($object->getContext())) {
                    $object->setContext($this->context);
                }
            }
        } else {
            $params = array_merge(['getobject' => 1], $params);
            $object = $this->mod()->apiFunc('dynamicdata', 'user', 'getitem', $params);
        }
        $object->getItem($params);
        // @todo not sure this will help unless we change template too
        $properties = $object->getProperties($params);
        return $properties;
    }

    public function xar_data_objectlist($args)
    {
        return $this->data()->getObjectList($args);
    }

    public function xar_data_object($args)
    {
        return $this->data()->getObject($args);
    }

    public function xar_data_property($args, $objectref = null)
    {
        $property = $this->prop()->getProperty($args);
        $property->objectref = $objectref ?? DummyObjectFactory::getDummyObject($this->context);
        return $property;
    }

    public function xar_access($args = [], $exclusive = 1)
    {
        /** @var \AccessProperty $access */
        $access = $this->prop()->getProperty(['type' => 'access']);
        return $access->checkAccessTag($args, $exclusive);
    }
}
