<?php
/**
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata
 * @link http://xaraya.com/index.php/release/182.html
 * @author mrb <marcel@xaraya.com>
 */
xarMod::loadDbInfo('dynamicdata','dynamicdata');
/**
 * Class to model registration information for a property
 *
 * This corresponds directly to the db info we register for a property.
 *
 */
class PropertyRegistration extends DataContainer
{
    public $id         = 0;                      // id of the property, hardcoded to make things easier
    public $name       = 'propertyType';         // what type of property are we dealing with
    public $desc       = 'Property Description'; // description of this type
    public $label      = 'propertyLabel';        // the label of the property are we dealing with
    public $type       = 1;
    public $parent     = '';                     // this type is derived from?
    public $class      = '';                     // what is the class?
    public $configuration = '';                     // what is its default configuration?
    public $source     = 'dynamic_data';         // what source is default for this type?
    public $reqfiles   = array();                // do we require some files to be present?
    public $reqmodules = array();                // do we require some modules to be present?
    public $args       = array();                // special args needed?
    public $aliases    = array();                // aliases for this property
    public $filepath   = '';                     // path to the directory where the property lives
    public $format     = 0;                      // what format type do we have here?
                                                 // 0 = ? what?
                                                 // 1 =

    function __construct(ObjectDescriptor $descriptor)
    {
        $args = $descriptor->getArgs();
        if (!empty($args)) {
            foreach($args as $key=>$value) {
                $this->$key = $value;
            }
        }
    }

    static function clearCache()
    {
        $dbconn = xarDB::getConn();
        $tables = xarDB::getTables();
        $sql = "DELETE FROM $tables[dynamic_properties_def]";
        $res = $dbconn->ExecuteUpdate($sql);
        return $res;
    }

    function getRegistrationInfo(Object $class)
    {
        $this->id   = $class->id;
        $this->name = $class->name;
        $this->desc = $class->desc;
        $this->reqmodules = $class->reqmodules;
        $this->args = $class->args;
        $this->filepath = $class->filepath;
        $this->template = $class->template;
        return $this;
    }

    /**
     * Register a DataProperty in the database
     */
    function Register()
    {
        static $stmt = null;
        static $types = array();

        // Sanity checks (silent)
        foreach($this->reqfiles as $required)
            if(!file_exists($required))
                return false;

/*
        foreach($this->reqmodules as $required)
            if(!xarModIsAvailable($required))
                return false;
*/
        $dbconn = xarDB::getConn();
        $tables = xarDB::getTables();
        $propdefTable = $tables['dynamic_properties_def'];

        // Make sure the db is the same as in the old days
        assert('count($this->reqmodules)==1; /* The reqmodules registration should only contain the name of the owning module */');
        $modInfo = xarMod::getBaseInfo($this->reqmodules[0]);
        $modId = $modInfo['systemid'];

        if($this->format == 0) $this->format = $this->id;

        if (empty($types)) {
            $sql = "SELECT id FROM $tables[dynamic_properties_def]";
            $res = $dbconn->executeQuery($sql);
            while($res->next()) {
                list($id) = $res->fields;
                $types[] = $id;
            }
        }
        
        $sql = "INSERT INTO $propdefTable
                (id, name, label,
                 parent, filepath, class,
                 format, configuration, source,
                 reqfiles, modid, args, aliases)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)";
        if(!isset($stmt))
            $stmt = $dbconn->prepareStatement($sql);

        $bindvars = array(
            (int) $this->id, $this->name, $this->desc,
            $this->parent, $this->filepath, $this->class,
            $this->format, $this->configuration, $this->source,
            serialize($this->reqfiles), $modId, is_array($this->args) ? serialize($this->args) : $this->args, serialize($this->aliases)
        );

        // Ignore if we already have this dataproperty
        if (!in_array($this->id, $types)) {
            $res = $stmt->executeUpdate($bindvars);
            $types[] = $this->id;
        } else {
            $res = true;
        }

