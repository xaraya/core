<?php
/**
 * @package modules\modules
 * @subpackage modules
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/1.html
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
    if(!xarSecurity::check('AdminModules')) return;

    // Get parameters from input
    xarVar::fetch('id', 'int:1', $regId, 0, xarVar::NOT_REQUIRED);
    if (empty($regId)) return xarResponse::notFound();


    if (!isset($regId)) throw new EmptyParameterException('regid');

    // Pass to API
    $updated = xarMod::apiFunc('modules',
                             'admin',
                             'updateversion',
                              array('regId' => $regId));

    if (!isset($updated)) return;

    // Redirect to module list
    xarController::redirect(xarController::URL('modules', 'admin', 'list'));

    return true;
}

?>
