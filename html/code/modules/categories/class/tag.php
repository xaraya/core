<?php

    class Tag extends Object
    {
        public $parentindices = array();

        function __construct($module=null, $itemtype=null, $itemid=null, $q=null, $tablename='zzztags')
        {
            if (empty($q) {
                  sys::import('xaraya.structures.query');
                $q = new Query();
            }
            $tags = xarDB::getPrefix() . '_tags';
            $q->addtable($tags, $tablename);

            if ($q->type == 'SELECT' {
                if (isset($module)) $q->eq($tablename . '.module_id',$module);
                if (isset($itemtype)) $q->eq($tablename . '.itemtype',$itemtype);
                if (isset($itemid)) $q->eq($tablename . '.itemid',$itemid);
            } else {
                if (isset($module)) $q->addfield($tablename . '.module_id',$module);
                if (isset($itemtype)) $q->addfield($tablename . '.itemtype',$itemtype);
                if (isset($itemid)) $q->addfield($tablename . '.itemid',$itemid);
            }
        }

    }
?>