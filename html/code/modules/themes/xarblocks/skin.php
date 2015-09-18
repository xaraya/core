<?php
/**
 * Skin Block
 *
 * @package modules
 * @subpackage themes module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.com/index.php/release/70.html
 */

/*
 * Initialise block info
 *
 * Skin Selection via block
 * @author Marco Canini
 * initialise block
 */
sys::import('xaraya.structures.containers.blocks.basicblock');

class Themes_SkinBlock extends BasicBlock implements iBlock
{
    protected $type                = 'skin';
    protected $module              = 'themes';
    protected $text_type           = 'Theme Switcher';
    protected $text_type_long      = 'User Theme Switcher Selection';

}
?>