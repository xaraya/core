<?php
/**
 * Categories Module
 *
 * @package modules\categories
 * @subpackage categories
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/147.html
 *
 */

/**
 * Hooks shows the configuration of hooks for other modules 
 * 
 * @param void N/A
 * @return array Returns display data array on success, null on security check failure
 */
function categories_admin_hooks()
{
    // Security check
    if(!xarSecurity::check('ManageCategories')) return;

    $data = array();

    return $data;
}

?>
