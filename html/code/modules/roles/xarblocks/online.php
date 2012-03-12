<?php
/**
 * Online Block
 *
 * @package modules
 * @subpackage roles module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/27.html
 */

/**
 * Online Block
 * @author Jim McDonald
 * @author Greg Allan
 * @author John Cox
 * @author Michael Makushev
 * @author Marc Lutolf
 */
sys::import('xaraya.structures.containers.blocks.basicblock');
class Roles_OnlineBlock extends BasicBlock
{
    protected $type                = 'online';
    protected $module              = 'roles';
    protected $text_type           = 'Online';
    protected $text_type_long      = 'Display who is online';
    protected $show_preview        = true;
        
    public $showusers = true;
    public $showusertotal = false;
    public $showanontotal = false;
    public $showlastuser = false;

}
?>