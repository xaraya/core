<?php
/**
 * Get Group information
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Blocks module
 */
/**
 * Get block group information
 *
 * @author Jim McDonald, Paul Rosania
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
