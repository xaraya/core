<?php
/**
 * @package modules
 * @subpackage modules module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/1.html
 */
/**
 * Update module information
 * @param array    $args array of optional parameters<br/>
 *        integer  $args['regid'] the id number of the module to update<br/>
 *        string   $args['displayname'] the new display name of the module<br/>
 *        string   $args['description'] the new description of the module
 * @return boolean true on success, false on failure
 */
function modules_adminapi_update(Array $args=array())
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
