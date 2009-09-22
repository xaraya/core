<?php

    sys::import('modules.dynamicdata.class.objects.base');

    class ModVarObject extends DataObject
    {
        function initialize(Array $args = array())
        {
            foreach ($this->properties as $name => $property) {
                $nameparts = explode(': ', $this->properties[$name]->source);
                if (empty($nameparts[1])) throw new Exception(xarML('Incorrect module name: #(1)',$modulename));
                $test = xarModVars::get($nameparts[1],$this->properties[$name]->name);
                if ($test === null)
                    xarModVars::set($nameparts[1],$this->properties[$name]->name,$this->properties[$name]->defaultvalue);
            }
        }
    }
?>