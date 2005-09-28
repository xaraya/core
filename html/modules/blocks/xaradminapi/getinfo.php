<?php
/** 
 * File: $Id$
 *
 * Get block information
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Blocks administration
 * @author Jim McDonald, Paul Rosania
*/
/**
 * Get block information
 *
 * @access public
 * @param see blocks/userapi/getinfo
 * @return see blocks/userapi/getinfo
 * @deprec Jan 2004
 * @raise DATABASE_ERROR, BAD_PARAM, ID_NOT_EXIST
 */
function blocks_adminapi_getinfo($args)
{
    return xarModAPIfunc('blocks', 'user', 'getinfo', $args);
}

?>