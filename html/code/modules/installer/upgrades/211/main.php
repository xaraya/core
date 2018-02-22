<?php
/**
 * Upgrade file
 *
 * @package modules\installer
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @subpackage installer
 * @link http://xaraya.info/index.php/release/200.html
 */

function main_upgrade_211()
{
    $data['upgrade']['message'] = xarML('The upgrade to version 2.1.1 was successfully completed');
    $data['upgrade']['tasks'] = array();

    $upgrades = array(
                        'sql_211_01',
                        'sql_211_02',
                    );
    foreach ($upgrades as $upgrade) {
        if (!Upgrader::loadFile('upgrades/211/database/' . $upgrade . '.php')) {
            $data['upgrade']['tasks'][] = array(
                'reply' => xarML('Failed!'),
                'description' => Upgrader::$errormessage,
                'reference' => $check,
                'success' => false,
            );
            $data['upgrade']['errormessage'] = xarML('Some checks failed. Check the reference(s) above to determine the cause.');
            continue;
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