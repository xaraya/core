<?php
/**
 * HTML block
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
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
        public $no_cache            = true;
        public $show_preview        = true;

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

            if (isset($data['expire']) && $now > $data['expire']){
                if ($data['expire'] != 0) return;
            }
            if (empty($data['content']['html_content'])) $data['content']['html_content'] = $this->html_content;
            return $data;
        }

/**
 * Modify Function to the Blocks Admin
 * @param $data array containing title,content
 */
        public function modify(Array $data=array())
        {
            $data = parent::modify($data);

            // Defaults
            if (empty($data['expire'])) $data['expire'] = $this->expire;
            if (empty($data['html_content'])) $data['html_content'] = $this->html_content;

            $now = time();
            if ($data['expire'] == 0){
                $data['expirein'] = 0;
            } else {
                $soon = $data['expire'] - $now ;
                $sooner = $soon / 3600;
                $data['expirein'] =  round($sooner);
            }

            return $data;
        }

/**
 * Updates the Block config from the Blocks Admin
 * @param $data array containing title,content
 */
        public function update(Array $data=array())
        {
            $data = parent::update($data);
            if (!xarVarFetch('expire', 'str:1', $args['expire'], 0, XARVAR_NOT_REQUIRED)) {return;}
            if (!xarVarFetch('html_content', 'str:1', $args['html_content'], '', XARVAR_NOT_REQUIRED)) {return;}

            // Defaults
            if ($args['expire'] != 0) {
                $now = time();
                $args['expire'] = $args['expire'] + $now;
            }

            $data['content'] = $args;
            return $data;
        }
    }

?>