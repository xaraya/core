<?php
/** 
 * File: $Id$
 *
 * Get block group information
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
 * Get block group information
 *
 * @access public
 * @param integer blockGroupId the block group id
 * @return array lock information
 * @raise DATABASE_ERROR, BAD_PARAM, ID_NOT_EXIST
 * @deprec 31-JAN-04 - moved to user API
 */
function blocks_adminapi_groupgetinfo($args)
{
    extract($args);

    if ($blockGroupId < 1) {
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', 'blockGroupId');
        return;
    }

    return xarModAPIFunc(
        'blocks', 'user', 'groupgetinfo',
        array('gid' => $blockGroupId)
    );
   
}

?>