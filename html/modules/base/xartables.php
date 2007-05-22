<?php
/**
 * @package modules
 * @copyright (C) 2005-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage base
 * @link http://xaraya.com/index.php/release/68.html
 */

/**
 * Passes table definitons back to Xaraya core
 * @author Paul Rosania
 * @return array
 */
function base_xartables()
{
    $tables = array();
    //@todo move this somewhere else
    $tables['template_tags'] = xarDB::getPrefix() . '_template_tags';
    return $tables;
}
?>
