<?php
/**
 * Get block information
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Blocks module
 * @link http://xaraya.com/index.php/release/13.html
 */

/**
 * Get block information
 *
 * @author Jim McDonald, Paul Rosania
 * @access public
 * @param see blocks/userapi/getinfo
 * @return see blocks/userapi/getinfo
 * @deprec Jan 2004
 * @throws DATABASE_ERROR, BAD_PARAM, ID_NOT_EXIST
 */
function blocks_adminapi_getinfo($args)
{
    return xarModAPIfunc('blocks', 'user', 'getinfo', $args);
}

?>