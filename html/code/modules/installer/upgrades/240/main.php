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

function main_upgrade_240()
{
    $data['upgrade']['message'] = xarML('The upgrade to version 2.4.0 was successfully completed');
    $data['upgrade']['tasks'] = [];

    $upgrades = [
        'sql_240_01', // Upgrading the core module version numbers
    ];
    foreach ($upgrades as $upgrade) {
        if (!xarUpgrader::loadFile('upgrades/240/database/' . $upgrade . '.php')) {
            $data['upgrade']['tasks'][] = [
                'reply' => xarML('Failed!'),
                'description' => xarUpgrader::$errormessage,
                'reference' => $upgrade,
                'success' => false,
            ];
            $data['upgrade']['errormessage'] = xarML('Some checks failed. Check the reference(s) above to determine the cause.');
            continue;
        }
        $result = $upgrade();
        $data['upgrade']['tasks'][] = [
            'reply' => $result['reply'],
            'description' => $result['task'],
            'reference' => $upgrade,
            'success' => $result['success'],
        ];
        if (!$result['success']) {
            $data['upgrade']['errormessage'] = xarML('Some parts of the upgrade failed. Check the reference(s) above to determine the cause.');
            //            break;
        }
    }
    return $data;
}
