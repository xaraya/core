<?php
// File: $Id$
// ----------------------------------------------------------------------
// Xaraya eXtensible Management System
// Copyright (C) 2002 by the Xaraya Development Team.
// http://www.xaraya.org
// ----------------------------------------------------------------------
// Original Author of file: Marco Canini
// Purpose of file: Event Messagging System
// ----------------------------------------------------------------------

/* TODO:
 * Document EMS
 * Document functions
 */
/* An event is a string composed by two part:
 * event := owner + '_' + name
 * where owner is a system short identifier and name is the proper event name
 */

/**
 * Start Event Messaging System
 * 
 * @access private
 * @param args['loadLevel']
 * returns bool
 */
function pnEvt_init($args)
{
    global $pnEvt_subscribed, $pnEvt_knownEvents;

    $pnEvt_subscribed = array();

    $pnEvt_knownEvents = array();

    return true;
}

/**
 * Subscribe to an event
 *
 * @access public
 * @param eventName
 * @param modName
 * @param modType
 * @returns
 */
function pnEvtSubscribe($eventName, $modName, $modType)
{
    global $pnEvt_subscribed;
    if (!pnEvt__checkEvent($eventName)) {
        $msg = pnML('Unknown event.');
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'UNKNOWN',
                       new SystemException($msg));
        return;
    }

    $pnEvt_subscribed[$eventName][] = array($modName, $modType);
}

/**
 * Unsubscribe from an event
 *
 * @access public
 * @param eventName
 * @param modName
 * @param modType
 * @returns
 */
function pnEvtUnsubscribe($eventName, $modName, $modType)
{
    global $pnEvt_subscribed;
    if (!isset($pnEvt_subscribed[$eventName])) return;

    for ($i = 0; $i < count($pnEvt_subscribed[$eventName]); $i++) {
        list($mn, $mt) = $pnEvt_subscribed[$eventName][$i];
        if ($modName == $mn && $modType == $mt) {
            unset($pnEvt_subscribed[$eventName][$i]);
            break;
        }
    }
}

// PROTECTED FUNCTIONS
function pnEvt_fire($eventName, $value = NULL)
{
    global $pnEvt_subscribed;
    if (!isset($pnEvt_subscribed[$eventName])) return;

    for ($i = 0; $i < count($pnEvt_subscribed[$eventName]); $i++) {
        $funcName = $pnEvt_subscribed[$eventName][$i];
        if (is_array($funcName)) {
            list($modName, $modType) = $funcName;

            pnModFunc($modName, $modType, '_On'.$eventName, array('value'=>$value));
            if (pnExceptionMajor() != PN_NO_EXCEPTION) {
                if (pnExceptionId() == 'MODULE_FUNCTION_NOT_EXIST') {
                    pnExceptionFree();
                } else {
                    return; // throw back
                }
            }
            pnModFunc($modName, $modType, '_OnEvent', array('eventName'=>$eventName, 'value'=>$value));
            if (pnExceptionMajor() != PN_NO_EXCEPTION) {
                return; // throw back
            }
        } else {
            // Raw callback
            if (function_exists($funcName)) {
                $funcName($value);
            }
        }
    }

}

function pnEvt_notify($modName, $modType, $eventName, $value)
{
    pnModFunc($modName, $modType, '_On'.$eventName, array('value'=>$value));
    if (pnExceptionMajor() != PN_NO_EXCEPTION) {
        if (pnExceptionId() == 'MODULE_FUNCTION_NOT_EXIST') {
            pnExceptionFree();
        } else {
            return; // throw back
        }
    }
    pnModFunc($modName, $modType, '_OnEvent', array('eventName'=>$eventName, 'value'=>$value));
    if (pnExceptionMajor() != PN_NO_EXCEPTION) {
        if (pnExceptionId() == 'MODULE_FUNCTION_NOT_EXIST') {
            pnExceptionFree();
        } else {
            return; // throw back
        }
    }
}

function pnEvt_subscribeRawCallback($eventName, $funcName)
{
    global $pnEvt_subscribed;
    if (!pnEvt__checkEvent($eventName)) {
        pnCore_die("pnEvt_subscribeRawCallback: Cannot subscribe to unexistent event $eventName.");
    }

    $pnEvt_subscribed[$eventName][] = $funcName;
}

function pnEvt_registerEvent($eventName)
{
    global $pnEvt_knownEvents;
    $pnEvt_knownEvents[$eventName] = true;
}

// PRIVATE FUNCTIONS

function pnEvt__checkEvent($eventName)
{
    global $pnEvt_knownEvents;
    return isset($pnEvt_knownEvents[$eventName]);
    /*
    Current list is:
    ModLoad
    ModAPILoad
    PostBodyStart
    PreBodyEnd
    MLSMissingTranslationString
    MLSMissingTranslationKey
    MLSMissingTranslationContext
    */
}

?>
