<?php

    sys::import('modules.dynamicdata.class.objects.base');

    class DProperty extends DataObject
    {
        function createItem(Array $args = array())
        {
            // If this is a modvar storage, create the modvar first
            if ($args['source'] == 'module variables') {
                $namepart = explode('_',$args['name']);
                if (empty($namepart[1])) {
                    $modulename = 'dynamicdata';
                    $varname = $namepart[0];
                } else {
                    $modulename = array_pop($namepart);
                    $varname = implode('_',$namepart);
                }
                $value = (isset($args['defaultvalue'])) ? $args['defaultvalue'] : true;
                xarModVars::set($modulename,$varname,$value);
            }
            
            $id = parent::createItem($args);
            return $id;
        }

        function deleteItem(Array $args = array())
        {
            $this->getItem($args);
            $source = $this->properties['source']->value;

            $id = parent::deleteItem($args);

            if ($source == '_module variables_') {
                $namepart = explode('_',$args['name']);
                xarModVars::delete($namepart[1],$namepart[0]);
            }
            return $id;
        }
    }

?>
