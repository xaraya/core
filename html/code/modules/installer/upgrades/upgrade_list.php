<?php
/**
 * Upgrade List file
 *
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @subpackage installer module
 * @link http://xaraya.info/index.php/release/200.html
 */
/* WARNING
 * Modification of this file is not supported.
 * Any modification is at your own risk and
 * may lead to inablity of the system to process
 * the file correctly, resulting in unexpected results.
 */
    function installer_adminapi_get_upgrade_list() 
    {    
        return array(
                    '210' => '2.1.0',
                    '211' => '2.1.1',
                    '212' => '2.1.2',
                    '213' => '2.1.3',
                    '220' => '2.2.0',
                    '230' => '2.3.0',
                    '231' => '2.3.1',
                    '240' => '2.4.0',
        );
    }

?>