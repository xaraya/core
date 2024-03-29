<?php
/**
 * Community configuration
 *
 */
/**
 * @package modules\installer\installer
 * @subpackage installer
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/200.html
 *
 * @author Marc Lutolf
 */
$configuration_name = xarML('Community Site -- modules and privilege for semi-open access');

/**
 * @package modules\installer\installer
 * @subpackage installer
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/200.html
 */
function installer_community_moduleoptions()
{
    return array(
        array('name' => "autolinks",            'regid' => 11),
        array('name' => "bloggerapi",           'regid' => 745),
        array('name' => "categories",           'regid' => 147),
        array('name' => "comments",             'regid' => 14),
        array('name' => "example",              'regid' => 36),
        array('name' => "hitcount",             'regid' => 177),
        array('name' => "ratings",              'regid' => 41),
        array('name' => "registration",         'regid' => 30205),
        array('name' => "search",               'regid' => 32),
        array('name' => "sniffer",              'regid' => 755),
        array('name' => "stats",                'regid' => 34),
        array('name' => "xmlrpcserver",         'regid' => 743),
        array('name' => "xmlrpcsystemapi",      'regid' => 744),
        array('name' => "xmlrpcvalidatorapi",   'regid' => 746),
        array('name' => "articles",             'regid' => 151)
    );
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
function installer_community_privilegeoptions()
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
                    )
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
function installer_community_configuration_load(Array $args=array())
{
// load the privileges chosen

    if(in_array('p1',$args)) {
        installer_community_readaccess();
        xarPrivileges::assign('ReadAccess','Users');
    }
    else {
        installer_community_casualaccess();
        xarPrivileges::assign('CasualAccess','Users');
    }

    if(in_array('p2',$args)) {
        // Only do readaccess if we havent already done so
        if(!in_array('p1',$args)) installer_community_readaccess();
        installer_community_readnoncore();
        xarPrivileges::assign('ReadNonCore','Everybody');
   }
    else {
        if(in_array('p1',$args)) installer_community_casualaccess();
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
function installer_community_casualaccess()
{
    xarPrivileges::register('CasualAccess','All','themes','Block','All','ACCESS_OVERVIEW','Minimal access to a site');
    xarPrivileges::register('ViewLogin','All','authsystem','Block','login:Login:All','ACCESS_OVERVIEW','View the Login block');
    xarPrivileges::register('ViewBlocks','All','base','Block','All','ACCESS_OVERVIEW','View blocks of the Base module');
    xarPrivileges::register('ViewLoginItems','All','dynamicdata','Item','All','ACCESS_OVERVIEW','View some Dynamic Data items');
    xarPrivileges::register('ViewBlockItems','All','blocks','BlockItem','All','ACCESS_OVERVIEW','View block items in general');
    xarPrivileges::makeMember('ViewLogin','CasualAccess');
    xarPrivileges::makeMember('ViewBlocks','CasualAccess');
    xarPrivileges::makeMember('ViewAuthsystem','CasualAccess');
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
function installer_community_readnoncore()
{
    xarPrivileges::register('ReadNonCore','All',null,'All','All','ACCESS_NONE','Read access only to none-core modules');
    xarPrivileges::register('DenyPrivileges','All','privileges','All','All','ACCESS_NONE','Deny access to the Privileges module');
    xarPrivileges::register('DenyMail','All','mail','All','All','ACCESS_NONE','Deny access to the Mail module');
    xarPrivileges::register('DenyModules','All','modules','All','All','ACCESS_NONE','Deny access to the Modules module');
    xarPrivileges::register('DenyThemes','All','themes','All','All','ACCESS_NONE','Deny access to the Themes module');
    xarPrivileges::makeMember('ReadAccess','ReadNonCore');
    xarPrivileges::makeMember('ViewAuthsystem','ReadNonCore');
    xarPrivileges::makeMember('DenyPrivileges','ReadNonCore');
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
function installer_community_readaccess()
{
        xarPrivileges::register('ReadAccess','All','All','All','All','ACCESS_READ','Read access to all modules');
}
