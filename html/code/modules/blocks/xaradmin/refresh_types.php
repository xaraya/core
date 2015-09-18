<?php
/**
 * @package modules\blocks
 * @scenario soloblock
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.com/index.php/release/13.html
 */

/**
 * 
 * 
 * @author Chris Powis <crisp@xaraya.com>
 * @param void N/A
 * @return type Returns data display array
 */
function blocks_admin_refresh_types(Array $args=array())
{
    if (!xarSecurityCheck('AdminBlocks')) return;

    $data = array();
    
    $old_db = xarMod::apiFunc('blocks', 'types', 'getitems');
    
    if (!xarMod::apiFunc('blocks', 'types', 'refresh')) return;
    
    $new_db = xarMod::apiFunc('blocks', 'types', 'getitems');
    
    $unchanged = array();
    
    $new = array();
    $missing = array();
    $error = array();
    $unavailable = array();
    $activated = array();
    
    foreach ($new_db as $type_id => $type) {
        if (isset($old_db[$type_id]) && $type['type_state'] == $old_db[$type_id]['type_state']) {
            $unchanged[$type_id] = $type;
        } else {
            if (!isset($old_db[$type_id])) {
                $new[$type_id] = $type;
            }
            switch ($type['type_state']) {
                case xarBlock::TYPE_STATE_ACTIVE:
                    $activated[$type_id] = $type;
                break;
                case xarBlock::TYPE_STATE_ERROR:
                    $error[$type_id] = $type;
                break;
                case xarBlock::TYPE_STATE_MISSING:
                    $missing[$type_id] = $type;
                break;
                case xarBlock::TYPE_STATE_MOD_UNAVAILABLE:
                    $unavailable[$type_id] = $type;
                break;
            }
        }
    }
    
    $data['unchanged'] = $unchanged;
    $data['new'] = $new;
    $data['missing'] = $missing;
    $data['error'] = $error;
    $data['unavailable'] = $unavailable;
    $data['activated'] = $activated;
    $data['type_states'] = xarMod::apiFunc('blocks', 'types', 'getstates');
    return $data;
}
?>