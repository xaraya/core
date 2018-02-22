<?php
/**
 * Queue mail
 * @package modules\mail
 * @subpackage mail
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/771.html
 */

/**
 * This is a private utility function that is called to queue mail
 * It is used by the private function _sendmail() and should not be
 * called directly. Its arguments are classified on a need-to-know
 * basis :-)
 * @author  John Cox <niceguyeddie@xaraya.com>
 * @param array    $args array of optional parameters<br/>
 */
function mail_adminapi__queuemail(Array $args=array())
{
    // see if we have a scheduler job running to send queued mail
    $job = xarMod::apiFunc('scheduler','user','get',
                         array('module' => 'mail',
                               'type' => 'scheduler',
                               'func' => 'sendmail'));
    if (empty($job) || empty($job['interval'])) {
        return false;
    }

// TODO: use separate xar_mail_queue table here someday
    // we use microtime in case someone sends lots of identical mails :)
    list($usec,$sec) = explode(' ',microtime());
    $args['queued'] = (float) $sec + (float) $usec;
    // serialize the arguments for storage
    $data = serialize($args);
    // create a unique id for this mail
    $id = md5($data);
    // store the mail for later
    xarModVars::set('mail',$id,$data);

    // put it in the waiting queue, together with when it should be sent
    $serialqueue = xarModVars::get('mail','queue');
    if (!empty($serialqueue)) {
        $queue = unserialize($serialqueue);
    } else {
        $queue = array();
    }
    $queue[$id] = $args['when'];
    $serialqueue = serialize($queue);
    xarModVars::set('mail','queue',$serialqueue);

    return true;
}

?>
