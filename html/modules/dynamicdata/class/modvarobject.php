<?php

    sys::import('modules.dynamicdata.class.objects.base');

    class ModVarObject extends DataObject
    {
        function initialize(Array $args = array())
        {
            foreach ($this->properties as $name => $property) {
                $modulename = substr($this->properties[$name]->source,18);
                if (empty($modulename)) throw new Exception(xarML('Incorrect module name: #(1)',$modulename));
                xarModVars::set($modulename,$this->properties[$name]->name,$this->properties[$name]->defaultvalue);
            }
        }
    }
?>