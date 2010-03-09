<?php
/**
 * Module insatller
 *
 * @package modules
 * @copyright see the html/credits.html file in this release
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
    private $extType                  = 'modules';
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

    public $fileExtensions            = array();
    public $databaseExtensions        = array();

    protected function __construct($type = 'modules')
    {
        $this->extType = $type;
        if ($this->extType == 'themes') {
            $this->fileExtensions = xarMod::apiFunc('themes','admin','getfilethemes');
            $this->databaseExtensions = xarMod::apiFunc('themes','admin','getdbthemes');
        } else {
            $this->fileExtensions = xarMod::apiFunc('modules','admin','getfilemodules');
            $this->databaseExtensions = xarMod::apiFunc('modules','admin','getdbmodules');
        }
        // FIXME do something else here
        if (empty($this->fileExtensions)) throw new ModuleNotFoundException();
        if (empty($this->databaseExtensions)) throw new ModuleNotFoundException();

        sys::import('xaraya.structures.sequences.stack');
        $this->modulestack = new Stack();
    }

    public static function getInstance($type = 'modules')
    {
        if (null === self::$instance) {
            self::$instance = new self($type);
        }
        return self::$instance;
    }
    
    public function checkformissing()
    {
        if ($this->dependencieschecked) {return true;}

        foreach ($this->databaseExtensions as $name => $extInfo) {
            if (empty($this->fileExtensions[$name])) {

                // Get module ID
                $regId = $extInfo['regid'];
                // Set state of module to 'missing'
                switch ($extInfo['state']) {
                    case XARMOD_STATE_UNINITIALISED: $newstate = XARMOD_STATE_MISSING_FROM_UNINITIALISED; break;
                    case XARMOD_STATE_INACTIVE:      $newstate = XARMOD_STATE_MISSING_FROM_INACTIVE; break;
                    case XARMOD_STATE_ACTIVE:        $newstate = XARMOD_STATE_MISSING_FROM_ACTIVE; break;
                    case XARMOD_STATE_UPGRADED:      $newstate = XARMOD_STATE_MISSING_FROM_UPGRADED; break;
                }
                if (isset($newstate)) {
                    $set = xarMod::apiFunc($this->extType, 'admin', 'setstate',
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
        $extInfo = xarMod::getInfo($regid);
        if (!isset($extInfo)) throw new ModuleBaseInfoNotFoundException("with regid $regid");

        // See if we have lost any modules since last generation
        if (!$this->checkformissing()) throw new ModuleNotFoundException();

        $dbMods = array();

        //Find the modules which are active (should upgraded be added too?)
        foreach ($this->databaseExtensions as $name => $dbInfo) {
            if (($dbInfo['state'] != XARMOD_STATE_MISSING_FROM_UNINITIALISED) && ($dbInfo['state'] < XARMOD_STATE_MISSING_FROM_INACTIVE))
            {
                $dbMods[$dbInfo['regid']] = $dbInfo;
            }
        }

        if (!empty($extInfo['extensions'])) {
            foreach ($extInfo['extensions'] as $extension) {
                if (!empty($extension) && !extension_loaded($extension)) {
                    $msg = xarML("Required PHP extension '#(1)' is missing for module '#(2)'", $extension, $extInfo['displayname']);
                    throw new Exception($msg);
                }
            }
        }

        $dependency = $extInfo['dependency'];
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
            $extInfo = xarMod::getInfo($regid);
        } catch (NotFoundExceptions $e) {
            //Add this module to the unsatisfiable list
            $this->unsatisfiable[] = $regid;
            //Return now, we cant find more info about this module
            return true;
        }

        if (!empty($extInfo['extensions'])) {
            foreach ($extInfo['extensions'] as $extension) {
                if (!empty($extension) && !extension_loaded($extension)) {
                    //Add this extension to the unsatisfiable list
                    $this->unsatisfiable[] = $extension;
                }
            }
        }

        $dependency = $extInfo['dependency'];
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
            $this->unsatisfiable[] = $extInfo;
        } elseif (count($this->satisfiable)) {
            //Then this module is satisfiable too
            //As if it were initialised, then all dependencies would have
            //to be already satisfied
            $this->satisfiable[] = $extInfo;
        } else {
            //Then this module is at least satisfiable
            //Depends if it is already initialised or not

            //TODO: Add version checks later on
            // Add a new state in the dependency array for version
            // So that we can present that nicely in the gui...

            switch ($extInfo['state']) {
                case XARMOD_STATE_ACTIVE:
                case XARMOD_STATE_UPGRADED:      $this->satisfied[] = $extInfo; break;
                case XARMOD_STATE_INACTIVE:
                case XARMOD_STATE_UNINITIALISED: $this->satisfiable[] = $extInfo; break;
                default:                         $this->unsatisfiable[] = $extInfo; break;
            }
        }
        $dependencies = array(
                            'satisfied'     => $this->satisfied,
                            'satisfiable'   => $this->satisfiable,
                            'unsatisfiable' => $this->unsatisfiable,
                            );
        return $dependencies;
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

        foreach ($this->fileExtensions as $name => $extInfo) {

            // If the module is not in the database, then its not initialised or activated
            if (!isset($this->databaseExtensions[$name])) continue;

            // If the module is not INITIALISED dont bother...
            // Later on better have a full range of possibilities (adding missing and
            // unitialised). For that a good cleanup in the constant logic and
            // adding a proper array of module states would be nice...
            if ($this->databaseExtensions[$name]['state'] == XARMOD_STATE_UNINITIALISED) continue;

            if (isset($extInfo['dependency']) &&
                !empty($extInfo['dependency'])) {
                $dependency = $extInfo['dependency'];
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
                if (!$this->getalldependents($extInfo['regid'])) {
                    $msg = xarML('Unable to get dependencies for module with ID (#(1)).', $extInfo['regid']);
                    throw new Exception($msg);
                }
            }
        }

        // Get module information
        $extInfo = xarMod::getInfo($regid);

        //TODO: Add version checks later on
        switch ($extInfo['state']) {
            case XARMOD_STATE_ACTIVE:
            case XARMOD_STATE_UPGRADED:  $this->active[] = $extInfo; break;
            case XARMOD_STATE_INACTIVE:
            default:                     $this->initialised[] = $extInfo; break;
        }

        $dependents = array(
                        'active' => $this->active,
                        'initialised' => $this->initialised,
                            );
        return $dependents;
    }

    public function installmodule($regid=null)
    {
        if ($this->extType == 'modules') $this->assembledependencies($regid);
        $this->installdependencies($regid);
    }
    
    public function assembledependencies($regid=null)
    {
        $extInfo = xarMod::getInfo($regid);
        if (!isset($extInfo)) {
            throw new ModuleNotFoundException($regid,'Module (regid: #(1)) does not exist.');
        }

        // Argument check
        if (!isset($regid)) throw new EmptyParameterException('regid');

        // See if we have lost any modules since last generation
        if (!$this->checkformissing()) {return;}

        // Make xarMod::getInfo not cache anything...
        //We should make a function to handle this or maybe whenever we
        //have a central caching solution...
        $GLOBALS['xarMod_noCacheState'] = true;

        if (!empty($extInfo['extensions'])) {
            foreach ($extInfo['extensions'] as $extension) {
                if (!empty($extension) && !extension_loaded($extension)) {
                    throw new ModuleNotFoundException(array($extension,$extInfo['displayname']),
                                                      "Required PHP extension '#(1)' is missing for module '#(2)'");
                }
            }
        }

        $dependencies = $extInfo['dependency'];
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
                if (!$this->assembledependencies($modId)) {
                    $msg = xarML('Unable to initialise dependency module with ID (#(1)).', $modId);
                    throw new Exception($msg);
                }
            }
        }
        return true;
    }

    public function installdependencies($regid)
    {
        $topid = $this->modulestack->peek();
        
        if ($this->extType == 'themes'){
            $extInfo = xarThemeGetInfo($regid);
            if (!isset($extInfo)) {
                throw new ThemeNotFoundException($regid,'Theme (regid: #(1)) does not exist.');
            }
        } else {
            $extInfo = xarMod::getInfo($regid);
            if (!isset($extInfo)) {
                throw new ModuleNotFoundException($regid,'Module (regid: #(1)) does not exist.');
            }
        }

        switch ($extInfo['state']) {
            case XARMOD_STATE_ACTIVE:
            case XARMOD_STATE_UPGRADED: return true;
            case XARMOD_STATE_INACTIVE: $initialised = true; break;
            default:                    $initialised = false; break;
        }

        if ($regid == $topid) {
            // First time we've come to this module
            $regid = $this->modulestack->pop();
            // Is there an install page?
            if (!$initialised && file_exists(sys::code() . 'modules/' . $extInfo['osdirectory'] . '/xartemplates/includes/installoptions.xt')) {
                xarResponse::redirect(xarModURL('modules','admin','modifyinstalloptions',array('regid' => $regid)));
                return true;
            }
        }

        //Checks if the extension is already initialised
        if (!$initialised) {
            // Finally, now that dependencies are dealt with, initialize the module
            if (!xarMod::apiFunc($this->extType, 'admin', 'initialise', array('regid' => $regid))) {
                $msg = xarML('Unable to initialise extension "#(1)".', $extInfo['displayname']);
                throw new Exception($msg);
            }
        }

        // And activate it!
        if (!xarMod::apiFunc($this->extType, 'admin', 'activate', array('regid' => $regid))) {
            $msg = xarML('Unable to activate extension "#(1)".', $extInfo['displayname']);
            throw new Exception($msg);
        }

        // if this is a theme we're done
        if ($this->extType == 'themes') {
            xarResponse::redirect(xarModURL($this->extType, 'admin', 'list', array('state' => 0)));
            return true;
        }
        
        PropertyRegistration::importPropertyTypes(true, array('modules/' . $extInfo['directory'] . '/xarproperties'));

        $nextmodule = $this->modulestack->peek();
        if (empty($nextmodule)) {
            // Looks like we're done
            $this->modulestack->clear();
            // set the target location (anchor) to go to within the page
            $target = $extInfo['name'];

            if (function_exists('xarOutputFlushCached')) {
                xarOutputFlushCached('base');
                xarOutputFlushCached('modules');
                xarOutputFlushCached('base-block');
            }

            xarResponse::redirect(xarModURL($this->extType, 'admin', 'list', array('state' => 0), NULL, $target));
        } else {
            // Do the next module
            if (!$this->installdependencies($nextmodule)) return;
        }
        return true;
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
        $extInfo = xarMod::getInfo($regid);
        if (!isset($extInfo)) throw new ModuleNotFoundException($regid,'Module (regid: #(1)) does not exist.');


        if ($extInfo['state'] != XARMOD_STATE_ACTIVE &&
            $extInfo['state'] != XARMOD_STATE_UPGRADED) {
            //We shouldnt be here
            //Throw Exception
            $msg = xarML('Module to be deactivated (#(1)) is not active nor upgraded', $extInfo['displayname']);
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