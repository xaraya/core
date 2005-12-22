<?php
/*
 * Determine wether a mail queue is active
 *
 * @param $qInfo in args as returned by getobjectinfo of dd
 * @returns bool 
 */
function mail_userapi_qisactive($args)
{
    extract($args);
    if(!isset($objectid)) return false; // we're lazy
    if(!isset($status)) return false;
    return $status;
}
?>