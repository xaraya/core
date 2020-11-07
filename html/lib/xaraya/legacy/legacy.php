<?php
/**
 * Exception raised by the legacy subsystem
 *
 * @package core\legacy
**/
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
 * Legacy Functions
 *
 * @package core\legacy
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author Marco Canini
 */

/**
 * Returns the relative path name for the var directory
 *
 * @package core\legacy
 * @uses sys::varpath()
 * @return string the var directory path name
 * @deprecated replaced by sys::varpath()
 **/
function xarCoreGetVarDirPath() { return sys::varpath(); }

/**
 * Wrapper functions to support Xaraya 1 API for systemvars *
 **/
/**
 * @package core\legacy
 * @uses   xarSystemVars
 * @deprecated replaced by xarSystemVars
 */
function xarCore_getSystemVar($name)
{
    sys::import('xaraya.variables.system');
    return xarSystemVars::get(null, $name);
}

/**
 * Get the database host
 *
 * @package core\legacy
 * @uses xarDB::getHost()
 * @deprecated
 */
function xarDBGetHost() { return xarDB::getHost(); }

/**
 * Get the database name
 *
 * @package core\legacy
 * @uses xarDB::getName()
 * @deprecated
 */
function xarDBGetName() { return xarDB::getName(); }

/*
 * Wrapper functions to support Xaraya 1 API for configvars
 * NOTE: the $prep in the signature has been dropped!!
 */
sys::import('xaraya.variables.config');

/**
 * @package core\legacy
 * @uses xarConfigVars
 * @deprecated
 */
function xarConfigSetVar($name, $value) { return xarConfigVars::set(null, $name, $value); }
/**
 * @package core\legacy
 * @uses xarConfigVars
 * @deprecated
 */
function xarConfigGetVar($name)         { return xarConfigVars::get(null, $name); }

sys::import('xaraya.variables.module');
sys::import('xaraya.variables.moduser');

/**
 * Wrapper functions to support Xaraya 1 API for modvars and moduservars
**/
/**
 * @package core\legacy
 * @uses xarModVars::get()
 * @deprecated
 */
function xarModGetVar($modName, $name, $prep = NULL) {   return xarModVars::get($modName, $name, $prep);  }

/**
 * @package core\legacy
 * @uses xarModVars::set()
 * @deprecated
 */
function xarModSetVar($modName, $name, $value)       {   return xarModVars::set($modName, $name, $value); }

/**
 * @package core\legacy
 * @uses xarModVars::delete()
 * @deprecated
 */
function xarModDelVar($modName, $name)               {   return xarModVars::delete($modName, $name);      }

/**
 * @package core\legacy
 * @uses xarModVars::delete_all()
 * @deprecated
 */
function xarModDelAllVars($modName)                  {   return xarModVars::delete_all($modName);         }

/**
 * @package core\legacy
 * @uses xarModUserVars::get()
 * @deprecated
 */
function xarModGetUserVar($modName, $name, $id = NULL, $prep = NULL){   return xarModUserVars::get($modName, $name, $id, $prep);  }

/**
 * @package core\legacy
 * @uses xarModUserVars::set()
 * @deprecated
 */
function xarModSetUserVar($modName, $name, $value, $id=NULL)        {   return xarModUserVars::set($modName, $name, $value, $id); }

// These functions no longer do anything
/**
 * @package core\legacy
 * @deprecated
 */
function xarMakePrivilegeRoot($privilege)        {   return true; }

/**
 * @package core\legacy
 * @deprecated
 */
function xarMakeRoleRoot($name) { return true; }

/**
 * Wrapper functions to support Xaraya 1 API Server functions
 *
**/
/**
 * @package core\legacy
 * @uses xarServer::getVar()
 * @deprecated
 */
function xarServerGetVar($name) { return xarServer::getVar($name); }

/**
 * @package core\legacy
 * @uses xarServer::getBaseURI()
 * @deprecated
 */
function xarServerGetBaseURI()  { return xarServer::getBaseURI();  }

/**
 * @package core\legacy
 * @uses xarServer::getHost()
 * @deprecated
 */
function xarServerGetHost()     { return xarServer::getHost();     }

/**
 * @package core\legacy
 * @uses xarServer::getProtocol()
 * @deprecated
 */
function xarServerGetProtocol() { return xarServer::getProtocol(); }

/**
 * @package core\legacy
 * @uses xarServer::getBaseURL()
 * @deprecated
 */
function xarServerGetBaseURL()  { return xarServer::getBaseURL();  }

/**
 * @package core\legacy
 * @uses xarServer::getCurrentURL()
 * @deprecated
 */
function xarServerGetCurrentURL($args = array(), $generateXMLURL = NULL, $target = NULL) { return xarServer::getCurrentURL($args, $generateXMLURL, $target); }

/**
 * @package core\legacy
 * @uses xarController::getVar()
 * @deprecated
 */
function xarRequestGetVar($name, $allowOnlyMethod = NULL) { return xarController::getVar($name, $allowOnlyMethod);}

/**
 * @package core\legacy
 * @uses xarController::getRequest()->getInfo()
 * @deprecated
 */
function xarRequestGetInfo()                              { return xarController::getRequest()->getInfo(); }

