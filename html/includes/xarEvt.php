<?php
/**
 * File: $Id$
 *
 * Event Messagging System
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 *
 * @subpackage Evt
 * @link xarEvt.php
 * @author Marco Canini <m.canini@libero.it>
 */

/* TODO:
 * Document EMS
 * Document functions
 */
/* An event is a string composed by two part:
 * event := owner + '_' + name
 * where owner is a system short identifier and name is the proper event name
 */

/**
 * Intializes Event Messaging System
 *
 * @author Marco Canini <m.canini@libero.it>
 * @access protected
 * @param args['loadLevel']
 * @return bool true
 */
function xarEvt_init($args, $whatElseIsGoingLoaded)
{
    $GLOBALS['xarEvt_subscribed'] = array();
    $GLOBALS['xarEvt_knownEvents'] = array();

    return true;
}

/**
 * Subscribes to an event
 *
 * @author Marco Canini <m.canini@libero.it>
 * @access public
 * @param eventName
 * @param modName
 * @param modType
 * @return void
 */
function xarEvtSubscribe($eventName, $modName, $modType)
{
    if (!xarEvt__checkEvent($eventName)) return; // throw back
    if (empty($modName)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'modName');
        return;
    }
    if ($modType != 'user' && $modType != 'admin') {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', 'modType');
        return;
    }

    $GLOBALS['xarEvt_subscribed'][$eventName][] = array($modName, $modType);
}

/**
 * Unsubscribes from an event
 *
 * @author Marco Canini <m.canini@libero.it>
 * @access public
 * @param eventName
 * @param modName
 * @param modType
 * @return void
 */
function xarEvtUnsubscribe($eventName, $modName, $modType)
{
    global $xarEvt_subscribed;

    if (!xarEvt__checkEvent($eventName)) return; // throw back
    if (empty($modName)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'modName');
        return;
    }
    if ($modType != 'user' && $modType != 'admin') {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', 'modType');
        return;
    }

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

    if (!xarEvt__checkEvent($eventName)) return; // throw back

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
    if (!xarEvt__checkEvent($eventName)) return; // throw back
    if (empty($modName)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'modName');
        return;
    }
    if ($modType != 'user' && $modType != 'admin') {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', 'modType');
        return;
    }

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

    if (!xarEvt__checkEvent($eventName)) return; // throw back
    if (empty($funcName)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'funcName');
        return;
    }

    $xarEvt_subscribed[$eventName][] = $funcName;
}

function xarEvt_registerEvent($eventName)
{
    global $xarEvt_knownEvents;

    if (empty($eventName)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'eventName');
        return;
    }

    $xarEvt_knownEvents[$eventName] = true;
}

// PRIVATE FUNCTIONS

function xarEvt__checkEvent($eventName)
{
    if (!isset($GLOBALS['xarEvt_knownEvents'][$eventName])) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EVENT_NOT_REGISTERED', $eventName);
        return;
    }
    return true;
    /*
    Current list is:
    ModLoad
    ModAPILoad
    BodyStart
    BodyEnd
    MLSMissingTranslationString
    MLSMissingTranslationKey
    MLSMissingTranslationDomain
    */
}

?>
