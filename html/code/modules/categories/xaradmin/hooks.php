<?php
/**
 * Categories Module
 *
 * @package modules
 * @subpackage categories module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/147.html
 *
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
