<?php
/**
 * File: $Id$
 *
 * Event Messagging System
 *
 * @package events
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.org
 * @author Marco Canini <m.canini@libero.it>
 * @todo Document EMS
 * @todo Document functions
 * @todo Implement discovery functions for modules
 *
 * An event is a string composed by two part:
 * event := owner + '_' + name
 * where owner is a system short identifier and name is the proper event name
 */

/**
 * Intializes Event Messaging System
 *
 * Initialisation of the event messaging system, basically set the 
 * subscription arrays to empty.
 *
 * @author Marco Canini <m.canini@libero.it>
 * @access protected
 * @param args['loadLevel']
 * @param whatElseIsGoingLoaded
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
    if (!xarCoreIsApiAllowed($modType)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', "modType : $modType for $modName");
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

    if (!xarEvt__checkEvent($eventName)) return; // throw back
    if (empty($modName)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'modName');
        return;
    }
    if (!xarCoreIsApiAllowed($modType)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', "modType : $modType for $modName");
        return;
    }

    if (!isset($GLOBALS['xarEvt_subscribed'][$eventName])) return;

    for ($i = 0; $i < count($GLOBALS['xarEvt_subscribed'][$eventName]); $i++) {
        list($mn, $mt) = $GLOBALS['xarEvt_subscribed'][$eventName][$i];
        if ($modName == $mn && $modType == $mt) {
            unset($GLOBALS['xarEvt_subscribed'][$eventName][$i]);
            break;
        }
    }
}

// PROTECTED FUNCTIONS
function xarEvt_fire($eventName, $value = NULL)
{

    if (!xarEvt__checkEvent($eventName)) return; // throw back

    if (!isset($GLOBALS['xarEvt_subscribed'][$eventName])) return;

    for ($i = 0; $i < count($GLOBALS['xarEvt_subscribed'][$eventName]); $i++) {
        $funcName = $GLOBALS['xarEvt_subscribed'][$eventName][$i];
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
    // Can we load this api?
    if(!xarCoreIsApiAllowed($modType)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', "modType : $modType for $modName");
        return;
    }
    // FIXME: This won't work without explicit API loading, either
    // use explicit loading or use xarModAPIFunc
    xarModAPILoad($modName,$modType);
    // FIXME: is $value an array? check for it.
    // xarModAPIFunc($modName,$modType,evt_On$eventName,$value)

    // Try to find the specific handler for this event
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

    if (!xarEvt__checkEvent($eventName)) return; // throw back
    if (empty($funcName)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'funcName');
        return;
    }

    $GLOBALS['xarEvt_subscribed'][$eventName][] = $funcName;
}

function xarEvt_registerEvent($eventName)
{

    if (empty($eventName)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'eventName');
        return;
    }

    $GLOBALS['xarEvt_knownEvents'][$eventName] = true;
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
    StartBodyTag
    EndBodyTag
    MLSMissingTranslationString
    MLSMissingTranslationKey
    MLSMissingTranslationDomain
    */
}

