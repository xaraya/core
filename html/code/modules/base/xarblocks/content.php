<?php
/**
 * Content Block
 *
 * @package modules
 * @subpackage base module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/68.html
 */

/**
 * Initialise block info
 *
 * Displays a Text/HTML/PHP Block
 *
 * @author Jason Judge
 */
sys::import('xaraya.structures.containers.blocks.basicblock');
class Base_ContentBlock extends BasicBlock implements iBlock
{
    protected $type                = 'content';
    protected $module              = 'base';
    protected $text_type           = 'Content';
    protected $text_type_long      = 'Generic Content Block';
    protected $show_preview        = true;

    public $html_content        = '';
    public $content_text        = '';
    public $content_type        = 'text';
    public $hide_empty          = true;
    public $custom_format       = '';
    public $hide_errors         = true;
    public $start_date          = '';
    public $end_date            = '';

    public $func_update         = 'base_contentblock_update';
    public $notes               = "content_type can be 'text', 'html', 'php' or 'data'";

}
?>