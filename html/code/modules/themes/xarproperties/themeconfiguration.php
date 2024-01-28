<?php
/**
 * Include the base class
 */
sys::import('modules.base.xarproperties.textbox');

/**
 * Handle the configuration property
 */
/**
 * The theme configuration property holds configuration settings for a theme
 *
 * @package modules\themes
 * @subpackage themes
 * @copyright see the html/credits.html file in this release
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/70.html
 * @author mikespub
 * @todo Remove this property? It has outlived its usefulness?
 */
class ThemeConfigurationProperty extends TextBoxProperty
{
    public $id         = 30107;
    public $name       = 'themeconfiguration';
    public $desc       = 'Theme Configuration';
    public $reqmodules = array('themes');

    /** @var int */
    public $theme_id;   // The regid of the theme this property belongs to

    public function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->filepath   = 'modules/themes/xarproperties';
        $this->tplmodule  = 'themes';
        $this->template   = 'themeconfiguration';
        // Make sure we get an object reference so we can get the theme ID value
        $this->include_reference = 1;
    }

    /**
     * Get the value of a textbox from a web page
     *
     * @param  string $name The name of the textbox
     * @param  string $value The value of the textbox
     * @return bool|void  Returns true
     */
    public function checkInput($name = '', $value = null)
    {
        $name = !empty($name) ? $name : $this->propertyprefix . $this->id;
        if (!xarVar::fetch($name, 'isset', $configuration, null, xarVar::NOT_REQUIRED)) {
            return;
        }
        $this->value = serialize($configuration);
        return true;
    }

    /**
     * Display a textbox for input
     *
     * @param array<string, mixed> $data An array of input parameters
     * @return string     HTML markup to display the property for input on a web page
     */
    public function showInput(array $data = array())
    {
        // set theme regid the from object reference (= theme_configuration) if possible
        if (!empty($this->objectref) && !empty($this->objectref->properties['regid'])) {
            $this->theme_id = $this->objectref->properties['regid']->value;
            $data['theme_id'] = $this->theme_id;
        }

        // Get the configuration of this theme and parse it
        $this->parseConfiguration($this->value);

        $data['configs'] = $this->configuration;
        return parent::showInput($data);
    }

    /**
     * Display a textbox for output
     *
     * @param array<string, mixed> $data An array of input parameters
     * @return string     HTML markup to display the property for output on a web page
     */
    public function showOutput(array $data = array())
    {
        // set theme regid the from object reference (= theme_configuration) if possible
        if (!empty($this->objectref) && !empty($this->objectref->properties['regid'])) {
            $this->theme_id = $this->objectref->properties['regid']->value;
            $data['theme_id'] = $this->theme_id;
        }

        // Get the configuration of this theme and parse it
        $this->parseConfiguration($this->value);

        $data['configs'] = $this->configuration;
        return parent::showOutput($data);
    }

    /**
     * Parse the configuration rule
     *
     * @param string|array<mixed> $configuration
     * @return array<string, mixed>
     */
    public function parseConfiguration($configuration = '')
    {
        if (is_array($configuration)) {
            $fields = $configuration;
        } elseif (empty($configuration)) {
            $fields = array();
            // try normal serialized configuration
        } else {
            try {
                $fields = unserialize($configuration);
            } catch (Exception $e) {
                return [];
            }
        }
        // Now match the parsed configurationproperties to those defined in the theme
        $properties = $this->getThemeConfigurations();
        $this->configuration = array();
        foreach ($properties as $name => $configarg) {
            if (isset($fields[$name])) {
                $configarg['value'] = $fields[$name];
            } else {
                $configarg['value'] = null;
            }
            $this->configuration[$name] = $configarg;
        }
        return $this->configuration;
    }

    /**
     * Get the configuration for the theme property type
     * @return array<string, mixed>
     */
    public function getThemeConfigurations()
    {
        // cache configuration for all properties
        if (xarCoreCache::isCached('Themes', 'Configurations')) {
            $allconfigurations = xarCoreCache::getCached('Themes', 'Configurations');
        } else {
            sys::import('xaraya.structures.query');
            xarMod::load('themes');
            $tables =  xarDB::getTables();
            $q = new Query('SELECT', $tables['themes_configurations']);
            $c[] = $q->peq('theme_id', $this->theme_id);
            $c[] = $q->peq('theme_id', 0);
            $q->qor($c);
            $q->run();
            $allconfigurations = array();
            foreach ($q->output() as $row) {
                $row['applies'] = 0;
                $allconfigurations[$row['name']] = $row;
            }

            sys::import('modules.themes.class.configurations');
            $config = new Configurations();
            $info = xarMod::getInfo($this->theme_id, 'theme');

            // Get the theme specific options being used in the theme
            $var_re = "!xarThemeVars::get\(\s*[\"|\']" . $info['name'] . "[\"|\']+\s*,\s*[\'|\"]+([^\"|\']*)[\"|\']\s*\)!is";
            $config->parseTheme($this->theme_id, $var_re);
            //$config->parseTheme($this->theme_id,"/xarThemeVars::get\(\W*[\'\"]" . $info['name'] . "[\'\"]\W*,\W*[\'\"](.+)[\'\"]\W*\)/");
            $activeoptions = array_keys($config->configurations);
            foreach ($allconfigurations as $key => $row) {
                if (in_array($key, $activeoptions)) {
                    $allconfigurations[$key]['applies'] = 2;
                }
            }
            $configoptions['specific'] = $config->configurations;


            // Get the common options being called in the theme
            $config = new Configurations();
            $commonlabel = "common";
            $var_re = "!xarThemeVars::get\(\s*[\"|\']" . $commonlabel . "[\"|\']+\s*,\s*[\'|\"]+([^\"|\']*)[\"|\']\s*\)!is";
            $config->parseTheme($this->theme_id, $var_re);
            //$config->parseTheme($this->theme_id,"/xarThemeVars::get\(\W*[\'\"]" . $commonlabel . "[\'\"]\W*,\W*[\'\"](.+)[\'\"]\W*\)/");
            $activeoptions = array_keys($config->configurations);
            foreach ($allconfigurations as $key => $row) {
                if (in_array($key, $activeoptions)) {
                    $allconfigurations[$key]['applies'] = 1;
                }
            }

            xarCoreCache::setCached('Themes', 'Configurations', $allconfigurations);
        }
        return $allconfigurations;
    }
}
