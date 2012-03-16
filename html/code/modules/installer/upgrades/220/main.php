<?php
/**
 * @package modules
 * @subpackage installer module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/200.html
 */

function main_upgrade_220()
{
    $data['upgrade']['message'] = xarML('The upgrade to version 2.2.0 was successfully completed');
    $data['upgrade']['tasks'] = array();
    
    $upgrades = array(
                        'sql_220_01',
                        'sql_220_02',
                        'sql_220_03', // Create event system table
                        'sql_220_04', // Initialise event system, register event subjects and observers
                        'sql_220_05', // Initialize hooks system, register hook subjects
                        'sql_220_06', // Register hook observers
                        'sql_220_07', // Create hooks table, register hooks
                        'sql_220_08', // Re-classify Authsystem to Users & Groups
                        'sql_220_09', // Add 3 configvars to themes module
                        'sql_220_10', // Move users that see debug info from DD to roles module
                        'sql_220_11', // Add a configvar for the ssl port
                        'sql_220_12', // Redefine the config property of the objects object
                        'sql_220_13', // Refactor roles name as textbox property
                        'sql_220_14', // Create an access field in the objects table
                        'sql_220_15', // Create an access property in the objects object
                        'sql_220_16', // Move access data from the config to the access field
                        'sql_220_17', // Update the configuration table
                        
                    );
    foreach ($upgrades as $upgrade) {
        if (!Upgrader::loadFile('upgrades/220/database/' . $upgrade . '.php')) {
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
?>