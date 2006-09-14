<?php
/**
 * Configuration Unit
 * 
 * @package config
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @author Marco Canini
*/

/**
 * Exceptions for this subsystem
 *
 * @todo this exception is too weak
 */
class ConfigurationException extends ConfigurationExceptions
{ 
    protected $message = 'There is an unknown configuration error detected.';
}

/**
 * Initialize config system
 *
 * @author  Marco Canini
 * @access public
 * @param array args
 * @param integer whatElseIsGoingLoaded
 * @return bool
*/
function xarConfig_init(&$args, $whatElseIsGoingLoaded)
{
    // Configuration Unit Tables
    $sitePrefix = xarDBGetSiteTablePrefix();

    // TODO: revisit nameing, this was minimal change when migrating
    $tables = array('config_vars' => $sitePrefix . '_module_vars');

    xarDB::importTables($tables);
    return true;
}

sys::import('variables.config');
/**
 * Wrapper functions to support Xaraya 1 API for modvars
 * NOTE: the $prep in the signature has been dropped!!
 */
function xarConfigSetVar($name, $value)
{   return xarConfigVars::set(null, $name, $value); }
function xarConfigGetVar($name)
{   return xarConfigVars::get(null, $name); }

?>
