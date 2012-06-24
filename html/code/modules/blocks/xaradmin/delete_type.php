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
function blocks_admin_delete_type(Array $args = array())
{
    if (!xarSecurityCheck('AdminBlocks')) return;

    if (!xarVarFetch('type_id', 'int:1:',
        $type_id, null, XARVAR_DONT_SET)) return;

        
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
        throw new IdNotFoundException($vars, $msg);
    }

    $data = array();
    
    if ($type['type_state'] == xarBlock::TYPE_STATE_MISSING ||
        $type['type_state'] == xarBlock::TYPE_STATE_MOD_UNAVAILABLE) {
        
        if (!xarVarFetch('confirm', 'checkbox',
            $confirmed, false, XARVAR_NOT_REQUIRED)) return;
        
        if ($confirmed) {
            if (!xarSecConfirmAuthKey())
                return xarTpl::module('privileges', 'user', 'errors', array('layout' => 'bad_author'));
            if (!xarMod::apiFunc('blocks', 'types', 'deleteitem', 
                array('type_id' => $type_id))) return;
            if (!xarVarFetch('return_url', 'pre:trim:str:1:',
                $return_url, '', XARVAR_NOT_REQUIRED)) return;
            if (empty($return_url))
                $return_url = xarModURL('blocks', 'admin', 'view_types');
            xarController::redirect($return_url);                
        }
        
    }

    $data['type'] = $type;
    $data['type_states'] = xarMod::apiFunc('blocks', 'types', 'getstates');
    $data['type_instances'] = xarMod::apiFunc('blocks', 'instances', 'getitems',
        array('type' => $type['type'], 'module' => $type['module']));
   
    return $data;
}
?>