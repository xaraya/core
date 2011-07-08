<?php
/**
 * @package modules
 * @subpackage modules module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/1.html
 */
/**
 * Update the module version in the database
 *
 * @param 'regId' the id number of the module to update
 * @return boolean true on success, false on failure
 *
 * @author Xaraya Development Team
 */
function modules_admin_updateversion()
{
    // Security
    if(!xarSecurityCheck('AdminModules')) return;

    // Get parameters from input
    xarVarFetch('id', 'int:1', $regId, 0, XARVAR_NOT_REQUIRED);
    if (empty($regId)) return xarResponse::notFound();


    if (!isset($regId)) throw new EmptyParameterException('regid');

    // Pass to API
    $updated = xarMod::apiFunc('modules',
                             'admin',
                             'updateversion',
                              array('regId' => $regId));

    if (!isset($updated)) return;

    // Redirect to module list
    xarController::redirect(xarModURL('modules', 'admin', 'list'));

    return true;
}

?>
