<?php
/**
 * @package modules\blocks
 * @subpackage blocks
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.info/index.php/release/13.html
 */

/**
 * Returns blocks state array
 * 
 * @author Chris Powis <crisp@xaraya.com>
 * 
 * @param array $args
 * @return array Block state array
 */
function blocks_typesapi_getstates(Array $args=array())
{

        return array(
            xarBlock::TYPE_STATE_ACTIVE => 
                array('id' => xarBlock::TYPE_STATE_ACTIVE, 'name' => xarML('Active')),
            xarBlock::TYPE_STATE_MISSING => 
                array('id' => xarBlock::TYPE_STATE_MISSING, 'name' => xarML('Missing')),
            xarBlock::TYPE_STATE_ERROR => 
                array('id' => xarBlock::TYPE_STATE_ERROR, 'name' => xarML('Error')),
            xarBlock::TYPE_STATE_MOD_UNAVAILABLE =>
                array('id' => xarBlock::TYPE_STATE_MOD_UNAVAILABLE, 'name' => xarML('Unavailable')),
        );
        
}
?>