<?php
/**
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */

/**
 * Check the properties directory for properties and import them into the Property Type table.
 *
 * @param $args['flush'] flush the property type table before import true/false (optional)
 * @return array an array of the property types currently available
 * @throws BAD_PARAM, NO_PERMISSION
 */
class PropertyDirectoryIterator extends DirectoryIterator
{
    public function __construct($file)
    {
        parent::__construct(realpath($file));
    }

    public function getExtension()
    {
        $filename = $this->GetFilename();
        $extension = strrpos($filename, ".", 1) + 1;
        if ($extension != false)
            return strtolower(substr($filename, $extension, strlen($filename) - $extension));
        else
            return "";
    }
}

function dynamicdata_adminapi_importpropertytypes( $args )
{
    extract( $args );
    $dbconn =& xarDBGetConn(); // Need this for the transaction
    $propDirs = array();

    // We do the whole thing, or not at all (given proper db support)
    try {
        $dbconn->begin();

        if(isset($dirs) && is_array($dirs)) {
            // We got an array of directories passed in for which to import properties
            // typical usecase: a module which has its own property, during install phase needs that property before
            // the module is active.
            $propDirs = $dirs;
        } else {
            // Clear the cache
            PropertyRegistration::ClearCache();

            $activeMods = xarModApiFunc('modules','admin','getlist', array('filter' => array('State' => XARMOD_STATE_ACTIVE)));
            assert('!empty($activeMods)'); // this should never happen

            foreach($activeMods as $modInfo) {
                // FIXME: the modinfo directory does NOT end with a /
                $dir = 'modules/' .$modInfo['osdirectory'] . '/xarproperties/';
                if(file_exists($dir)){
                    $propDirs[] = $dir;
                }
            }
        }

        // Get list of properties in properties directories
        static $loaded = array();
        $proptypes = array(); $numLoaded = 0;
        foreach($propDirs as $PropertiesDir) {
            if (!file_exists($PropertiesDir)) continue;
            // The iterator takes an absolute directory, so we use a slightly extended class
            $dir = new PropertyDirectoryIterator($PropertiesDir);
            // Loop through properties directory
            for($dir->rewind();$dir->valid();$dir->next()) {
                if($dir->isDir()) continue; // no dirs
                if($dir->getExtension() != 'php') continue; // only php files
                if($dir->isDot()) continue; // temp for emacs insanity and skip hidden files while we're at it

                // Include the file into the environment
                $file = $dir->getPathName();
                if(!isset($loaded[$file])) {
                    // FIXME: later -> include
                    $dp = str_replace('/','.',substr($PropertiesDir.basename($file),0,-4));
                    sys::import($dp);
                    $loaded[$file] = true;
                }
            } // loop over the files in a directory
        } // loop over the directories

        // FIXME: this wont work reliable enough, since we have the static now
        // might as well put this directly after the include above.
        $newClasses = get_declared_classes();

        // See what class(es) we have here
        foreach($newClasses as $index => $propertyClass) {
            // If it doesnt exist something weird is goin on

            if(!is_subclass_of ($propertyClass, 'DataProperty')) {;continue;}
            $processedClasses[] = $propertyClass;

            // Main part
            // Call the class method on each property to get the registration info
            if (!is_callable(array($propertyClass,'getRegistrationInfo'))) continue;
            $baseInfo = new PropertyRegistration(array());
            $property = new $propertyClass(array());
            if (empty($property->id)) continue;   // Don't register the base property
            $baseInfo->getRegistrationInfo($property);
            // Fill in the info we dont have in the registration class yet
            // TODO: see if we can have it in the registration class
            $baseInfo->class = $propertyClass;
            $baseInfo->filepath = $property->filepath . '/' . $baseInfo->name . '.php';
            $currentproptypes[$baseInfo->id] = $baseInfo;
            $proptypes[$baseInfo->id] = $baseInfo;

             // Check for aliases
             $aliases = $property->aliases();
            if(!empty($aliases)) {
                // Each alias is also a propertyRegistration object
                foreach($aliases as $alias) {
                    $aliasInfo = new PropertyRegistration($alias);
                    $aliasInfo->class = $propertyClass;
                    $aliasInfo->filepath = $property->filepath .'/'. $property->name . '.php';
                    $currentproptypes[$aliasInfo->id] = $aliasInfo;
                    $proptypes[$aliasInfo->id] = $aliasInfo;
                }
            }

            // Update database entry for this property
            // This will also do the aliases
            // TODO: check the result, now silent failure
            foreach ($currentproptypes as $proptype) $registered = $proptype->Register();
            unset($currentproptypes);
        } // next property class in the same file
        $dbconn->commit();
    } catch(Exception $e) {
        // TODO: catch more specific exceptions than all?
        $dbconn->rollback();
        throw $e;
    }

    // Sort the property types
    ksort( $proptypes );
    return $proptypes;
}
?>
