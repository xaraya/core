<?php
/**
 * Form Block
 *
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * Initialise block info
 */
sys::import('xaraya.structures.containers.blocks.basicblock');

class Dynamicdata_FormBlock extends BasicBlock implements iBlock
{
    protected $type                = 'form';
    protected $module              = 'dynamicdata';
    protected $text_type           = 'Form';
    protected $text_type_long      = 'Show dynamic data form';
    protected $allow_multiple      = true;
    protected $show_preview        = true;

    public $objectid            = 0;
}
?>
