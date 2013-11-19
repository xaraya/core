<?php
/**
 * Login Block
 *
 * @package modules\authsystem
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/42.html
 */

/**
 * Initialise block info
 *
 * @author Jim McDonald
 * @return array
 */
sys::import('xaraya.structures.containers.blocks.basicblock');

/**
 * Authsystem Login Block
 *
 * @author Jim McDonald
 */
class Authsystem_LoginBlock extends BasicBlock implements iBlock
{
    protected $type                = 'login';
    protected $module              = 'authsystem';
    protected $text_type           = 'Login';
    protected $text_type_long      = 'User Login';

    public $showlogout          = 0;
    public $logouttitle         = '';
    
}
?>