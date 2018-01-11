<?php
/**
 * @package modules\themes
 * @subpackage themes
 * @copyright see the html/credits.html file in this release
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/70.html
 */
/**
 * List themes and current settings
 * @author Marty Vance
 * @author Chris Powis <crisp@xaraya.com>
 * @return array data for the template display
 */
function themes_admin_list()
{
    xarController::redirect(xarModURL('themes', 'admin', 'view'));
    return true;
}
?>