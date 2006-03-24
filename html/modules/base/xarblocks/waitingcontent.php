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

/**
 * initialise block
 *
 * @author  John Cox <niceguyeddie@xaraya.com>
 * @access  public
 * @param   none
 * @return  nothing
 * @throws  no exceptions
 * @todo    nothing
*/
function base_waitingcontentblock_init()
{
    // Nothing to configure.
    return array('nocache'     => 0,
                 'pageshared'  => 1,
                 'usershared'  => 1,
                 'cacheexpire' => null);
}

/**
 * get information on block
 *
 * @author  John Cox <admin@dinerminor.com>
 * @access  public
 * @param   none
 * @return  data array
 * @throws  no exceptions
 * @todo    nothing
*/
function base_waitingcontentblock_info()
{
    return array(
        'text_type' => 'Waiting Content',
        'text_type_long' => 'Displays Waiting Content for All Modules',
        'module' => 'base',
        'allow_multiple' => false,
        'form_content' => false,
        'form_refresh' => false,
        'show_preview' => true
    );
}

/**
 * display waitingcontent block
 *
 * @author  John Cox <admin@dinerminor.com>
 * @access  public
 * @param   none
 * @return  data array on success or void on failure
 * @throws  no exceptions
*/
function base_waitingcontentblock_display($blockinfo)
{
    // Security Check
    if(!xarSecurityCheck('ViewBaseBlocks',0,'Block',"waitingcontent:$blockinfo[title]:$blockinfo[bid]")) {return;}

    // Get publication types
    $data = xarModAPIFunc('base', 'admin', 'waitingcontent');

    $blockinfo['content'] = array(
        'output'   => $data['output'],
        'message'  => $data['message']
    );

    return $blockinfo;
}

?>