<?php
/**
 * File: $Id$
 *
 * Update configuration parameters of the module
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata module
 * @author mikespub <mikespub@xaraya.com>
*/
/**
 * This is a standard function to update the configuration parameters of the
 * module given the information passed back by the modification form
 */
function dynamicdata_admin_updateconfig( $args )
{
    extract( $args );

    $flushPropertyCache = xarVarCleanFromInput( 'flushPropertyCache' );

    // Security Check
    if (!xarSecurityCheck('AdminDynamicData')) return;

	// TODO: Check authid
	
	if ( isset($flushPropertyCache) && ($flushPropertyCache == true) )
	{
		$args['flush'] = 'true';
		$success = xarModAPIFunc('dynamicdata','admin','importpropertytypes', $args);
		
		if( $success )
		{
		    return 'Property Definitions Cache has been cleared and reloaded.';
		} else {
		    return 'Unknown error while clearing and reloading Property Definition Cache.';
		}
	}

    return 'insert update code for property types here ?';
}

?>