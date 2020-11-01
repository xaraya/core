<?php
/**
 * List modules and current settings
 *
 * @package modules\modules
 * @subpackage modules
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/1.html
 */
/**
 * List modules and current settings
 * @author Xaraya Development Team
 * @param several params from the associated form in template
 * @todo  finish cleanup, styles, filters and sort orders
 * @return array data for the template display
 */
function modules_admin_list()
{
    xarController::redirect(xarController::URL('modules', 'admin', 'view'));
    return true;
}
?>