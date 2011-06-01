<?php
/**
 * File: $Id: s.xarinit.php 1.22 03/01/26 20:03:00-05:00 John.Cox@mcnabb. $
 *
 * Categories System
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 *
 * @subpackage categories module
 * @author Jim McDonald, Flávio Botelho <nuncanada@xaraya.com>, mikespub <postnuke@mikespub.net>
*/

/**
 * specifies module tables namees
 *
 * @author  Jim McDonald, Flávio Botelho <nuncanada@xaraya.com>, mikespub <postnuke@mikespub.net>
 * @access  public
 * @param   none
 * @return  $xartable array
 * @throws  no exceptions
 * @todo    nothing
*/
function categories_xartables()
{
    // Initialise table array
    $xartable = array();

    // Set the table name
    $xartable['tags'] = xarDB::getPrefix() . '_tags';
    $xartable['categories'] = xarDB::getPrefix() . '_categories';
    $xartable['categories_linkage'] = xarDB::getPrefix() . '_categories_linkage';
    $xartable['categories_basecategories'] = xarDB::getPrefix() . '_categories_basecategories';
    return $xartable;
}

?>