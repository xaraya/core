<?php
/**
 * @package modules
 * @subpackage installer module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/200.html
 */

function main_220()
{
    $data['upgrade']['message'] = xarML('The upgrade to version 2.2.0 was successfully completed');
    $data['upgrade']['tasks'] = array();
    
    $upgrades = array(
                        'sql_220_01',
                        'sql_220_02',
                        'sql_220_03',
                    );
    foreach ($upgrades as $upgrade) {
        if (!Upgrader::loadFile('upgrades/220/database/' . $upgrade . '.php')) {
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