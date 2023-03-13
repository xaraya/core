<?php
/**
 * @package modules\modules
 * @subpackage modules
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/1.html
 */
/**
 * Set preferences for modules module
 *
 * @author Xaraya Development Team
 * @access public
 * @return array|void data for the template display
 */
function modules_admin_prefs()
{
    // Security
    if(!xarSecurity::check('AdminModules')) return;
    
    $data = array();
    
    // done
    return $data;
}
