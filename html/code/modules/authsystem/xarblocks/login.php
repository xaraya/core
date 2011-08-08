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
    function display(Array $args=array())
    {
        $data = parent::display($args);
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
            xarVarFetch('redirecturl',   'isset', $args['return_url']   , xarServer::getCurrentURL(array(),false), XARVAR_NOT_REQUIRED);
        } else {
            // Base URL of the site
            xarVarFetch('redirecturl',   'isset', $args['return_url']   , xarServer::getBaseURL(), XARVAR_NOT_REQUIRED);
        }

        $data['content'] = $args;
        return $data;
    }
}
?>
