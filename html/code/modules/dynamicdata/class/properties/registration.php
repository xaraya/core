<?php

/**
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.6.2
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mrb <marcel@xaraya.com>
 */

sys::import('xaraya.services.xar');
use Xaraya\Services\xar;

/**
 * Class to model registration information for a property
 *
 * This corresponds directly to the db info we register for a property.
 *
 */
class PropertyRegistration extends DataContainer
{
    private $stmt;                               // Prepared SQL statement for reuse

    public $id         = 0;                      // id of the property, hardcoded to make things easier
    public $name       = 'propertyType';         // what type of property are we dealing with
    public $desc       = 'Property Description'; // description of this type
    public $label      = 'propertyLabel';        // the label of the property are we dealing with
    public $type       = 1;
    public $parent     = '';                     // this type is derived from?
    public $class      = '';                     // what is the class?
    public $configuration = '';                  // what is its default configuration?
    public $source     = 'dynamic_data';         // what source is default for this type?
    public $reqfiles   = [];                     // do we require some files to be present?
    public $reqmodules = [];                     // do we require some modules to be present?
    public $args       = [];                     // special args needed?
    public $aliases    = [];                     // aliases for this property
    public $filepath   = '';                     // path to the directory where the property lives
    public $template   = '';                     // the template for this property
    public $format     = 0;                      // what format type do we have here?
    // 0 = ? what?
    // 1 =

    public function __construct(ObjectDescriptor $descriptor)
    {
        $args = $descriptor->getArgs();
        if (!empty($args)) {
            foreach ($args as $key => $value) {
                $this->$key = $value;
            }
        }
    }

    public static function clearCache()
    {
        $dbconn = xar::db()->getConn();
        xar::mod()->loadDbInfo('dynamicdata', 'dynamicdata');
        $tables = xar::db()->getTables();
        $sql = "DELETE FROM $tables[dynamic_properties_def]";
        $res = $dbconn->ExecuteUpdate($sql);
        return $res;
    }

    public function getRegistrationInfo(DataProperty $class)
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
    public function Register()
    {
        static $stmt = null;
        static $types = [];

        // Sanity checks (silent)
        foreach ($this->reqfiles as $required) {
            if (!file_exists($required)) {
                return false;
            }
        }

        /*
                foreach($this->reqmodules as $required)
                    if(!xar::mod()->isAvailable($required))
                        return false;
        */
        $dbconn = xar::db()->getConn();
        xar::mod()->loadDbInfo('dynamicdata', 'dynamicdata');
        $tables = xar::db()->getTables();
        $propdefTable = $tables['dynamic_properties_def'];

        // Make sure the db is the same as in the old days
        assert(count($this->reqmodules) <= 1);
        $module_id = empty($this->reqmodules) ? 0 : xar::mod()->getID($this->reqmodules[0]);

        if ($this->format == 0) {
            $this->format = $this->id;
        }

        if (empty($types)) {
            $sql = "SELECT id FROM $tables[dynamic_properties_def]";
            $res = $dbconn->executeQuery($sql);
            while ($res->next()) {
                [$id] = $res->fields;
                $types[] = $id;
            }
        }

        if ($this->stmt == null) {
            $sql = "INSERT INTO $propdefTable
                    (id, name, label,
                     filepath, class,
                     format, configuration, source,
                     reqfiles, modid, args, aliases)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?)";
            $this->stmt = $dbconn->prepareStatement($sql);
        }

        $bindvars = [
            (int) $this->id, $this->name, $this->desc,
            $this->filepath, $this->class,
            $this->format, $this->configuration, $this->source,
            serialize($this->reqfiles), $module_id, is_array($this->args) ? serialize($this->args) : $this->args, serialize($this->aliases),
        ];

        // Ignore if we already have this dataproperty
        if (!in_array($this->id, $types)) {
            $res = $this->stmt->executeUpdate($bindvars);
            $types[] = $this->id;
        } else {
            $res = true;
        }

