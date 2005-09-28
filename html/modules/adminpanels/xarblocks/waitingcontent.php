<?php
/**
 * File: $Id$
 *
 * Administration System
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage adminpanels module
 * @author John Cox <admin@dinerminor.com>
*/

/**
 * initialise block
 *
 * @author  John Cox <admin@dinerminor.com>
 * @access  public
 * @param   none
 * @return  nothing
 * @throws  no exceptions
 * @todo    nothing
*/
function adminpanels_waitingcontentblock_init()
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
function adminpanels_waitingcontentblock_info()
{
    return array(
        'text_type' => 'Waiting Content',
        'text_type_long' => 'Displays Waiting Content for All Modules',
        'module' => 'adminpanels',
        'allow_multiple' => false,
        'form_content' => false,
        'form_refresh' => false,
        'show_preview' => true
    );
}

/**
 * display adminmenu block
 *
 * @author  John Cox <admin@dinerminor.com>
 * @access  public
 * @param   none
 * @return  data array on success or void on failure
 * @throws  no exceptions
*/
function adminpanels_waitingcontentblock_display($blockinfo)
{
    // Security Check
    if(!xarSecurityCheck('AdminPanel',0,'Block',"waitingcontent:$blockinfo[title]:$blockinfo[bid]")) {return;}

    // Get publication types
    $data = xarModAPIFunc('adminpanels', 'admin', 'waitingcontent');

    $blockinfo['content'] = array(
        'output'   => $data['output'],
        'message'  => $data['message']
    );

    return $blockinfo;
}

?>
