<?php
/**
 * @package Xaraya eXtensible Management System
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Modules module
 */
/**
 * Update module information
 * @param $args['regid'] the id number of the module to update
 * @param $args['displayname'] the new display name of the module
 * @param $args['description'] the new description of the module
 * @returns bool
 * @return true on success, false on failure
 */
function modules_adminapi_update($args)
{
    // Get arguments from argument array
    extract($args);

    // Argument check
    if (!isset($regid)) throw new EmptyParameterException('regid');

    // Security Check
    if(!xarSecurityCheck('AdminModules',0,'All',"All:All:$regid")) return;

    if (!empty($observers)) {
        foreach ($observers as $hookmod => $subjects) {
            $observer_id = xarMod::getRegID($hookmod);
            if (!xarMod::apiFunc('modules', 'admin', 'updatehooks',
                array(
                    'regid' => $observer_id,
                    'subjects' => $subjects,
                ))) return;
        }
    } 

    return true;
}

?>