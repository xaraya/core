<?php
/**
 * File: $Id$
 *
 * Dynamic form block
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata module
 * @author mikespub <mikespub@xaraya.com>
*/
/**
 * initialise block
 */
function dynamicdata_formblock_init()
{
    return true;
}

/**
 * get information on block
 */
function dynamicdata_formblock_info()
{
    // Values
    return array('text_type' => 'form',
                 'module' => 'dynamicdata',
                 'text_type_long' => 'Show dynamic data form',
                 'allow_multiple' => true,
                 'form_content' => false,
                 'form_refresh' => false,
                 'show_preview' => true);
}

/**
 * display block
 */
function dynamicdata_formblock_display($blockinfo)
{
    // Security check
    if(!xarSecurityCheck('ReadDynamicDataBlock',1,'Block',"$blockinfo[title]:All:All")) return;

    // Get variables from content block
    $vars = @unserialize($blockinfo['content']);

    // Defaults
    if (empty($vars['numitems'])) {
        $vars['numitems'] = 5;
    }

    // Database information
    xarModDBInfoLoad('dynamicdata');
    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();
    $dynamicdata = $xartable['dynamic_data'];

    // Query
    $sql = "SELECT xar_dd_id,
                   xar_dd_value
            FROM $dynamicdata
            ORDER by xar_dd_value";
    $result = $dbconn->SelectLimit($sql, $vars['numitems']);

    if ($dbconn->ErrorNo() != 0) {
        return;
    }

    if ($result->EOF) {
        return;
    }
    $items = array();
    // Display each item, privileges permitting
    for (; !$result->EOF; $result->MoveNext()) {
        list($itemid, $name) = $result->fields;
        $item = array();

        if(!xarSecurityCheck('ViewDynamicDataBlocks',1,'Block',"$name:All:$itemid")) {
            if(!xarSecurityCheck('ReadDynamicDataBlock',1,'Block',"$name:All:$itemid")) {
                $item['link'] = xarModURL('dynamicdata',
                                          'user',
                                          'display',
                                          array('itemid' => $itemid));
                $item['title'] = $name;
                $items[] = $item;
            } else {
                $item['link'] = '';
                $item['title'] = $name;
                $items[] = $item;
            }
        }
    }

    // Populate block info and pass to theme
    if (count($items) > 0) {
        $blockinfo['content'] = array('items' => $items);
        return $blockinfo;
    }
}

/**
 * built-in block help/information system.
 */
function dynamicdata_formblock_help()
{
    // No information yet.
    return '';
}
?>