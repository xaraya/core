<?php
/**
 * Login Block display interface
 *
 * @package modules\authsystem
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/42.html
 */

/**
 * Display block
 *
 * @author Jim McDonald
 */
sys::import('modules.authsystem.xarblocks.login');

/**
 * Authsystem Login Block Display
 * 
 * @author Jim McDonald
 */
class Authsystem_LoginBlockDisplay extends Authsystem_LoginBlock implements iBlock
{

    /**
     * Method to display the login
     * 
     * @return array<mixed>|void Returns display data array.
     */
    function display()
    {
        $data = $this->getContent();
        if (xarUser::isLoggedIn()) {    
            if (!empty($this->showlogout)) {
                $data['name'] = xarUser::getVar('name');
                $this->setTemplateBase('logout');
                if (!empty($this->logouttitle))
                    $this->setTitle($this->logouttitle);
            } else {
                return;
            }
        } elseif (xarServer::getVar('REQUEST_METHOD') == 'GET') {
            $this->var()->find('redirecturl',
               $data['return_url'],
               'pre:trim:str:1:',
               $this->ctl()->getCurrentURL([], false));
        } else {
            $this->var()->find('redirecturl', 
                $data['return_url'],
                'pre:trim:str:1',
                xarServer::getBaseURL());
        }
        return $data;
    }
}
