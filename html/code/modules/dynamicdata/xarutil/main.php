<?php
/**
 * Main entry point for the utility interface of this module
 *
 * @package modules
 * @subpackage dynamicdata module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * The main utility interface function of this module.
 * This function is the default function for the admin interface, and is called whenever the module is
 * initiated with only a util type but no func parameter passed.  
 * The function displays the utilities page of this module.
 * @return array array of template data
 */
function dynamicdata_util_main()
{
    // Security
    if(!xarSecurityCheck('AdminDynamicData')) return;

    $data = array();
    $data['menutitle'] = xarML('Dynamic Data Utilities');

    xarTpl::setPageTemplateName('admin');

    return $data;
}

?>
