<?php
/**
 * User Info via block
 *
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage roles module
 * @link http://xaraya.com/index.php/release/27.html
 */

/*
 * User Info via block
 * @author Marco Canini
 */

sys::import('xaraya.structures.containers.blocks.basicblock');

class Roles_UserBlock extends BasicBlock
{
    public $name                = 'UserBlock';
    public $module              = 'roles';
    public $text_type           = 'User';
    public $text_type_long      = 'User\'s Custom Box';
    public $show_preview        = true;

    public $nocache             = 1;
    public $usershared          = 0;

    function display(Array $data=array())
    {
        $data = parent::display($data);
        if (empty($data)) return;
        if (xarUserIsLoggedIn()) return $data;
    }
}
?>