/**
 * @package core\legacy
 * @uses xarController::isLocalReferer()
 * @deprecated
 */
function xarRequestIsLocalReferer()                       { return xarController::isLocalReferer(); }

/**
 * @package core\legacy
 * @uses xarController::redirect()
 * @deprecated
 */
function xarResponseRedirect($redirectURL)                { return xarController::redirect($redirectURL); }
//function xarRequest::getVar($name, $allowOnlyMethod)      { return xarController::getVar($name, $allowOnlyMethod);}
//function xarRequest::getInfo()                            { return xarController::$request->getInfo(); }
//function xarRequest::isLocalReferer()                     { return xarController::isLocalReferer(); }

/**
 * Wrapper functions to support Xaraya 1 API Module functions
 *
 * TODO: see lib/xaraya/modules.php for more functions
 *
**/
//function xarModURL($modName=NULL, $modType='user', $funcName='main', $args=array(), $generateXMLURL=NULL, $fragment=NULL, $entrypoint=array())
//{   
//    return xarController::URL($modName, $modType, $funcName, $args, $generateXMLURL, $fragment, $entrypoint); 
//}

/**
 * Wrapper functions to support Xaraya 1 API Database functions
 *
**/
/**
 * @package core\legacy
 * @uses xarDB::getConn()
 * @deprecated
 */
function &xarDBGetConn($index = 0)   { return xarDB::getConn($index);}
/**
 * @package core\legacy
 * @uses xarDB::getPrefix()
 * @deprecated
 */
function xarDBGetSystemTablePrefix() { return xarDB::getPrefix(); }

/**
 * @package core\legacy
 * @uses xarDB::getPrefix()
 * @deprecated
 */
function xarDBGetSiteTablePrefix()   { return xarDB::getPrefix(); }

/**
 * @package core\legacy
 * @uses xarDB::getTables()
 * @deprecated
 */
function &xarDBGetTables()           { return xarDB::getTables();}

// Does this work?
/**
 * @package core\legacy
 * @uses lib/xaraya/tableddl.php
 * @deprecated
 */
function xarDBLoadTableMaintenanceAPI() { return sys::import('xaraya.tableddl'); }

/**
 * @package core\legacy
 * @uses xarDB::getType()
 * @deprecated
 */
function xarDBGetType()              { return xarDB::getType(); }

/**
 * @package core\legacy
 * @uses lib/xaraya/tableddl.php
 * @deprecated
 */
function &xarDBNewDataDict(Connection &$dbconn, $mode = 'READONLY') 
{
    throw new ApiDeprecationException(array('xarDBNewDataDict','[TO BE DETERMINED]'));
}

/**
 * Wrapper function to support Xaraya 1 Block functions
 *
**/
/**
 * @package core\legacy
 * @uses xarBlock::init()
 * @deprecated
 */
function xarBlock_init(&$args) { return xarBlock::init($args); }

/**
 * @package core\legacy
 * @uses xarBlock::render()
 * @deprecated
 */
function xarBlock_render($blockinfo) { return xarBlock::render($blockinfo); }

/**
 * @package core\legacy
 * @uses xarBlock::renderBlock()
 * @deprecated
 */
function xarBlock_renderBlock($args) { return xarBlock::renderBlock($args); }

/**
 * @package core\legacy
 * @uses xarBlock::renderGroup()
 * @deprecated
 */
function xarBlock_renderGroup($groupname, $template=NULL) { return xarBlock::renderGroup($groupname, $template); }

/**
 * Wrapper function to support Xaraya 1 Cache functions
 *
**/
/**
 * @package core\legacy
 * @uses xarCache::init()
 * @deprecated
 */
function xarCache_init($args = false) { return xarCache::init($args); }

/**
 * @package core\legacy
 * @uses xarCache::getStorage()
 * @deprecated
 */
function xarCache_getStorage(array $args = array()) { return xarCache::getStorage($args); }

/**
 * Support Xaraya 1 pager functions
 *
**/
/**
 * @package core\legacy
 * @uses xarTplPager::getInfo()
 * @deprecated
 */
function xarTplPagerInfo($currentItem, $total, $itemsPerPage = 10, $blockOptions = 10)
{
    sys::import('modules.base.class.pager');
    return xarTplPager::getInfo($currentItem, $total, $itemsPerPage, $blockOptions);
}

/**
 * @package core\legacy
 * @uses xarTplPager::getPager()
 * @deprecated
 */
function xarTplGetPager($startNum, $total, $urltemplate, $itemsPerPage = 10, $blockOptions = array(), $template = 'default', $tplmodule = 'base')
{
    sys::import('modules.base.class.pager');
    return xarTplPager::getPager($startNum, $total, $urltemplate, $itemsPerPage, $blockOptions, $template, $tplmodule);
}

sys::import('modules.dynamicdata.class.properties.base');
/**
 * Map legacy Dynamic_Property base class to DataProperty
 * Note: this does not mean the property will actually work
 *
 * @package core\legacy
 * @uses DataProperty
 * @deprecated
 */
class Dynamic_Property extends DataProperty 
{
    function __construct($args)
    {
        parent::__construct($args);
    }
}

