<?php
/**
 * @package modules
 * @subpackage installer module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/42.html
 */

function main_210()
{
    $data['upgrade']['message'] = xarML('The upgrade to version 2.1.0 was successfully completed');
    $data['upgrade']['tasks'] = array();
    
    $upgrades = array(
                        'sql_210_01',
                        'sql_210_02',
                        'sql_210_03',
                        'sql_210_04',
                        'sql_210_05',
                        'sql_210_06',
                        'sql_210_07',
                        'sql_210_08',
                        'sql_210_09',
                        'sql_210_10',
                        'sql_210_11',
                        'sql_210_12',
                        'sql_210_13',
                        'sql_210_14',
                        'sql_210_15',
                        'sql_210_16',
                        'sql_210_17',
                        'sql_210_18',
                        'sql_210_19',
                        'sql_210_20',
                        'sql_210_21',
                        'sql_210_22',
                        'sql_210_23',
                    );
    foreach ($upgrades as $upgrade) {
        if (!Upgrader::loadFile('upgrades/210/database/' . $upgrade . '.php')) {
            $data['upgrade']['errormessage'] = Upgrader::$errormessage;
            return $data;
        }
        $result = $upgrade();
        $data['upgrade']['tasks'][] = array(
                            'reply' => $result['reply'],
                            'description' => $result['task'],
                            'reference' => $upgrade,
                            'success' => $result['success'],
                            );        
        if (!$result['success']) {
            $data['upgrade']['errormessage'] = xarML('Some parts of the upgrade failed. Check the reference(s) above to determine the cause.');
//            break;
        }
    }
    return $data;
}
?>