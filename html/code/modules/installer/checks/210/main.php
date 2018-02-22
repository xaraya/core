<?php
/**
 * @package modules\installer
 * @subpackage installer
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.info/index.php/release/200.html
 */

/**
 * Check file
 *
 * @package modules\installer
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @subpackage installer
 * @link http://xaraya.info/index.php/release/200.html
 */
function main_check_210()
{
    $data['check']['message'] = xarML('The database checks to version 2.1.0 were successfully completed');
    $data['check']['tasks'] = array();
    
    $checks = array(
                        'sql_210_block_group_instances',
                        'sql_210_block_instances',
                        'sql_210_block_types',
                        'sql_210_cache_blocks',
                        'sql_210_dynamic_data',
                        'sql_210_dynamic_configurations',
                        'sql_210_dynamic_objects',
                        'sql_210_dynamic_properties',
                        'sql_210_dynamic_properties_def',
                        'sql_210_hooks',
                        'sql_210_modules',
                        'sql_210_module_vars',
                        'sql_210_module_itemvars',
                        'sql_210_privileges',
                        'sql_210_privmembers',
                        'sql_210_roles',
                        'sql_210_rolemembers',
                        'sql_210_security_acl',
                        'sql_210_security_instances',
                        'sql_210_security_realms',
                        'sql_210_session_info',
                        'sql_210_themes',
                        'sql_210_dynamicdata_objects',
                        'sql_210_roles_roles',
                        'sql_210_roles_tree',
                    );
    foreach ($checks as $check) {
        if (!Upgrader::loadFile('checks/210/database/' . $check . '.php')) {
            $data['check']['tasks'][] = array(
                'reply' => xarML('Failed!'),
                'description' => Upgrader::$errormessage,
                'reference' => $check,
                'success' => false,
            );
            $data['check']['errormessage'] = xarML('Some checks failed. Check the reference(s) above to determine the cause.');
            continue;
        }
        $result = $check();
        $data['check']['tasks'][] = array(
                            'reply' => $result['reply'],
                            'description' => $result['task'],
                            'reference' => $check,
                            'success' => $result['success'],
                            );
        if (!$result['success']) {
            $data['check']['errormessage'] = xarML('Some checks failed. Check the reference(s) above to determine the cause.');
//            break;
        }
    }
    return $data;
}
?>
