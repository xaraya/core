<?php
/**
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamic Data module
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * Main menu for utility functions
 */
function dynamicdata_util_main()
{
// Security Check
    if(!xarSecurityCheck('AdminDynamicData')) return;

    $data = array();
    $data['menutitle'] = xarML('Dynamic Data Utilities');

    xarTplSetPageTemplateName('admin');

    return $data;
}

?>
