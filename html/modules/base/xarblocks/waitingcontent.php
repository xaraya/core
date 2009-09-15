<?php
/**
 * Waiting content block management
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 * @link http://xaraya.com/index.php/release/68.html
 */

    sys::import('xaraya.structures.containers.blocks.basicblock');

    class WaitingContentBlock extends BasicBlock implements iBlock
    {
        public $no_cache            = 1;

        public $name                = 'WaitingContentBlock';
        public $module              = 'base';
        public $text_type           = 'Waiting Content';
        public $text_type_long      = 'Displays Waiting Content for All Modules';
        public $show_preview        = true;

        function display(Array $data=array())
        {
            $data = parent::display($data);

            // Get waiting content
            $waitingcontent = xarModAPIFunc('base', 'admin', 'waitingcontent');
            
            // Hooks (we specify that we want the ones for adminpanels here)
            $output = array();
            $output = xarModCallHooks('item', 'waitingcontent', '', array('module' => 'base'));

            if (empty($output)) {
                $message = xarML('Waiting Content has not been configured');
            }
            if (empty($message)) $message = '';var_dump($output);

            $data['content'] = array(
                'output'   => $output,
                'message'  => $message
            );

            return $data;
        }
    }
?>