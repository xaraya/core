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
 * Function to delete type
 * 
 * @author Chris Powis <crisp@xaraya.com>
 * 
 * @param array $args Set of optional parameters
 * @return array Returns data array
 * @throws EmptyParameterException
 * @throws IDNotFoundException
 */
function blocks_admin_delete_type(Array $args = array())
{
    if (!xarSecurity::check('AdminBlocks')) return;

    if (!xarVar::fetch('type_id', 'int:1:',
        $type_id, null, xarVar::DONT_SET)) return;

        
    if (!isset($type_id)) {
        $msg = 'Missing #(1) for #(2) module #(3) function #(4)()';
        $vars = array('type_id', 'blocks', 'admin', 'delete_type');
        throw new EmptyParameterException($vars, $msg);
    }
    
    $type = xarMod::apiFunc('blocks', 'types', 'getitem',
        array('type_id' => $type_id));
    
    if (!$type) {
        $msg = 'Block type id "#(1)" does not exist';
        $vars = array($type_id);
        throw new IDNotFoundException($vars, $msg);
    }

    $data = array();
    
    if ($type['type_state'] == xarBlock::TYPE_STATE_MISSING ||
        $type['type_state'] == xarBlock::TYPE_STATE_MOD_UNAVAILABLE) {
        
        if (!xarVar::fetch('confirm', 'checkbox',
            $confirmed, false, xarVar::NOT_REQUIRED)) return;
        
        if ($confirmed) {
            if (!xarSec::confirmAuthKey())
                return xarTpl::module('privileges', 'user', 'errors', array('layout' => 'bad_author'));
            if (!xarMod::apiFunc('blocks', 'types', 'deleteitem', 
                array('type_id' => $type_id))) return;
            if (!xarVar::fetch('return_url', 'pre:trim:str:1:',
                $return_url, '', xarVar::NOT_REQUIRED)) return;
            if (empty($return_url))
                $return_url = xarController::URL('blocks', 'admin', 'view_types');
            xarController::redirect($return_url);                
        }
        
    }

    $data['type'] = $type;
    $data['type_states'] = xarMod::apiFunc('blocks', 'types', 'getstates');
    $data['type_instances'] = xarMod::apiFunc('blocks', 'instances', 'getitems',
        array('type' => $type['type'], 'module' => $type['module']));
   
    return $data;
}
