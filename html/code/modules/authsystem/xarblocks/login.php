<?php
/**
 * Login Block user interface
 *
 * @package modules
 * @subpackage authsystem module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/42.html
 */

/**
 * Login via a block: initialise block
 *
 * @author Jim McDonald
 * @return array
 */
sys::import('xaraya.structures.containers.blocks.basicblock');

class Authsystem_LoginBlock extends BasicBlock implements iBlock
{
    protected $type                = 'login';
    protected $module              = 'authsystem';
    protected $text_type           = 'Login';
    protected $text_type_long      = 'User Login';

    public $showlogout          = 0;
    public $logouttitle         = '';

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
