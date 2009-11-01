<?php
/**
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage themes
 * @link http://xaraya.com/index.php/release/70.html
 */

/** 
 * Return table names to the core
 * @author Marty Vance
 * @return array
 */
function themes_xartables()
{
    $prefix = xarDB::getPrefix();
    $tables['themes'] = $prefix . '_themes';
    return $tables;
}
?>
