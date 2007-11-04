<?php
/**
 * Legacy Functions
 *
 * @package lib
 * @subpackage legacy
 * @copyright (C) 2002-2007 The Digital Development Foundation
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

?>
