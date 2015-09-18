<?php
/**
 * @package modules
 * @subpackage installer module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.com/index.php/release/200.html
 */

/**
 * Check file
 *
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @subpackage installer module
 * @link http://xaraya.com/index.php/release/200.html
 */
function main_check_220()
{
    $data['check']['message'] = xarML('The database checks to version 2.2.0 were successfully completed');
    $data['check']['tasks'] = array();
    $data['upgrade'] = array();
    
    $checks = array(
                        'sql_220_dynamic_objects',
                        'sql_220_events',
                        'sql_220_hooks',
                    );
    foreach ($checks as $check) {
        if (!Upgrader::loadFile('checks/220/database/' . $check . '.php')) {
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