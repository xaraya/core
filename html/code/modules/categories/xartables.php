<?php
/**
 * Categories Module
 *
 * @package modules\categories
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.info/index.php/release/147.html
 *
 * @author Jim McDonald, Fl�vio Botelho <nuncanada@xaraya.com>, mikespub <postnuke@mikespub.net>
 */

/**
 * Specifies module tables namees
 *
 * @author  Jim McDonald, Fl�vio Botelho <nuncanada@xaraya.com>
 * @author  mikespub <postnuke@mikespub.net>
 * @return  array Table information
 */
function categories_xartables()
{
    // Initialise table array
    $xartable = array();

    // Set the table name
    $xartable['categories'] = xarDB::getPrefix() . '_categories';
    $xartable['categories_linkage'] = xarDB::getPrefix() . '_categories_linkage';
    $xartable['categories_basecategories'] = xarDB::getPrefix() . '_categories_basecategories';
    return $xartable;
}

?>