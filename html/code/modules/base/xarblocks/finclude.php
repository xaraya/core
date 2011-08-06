<?php
/**
 * Finclude block
 * @package modules
 * @subpackage base module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/68.html
 */
/**
 * Block init - holds security.
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

/**
 * Display func.
 * @param $blockinfo array containing title,content
 */
        function display(Array $data=array())
        {
            $data = parent::display($data);
            if (empty($data)) return;
            if (empty($data['content']['url'])){
                $data['content']['url'] = xarML('Block has no file defined to include');
            } else {
                if (!file_exists($data['content']['url'])) {
                    $data['content']['url'] = xarML('Warning: File to include does not exist. Check file definition in finclude block instance.');
                } else {
                    $data['content']['url'] = implode(file($data['content']['url']), '');
                }
            }
            return $data;
        }

/**
 * Modify Function to the Blocks Admin
 * @param $blockinfo array containing title,content
 */
        public function modify(Array $data=array())
        {
            $data = $this->getContent();

            if (!empty($data['url'])) {
                $args['url'] = $data['url'];
            } else {
                $args['url'] = '';
            }
            $data['url'] = $args['url'];
            return $data;
        }

/**
 * Updates the Block config from the Blocks Admin
 * @param $blockinfo array containing title,content
 */
        public function update(Array $data=array())
        {
            $data = parent::update($data);
            $args = array();
            if (!xarVarFetch('url', 'isset', $args['url'], xarML('Error - No Url Specified'), XARVAR_DONT_SET)) {return;}

            $data['content'] = $args;
            return $data;
        }
    }
?>