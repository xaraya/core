<?php
/**
 * Core configuration
 *
 */
/*
 * @author Marcel van der Boom <marcel@xaraya.com>
 */

/**
 * @package modules\installer\installer
 * @subpackage installer
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/200.html
 */
$configuration_name = xarML('Core Xaraya install - minimal modules needed to run Xaraya');

/**
 * @package modules\installer\installer
 * @subpackage installer
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/200.html
 */
function installer_core_moduleoptions()
{
    return array();
}

/**
 * @package modules\installer\installer
 * @subpackage installer
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/200.html
 */
function installer_core_privilegeoptions()
{
    return array(
        array(
            'item' => 'p1',
            'option' => 'true',
            'comment' => xarML('Registered users have read access to all modules of the site.')
        ),
        array(
            'item' => 'p2',
            'option' => 'false',
            'comment' => xarML('Unregistered users have read access to the non-core modules of the site. If this option is not chosen unregistered users see only the first page.')
        ),

    );
}

/**
 * Load the configuration
 *
 * @access public
 * @return boolean
 */
/**
 * @package modules\installer\installer
 * @subpackage installer
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/200.html
 */
function installer_core_configuration_load(Array $args=array())
{
// load the privileges chosen

    if(in_array('p1',$args)) {
        installer_core_readaccess();
        xarPrivileges::assign('ReadAccess','Users');
    }
    else {
        installer_core_casualaccess();
        xarPrivileges::assign('CasualAccess','Users');
    }

    if(in_array('p2',$args)) {
        installer_core_readaccess();
        installer_core_readnoncore();
        xarPrivileges::assign('ReadNonCore','Everybody');
   }
    else {
        if(in_array('p1',$args)) installer_core_casualaccess();
        xarPrivileges::assign('CasualAccess','Everybody');
    }

    return true;
}

/**
 * @package modules\installer\installer
 * @subpackage installer
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/200.html
 */
function installer_core_casualaccess()
{
    xarPrivileges::register('CasualAccess','All','themes','Block','All','ACCESS_OVERVIEW','Minimal access to a site');
    xarPrivileges::register('ViewLogin','All','authsystem','Block','login:Login:All','ACCESS_OVERVIEW','View the Login block');
    xarPrivileges::register('ViewBlocks','All','base','Block','All','ACCESS_OVERVIEW','View blocks of the Base module');
    xarPrivileges::register('ViewLoginItems','All','dynamicdata','Item','All','ACCESS_OVERVIEW','View some Dynamic Data items');
    xarPrivileges::register('ViewBlockItems','All','blocks','BlockItem','All','ACCESS_OVERVIEW','View block items in general');
    xarPrivileges::makeMember('ViewAuthsystem','CasualAccess');
    xarPrivileges::makeMember('ViewLogin','CasualAccess');
    xarPrivileges::makeMember('ViewBlocks','CasualAccess');
    xarPrivileges::makeMember('ViewLoginItems','CasualAccess');
    xarPrivileges::makeMember('ViewBlockItems','CasualAccess');
}

/**
 * @package modules\installer\installer
 * @subpackage installer
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/200.html
 */
function installer_core_readnoncore()
{
    xarPrivileges::register('ReadNonCore','All',null,'All','All','ACCESS_NONE','Read access only to none-core modules');
    xarPrivileges::register('DenyPrivileges','All','privileges','All','All','ACCESS_NONE','Deny access to the Privileges module');
//    xarPrivileges::register('DenyBlocks','All','blocks','All','All','ACCESS_NONE','Deny access to the Blocks module');
    xarPrivileges::register('DenyMail','All','mail','All','All','ACCESS_NONE','Deny access to the Mail module');
    xarPrivileges::register('DenyModules','All','modules','All','All','ACCESS_NONE','Deny access to the Modules module');
    xarPrivileges::register('DenyThemes','All','themes','All','All','ACCESS_NONE','Deny access to the Themes module');
    xarPrivileges::makeMember('ReadAccess','ReadNonCore');
    xarPrivileges::makeMember('DenyPrivileges','ReadNonCore');
//    xarPrivileges::makeMember('DenyBlocks','ReadNonCore');
    xarPrivileges::makeMember('DenyMail','ReadNonCore');
    xarPrivileges::makeMember('DenyModules','ReadNonCore');
    xarPrivileges::makeMember('DenyThemes','ReadNonCore');
}

/**
 * @package modules\installer\installer
 * @subpackage installer
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/200.html
 */
function installer_core_readaccess()
{
        xarPrivileges::register('ReadAccess','All','All','All','All','ACCESS_READ','Read access to all modules');
}
?>