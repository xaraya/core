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
function xarEvt_init($args)
{
    global $xarEvt_subscribed, $xarEvt_knownEvents;

    $xarEvt_subscribed = array();

    $xarEvt_knownEvents = array();

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
function xarEvtSubscribe($eventName, $modName, $modType)
{
    global $xarEvt_subscribed;
    if (!xarEvt__checkEvent($eventName)) {
        $msg = xarML('Unknown event.');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'UNKNOWN',
                       new SystemException($msg));
        return;
    }

    $xarEvt_subscribed[$eventName][] = array($modName, $modType);
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
function xarEvtUnsubscribe($eventName, $modName, $modType)
{
    global $xarEvt_subscribed;
    if (!isset($xarEvt_subscribed[$eventName])) return;

    for ($i = 0; $i < count($xarEvt_subscribed[$eventName]); $i++) {
        list($mn, $mt) = $xarEvt_subscribed[$eventName][$i];
        if ($modName == $mn && $modType == $mt) {
            unset($xarEvt_subscribed[$eventName][$i]);
            break;
        }
    }
}

// PROTECTED FUNCTIONS
function xarEvt_fire($eventName, $value = NULL)
{
    global $xarEvt_subscribed;
    if (!isset($xarEvt_subscribed[$eventName])) return;

    for ($i = 0; $i < count($xarEvt_subscribed[$eventName]); $i++) {
        $funcName = $xarEvt_subscribed[$eventName][$i];
        if (is_array($funcName)) {
            list($modName, $modType) = $funcName;
            xarEvt_notify($modName, $modType, $eventName, $value);
        } else {
            // Raw callback
            if (function_exists($funcName)) {
                $funcName($value);
            }
        }
    }

}

function xarEvt_notify($modName, $modType, $eventName, $value)
{
    $funcName = "{$modName}_{$modType}evt_On$eventName";
    if (function_exists($funcName)) {
        $funcName($value);
        if (xarExceptionMajor() != XAR_NO_EXCEPTION) return;
    } else {
        $funcName = "{$modName}_{$modType}evt_OnEvent";
        if (function_exists($funcName)) {
            $funcName($eventName, $value);
            if (xarExceptionMajor() != XAR_NO_EXCEPTION) return;
        }
    }
}

function xarEvt_subscribeRawCallback($eventName, $funcName)
{
    global $xarEvt_subscribed;
    if (!xarEvt__checkEvent($eventName)) {
        xarCore_die("xarEvt_subscribeRawCallback: Cannot subscribe to unexistent event $eventName.");
    }

    $xarEvt_subscribed[$eventName][] = $funcName;
}

function xarEvt_registerEvent($eventName)
{
    global $xarEvt_knownEvents;
    $xarEvt_knownEvents[$eventName] = true;
}

// PRIVATE FUNCTIONS

function xarEvt__checkEvent($eventName)
{
    global $xarEvt_knownEvents;
    return isset($xarEvt_knownEvents[$eventName]);
    /*
    Current list is:
    ModLoad
    ModAPILoad
    BodyStart
    BodyEnd
    MLSMissingTranslationString
    MLSMissingTranslationKey
    MLSMissingTranslationContext
    */
}

?>
