<?php
/**
 * User System
 *
 * @package core\users\legacy
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author Jim McDonald
 * @author Marco Canini <marco@xaraya.com>
 * @author Marc Lutolf
 * @todo <marco> user status field
 */

/*
 * Error codes
 * @deprecated
 */
define('XARUSER_AUTH_FAILED', -1);
define('XARUSER_AUTH_DENIED', -2);
define('XARUSER_LAST_RESORT', -3);

// Legacy calls

/**
 * Legacy call
 * @uses xarUser::logIn()
 * @deprecated
 */
function xarUserLogIn($userName, $password, $rememberMe = 0)
{   
    return xarUser::logIn($userName, $password, $rememberMe = 0); 
}
/**
 * Legacy call
 * @uses xarUser::logOut()
 * @deprecated
 */
function xarUserLogOut()
{   
    return xarUser::logOut(); 
}
/**
 * Legacy call
 * @uses xarUser::isLoggedIn()
 * @deprecated
 */
function xarUserIsLoggedIn()
{   
    return xarUser::isLoggedIn(); 
}
/**
 * Legacy call
 * @uses xarUser::getNavigationThemeName()
 * @deprecated
 */
function xarUserGetNavigationThemeName()
{   
    return xarUser::getNavigationThemeName(); 
}
/**
 * Legacy call
 * @uses xarUser::setNavigationThemeName()
 * @deprecated
 */
function xarUserSetNavigationThemeName($themeName)
{   
    xarUser::setNavigationThemeName($themeName);
}
/**
 * Legacy call
 * @uses xarUser::getNavigationLocale()
 * @deprecated
 */
function xarUserGetNavigationLocale()
{   
    return xarUser::getNavigationLocale(); 
}
/**
 * Legacy call
 * @uses xarUser::setNavigationLocale()
 * @deprecated
 */
function xarUserSetNavigationLocale($locale)
{   
    return xarUser::setNavigationLocale($locale); 
}
/**
 * Legacy call
 * @uses xarUser::getVar()
 * @deprecated
 */
function xarUserGetVar($name, $userId = NULL)
{   
    return xarUser::getVar($name, $userId); 
}
/**
 * Legacy call
 * @uses xarUser::setVar()
 * @deprecated
 */
function xarUserSetVar($name, $value, $userId = null)
{   
    return xarUser::setVar($name, $value, $userId); 
}
/**
 * Legacy call
 * @uses xarUser::comparePasswords()
 * @deprecated
 */
function xarUserComparePasswords($givenPassword, $realPassword, $userName, $cryptSalt = '')
{   
    return xarUser::comparePasswords($givenPassword, $realPassword, $userName, $cryptSalt); 
}

