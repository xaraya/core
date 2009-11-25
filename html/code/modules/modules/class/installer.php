<?php
/**
 * Module insatller
 *
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage modules
 * @author Marc Lutolf <mfl@netspan.ch>
 */

/**
 * Initialise the modules module
 */
 
class Installer extends Object
{
    private $dependencieschecked      = false;
    private $moduleschecked           = array();
    private $dependentmodules         = array();
    private $modulestack;

    protected static $instance        = null;
    protected $unsatisfiable          = array();
    protected $satisfiable            = array();
    protected $satisfied              = array();
    protected $active                 = array();
    protected $initialised            = array();

    public $fileModules               = array();
    public $databaseModules           = array();

    protected function __construct()
    {
        $this->fileModules = xarMod::apiFunc('modules','admin','getfilemodules');
        $this->databaseModules = xarMod::apiFunc('modules','admin','getdbmodules');
        // FIXME do something else here
        if (empty($this->databaseModules)) throw new ModuleNotFoundException();
        if (empty($this->fileModules)) throw new ModuleNotFoundException();

        sys::import('xaraya.structures.sequences.stack');
        $this->modulestack = new Stack();
    }

    public static function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function checkformissing()
    {
        if ($this->dependencieschecked) {return true;}

        foreach ($this->databaseModules as $name => $modInfo) {
            if (empty($this->fileModules[$name])) {

                // Get module ID
                $regId = $modInfo['regid'];
                // Set state of module to 'missing'
                switch ($modInfo['state']) {
                    case XARMOD_STATE_UNINITIALISED: $newstate = XARMOD_STATE_MISSING_FROM_UNINITIALISED; break;
                    case XARMOD_STATE_INACTIVE:      $newstate = XARMOD_STATE_MISSING_FROM_INACTIVE; break;
                    case XARMOD_STATE_ACTIVE:        $newstate = XARMOD_STATE_MISSING_FROM_ACTIVE; break;
                    case XARMOD_STATE_UPGRADED:      $newstate = XARMOD_STATE_MISSING_FROM_UPGRADED; break;
                }
                if (isset($newstate)) {
                    $set = xarMod::apiFunc('modules', 'admin', 'setstate',
                                        array('regid'=> $regId,
                                              'state'=> $newstate));
                }
            }
        }
        $this->dependencieschecked = true;
        return $this->dependencieschecked;
    }
    
    public function verifydependency($regid=null)
    {
        if (!isset($regid)) throw new EmptyParameterException('regid');

        // Get module information
        $modInfo = xarMod::getInfo($regid);
        if (!isset($modInfo)) throw new ModuleBaseInfoNotFoundException("with regid $regid");

        // See if we have lost any modules since last generation
        if (!$this->checkformissing()) throw new ModuleNotFoundException();

        $dbMods = array();

        //Find the modules which are active (should upgraded be added too?)
        foreach ($this->databaseModules as $name => $dbInfo) {
            if (($dbInfo['state'] != XARMOD_STATE_MISSING_FROM_UNINITIALISED) && ($dbInfo['state'] < XARMOD_STATE_MISSING_FROM_INACTIVE))
            {
                $dbMods[$dbInfo['regid']] = $dbInfo;
            }
        }

        if (!empty($modInfo['extensions'])) {
            foreach ($modInfo['extensions'] as $extension) {
                if (!empty($extension) && !extension_loaded($extension)) {
                    $msg = xarML("Required PHP extension '#(1)' is missing for module '#(2)'", $extension, $modInfo['displayname']);
                    throw new Exception($msg);
                }
            }
        }

        $dependency = $modInfo['dependency'];
        if (empty($dependency)) $dependency = array();

        foreach ($dependency as $module_id => $conditions) {

            if (is_array($conditions)) {

                //Required module inexistent
                if (!isset($dbMods[$module_id]))
                    throw new ModuleNotFoundException($module_id,'Required module missing (ID #(1))');

                if (xarMod::apiFunc('base','versions','compare',array(
                    'version1'      => $conditions['minversion'],
                    'version2'      => $dbMods[$module_id]['version'],
                    )) < 0) {
                    //Need to add some info for the user
                    return false; // 1st version is bigger
                }

               //Not to be checked, at least not for now
               /*
                if (xarMod::apiFunc('base','versions','compare',array(
                    'version1'       => $conditions['maxversion'],
                    'version2'       => $dbMods[$module_id]['version'],
                    )) > 0) {
                    //Need to add some info for the user
                    return false; // 1st version is smaller
                }
                */

            } else {
                //Required module inexistent
                if (!isset($dbMods[$conditions]))
                    throw new ModuleNotFoundException($conditions,'Required module missing (ID #(1))');
            }
        }

        return true;
    }

