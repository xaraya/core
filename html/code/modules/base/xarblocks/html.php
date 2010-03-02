<?php
/**
 * HTML block
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 * @link http://xaraya.com/index.php/release/68.html
 */

/**
 * Block init - holds security.
 * @author Patrick Kellum
 */
    sys::import('xaraya.structures.containers.blocks.basicblock');

    class HtmlBlock extends BasicBlock implements iBlock
    {
        public $name                = 'HTMLBlock';
        public $module              = 'base';
        public $text_type           = 'HTML';
        public $text_type_long      = 'HTML';
        public $allow_multiple      = true;
        public $nocache             = 1;
        public $html_content        = '';

/**
 * Display func.
 * @param $data array containing title,content
 */
        function display(Array $data=array())
        {
            $data = parent::display($data);
            if (empty($data)) return;
            if (empty($data['content']['html_content'])) $data['content']['html_content'] = $this->html_content;
            return $data;
        }
    }

?>