        if(!empty($this->aliases))
        {
            foreach($this->aliases as $aliasInfo)
            {
                if (!isset($aliasInfo['filepath'])) $aliasInfo['filepath'] = $this->filepath;
                if (!isset($aliasInfo['class'])) $aliasInfo['class'] = $this->class;
                if (!isset($aliasInfo['format'])) $aliasInfo['format'] = $this->format;
                if (!isset($aliasInfo['reqmodules'])) $aliasInfo['reqmodules'] = $this->reqmodules;
                // Recursive!!
                $res = $aliasInfo->Register();
            }
        }
        return $res;
    }

    static function Retrieve()
    {
        if(xarCore::isCached('DynamicData','PropertyTypes')) {
            return xarCore::getCached('DynamicData','PropertyTypes');
        }
        $dbconn = xarDB::getConn();
        $tables = xarDB::getTables();
        // Sort by required module(s) and then by name
        $query = "SELECT  p.id, p.name, p.label,
                          p.parent, p.filepath, p.class,
                          p.format, p.configuration, p.source,
                          p.reqfiles, m.name, p.args,
                          p.aliases
                  FROM    $tables[dynamic_properties_def] p INNER JOIN $tables[modules] m
                  ON      p.modid = m.id
                  ORDER BY m.name, p.name";
        $result = $dbconn->executeQuery($query);
        $proptypes = array();
        if($result->RecordCount() == 0 ) {
            $proptypes = self::importPropertyTypes(false);
        } else {
            while($result->next())
            {
                list(
                    $id,$name,$label,$parent,$filepath,$class,$format,
                    $configuration,$source,$reqfiles,$modname,$args,$aliases
                ) = $result->fields;

                $property['id']             = $id;
                $property['name']           = $name;
                $property['label']          = $label;
                $property['format']         = $format;
                $property['filepath']       = $filepath;
                $property['configuration']  = $configuration;
                $property['source']         = $source;
                $property['dependancies']   = unserialize($reqfiles);
                $property['requiresmodule'] = $modname;
                $property['args']           = $args;
                $property['class']          = $class;
                // TODO: this return a serialized array of objects, does that hurt?
                $property['aliases']        = unserialize($aliases);

                $proptypes[$id] = $property;
            }
        }

        xarCore::setCached('DynamicData','PropertyTypes',$proptypes);

        return $proptypes;
    }

    /**
     * Import DataProperty types into the property_types table
     *
     * @param bool $flush
     * @param array dirs
     * @return array an array of the property types currently available
     * @todo flush seems to be unused
     */
    static public function importPropertyTypes($flush = true, $dirs = array())
    {
        sys::import('xaraya.structures.relativedirectoryiterator');

        $dbconn = xarDB::getConn(); // Need this for the transaction
        $propDirs = array();

        // We do the whole thing, or not at all (given proper db support)
        try {
             $dbconn->begin();

            if (!empty($dirs) && is_array($dirs)) {
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
                    $dir = 'modules/' .$modInfo['osdirectory'] . '/xarproperties';
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

                $dir = new RelativeDirectoryIterator($PropertiesDir);
                // Loop through properties directory
                for ($dir->rewind();$dir->valid();$dir->next()) {
                    if ($dir->isDir()) continue; // no dirs
                    if ($dir->getExtension() != 'php') continue; // only php files
                    if ($dir->isDot()) continue; // temp for emacs insanity and skip hidden files while we're at it

                    // Include the file into the environment
                    $file = $dir->getPathName();
                    if (!isset($loaded[$file])) {
                        // FIXME: later -> include
                        $dp = str_replace('/','.',substr($PropertiesDir . "/" . basename($file),0,-4));
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

                if (!is_subclass_of($propertyClass, 'DataProperty')) {;continue;}
                $processedClasses[] = $propertyClass;

                // Main part
                // Call the class method on each property to get the registration info
                if (!is_callable(array($propertyClass,'getRegistrationInfo'))) continue;
                $descriptor = new ObjectDescriptor(array());
                $baseInfo = new PropertyRegistration($descriptor);
                $property = new $propertyClass($descriptor);
                if (empty($property->id)) continue;   // Don't register the base property
                $baseInfo->getRegistrationInfo($property);
                
                // If we are adding properties from specific dirs, only look for those
                // FIXME: the dirs should *always* be passed as an array (random)
                if (!empty($dirs) && is_array($dirs) && !in_array($baseInfo->filepath,$dirs)) continue;
                if (!empty($dirs) && !is_array($dirs) && ($baseInfo->filepath != $dirs)) continue;

                // Fill in the info we dont have in the registration class yet
                // TODO: see if we can have it in the registration class
                $baseInfo->class = $propertyClass;
                $baseInfo->filepath = $property->filepath . '/' . $baseInfo->name . '.php';
                $currentproptypes[$baseInfo->id] = $baseInfo;
                $proptypes[$baseInfo->id] = $baseInfo->getPublicProperties();

                // Check for aliases
                $aliases = $property->aliases();
                if (!empty($aliases)) {
                    // Each alias is also a propertyRegistration object
                    foreach($aliases as $alias) {
                        $descriptor = new ObjectDescriptor($alias);
                        $aliasInfo = new PropertyRegistration($descriptor);
                        $aliasInfo->class = $propertyClass;
                        $aliasInfo->filepath = $property->filepath .'/'. $property->name . '.php';
                        $currentproptypes[$aliasInfo->id] = $aliasInfo;
                        $proptypes[$aliasInfo->id] = $aliasInfo->getPublicProperties();
                    }
                }

                // Update database entry for this property
                // This will also do the aliases
                // TODO: check the result, now silent failure
                foreach ($currentproptypes as $proptype) {
                    $registered = $proptype->Register();
                }
                unset($currentproptypes);
            } // next property class in the same file
            $dbconn->commit();
        } catch(Exception $e) {
            // TODO: catch more specific exceptions than all?
            $dbconn->rollback();
            throw $e;
        }

        // Sort the property types
        ksort($proptypes);
        return $proptypes;
    }
}
?>
