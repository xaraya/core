<?php
/**
 * @package modules
 * @subpackage blocks module
 * @scenario soloblock
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/13.html
 */
/**
 * @author Chris Powis <crisp@xaraya.com>
 * @todo 
**/
function blocks_instancesapi_getstates(Array $args=array())
{
    return array(
        xarBlock::BLOCK_STATE_INACTIVE =>
            array('id' => xarBlock::BLOCK_STATE_INACTIVE, 'name' => xarML('Inactive')),
        xarBlock::BLOCK_STATE_HIDDEN =>
            array('id' => xarBlock::BLOCK_STATE_HIDDEN, 'name' => xarML('Hidden')),
        xarBlock::BLOCK_STATE_VISIBLE =>
            array('id' => xarBlock::BLOCK_STATE_VISIBLE, 'name' => xarML('Visible')),
    );
}
?>