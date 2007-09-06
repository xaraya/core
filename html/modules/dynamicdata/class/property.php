<?php

    sys::import('modules.dynamicdata.class.objects.base');

    class DProperty extends DataObject
    {
        function createItem(Array $args = array())
        {
            if ($args['source'] == 'module variables') {
				$namepart = explode('_',$args['name']);
				if (empty($namepart[1])) $namepart[1] = 'dynamicdata';
				xarModVars::set($namepart[1],$namepart[0],serialize(true));
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
