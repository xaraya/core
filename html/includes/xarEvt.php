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
 * @author Marcel van der Boom <marcel@hsdev.com>
 * @todo Document EMS
 * @todo Document functions
 * @todo Implement discovery functions for modules
 *
 * An event is a string composed by two part:
 * event := owner + name
 * where owner is a system short identifier and name is the proper event name
 * Example: ModLoad -> system short identifier = Mod, event = Load
 */

/**
 * List of supported events
 * Blocks package:
 * ---------------
 * none
 *
 * Config package:
 * --------------
 * none
 * 
 * Core:
 * -----
 * none
 *
 * DB package:
 * -----------
 * none
 *
 * Evt package:
 * ------------
 * none
 *
 * Exception package:
 * ------------------
 * none
 * 
 * Logging package:
 * ----------------
 * none
 * 
 * Multilanguage package:
 * ----------------------
 * MLSMissingTranslationKey    - translationkey is missing
 * MLSMissingTranslationString - translation string is missing
 * MLSMissingTranslationDomain - translation domain is missing
 *
 * Module package: 
 * ---------------
 * ModLoad    - event is issued at the end of the xarModLoad function, just before returning true
 * ModAPILoad - event is issued at the end of the xarModAPILoad function, just before returning true
 *
 * Security package:
 * ----------------- 
 * none
 *
 * Server package:
 * ---------------
 * ServerRequest - fires when a request is received at the server.
 * 
 * Session package:
 * ----------------
 * none
 *
 * TableDDL package :
 * ------------------
 * none
 * 
 * Template package :
 * ------------------
 * none
 * 
 * Theme package:
 * -------------
 * none
 * 
 * User package:
 * ------------
 * none
 * 
 * Variables package:
 * ------------------
 * none
 *
 */

/**
 * Intializes Event Messaging System
 *
 * @author Marco Canini <m.canini@libero.it>
 * @access protected
 * @param $args['loadLevel']
 * @return bool true
 */
function xarEvt_init($args, $whatElseIsGoingLoaded)
{
    // Deprecated
    //$GLOBALS['xarEvt_subscribed'] = array();
    //$GLOBALS['xarEvt_knownEvents'] = array();

    return true;
}

/**
 * Subscribes to an event
 *
 * @author Marco Canini <m.canini@libero.it>
 * @access public
 * @deprec 20030222
 * @param $eventName
 * @param $modName
 * @param $modType
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
 * @deprec 20030222
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



/**
 * Fire an event and call the potential handlers for it in the modules
 *
 * The specified event is issued to the active modules. If a module
 * has defined a specific handler for that event, that function is
 * executed.
 * 
 * @author  Marco Canini
 * @author  Marcel van der Boom <marcel@xaraya.com>
 * @access  protected
 * @param   $eventName string The name of the event
 * @param   $value mixed Passed as parameter to the even handler function in the module
 * @return  void
 * @todo    Analyze thoroughly for performance issues
*/
function xarEvt_fire($eventName, $value = NULL)
{
    // Must make sure the event exists.
    if (!xarEvt__checkEvent($eventName)) return; // throw back
    
    //if (!isset($GLOBALS['xarEvt_subscribed'][$eventName])) return;

    // Call the event handlers in the active modules
    $activemods = xarModGetList(array('State'=>XARMOD_STATE_ACTIVE));
    $nractive=count($activemods);
    for ($i =0; $i < $nractive; $i++) {
        // We issue the event to the user api for now
        // FIXME: Could all 4 types be supported? In which situations?
        xarEvt_notify($activemods[$i]['name'], 'user', $eventName, $value);
    }
    
}

/**
 * Notify the event handlers that an event has occurred
 *
 * Notifies a module that a certain event has occurred
 * the event handler in the module is called
 *
 * @author  Marco Canini
 * @author  Marcel van der Boom <marcel@xaraya.com>
 * @access  protected
 * @param   $modName string The name of the module
 * @param   $modType string userapi / adminapi
 * @return  void
 * @throws  BAD_PARAM
 * @todo    Analyze thoroughly for performance issues.
*/
function xarEvt_notify($modName, $modType, $eventName, $value)
{
    if (!xarEvt__checkEvent($eventName)) return; // throw back
    if (empty($modName)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'modName');
        return;
    }

    // We can't rely on the API, the event system IS the API
    
    // We can't use xarModAPIFunc because that sets exceptions and we 
    // don't want that when a module doesn't react to an event.

    // We can use xarModAPILoad. This will create another event ModAPILoad 
    // if the api wasn't loaded yet. The event will *not* be created if the
    // API was already loaded. However, this would mean that all module APIs
    // are always loaded, which is a bit too much, so we try it another way

    // First issue it to the specific event handler
    // Function naming: module_userapievt_OnEventName
    $funcName=array();
    $funcSpecific = "{$modName}_{$modType}apievt_On$eventName";
    $funcGeneral  = "{$modName}_{$modType}apievt_OnEvent";
    $xarapifile="modules/{$modName}/xar{$modType}api.php";

    if(function_exists($funcSpecific)) {
        $funcSpecific($value);
        if (xarExceptionMajor() != XAR_NO_EXCEPTION) return;
    } elseif (function_exists($funcGeneral)) {
        $funcGeneral($eventName,$value);
        if (xarExceptionMajor() != XAR_NO_EXCEPTION) return;
    } elseif (file_exists($xarapifile)) {
        // FIXME: can we do without this call?
        xarModAPILoad($modName,$modType);
        if(function_exists($funcSpecific)) {
            $funcSpecific($value);
            if (xarExceptionMajor() != XAR_NO_EXCEPTION) return;
        } elseif (function_exists($funcGeneral)) {
            $funcGeneral($eventName,$value);
            if (xarExceptionMajor() != XAR_NO_EXCEPTION) return;
        }
    }   
}

/**
 * Subscribe to a raw callback function
 *
 * @deprec 20030222
 */
function xarEvt_subscribeRawCallback($eventName, $funcName)
{

    if (!xarEvt__checkEvent($eventName)) return; // throw back
    if (empty($funcName)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'funcName');
        return;
    }

    $GLOBALS['xarEvt_subscribed'][$eventName][] = $funcName;
}


/**
 * Register a supported event
 *
 * The event 'eventName' is registered as a supported event
 *
 * @author  Marco Canini
 * @access  protected
 * @param   $eventName string Which event are we registering?
 * @return  void
 * @throws  EMPTY_PARAM
 */
function xarEvt_registerEvent($eventName)
{
    
    if (empty($eventName)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'eventName');
        return;
    }
    
    $GLOBALS['xarEvt_knownEvents'][$eventName] = true;
}


/**
 * Check whether an event is registered
 *
 * @author  Marco Canini
 * @author  Marcel van der Boom
 * @access  private
 * @param   $eventName Name of the event to check
 * @returns boolean 
 * @throws  EVENT_NOT_REGISTERED
*/
function xarEvt__checkEvent($eventName)
{
    if (!isset($GLOBALS['xarEvt_knownEvents'][$eventName])) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EVENT_NOT_REGISTERED', $eventName);
        return;
    }
    return true;

}

?>
