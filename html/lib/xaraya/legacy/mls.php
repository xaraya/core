<?php
/**
 * Multi Language System
 *
 * @package core\multilanguage\legacy
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author Marco Canini <marco@xaraya.com>
 * @author Roger Raymond <roger@asphyxia.com>
 * @author Marcel van der Boom <mrb@hsdev.com>
 * @author Volodymyr Metenchuk <voll@xaraya.com>
 * @author Marc Lutolf
 * @todo Dynamic Translations
 * @todo Timezone and DST support (default offset is supported now)
 * @todo Write standard core translations
 * @todo Complete changes as described in version 0.9 of MLS RFC
 * @todo Implements the request(ed) locale APIs for backend interactions
 * @todo See how utf-8 works for xml backend
 */

// Legacy calls

/**
 * Legacy call
 * @uses xarMLS::getMode()
 * @deprecated
 */
function xarMLSGetMode()
{   
    return xarMLS::getMode(); 
}

/**
 * Legacy call
 * @uses xarMLS::getSiteLocale()
 * @deprecated
 */
function xarMLSGetSiteLocale()
{   
    return xarMLS::getSiteLocale(); 
}

/**
 * Legacy call
 * @uses xarMLS::listSiteLocales()
 * @deprecated
 */
function xarMLSListSiteLocales()
{   
    return xarMLS::listSiteLocales(); 
}

/**
 * Legacy call
 * @uses xarMLS::getCurrentLocale()
 * @deprecated
 */
function xarMLSGetCurrentLocale()
{   
    return xarMLS::getCurrentLocale(); 
}

/**
 * Legacy call
 * @uses xarMLS::getCharsetFromLocale()
 * @deprecated
 */
function xarMLSGetCharsetFromLocale($locale)
{   
    return xarMLS::getCharsetFromLocale($locale); 
}

/**
 * Legacy call
 * @uses xarMLS::translate()
 * @deprecated
 */
function xarML($rawstring/*, ...*/)
{
    return call_user_func_array(array('xarMLS', 'translate'), func_get_args());
}

/**
 * Legacy call
 * @uses xarMLS::translateByKey()
 * @deprecated
 */
function xarMLByKey($key/*, ...*/)
{   
    return call_user_func_array(array('xarMLS', 'translateByKey'), func_get_args());
}

/**
 * Legacy call
 * @uses xarMLS::localeGetInfo()
 * @deprecated
 */
function xarLocaleGetInfo($locale)
{   
    return xarMLS::localeGetInfo($locale); 
}

/**
 * Legacy call
 * @uses xarMLS::localeGetString()
 * @deprecated
 */
function xarLocaleGetString($localeInfo)
{   
    return xarMLS::localeGetString($localeInfo); 
}

/**
 * Legacy call
 * @uses xarMLS::localeGetList()
 * @deprecated
 */
function xarLocaleGetList($filter=array())
{   
    return xarMLS::localeGetList($filter); 
}

/**
 * Legacy call
 * @uses xarMLS::userTime()
 * @deprecated
 */
function xarMLS_userTime($time=null,$flag=1)
{   
    return xarMLS::userTime($time,$flag); 
}

/**
 * Legacy call
 * @uses xarMLS::userOffset()
 * @deprecated
 */
function xarMLS_userOffset($timestamp = null)
{   
    return xarMLS::userOffset($timestamp); 
}

/**
 * Legacy call
 * @uses xarMLS::setCurrentLocale()
 * @deprecated
 */
function xarMLS_setCurrentLocale($locale)
{   
    return xarMLS::setCurrentLocale($locale); 
}

/**
 * Legacy call
 * @uses xarMLS::_loadTranslations()
 * @deprecated
 */
function xarMLS_loadTranslations($dnType, $dnName, $ctxType, $ctxName)
{   
    return xarMLS::_loadTranslations($dnType, $dnName, $ctxType, $ctxName); 
}

/**
 * Legacy call
 * @uses xarMLS::loadTranslations()
 * @deprecated
 */
function xarMLSLoadTranslations($path)
{   
    return xarMLS::loadTranslations($path); 
}

/**
 * Legacy call
 * @uses xarMLS::convertFromInput()
 * @deprecated
 */
function xarMLS_convertFromInput($var, $method)
{   
    return xarMLS::convertFromInput($var, $method); 
}

/**
 * Legacy call
 * @uses xarMLS::parseLocaleString()
 * @deprecated
 */
function xarMLS__parseLocaleString($locale)
{   
    return xarMLS::parseLocaleString($locale); 
}

/**
 * Legacy call
 * @uses xarMLS::mkdirr()
 * @deprecated
 */
function xarMLS__mkdirr($path)
{   
    return xarMLS::mkdirr($path); 
}

/**
 * Legacy call
 * @uses xarMLS::iswritable()
 * @deprecated
 */
function xarMLS__iswritable($directory=NULL)
{   
    return xarMLS::iswritable($directory=NULL); 
}

