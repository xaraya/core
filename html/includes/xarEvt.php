<?php
/**
 * File: $Id$
 *
 * Event Messagging System
 *
 * @package events
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @author Marco Canini <marco@xaraya.com>
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
 * ServerRequest - event is issued at the end of processing a server request
 * 
 * Session package:
 * ----------------
 * SessionCreate - event is triggered when a new session is being created (see xarSession.php)
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
 * @author Marco Canini <marco@xaraya.com>
 * @access protected
 * @param $args['loadLevel']
 * @return bool true
 */
function xarEvt_init($args, $whatElseIsGoingLoaded)
{
    // Subsystem initialized, register a handler to run when the request is over
    register_shutdown_function ('xarEvt__shutdown_handler');
    
    return true;
}

/**
 * Shutdown handler for events subsystem
 *
 * @access private
 */
function xarEvt__shutdown_handler()
{
    //xarLogMessage("xarEvt shutdown handler");
}

/**
 * Trigger an event and call the potential handlers for it in the modules
 *
 * The specified event is issued to the active modules. If a module
 * has defined a specific handler for that event, that function is
 * executed.
 * 
 * @author  Marco Canini
 * @author  Marcel van der Boom <marcel@xaraya.com>
 * @access  protected
 * @param   string $eventName The name of the event
 * @param   mixed  $value Passed as parameter to the even handler function in the module
 * @return  void
 * @todo    Analyze thoroughly for performance issues
*/
function xarEvt_trigger($eventName, $value = NULL)
{
    // Must make sure the event exists.
    if (!xarEvt__checkEvent($eventName)) return; // throw back

    // Call the event handlers in the active modules
    $activemods = xarEvt__GetActiveModsList();
    xarLogMessage("Triggered event ($eventName)");
    //FIXME: <besfred> ^^^ should we catch its return value and react?

    $nractive=count($activemods);
    for ($i =0; $i < $nractive; $i++) {
        // We issue the event to the user api for now
        // FIXME: Could all 4 types be supported? In which situations?
        xarEvt__notify($activemods[$i]['name'], $eventName, $value);
        //FIXME: <besfred> ^^^ should we catch its return value and react?
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
 * @access  private
 * @param   $modName string The name of the module
 * @param   $modType string userapi / adminapi
 * @return  void
 * @throws  BAD_PARAM
 * @todo    Analyze thoroughly for performance issues.
*/
function xarEvt__notify($modName, $eventName, $value)
{
    if (!xarEvt__checkEvent($eventName)) return; // throw back

    if (empty($modName)) {
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'modName');
        return;
    }

    // We can't rely on the API, the event system IS the API!
    // - no use of xarModAPIFunc because that sets exceptions and we 
    //   don't want that when a module doesn't react to an event.
    // - we could use xarModAPILoad. This will create another event ModAPILoad 
    //   if the api wasn't loaded yet. The event will *not* be created if the
    //   API was already loaded. However, this would mean that all module APIs
    //   are always loaded, which is a bit too much, so we should try it another way

    // First issue it to the specific event handler
    // Function naming: module_eventapi_OnEventName
    $funcSpecific = "{$modName}_eventapi_On$eventName";
    // $funcGeneral  = "{$modName}_eventapi_OnEvent";

    // set which file to load for looking up the event handler
    $xarapifile="modules/{$modName}/xareventapi.php";
    $xartabfile="modules/{$modName}/xartables.php";
    // $xarapifile="modules/{$modName}/xar{$modType}evt.php";    

    if(function_exists($funcSpecific)) {

        $funcSpecific($value);
        if (xarCurrentErrorType() != XAR_NO_EXCEPTION) return;
//    } elseif (function_exists($funcGeneral)) {
//        $funcGeneral($eventName,$value);
//        if (xarCurrentErrorType() != XAR_NO_EXCEPTION) return;
    } elseif (file_exists($xarapifile)) {

        include_once($xarapifile);

        if (file_exists($xartabfile)) {
            include_once($xartabfile);
            $xartabfunc = $modName.'_xartables';

            if (function_exists($xartabfunc)) 
                xarDB_importTables($xartabfunc());
        }

        if(function_exists($funcSpecific)) {

            $funcSpecific($value);

            if (xarCurrentErrorType() != XAR_NO_EXCEPTION) return;

//        } elseif (function_exists($funcGeneral)) {
//            $funcGeneral($eventName,$value);
//            if (xarCurrentErrorType() != XAR_NO_EXCEPTION) return;
        }
    }   
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
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'eventName');
        return;
    }
    
    $GLOBALS['xarEvt_knownEvents'][$eventName] = true;
    return true;
}

/**
 * Check whether an event is registered
 *
 * @author  Marco Canini
 * @author  Marcel van der Boom
 * @access  private
 * @param   $eventName Name of the event to check
 * @return  bool 
 * @throws  EVENT_NOT_REGISTERED
*/
function xarEvt__checkEvent($eventName)
{
    if (!isset($GLOBALS['xarEvt_knownEvents'][$eventName])) {
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'EVENT_NOT_REGISTERED', $eventName);
        return;
    }
    return true;
}

/**
 * Replicate the functionality of xarModGetList(array('State'=>XARMOD_STATE_ACTIVE));
 *
 * @author  Frank Besler
 * @access  private
 * @return array of module information arrays
 */
function xarEvt__GetActiveModsList()
{
    // use vars instead of defines to narrow the scope
    $XARMOD_STATE_ACTIVE  = 3;
    $XARMOD_MODE_SHARED   = 1;
    $XARMOD_MODE_PER_SITE = 2;
    
    $dbconn =& xarDBGetConn();

    $systabpre = xarDBGetSystemTablePrefix();
    $sitetabpre = xarDBGetSiteTablePrefix();
    $modulestable = $sitetabpre.'_modules';
    $module_statesTables = array(($systabpre.'_module_states'), ($sitetabpre.'_module_states'));
    $mode = $XARMOD_MODE_SHARED;
    $modList = array();

    // Here we do 2 SELECTs: one for SHARED moded modules and
    // one for PER_SITE moded modules
    // Maybe this could be done with a single query?
    for ($i = 0; $i < 2; $i++ ) {
        $module_statesTable = $module_statesTables[$i];

        $query = "SELECT    mods.xar_regid, mods.xar_name,
                            mods.xar_directory, mods.xar_version,
                            states.xar_state
                  FROM      $modulestable AS mods
                  LEFT JOIN $module_statesTable AS states 
                  ON        mods.xar_regid = states.xar_regid
                  WHERE     states.xar_state = ? AND mods.xar_mode = ?";
        $result =& $dbconn->Execute($query,array($XARMOD_STATE_ACTIVE,$mode));
        if (!$result) return;
        
        while(!$result->EOF) {
            list($modInfo['regid'],     $modInfo['name'],
                 $modInfo['directory'], $modInfo['version'],
                 $modState) = $result->fields;
                
            if (xarVarIsCached('Evt.Mod.Infos', $modInfo['regid'])) {
                // Get infos from cache
                $modList[] = xarVarGetCached('Evt.Mod.Infos', $modInfo['regid']);
            } else {
                $modInfo['mode'] = (int) $mode;
                $modInfo['state'] = (int) $modState;
                xarVarSetCached('Evt.Mod.BaseInfos', $modInfo['name'], $modInfo);
                xarVarSetCached('Evt.Mod.Infos', $modInfo['regid'], $modInfo);
                $modList[] = $modInfo;
            }
            $modInfo = array();
            $result->MoveNext();
        }
        $result->Close();
        $mode = $XARMOD_MODE_PER_SITE;
    }
    return $modList;
}

?>