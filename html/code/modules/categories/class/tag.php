<?php
/**
 * Categories Module
 *
 * @package modules
 * @subpackage categories module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/147.html
 *
 * @author Marc Lutolf <mfl@netspan.ch>
 */

    class Tag extends Object
    {
        public $parentindices = array();

        function __construct($module=null, $itemtype=null, $itemid=null, $q=null, $tablename='zzztags')
        {
            if (empty($q)) {
                  sys::import('xaraya.structures.query');
                $q = new Query();
            }
            $tags = xarDB::getPrefix() . '_tags';
            $q->addtable($tags, $tablename);

            if ($q->type == 'SELECT') {
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