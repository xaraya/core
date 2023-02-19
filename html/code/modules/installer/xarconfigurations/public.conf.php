<?php
/**
 * Public configuration
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
$configuration_name = xarML('Public Site - modules and privilege appropriate for open access');

/**
 * @package modules\installer\installer
 * @subpackage installer
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/200.html
 */
function installer_public_moduleoptions()
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
function installer_public_privilegeoptions()
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
                        'comment' => xarML('Unregistered users have read access to the non-core modules of the site and can submit articles. If this option is not chosen unregistered users see only the first page.')
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
function installer_public_configuration_load(Array $args=array())
{
    if(in_array('p1',$args)) {
        installer_public_moderatenoncore();
        xarPrivileges::assign('ModerateNonCore','Users');
    }
    else {
        installer_public_readnoncore();
        xarPrivileges::assign('ReadNonCore','Users');
    }

    if(in_array('p2',$args)) {
        installer_public_commentnoncore();
        xarPrivileges::assign('CommentNonCore','Everybody');
   }
    else {
        if(in_array('p1',$args)) installer_public_readnoncore2();
        xarPrivileges::assign('ReadNonCore','Everybody');
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
function installer_public_commentnoncore()
{
    xarPrivileges::register('CommentNonCore','All',null,'All','All','ACCESS_NONE','Read access only to none-core modules');
    xarPrivileges::register('CommentAccess','All','All','All','All','ACCESS_COMMENT','Comment access to all modules');
    xarPrivileges::makeMember('CommentAccess','CommentNonCore');
    xarPrivileges::makeMember('DenyPrivileges','CommentNonCore');
    xarPrivileges::makeMember('ViewAuthsystem','CommentNonCore');
    xarPrivileges::makeMember('DenyMail','CommentNonCore');
    xarPrivileges::makeMember('DenyModules','CommentNonCore');
    xarPrivileges::makeMember('DenyThemes','CommentNonCore');
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
function installer_public_moderatenoncore()
{
    xarPrivileges::register('ModerateNonCore','All',null,'All','All','ACCESS_NONE','Read access only to none-core modules');
    xarPrivileges::register('ModerateAccess','All','All','All','All','ACCESS_MODERATE','Moderate access to all modules');
    xarPrivileges::register('DenyPrivileges','All','privileges','All','All','ACCESS_NONE','Deny access to the Privileges module');
    xarPrivileges::register('DenyMail','All','mail','All','All','ACCESS_NONE','Deny access to the Mail module');
    xarPrivileges::register('DenyModules','All','modules','All','All','ACCESS_NONE','Deny access to the Modules module');
    xarPrivileges::register('DenyThemes','All','themes','All','All','ACCESS_NONE','Deny access to the Themes module');
    xarPrivileges::makeMember('ModerateAccess','ModerateNonCore');
    xarPrivileges::makeMember('DenyPrivileges','ModerateNonCore');
    xarPrivileges::makeMember('ViewAuthsystem','ModerateNonCore');
    xarPrivileges::makeMember('DenyMail','ModerateNonCore');
    xarPrivileges::makeMember('DenyModules','ModerateNonCore');
    xarPrivileges::makeMember('DenyThemes','ModerateNonCore');
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
function installer_public_readnoncore()
{
    xarPrivileges::register('ReadNonCore','All',null,'All','All','ACCESS_NONE','Read access only to none-core modules');
    xarPrivileges::register('ReadAccess','All','All','All','All','ACCESS_READ','Read access to all modules');
    xarPrivileges::register('DenyPrivileges','All','privileges','All','All','ACCESS_NONE','Deny access to the Privileges module');
    xarPrivileges::register('DenyMail','All','mail','All','All','ACCESS_NONE','Deny access to the Mail module');
    xarPrivileges::register('DenyModules','All','modules','All','All','ACCESS_NONE','Deny access to the Modules module');
    xarPrivileges::register('DenyThemes','All','themes','All','All','ACCESS_NONE','Deny access to the Themes module');
    xarPrivileges::makeMember('ReadAccess','ReadNonCore');
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
function installer_public_readnoncore2()
{
    xarPrivileges::register('ReadNonCore','All',null,'All','All','ACCESS_NONE','Read access only to none-core modules');
    xarPrivileges::register('ReadAccess','All','All','All','All','ACCESS_READ','Read access to all modules');
    xarPrivileges::makeMember('ReadAccess','ReadNonCore');
    xarPrivileges::makeMember('DenyPrivileges','ReadNonCore');
    xarPrivileges::makeMember('ViewAuthsystem','ReadNonCore');
    xarPrivileges::makeMember('DenyMail','ReadNonCore');
    xarPrivileges::makeMember('DenyModules','ReadNonCore');
    xarPrivileges::makeMember('DenyThemes','ReadNonCore');
}
