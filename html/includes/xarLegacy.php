<?php
/**
 * Legacy Functions
 *
 * @package legacy
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @author Marco Canini
*/

/**
 * Exceptions defined by this subsystem
 *
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
* xarModEmailURL                -> no direct equivalent
* xarVarPrepForStore()          -> use bind vars or dbconn->qstr() method
* xarPage_sessionLess()         -> xarPageCache_sessionLess()
* xarPage_httpCacheHeaders()    -> xarPageCache_sendHeaders()
* xarVarCleanUntrused           -> use xarVarFetch validations
* xarVarCleanFromInput          -> use xarVarFetch validations
* xarTplAddStyleLink            -> use xar:style tag
* xarTplAddJavaScriptCode       -> use xar:base-include-javascript
* xarInclude                    -> use sys:import('dot.separated.path.below.includes') 
*/
?>
