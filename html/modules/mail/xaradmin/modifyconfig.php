<?php
/**
 * Update the configuration parameters
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Mail System
 * @link http://xaraya.com/index.php/release/771.html
 */

/**
 * Update the configuration parameters of the module based on data from the modification form
 *
 * @author  John Cox <niceguyeddie@xaraya.com>
 * @access  public
 * @param   no parameters
 * @return  true on success or void on failure
 * @throws  no exceptions
 * @todo    nothing
*/
function mail_admin_modifyconfig()
{
    // Security Check
    if (!xarSecurityCheck('AdminMail')) return; 
    // Generate a one-time authorisation code for this operation
    $data['authid'] = xarSecGenAuthKey(); 
    // Quick Check for E_ALL
    $searchstrings = xarModGetVar('mail', 'searchstrings');
    $replacestrings = xarModGetVar('mail', 'replacestrings');
    if (empty($searchstrings)){
        $searchstrings = serialize('%%Search%%');
        xarModSetVar('mail', 'searchstrings', $searchstrings);
    }
    if (empty($replacestrings)){
        $replacestrings = serialize('Replace %%Search%% with this text');
        xarModSetVar('mail', 'replacestrings', $replacestrings);
    }
    $data['createlabel'] = xarML('Submit');
    $data['searchstrings'] = unserialize(xarModGetVar('mail', 'searchstrings'));
    $data['replacestrings'] = unserialize(xarModGetVar('mail', 'replacestrings'));

    // Get encoding
    $data['encoding'] = xarModGetVar('mail', 'encoding');
    
    //redirect address - ensure it's set
    $redirectaddress = trim(xarModGetVar('mail', 'redirectaddress'));
    if (isset($redirectaddress) && !empty($redirectaddress)){
        $data['redirectaddress']=xarVarPrepForDisplay($redirectaddress);
    }else{
        $data['redirectaddress']='';
    }
    // Include 'formcheck' JavaScript.
    // TODO: move this to a template widget when available.
    xarModAPIfunc(
        'base', 'javascript', 'modulefile',
        array('module'=>'base', 'filename'=>'formcheck.js')
    );

    if (xarModIsAvailable('scheduler')) {
        $data['intervals'] = xarModAPIFunc('scheduler','user','intervals');
        // see if we have a scheduler job running to send queued mail
        $job = xarModAPIFunc('scheduler','user','get',
                             array('module' => 'mail',
                                   'type' => 'scheduler',
                                   'func' => 'sendmail'));
        if (empty($job) || empty($job['interval'])) {
            $data['interval'] = '';
        } else {
            $data['interval'] = $job['interval'];
        }
        // get the waiting queue
        $serialqueue = xarModGetVar('mail','queue');
        if (!empty($serialqueue)) {
            $queue = unserialize($serialqueue);
        } else {
            $queue = array();
        }
        $data['unsent'] = count($queue);
    }

    // everything else happens in Template for now
    return $data;
} 
?>