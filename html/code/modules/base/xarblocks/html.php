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

    class HTMLBlock extends BasicBlock implements iBlock
    {
        public $name                = 'HTMLBlock';
        public $module              = 'base';
        public $text_type           = 'HTML';
        public $text_type_long      = 'HTML';
        public $allow_multiple      = true;
        public $show_preview        = true;

        public $nocache             = 1;
        public $expire              = 0;

/**
 * Display func.
 * @param $data array containing title,content
 */
        function display(Array $data=array())
        {
            $data = parent::display($data);
            if (empty($data)) return;
            $now = time();

            if (isset($data['expire']) && $now > $data['expire']) {
                if ($data['expire'] != 0) return;
            }
            if (empty($data['content']['html_content'])) $data['content']['html_content'] = $this->html_content;
            return $data;
        }
    }

?>