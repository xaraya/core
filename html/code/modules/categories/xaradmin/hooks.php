<?php
/**
 * Categories module
 *
 * @package modules
 * @copyright (C) copyright-placeholder
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Categories Module
 * @link http://xaraya.com/index.php/release/147.html
 * @author Categories module development team
 */
/**
 * Hooks shows the configuration of hooks for other modules
 *
 * @return array $data containing template data
 */
function categories_admin_hooks()
{
    // Security check
    if(!xarSecurityCheck('ViewCategories')) return;

    $data = array();

    return $data;
}

?>
