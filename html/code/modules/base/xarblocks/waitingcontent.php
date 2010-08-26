<?php
/**
 * Waiting content block management
 *
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 * @link http://xaraya.com/index.php/release/68.html
 */

    sys::import('xaraya.structures.containers.blocks.basicblock');

    class Base_WaitingContentBlock extends BasicBlock implements iBlock
    {
        public $nocache             = 1;

        public $name                = 'WaitingContentBlock';
        public $module              = 'base';
        public $text_type           = 'Waiting Content';
        public $text_type_long      = 'Displays Waiting Content for All Modules';
        public $show_preview        = true;

        function display(Array $data=array())
        {
            $data = parent::display($data);
            if (empty($data)) return;
            /*
            // Hooks (we specify that we want the ones for adminpanels here)
            $output = array();
            $output = xarModCallHooks('item', 'waitingcontent', '');
            
            $data['content'] = array(
                'output'   => $output,
            );
            */

            $data['content'] = xarMod::apiFunc('base', 'admin', 'waitingcontent');
            return $data;
        }
    }
?>