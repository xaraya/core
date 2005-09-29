<?php
/**
 * Get block information
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Blocks module
 */

/**
 * Get block information
 *
 * @author Jim McDonald, Paul Rosania
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