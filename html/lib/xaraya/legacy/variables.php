<?php
/**
 * Variable utilities (legacy)
 *
 * @package core\variables\legacy
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author Marco Canini marco@xaraya.com
 * @author Flavio Botelho
 */

/**
 * Variables package defines
 * @package core\variables\legacy
 * @deprecated
 */
define('XARVAR_ALLOW_NO_ATTRIBS', 1);
define('XARVAR_ALLOW', 2);

define('XARVAR_GET_OR_POST',  0);
define('XARVAR_GET_ONLY',     2);
define('XARVAR_POST_ONLY',    4);

define('XARVAR_NOT_REQUIRED', 64);
define('XARVAR_DONT_SET',     128);
define('XARVAR_DONT_REUSE',   256);

define('XARVAR_PREP_FOR_NOTHING', 0);
define('XARVAR_PREP_FOR_DISPLAY', 1);
define('XARVAR_PREP_FOR_HTML',    2);
define('XARVAR_PREP_FOR_STORE',   4);
define('XARVAR_PREP_TRIM',        8);

/**
 * Legacy call
 * @uses xarVar::init()
 * @deprecated
 */
function xarVar_init(&$args)
{
    return xarVar::init($args);
}

/**
 * Legacy call
 * @uses xarVar::batchFetch()
 * @deprecated
 */
function xarVarBatchFetch()
{
    return xarVar::batchFetch();
}

/**
 * Legacy call
 * @uses xarVar::fetch()
 * @deprecated
 */
function xarVarFetch($name, $validation, &$value, $defaultValue = NULL, $flags = xarVar::GET_OR_POST, $prep = xarVar::PREP_FOR_NOTHING)
{
    return xarVar::fetch($name, $validation, $value, $defaultValue, $flags, $prep);
}

/**
 * Legacy call
 * @uses xarVar::validate()
 * @deprecated
 */
function xarVarValidate($validation, &$subject, $supress = false, $name = '')
{
    return xarVar::validate($validation, $subject, $supress, $name);
}


/*
 * Functions providing variable caching (within a single page request)
 *
 * Example :
 *
 * if (xarVarIsCached('MyCache', 'myvar')) {
 *     $var = xarVarGetCached('MyCache', 'myvar');
 * }
 * ...
 * xarVarSetCached('MyCache', 'myvar', 'this value');
 * ...
 * xarVarDelCached('MyCache', 'myvar');
 * ...
 * xarVarFlushCached('MyCache');
 * ...
 *
 */

/**@+
 * Wrapper functions for var caching as in Xaraya 1 API
 * See the documentation of protected xarCoreCache::*Cached for details
 *
 * @see xarCore
 */
/**
 * Legacy call
 * @uses xarVar::isCached()
 * @deprecated
 */
function xarVarIsCached($scope,  $name)         { return xarCoreCache::isCached($scope, $name);         }
/**
 * Legacy call
 * @uses xarVar::getCached()
 * @deprecated
 */
function xarVarGetCached($scope, $name)         { return xarCoreCache::getCached($scope, $name);        }
/**
 * Legacy call
 * @uses xarVar::setCached()
 * @deprecated
 */
function xarVarSetCached($scope, $name, $value) { return xarCoreCache::setCached($scope, $name, $value);}
/**
 * Legacy call
 * @uses xarVar::delCached()
 * @deprecated
 */
function xarVarDelCached($scope, $name)         { return xarCoreCache::delCached($scope, $name);        }
/**
 * Legacy call
 * @uses xarVar::flushCached()
 * @deprecated
 */
function xarVarFlushCached($scope)              { return xarCoreCache::flushCached($scope);             }

