<?php
/**
 * Waiting content block management
 *
 * @package modules
 * @subpackage base module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/68.html
 */

    sys::import('xaraya.structures.containers.blocks.basicblock');

    class Base_WaitingContentBlock extends BasicBlock implements iBlock
    {

        public $type                = 'waitingcontent';
        public $module              = 'base';
        public $text_type           = 'Waiting Content';
        public $text_type_long      = 'Displays Waiting Content for All Modules';

        function display(Array $data=array())
        {
            return $data['output'] = xarMod::apiFunc('base', 'admin', 'waitingcontent');
        }
    }
?>