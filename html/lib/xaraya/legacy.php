<?php
/**
 * Legacy Functions
 *
 * @package lib
 * @subpackage legacy
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @author Marco Canini
 */

/**
 * Exceptions defined by this subsystem
 */
class ApiDeprecationException extends DeprecationExceptions
{
    protected $message = "You are trying to use a deprecated API function [#(1)], Replace this call with #(2)";
}

/***********************************************************************
* This file is for legacy functions needed to make it
* easier to use modules from Xaraya version 1.x in the version 2 series
*
* Please don't fill it with useless
* stuff except as wrappers, and also.. please
* do not duplicate constants that already exist in xaraya core
***********************************************************************/

/**********************************************************************
* WARNING: THIS FILE IS A WORK IN PROGRESS!!!!!!!!!!!!!!!!!!!
* Please mark all stuff that you need in this file or file a bug report
*
* Necessary functions to duplicate
* MODULE SYSTEM FUNCTIONS

* DEPRECATED XAR FUNCTIONS
* xarInclude                    -> use sys:import('dot.separated.path')
*/

/**
 * Add code directory to include_path for modules including other module files
 */
set_include_path(realpath(sys::code()) . PATH_SEPARATOR . get_include_path());

/**
 * Returns the relative path name for the var directory
 *
 * @access public
 * @return string the var directory path name
 * @deprec replaced by sys::varpath()
 * @see    sys
 **/
function xarCoreGetVarDirPath() { return sys::varpath(); }

/**
 * Wrapper functions to support Xaraya 1 API for systemvars
 *
 * @todo this was a protected function by mistake i think
 * @deprec replaced by xarSystemVars
 * @see    xarSystemVars
 **/
function xarCore_getSystemVar($name)
{
    sys::import('xaraya.variables.system');
    return xarSystemVars::get(null, $name);
}

/**
 * Get the database host
 *
 * @deprec
 * @see xarDB::getHost()
 */
function xarDBGetHost() { return xarDB::getHost(); }

/**
 * Get the database name
 *
 * @deprec
 * @see xarDB::getName();
 */
function xarDBGetName() { return xarDB::getName(); }

/*
 * Wrapper functions to support Xaraya 1 API for configvars
 * NOTE: the $prep in the signature has been dropped!!
 */
sys::import('xaraya.variables.config');
function xarConfigSetVar($name, $value) { return xarConfigVars::set(null, $name, $value); }
function xarConfigGetVar($name)         { return xarConfigVars::get(null, $name); }

sys::import('xaraya.variables.module');
sys::import('xaraya.variables.moduser');

/**
 * Wrapper functions to support Xaraya 1 API for modvars and moduservars
**/
function xarModGetVar($modName, $name, $prep = NULL) {   return xarModVars::get($modName, $name, $prep);  }
function xarModSetVar($modName, $name, $value)       {   return xarModVars::set($modName, $name, $value); }
function xarModDelVar($modName, $name)               {   return xarModVars::delete($modName, $name);      }
function xarModDelAllVars($modName)                  {   return xarModVars::delete_all($modName);         }

function xarModGetUserVar($modName, $name, $id = NULL, $prep = NULL){   return xarModUserVars::get($modName, $name, $id, $prep);  }
function xarModSetUserVar($modName, $name, $value, $id=NULL)        {   return xarModUserVars::set($modName, $name, $value, $id); }

// These functions no longer do anything
function xarMakePrivilegeRoot($privilege)        {   return true; }
function xarMakeRoleRoot($name) { return true; }

/**
 * Wrapper functions to support Xaraya 1 API Server functions
 *
**/
function xarServerGetVar($name) { return xarServer::getVar($name); }
function xarServerGetBaseURI()  { return xarServer::getBaseURI();  }
function xarServerGetHost()     { return xarServer::getHost();     }
function xarServerGetProtocol() { return xarServer::getProtocol(); }
function xarServerGetBaseURL()  { return xarServer::getBaseURL();  }
function xarServerGetCurrentURL($args = array(), $generateXMLURL = NULL, $target = NULL) { return xarServer::getCurrentURL($args, $generateXMLURL, $target); }
function xarRequestGetVar($name, $allowOnlyMethod = NULL) { return xarRequest::getVar($name, $allowOnlyMethod);}
function xarRequestGetInfo()                              { return xarRequest::getInfo();        }
function xarRequestIsLocalReferer()                       { return xarRequest::isLocalReferer(); }
function xarResponseRedirect($redirectURL)                { return xarResponse::Redirect($redirectURL); }


/**
 * Wrapper function to support Xaraya 1 API Database functions
 *
**/
function &xarDBGetConn($index = 0)   { return xarDB::getConn($index);}
function xarDBGetSystemTablePrefix() { return xarDB::getPrefix(); }
function xarDBGetSiteTablePrefix()   { return xarDBGetSystemTablePrefix(); }
function &xarDBGetTables()           { return xarDB::getTables();}
// Does this work?
function xarDBLoadTableMaintenanceAPI() { return sys::import('xaraya.tableddl'); }
function xarDBGetType()              { return xarDB::getType(); }
function &xarDBNewDataDict(Connection &$dbconn, $mode = 'READONLY') 
{
    throw new ApiDeprecationException(array('xarDBNewDataDict','[TO BE DETERMINED]'));
}

/**
 * Wrapper function to support Xaraya 1 Error functions
 *
**/
function xarCurrentErrorType()
{
    // Xaraya 2.x throws exceptions, use try { ... } catch (Exception $e) { ... }
    if (!defined('XAR_NO_EXCEPTION')) {
        define('XAR_NO_EXCEPTION', 0);
    }
    // pretend everything is OK for now
    return XAR_NO_EXCEPTION;
}

/**
 * Wrapper function to support Xaraya 1 Block functions
 *
**/
function xarBlock_init(&$args) { return xarBlock::init($args); }
function xarBlock_render($blockinfo) { return xarBlock::render($blockinfo); }
function xarBlock_renderBlock($args) { return xarBlock::renderBlock($args); }
function xarBlock_renderGroup($groupname, $template=NULL) { return xarBlock::renderGroup($groupname, $template); }

/**
 * Wrapper function to support Xaraya 1 Cache functions
 *
**/
function xarCache_init($args = false) { return xarCache::init($args); }
function xarCache_getStorage(array $args = array()) { return xarCache::getStorage($args); }

/**
 * Support Xaraya 1 pager functions
 *
**/
function xarTplPagerInfo($currentItem, $total, $itemsPerPage = 10, $blockOptions = 10)
{
    sys::import('modules.base.class.pager');
    return xarTplPager::getInfo($currentItem, $total, $itemsPerPage, $blockOptions);
}
function xarTplGetPager($startNum, $total, $urltemplate, $itemsPerPage = 10, $blockOptions = array(), $template = 'default', $tplmodule = 'base')
{
    sys::import('modules.base.class.pager');
    return xarTplPager::getPager($startNum, $total, $urltemplate, $itemsPerPage, $blockOptions, $template, $tplmodule);
}

/**
 * Map legacy Dynamic_Property base class to DataProperty
 * Note: this does not mean the property will actually work
 */
sys::import('modules.dynamicdata.class.properties.base');
class Dynamic_Property extends DataProperty {}

?>