    public function getalldependencies($regid)
    {
        static $checked_ids = array();

        // Argument check
        if (!isset($regid)) throw new EmptyParameterException('regid');

        // See if we have lost any modules since last generation
        if (!$this->checkformissing()) { return; }

        if(in_array($regid,$checked_ids)) {
            xarLogMessage("Already got the dependencies of $regid, skipping");
            return true; 
        }
        $this->moduleschecked[] = $regid;

        // Get module information
        try {
            $modInfo = xarMod::getInfo($regid);
        } catch (NotFoundExceptions $e) {
            //Add this module to the unsatisfiable list
            $this->unsatisfiable[] = $regid;
            //Return now, we cant find more info about this module
            return true;
        }

        if (!empty($modInfo['extensions'])) {
            foreach ($modInfo['extensions'] as $extension) {
                if (!empty($extension) && !extension_loaded($extension)) {
                    //Add this extension to the unsatisfiable list
                    $this->unsatisfiable[] = $extension;
                }
            }
        }

        $dependency = $modInfo['dependency'];
        if (empty($dependency)) $dependency = array();

        //The dependencies are ok, they shouldnt change in the middle of the
        //script execution, so let's assume this.
        foreach ($dependency as $module_id => $conditions) {
            if (is_array($conditions)) {
                //The module id is in $modId
                $modId = $module_id;
            } else {
                //The module id is in $conditions
                $modId = $conditions;
            }

            // RECURSIVE CALL
            if (!$this->getalldependencies($modId)) {
                $msg = xarML('Unable to get dependencies for module with ID (#(1)).', $modId);
                throw new Exception($msg);
            }
        }

        // Unsatisfiable and Satisfiable are assuming the user can't
        //use some hack or something to set the modules as initialised/active
        //without its proper dependencies
        if (count($this->unsatisfiable)) {
            //Then this module is unsatisfiable too
            $this->unsatisfiable[] = $modInfo;
        } elseif (count($this->satisfiable)) {
            //Then this module is satisfiable too
            //As if it were initialised, then all dependencies would have
            //to be already satisfied
            $this->satisfiable[] = $modInfo;
        } else {
            //Then this module is at least satisfiable
            //Depends if it is already initialised or not

            //TODO: Add version checks later on
            // Add a new state in the dependency array for version
            // So that we can present that nicely in the gui...

            switch ($modInfo['state']) {
                case XARMOD_STATE_ACTIVE:
                case XARMOD_STATE_UPGRADED:      $this->satisfied[] = $modInfo; break;
                case XARMOD_STATE_INACTIVE:
                case XARMOD_STATE_UNINITIALISED: $this->satisfiable[] = $modInfo; break;
                default:                         $this->unsatisfiable[] = $modInfo; break;
            }
        }

        return true;
    }

