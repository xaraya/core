<?php
/**
 * Login Block display interface
 *
 * @package modules
 * @subpackage authsystem module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/42.html
 */

/**
 * Display block
 *
 * @author Jim McDonald
 * @return array
 */
sys::import('modules.authsystem.xarblocks.login');
class Authsystem_LoginBlockDisplay extends Authsystem_LoginBlock implements iBlock
{
/**
 * Display func.
 * @param $data array containing title,content
 */
    function display()
    {
        $data = $this->getContent();
        if (xarUserIsLoggedIn()) {    
            if (!empty($this->showlogout)) {
                $data['name'] = xarUserGetVar('name');
                $this->setTemplateBase('logout');
                if (!empty($this->logouttitle))
                    $this->setTitle($this->logouttitle);
            } else {
                return;
            }
        } elseif (xarServer::getVar('REQUEST_METHOD') == 'GET') {
            xarVarFetch('redirecturl',   'pre:trim:str:1:', 
                $data['return_url']   , xarServer::getCurrentURL(array(),false), XARVAR_NOT_REQUIRED);
        } else {
            xarVarFetch('redirecturl',   'pre:trim:str:1', 
                $data['return_url']   , xarServer::getBaseURL(), XARVAR_NOT_REQUIRED);
        }
        return $data;
    }
}
?>