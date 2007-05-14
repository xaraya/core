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
function xarCoreGetVarDirPath()
{
    return sys::varpath();
}

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
 * Create a data dictionary object
 *
 * @deprec does not work with creole, will be replaced by schema.xml
 */
function &xarDBNewDataDict(Connection &$dbconn, $mode = 'READONLY')
{
    throw new ApiDeprecationException(array('xarDBNewDataDict',''));
}

/**
 * Load the Table Maintenance API
 *
 * @deprec no need for this anymore
 */
function xarDBLoadTableMaintenanceAPI()
{
    return sys::import('xaraya.xarTableDDL');
}

/**
 * Get the database host
 *
 * @deprec
 */
function xarDBGetHost()
{
    return xarDB::getHost();
}

/**
 * Get the database type
 *
 * @deprec
 */
function xarDBGetType()
{
    return xarDB::getType();
}

/**
 * Get the database name
 *
 * @deprec
 */
function xarDBGetName()
{
    return xarDB::getName();
}
?>