    public function getalldependents($regid=null)
    {
        // Argument check
        if (!isset($regid)) throw new EmptyParameterException('regid');

        // See if we have lost any modules since last generation
        if (!$this->checkformissing()) { return; }

        // If we have already got the same id in the same request, dont do it again.
        if(in_array($regid, $this->dependentmodules)) {
            xarLogMessage("We already checked module $regid, not doing it a second time");
            return true;
        }
        $this->dependentmodules[] = $regid;

        foreach ($this->fileModules as $name => $modinfo) {

            // If the module is not in the database, then its not initialised or activated
            if (!isset($this->databaseModules[$name])) continue;

            // If the module is not INITIALISED dont bother...
            // Later on better have a full range of possibilities (adding missing and
            // unitialised). For that a good cleanup in the constant logic and
            // adding a proper array of module states would be nice...
            if ($this->databaseModules[$name]['state'] == XARMOD_STATE_UNINITIALISED) continue;

            if (isset($modinfo['dependency']) &&
                !empty($modinfo['dependency'])) {
                $dependency = $modinfo['dependency'];
            } else {
                $dependency = array();
            }

            foreach ($dependency as $module_id => $conditions) {
                if (is_array($conditions)) {
                    //The module id is in $modId
                    $modId = $module_id;
                } else {
                    //The module id is in $conditions
                    $modId = $conditions;
                }

                //Not dependent, then go to the next dependency!!!
                if ($modId != $regid) continue;

                //If we are here, then it is dependent
                // RECURSIVE CALL                ;
                if (!$this->getalldependents($modinfo['regid'])) {
                    $msg = xarML('Unable to get dependencies for module with ID (#(1)).', $modinfo['regid']);
                    throw new Exception($msg);
                }
            }
        }

        // Get module information
        $modInfo = xarMod::getInfo($regid);

        //TODO: Add version checks later on
        switch ($modInfo['state']) {
            case XARMOD_STATE_ACTIVE:
            case XARMOD_STATE_UPGRADED:  $this->active[] = $modInfo; break;
            case XARMOD_STATE_INACTIVE:
            default:                     $this->initialised[] = $modInfo; break;
        }

        $dependents = array(
                        'active' => $this->active,
                        'initialised' => $this->initialised,
                            );
        return $dependents;
    }

    public function installwithdependencies($regid=null, $phase=0)
    {
        $modInfo = xarMod::getInfo($regid);
        if (!isset($modInfo)) {
            throw new ModuleNotFoundException($regid,'Module (regid: #(1)) does not exist.');
        }

        switch ($modInfo['state']) {
            case XARMOD_STATE_ACTIVE:
            case XARMOD_STATE_UPGRADED: return true;
            case XARMOD_STATE_INACTIVE: $initialised = true; break;
            default:                    $initialised = false; break;
        }

        switch ($phase) {

            case 0:

                // Argument check
                if (!isset($regid)) throw new EmptyParameterException('regid');

                // See if we have lost any modules since last generation
                if (!$this->checkformissing()) {return;}

                // Make xarMod::getInfo not cache anything...
                //We should make a function to handle this or maybe whenever we
                //have a central caching solution...
                $GLOBALS['xarMod_noCacheState'] = true;

                if (!empty($modInfo['extensions'])) {
                    foreach ($modInfo['extensions'] as $extension) {
                        if (!empty($extension) && !extension_loaded($extension)) {
                            throw new ModuleNotFoundException(array($extension,$modInfo['displayname']),
                                                              "Required PHP extension '#(1)' is missing for module '#(2)'");
                        }
                    }
                }

                $dependencies = $modInfo['dependency'];

                if (empty($dependencies)) $dependencies = array();

                $testmod = $this->modulestack->peek();
                if ($regid != $testmod) $this->modulestack->push($regid);
            
                //The dependencies are ok, assuming they shouldnt change in the middle of the
                //script execution.
                foreach ($dependencies as $module_id => $dependency) {
                    if (is_array($dependency)) {
                        //The module id is in $modId
                        $modId = $module_id;
                    } else {
                        //The module id is in $dependency
                        $modId = $dependency;
                    }
                    if (!xarMod::isAvailable(xarMod::getName($modId))) {
                        if (!$this->installwithdependencies($modId)) {
                            $msg = xarML('Unable to initialise dependency module with ID (#(1)).', $modId);
                            throw new Exception($msg);
                        }
                    }
                }

                // Is there an install page?
                if (!$initialised && file_exists(sys::code() . 'modules/' . $modInfo['osdirectory'] . '/xartemplates/includes/installoptions.xt')) {
                    xarResponse::redirect(xarModURL('modules','admin','modifyinstalloptions',array('regid' => $regid)));
                    return true;
                }

            case 1:
                $regid = $this->modulestack->pop();

                //Checks if the module is already initialised
                if (!$initialised) {
                    // Finally, now that dependencies are dealt with, initialize the module
                    if (!xarMod::apiFunc('modules', 'admin', 'initialise', array('regid' => $regid))) {
                        $msg = xarML('Unable to initialise module "#(1)".', $modInfo['displayname']);
                        throw new Exception($msg);
                    }
                }

                // And activate it!
                if (!xarMod::apiFunc('modules', 'admin', 'activate', array('regid' => $regid))) {
                    $msg = xarML('Unable to activate module "#(1)".', $modInfo['displayname']);
                    throw new Exception($msg);
                }

                PropertyRegistration::importPropertyTypes(true, array('modules/' . $modInfo['directory'] . '/xarproperties'));

                $nextmodule = $this->modulestack->peek();
                if (empty($nextmodule)) {
                    // Looks like we're done
                    $this->modulestack->clear();
                    // set the target location (anchor) to go to within the page
                    $target = $modInfo['name'];

                    if (function_exists('xarOutputFlushCached')) {
                        xarOutputFlushCached('base');
                        xarOutputFlushCached('modules');
                        xarOutputFlushCached('base-block');
                    }

                    xarResponse::redirect(xarModURL('modules', 'admin', 'list', array('state' => 0), NULL, $target));
                } else {
                    // Do the next module
                    if (!$this->installwithdependencies($this->modulestack->pop())) return;
                }
                return true;

            default:
                throw new Exception('Unknown install phase...aborting');
        }
    }