        if (!empty($this->aliases)) {
            foreach ($this->aliases as $aliasInfo) {
                if (!isset($aliasInfo['filepath'])) {
                    $aliasInfo['filepath'] = $this->filepath;
                }
                if (!isset($aliasInfo['class'])) {
                    $aliasInfo['class'] = $this->class;
                }
                if (!isset($aliasInfo['format'])) {
                    $aliasInfo['format'] = $this->format;
                }
                if (!isset($aliasInfo['reqmodules'])) {
                    $aliasInfo['reqmodules'] = $this->reqmodules;
                }
                // Recursive!!
                $res = $aliasInfo->Register();
            }
        }
        return $res;
    }

    public static function Retrieve()
    {
        if (xar::var()->isCached('DynamicData', 'PropertyTypes')) {
            return xar::var()->getCached('DynamicData', 'PropertyTypes');
        }
        $dbconn = xar::db()->getConn();
        xar::mod()->loadDbInfo('dynamicdata', 'dynamicdata');
        // CHECKME: $tables[modules] is defined in xar::mod()->init()
        if (!xarCore::isLoaded(xarCore::SYSTEM_MODULES)) {
            xar::mod()->loadDbInfo('modules', 'modules');
        }
        $tables = xar::db()->getTables();
        // Sort by required module(s) and then by name
        $query = "SELECT  p.id, p.name, p.label,
                          p.filepath, p.class,
                          p.format, p.configuration, p.source,
                          p.reqfiles, m.name as modname, p.args,
                          p.aliases
                  FROM    $tables[dynamic_properties_def] p LEFT JOIN $tables[modules] m
                  ON      p.modid = m.id
                  ORDER BY m.name, p.name";

        $result = $dbconn->executeQuery($query);
        $proptypes = [];
        if ($result->RecordCount() === 0) {
            $proptypes = self::importPropertyTypes(false);
        } else {
            while ($result->next()) {
                [
                    $id, $name, $label, $filepath, $class, $format,
                    $configuration, $source, $reqfiles, $modname, $args, $aliases
                ] = $result->fields;

                $property['id']             = $id;
                $property['name']           = $name;
                $property['label']          = $label;
                $property['filepath']       = $filepath;
                $property['class']          = $class;
                $property['format']         = $format;
                $property['configuration']  = $configuration;
                $property['source']         = $source;
                $property['dependancies']   = unserialize((string) $reqfiles);
                $property['requiresmodule'] = $modname;
                $property['args']           = $args;
                // TODO: this returns a serialized array of objects, does that hurt?
                try {
                    $property['aliases']        = unserialize($aliases);
                } catch (Exception) {
                    $property['aliases']        = [];
                }
                $proptypes[$id] = $property;
            }
        }
        $result->close();
        xar::var()->setCached('DynamicData', 'PropertyTypes', $proptypes);
        return $proptypes;
    }

    /**
     * Import DataProperty types into the property_types table
     *
     * @param bool $flush
     * @param array<string> $dirs
     * @return array<mixed> an array of the property types currently available
     * @todo flush seems to be unused
     */
    public static function importPropertyTypes($flush = true, $dirs = [])
    {
        xar::log()->notice('DynamicData: Flushing the property cache');
        sys::import('xaraya.structures.relativedirectoryiterator');

        $dbconn = xar::db()->getConn(); // Need this for the transaction
        $propDirs = [];

        // @todo use xarClassMap::getProperties() and filter on $dirs if needed?

        // We do the whole thing, or not at all (given proper db support)
        try {
            $dbconn->begin();

            # --------------------------------------------------------
            #
            # Get the list of properties directories in the active modules
            #
            xar::log()->notice('DynamicData: Searching for property directories');
            if (!empty($dirs) && is_array($dirs)) {
                // We got an array of directories passed in for which to import properties
                // typical usecase: a module which has its own property, during install phase needs that property before
                // the module is active.
                $propDirs = $dirs;
            } else {
                if (!xar::var()->getCached('installer', 'installing')) {
                    // Repopulate the configurations table
                    $tables = xar::db()->getTables();
                    $sql = "DELETE FROM $tables[dynamic_configurations]";
                    $res = $dbconn->ExecuteUpdate($sql);

                    $dat_file = sys::code() . 'modules/dynamicdata/xardata/configurations-dat.xml';
                    $data = ['file' => $dat_file];
                    $objectid = xar::mod()->apiFunc('dynamicdata', 'util', 'import', $data);
                }
                xar::log()->notice('DynamicData: Looking for active modules');
                $activeMods = xar::mod()->apiFunc('modules', 'admin', 'getlist', ['filter' => ['State' => xar::mod()::STATE_ACTIVE]]);
                assert(!empty($activeMods)); // this should never happen
                xar::log()->debug('DynamicData: There are ' . count($activeMods) . ' active modules');

                foreach ($activeMods as $modInfo) {
                    // FIXME: the modinfo directory does NOT end with a /
                    $dir = 'modules/' . $modInfo['osdirectory'] . '/xarproperties';
                    if (file_exists(sys::code() . $dir)) {
                        $propDirs[] = $dir;
                    }

                    // Ignore the next part if this is dynamicdata, as it was already loaded above
                    if ($modInfo['osdirectory'] == 'dynamicdata') {
                        continue;
                    }

                    // If there is a configurations-dat.xml file in this module, then load it now
                    // CHECKME: For more flexibility this could be done through a PropertyInstall class
                    $dir = 'modules/' . $modInfo['osdirectory'] . '/xardata/configurations-dat.xml';
                    if (file_exists(sys::code() . $dir)) {
                        $dat_file = sys::code() . $dir;
                        $data = ['file' => $dat_file];
                        try {
                            $objectid = xar::mod()->apiFunc('dynamicdata', 'util', 'import', $data);
                        } catch (Exception) {
                        }
                    }
                }

                // Clear the cache
                self::clearCache();
            }
            xar::log()->notice('DynamicData: Retrieved the list of directories to be searched');

            # --------------------------------------------------------
            #
            # Get the list of property types in the various properties directories
            # Include each into the environment
            #
            static $loaded = [];
            $proptypes = [];
            $numLoaded = 0;
            foreach ($propDirs as $PropertiesDir) {
                $propertiesdir = sys::code() . $PropertiesDir;
                if (!file_exists($propertiesdir)) {
                    continue;
                }

                $dir = new RelativeDirectoryIterator($propertiesdir);
                // Loop through properties directory
                for ($dir->rewind();$dir->valid();$dir->next()) {
                    if ($dir->isDir()) {
                        continue;
                    } // no dirs
                    if ($dir->getExtension() != 'php') {
                        continue;
                    } // only php files
                    if ($dir->isDot()) {
                        continue;
                    } // temp for emacs insanity and skip hidden files while we're at it

                    // Include the file into the environment
                    $file = $dir->getPathName();
                    if (!isset($loaded[$file])) {
                        // FIXME: later -> include
                        $dp = str_replace('/', '.', substr($PropertiesDir . "/" . basename($file), 0, -4));
                        try {
                            sys::import($dp);
                        } catch (Exception) {
                            throw new Exception(xar::mls()->translate('The file #(1) could not be loaded<br/>', $dp . '.php'));
                        }
                        $loaded[$file] = true;
                    }
                } // loop over the files in a directory
            } // loop over the directories
            xar::log()->notice('DynamicData: Retrieved the list of properties in modules');

            # --------------------------------------------------------
            #
            # Now get the property types in the properties directory
            # Include each into the environment
            #
            $propertiesdir = sys::code() . 'properties/';
            if (!file_exists($propertiesdir)) {
                throw new DirectoryNotFoundException($propertiesdir);
            }

            $dir = new RelativeDirectoryIterator($propertiesdir);
            // Loop through properties directory
            for ($dir->rewind();$dir->valid();$dir->next()) {
                if ($dir->isDot()) {
                    continue;
                } // temp for emacs insanity and skip hidden files while we're at it
                if (!$dir->isDir()) {
                    continue;
                } // only dirs

                // Include the file into the environment
                $file = $dir->getPathName();
                if (!isset($loaded[$file])) {
                    // FIXME: later -> include
                    $dp = str_replace('/', '.', 'properties/' . basename($file) . "/main");
                    try {
                        sys::import($dp);
                    } catch (Exception) {
                        // Die silently for now
                        if (xar::mod()->getVar('debugmode', 'dynamicdata') && xarUser::isDebugAdmin()) {
                            echo xar::mls()->translate('The file #(1) could not be loaded<br/>', $dp . '.php');
                        }
                    }
                    $loaded[$file] = true;
                }
            }
            // We don't need the array of loaded files any more
            unset($loaded);

            xar::log()->notice('DynamicData: Retrieved the list of standalone properties');

            # --------------------------------------------------------
            #
            # Sort the classes into the proper order for installation
            #
            // FIXME: this wont work reliable enough, since we have the static now
            // might as well put this directly after the include above.
            $newClasses = get_declared_classes();

            $classesToSort = [];
            $edges = [];
            foreach ($newClasses as $thisclass) {
                // Just get properties
                if (!is_subclass_of($thisclass, 'DataProperty')) {
                    continue;
                }

                // Ignore installer classes of properties (they are extensions)
                if (str_ends_with($thisclass, 'Install')) {
                    continue;
                }

                // Good class: add it to the array
                $classesToSort[$thisclass] = $thisclass;

                if (property_exists($thisclass, 'deferto')) {
                    $vars = get_class_vars($thisclass);
                    $deferto = $vars['deferto'];
                    if (isset($deferto) && is_array($deferto)) {
                        foreach ($thisclass::$deferto as $defered) {
                            $edges[$defered] = [$defered,$thisclass];
                        }
                    }
                }
            }

            // Remove any deferments where the class is not loaded
            foreach ($edges as $key => $value) {
                if (!isset($classesToSort[$key])) {
                    unset($edges[$key]);
                }
            }

            // Now sort the properties in the order they need to be installed
            $sortedClasses = self::topological_sort($classesToSort, $edges);

            xar::log()->notice('DynamicData: Checked and sorted the property classes to register');

            // Process the sorted classes
            $i = 0;
            foreach ($sortedClasses as $index => $propertyClass) {
                $processedClasses[] = $propertyClass;

                // Main part
                // Call the class method on each property to get the registration info
                //if (!is_callable(array($propertyClass,'getRegistrationInfo'))) continue;
                $descriptor = new ObjectDescriptor([]);
                $baseInfo = new PropertyRegistration($descriptor);
                try {
                    /** @var DataProperty $property */
                    $property = new $propertyClass($descriptor);
                    xar::log()->debug('DynamicData: Registering the property ' . $propertyClass);
                } catch (Exception $e) {
                    xar::log()->debug('DynamicData: The property ' . $propertyClass . ' could not be instantiated');
                    throw new Exception(xar::mls()->translate('The property #(1) could not be instantiated. #(2)', $propertyClass, $e->getMessage()));
                }
                if (empty($property->id)) {
                    continue;
                }   // Don't register the base property
                $baseInfo->getRegistrationInfo($property);

                // If we are adding properties from specific dirs, only look for those
                // FIXME: the dirs should *always* be passed as an array (random)
                if (!empty($dirs) && is_array($dirs) && !in_array($baseInfo->filepath, $dirs)) {
                    continue;
                }
                if (!empty($dirs) && !is_array($dirs) && ($baseInfo->filepath != $dirs)) {
                    continue;
                }

                // Fill in the info we don't have in the registration class yet
                // TODO: see if we can have it in the registration class
                $baseInfo->class = $propertyClass;
                if ($property->filepath == 'auto') {
                    $baseInfo->filepath = 'properties/' . $baseInfo->name . '/main.php';
                } else {
                    $baseInfo->filepath = $property->filepath . '/' . $baseInfo->name . '.php';
                }
                $currentproptypes[$baseInfo->id] = $baseInfo;
                $proptypes[$baseInfo->id] = $baseInfo->getPublicProperties();

                // Check for aliases
                $aliases = $property->aliases();
                if (!empty($aliases)) {
                    // Each alias is also a propertyRegistration object
                    foreach ($aliases as $alias) {
                        $descriptor = new ObjectDescriptor($alias);
                        $aliasInfo = new PropertyRegistration($descriptor);
                        $aliasInfo->class = $propertyClass;
                        if ($property->filepath == 'auto') {
                            $aliasInfo->filepath = 'properties/' . $property->name . '/main.php';
                        } else {
                            $aliasInfo->filepath = $property->filepath . '/' . $property->name . '.php';
                        }
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

                // Configuring each property type
                if (xar::var()->getCached('installer', 'installing') === true) {
                    // We don't need this when installing Xaraya
                    // This saves a lot of db calls
                    continue;
                } else {
                    // Run the install function if it exists
                    self::installproperty($baseInfo->name);
                }
            } // next property class in the same file
            $dbconn->commit();
        } catch (Exception $e) {
            // TODO: catch more specific exceptions than all?
            $dbconn->rollback();
            throw $e;
        }

        // Clear the property types from cached memory
        xar::var()->delCached('DynamicData', 'PropertyTypes');

        // Sort the property types
        ksort($proptypes);
        xar::log()->notice('DynamicData: Property cache successfully flushed');
        return $proptypes;
    }

    public static function installproperty($propertyname)
    {
        $frommodule = !file_exists(sys::code() . 'properties/' . $propertyname . '/install.php');
        $class = ucfirst($propertyname) . 'PropertyInstall';
        if ($frommodule) {
            // Assume this is a property in a module
            if (class_exists($class)) {
                $descriptor = new DataObjectDescriptor();
                $installer = new $class($descriptor);
                $installer->install();
            }
        } else {
            // Assume this is a standalone property in the properties directory
            if (!class_exists($class)) {
                sys::import('properties.' . $propertyname . '.install');
                $descriptor = new DataObjectDescriptor();
                $installer = new $class($descriptor);
            } else {
                $descriptor = new DataObjectDescriptor();
                $installer = new $class($descriptor);
            }
            $installer->install();
        }
    }

    // Taken from http://www.calcatraz.com/blog/php-topological-sort-function-384
    private static function topological_sort($nodeids, $edges)
    {
        $L = $S = $nodes = [];
        foreach ($nodeids as $id) {
            $nodes[$id] = ['in' => [], 'out' => []];
            foreach ($edges as $e) {
                if ($id == $e[0]) {
                    $nodes[$id]['out'][] = $e[1];
                }
                if ($id == $e[1]) {
                    $nodes[$id]['in'][] = $e[0];
                }
            }
        }
        foreach ($nodes as $id => $n) {
            if (empty($n['in'])) {
                $S[] = $id;
            }
        }
        while (!empty($S)) {
            $L[] = $id = array_shift($S);
            foreach ($nodes[$id]['out'] as $m) {
                $nodes[$m]['in'] = array_diff($nodes[$m]['in'], [$id]);
                if (empty($nodes[$m]['in'])) {
                    $S[] = $m;
                }
            }
            $nodes[$id]['out'] = [];
        }
        foreach ($nodes as $n) {
            if (!empty($n['in']) or !empty($n['out'])) {
                return null; // not sortable as graph is cyclic
            }
        }
        return $L;
    }
}
