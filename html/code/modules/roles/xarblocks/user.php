<?php
/**
 * User Info via block
 *
 * @package modules\roles
 * @subpackage roles
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/27.html
 */

/*
 * User Info via block
 * @author Marco Canini
 */

sys::import('xaraya.structures.containers.blocks.basicblock');

class Roles_UserBlock extends BasicBlock
{
    protected $type                = 'user';
    protected $module              = 'roles';
    protected $text_type           = 'User';
    protected $text_type_long      = 'User\'s Custom Box';
    protected $show_preview        = true;

	/**
     * Display the user info via block
     * 
     * @param array<string, mixed> $data Data array
     * @return array<mixed>|void Display data array or null if nothing is to display.
     */
    function display(Array $data=array())
    {
        if (!xarUser::isLoggedIn()) return;
        $data['name'] = xarUser::getVar('name');
        return $data;
    }
}