    public function deactivatewithdependents ($regid=null)
    {
        // Argument check
        if (!isset($regid)) throw new EmptyParameterException('regid');

        // See if we have lost any modules since last generation
        if (!$this->checkformissing()) { return; }

        // Make xarMod::getInfo not cache anything...
        //We should make a funcion to handle this instead of seeting a global var
        //or maybe whenever we have a central caching solution...
        $GLOBALS['xarMod_noCacheState'] = true;

        // Get module information
        $modInfo = xarMod::getInfo($regid);
        if (!isset($modInfo)) throw new ModuleNotFoundException($regid,'Module (regid: #(1)) does not exist.');


        if ($modInfo['state'] != XARMOD_STATE_ACTIVE &&
            $modInfo['state'] != XARMOD_STATE_UPGRADED) {
            //We shouldnt be here
            //Throw Exception
            $msg = xarML('Module to be deactivated (#(1)) is not active nor upgraded', $modInfo['displayname']);
            throw new Exception($msg);
        }

        $dependents = $this->getalldependents($regid);

        foreach ($dependents['active'] as $active_dependent) {
            if (!xarMod::apiFunc('modules', 'admin', 'deactivate', array('regid' => $active_dependent['regid']))) {
                $msg = xarML('Unable to deactivate module "#(1)".', $active_dependent['displayname']);
                throw new Exception($msg);
            }
        }

        return true;
    }

    function removewithdependents($regid=null)
    {
        xarLogMessage('Removing with dependents');

        // Argument check
        if (!isset($regid)) throw new EmptyParameterException('regid');

        // See if we have lost any modules since last generation
        if (!$this->checkformissing()) {
            xarLogMessage('Missing module since last generation');
            return;
        }

        //Get the dependents list
        $dependents = $this->getalldependents($regid);
        xarLogVariable('dependents',$dependents);

        //Deactivate Actives
        foreach ($dependents['active'] as $active_dependent) {
            if (!xarMod::apiFunc('modules', 'admin', 'deactivate', array('regid' => $active_dependent['regid']))) {
                throw new BadParameterException($active_dependent['displayname'],'Unable to deactivate module "#(1)".');
            }
        }

        //Remove the previously active
        foreach ($dependents['active'] as $active_dependent) {
            if (!xarMod::apiFunc('modules', 'admin', 'remove', array('regid' => $active_dependent['regid']))) {
                throw new BadParameterException($active_dependent['displayname'], 'Unable to remove module "#(1)".');
            }
        }

        //Remove the initialised
        foreach ($dependents['initialised'] as $active_dependent) {
            if (!xarMod::apiFunc('modules', 'admin', 'remove', array('regid' => $active_dependent['regid']))) {
                throw new BadParameterException($active_dependent['displayname'], 'Unable to remove module "#(1)".');
            }
        }

        return true;
    }
}

?>