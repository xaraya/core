<?php
/**
 * Login via a block.
 *
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Authsystem module
 * @link http://xaraya.com/index.php/release/42.html
 */

/**
 * Login via a block: initialise block
 *
 * @author Jim McDonald
 * @return array
 */
sys::import('xaraya.structures.containers.blocks.basicblock');

class LoginBlock extends BasicBlock implements iBlock
{
    public $no_cache            = 1;

    public $name                = 'LoginBlock';
    public $module              = 'authsystem';
    public $text_type           = 'Login';
    public $text_type_long      = 'User Login';
    public $pageshared          = 1;

    public $showlogout          = 0;
    public $logouttitle         = '';

/**
 * Display func.
 * @param $data array containing title,content
 */
    function display(Array $data=array())
    {
        $data = parent::display($data);
        if (empty($data)) return;

        $vars = $data;
        if (!isset($vars['showlogout'])) $vars['showlogout'] = $this->showlogout;
        if (!isset($vars['logouttitle'])) $vars['logouttitle'] = $this->logouttitle;

        // Display logout block if user is already logged in
        // e.g. when the login/logout block also contains a search box
        if (xarUserIsLoggedIn()) {
            if (!empty($vars['showlogout'])) {
                $args['name'] = xarUserGetVar('name');

                // Since we are logged in, set the template base to 'logout'.
                // FIXME: not allowed to set BL variables directly
                $data['_bl_template_base'] = 'logout';

                if (!empty($vars['logouttitle'])) {
                    $data['title'] = $vars['logouttitle'];
                }
            } else {
                return;
            }
        } elseif (xarServer::getVar('REQUEST_METHOD') == 'GET') {
            // URL of this page
            $args['return_url'] = xarServer::getCurrentURL();
        } else {
            // Base URL of the site
            $args['return_url'] = xarServer::getBaseURL();
        }

        // Used in the templates.
        $args['blockid'] = $data['bid'];

        $data['content'] = $args;
        return $data;
    }

/**
 * Modify Function to the Blocks Admin
 * @param $data array containing title,content
 */
    public function modify(Array $data=array())
    {
        $data = parent::modify($data);

        if (!isset($data['showlogout'])) $data['showlogout'] = $this->showlogout;
        if (!isset($data['logouttitle'])) $data['logouttitle'] = $this->logouttitle;

        $data['blockid'] = $data['bid'];
        return $data;

    }

/**
 * Updates the Block config from the Blocks Admin
 * @param $data array containing title,content
 */
    public function update(Array $data=array())
    {
        $data = parent::update($data);
        if (!xarVarFetch('showlogout', 'checkbox', $vars['showlogout'], $this->showlogout, XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('logouttitle', 'str', $vars['logouttitle'], $this->logouttitle, XARVAR_NOT_REQUIRED)) return;

        $data['content'] = $vars;

        return $data;
    }
}
?>