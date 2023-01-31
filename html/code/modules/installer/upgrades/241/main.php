<?php
/**
 * @package modules\installer
 * @subpackage installer
 * @category Xaraya Web Applications Framework
 * @version 2.4.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.info/index.php/release/200.html
 */

function main_upgrade_241()
{
    $data['upgrade']['message'] = xarML('The upgrade to version 2.4.1 was successfully completed');
    $data['upgrade']['tasks'] = array();

    $upgrades = array(
                        'sql_241_01', // Upgrading the core module version numbers
                        'sql_241_02', // Add a class column to the eventsystem table
                        'sql_241_03', // Add class and filepath columns to the block_types table
                    );
    foreach ($upgrades as $upgrade) {
        if (!Upgrader::loadFile('upgrades/241/database/' . $upgrade . '.php')) {
            $data['upgrade']['tasks'][] = array(
                'reply' => xarML('Failed!'),
                'description' => Upgrader::$errormessage,
                'reference' => $upgrade,
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
