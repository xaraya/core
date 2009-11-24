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
 
class Installer
{
    private $dependencieschecked      = false;
    private $moduleschecked           = array();

    protected static $instance        = null;
    protected static $unsatisfiable   = array();
    protected static $satisfiable     = array();
    protected static $satisfied       = array();

    public $fileModules               = array();
    public $databaseModules           = array();

    protected function __construct()
    {
        $this->fileModules = xarMod::apiFunc('modules','admin','getfilemodules');
        $this->databaseModules = xarMod::apiFunc('modules','admin','getdbmodules');
        // FIXME do something else here
        if (empty($this->databaseModules)) throw new ModuleNotFoundException();
        if (empty($this->fileModules)) throw new ModuleNotFoundException();
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
        if (!isset($regid)) throw new EmptyParameterException('mainId');

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
        //use some hack or something to set the modules as initialized/active
        //without its proper dependencies
        if (count($this->unsatisfiable)) {
            //Then this module is unsatisfiable too
            $this->unsatisfiable[] = $modInfo;
        } elseif (count($this->satisfiable)) {
            //Then this module is satisfiable too
            //As if it were initialized, then all depdencies would have
            //to be already satisfied
            $this->satisfiable[] = $modInfo;
        } else {
            //Then this module is at least satisfiable
            //Depends if it is already initialized or not

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

        return $dependency_array;
    }
}

?>