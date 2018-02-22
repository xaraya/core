<?php
/**
 * Finclude Block
 *
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/68.html
 */
/**
 * Initialise block info
 *
 * @author Patrick Kellum
 */
sys::import('xaraya.structures.containers.blocks.basicblock');
class Base_FincludeBlock extends BasicBlock implements iBlock
{

    protected $type                = 'finclude';
    protected $module              = 'base';
    protected $text_type           = 'finclude';
    protected $text_type_long      = 'Simple File Include';
    protected $show_preview        = true;

    public $url                 = 'http://www.xaraya.com/';

}
?>