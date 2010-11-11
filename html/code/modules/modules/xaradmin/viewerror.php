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
 * View an error with a module
 *
 * @author Xaraya Development Team
 * @param id the module's registered id
 * @return mixed true on success, error message on failure
 */
function modules_admin_viewerror()
{
    // Get parameters
    xarVarFetch('id', 'int', $regId, 0, XARVAR_NOT_REQUIRED);
    if (empty($regId)) return xarResponse::notFound();

    //if (!xarSecConfirmAuthKey()) return;

    // Get module information from the database
    $dbModule = xarMod::apiFunc('modules',
                              'admin',
                              'getdbmodules',
                              array('regId' => $regId));
    if (!isset($dbModule)) return;

    // Get module information from the filesystem
    $fileModule = xarMod::apiFunc('modules',
                                'admin',
                                'getfilemodules',
                                array('regId' => $regId));
    if (!isset($fileModule)) return;

    // Get the module state and display appropriate template
    // for the error that was encountered with the module
    switch($dbModule['state']) {
        case XARMOD_STATE_ERROR_UNINITIALISED:
        case XARMOD_STATE_ERROR_INACTIVE:
        case XARMOD_STATE_ERROR_ACTIVE:
        case XARMOD_STATE_ERROR_UPGRADED: 
            // Set template to 'update'
            $template = 'errorupdate';

            // Set regId 
            $data['regId'] = $regId;

            // Set module name
            $data['modname'] = $dbModule['name'];

            // Set db version
            $data['dbversion'] = $dbModule['version'];

            // Set file version number of module
            $data['fileversion'] = $fileModule['version'];

            break;

        default:
            break;
    }

    // Return the template variables to BL
    return xarTplModule('modules', 'admin', $template, $data);
}

?>
