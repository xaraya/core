<?php
/**
 * @package modules
 * @subpackage themes module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/182.html
 *
 * @author Marc Lutolf <mfl@netspan.ch>
 */

/**
 * Include the base class
 */
sys::import('modules.base.xarproperties.textbox');

/**
 * Handle the configuration property
 */
class ThemeConfigurationProperty extends TextBoxProperty
{
    public $id         = 30107;
    public $name       = 'themeconfiguration';
    public $desc       = 'Theme Configuration';
    public $reqmodules = array('themes');

    public $theme_id;   // The regid of the theme this property belongs to

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->filepath   = 'modules/themes/xarproperties';
        $this->tplmodule  = 'themes';
        $this->template   = 'themeconfiguration';
        // Make sure we get an object reference so we can get the theme ID value
        $this->include_reference = 1;
    }

    public function checkInput($name = '', $value = null)
    {
        $name = !empty($name) ? $name : 'dd_'.$this->id;        
        if (!xarVarFetch($name,'isset',$configuration,NULL,XARVAR_NOT_REQUIRED)) return;        
        $this->value = serialize($configuration);
        return true;
    }

    public function showInput(Array $data = array())
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
                return true;
            }
        }
        // Now match the parsed configurationproperties to those defined in the theme
        $properties = $this->getThemeConfigProperties(1);
        $this->configuration = array();
        foreach ($properties as $name => $configarg) {
            if (isset($fields[$name])) {
                $configarg['value'] = $fields[$name];
            } else {
                $configarg['value'] = null;
            }
            $this->configuration[$name] = $configarg;
        }
    }
    public function getThemeConfigProperties($fullname=0)
    {
        // cache configuration for all properties
        if (xarCoreCache::isCached('Themes','Configurations')) {
             $allconfigproperties = xarCoreCache::getCached('Themes','Configurations');
        } else {
            sys::import('xaraya.structures.query');
            $tables = xarDB::getTables();
            $q = new Query('SELECT',$tables['themes_configurations']);
            $q->eq('theme_id',$this->theme_id);
            $q->run();
            $result = $q->output();
            $allconfigproperties = array();
            foreach ($q->output() as $row)
            {
                $allconfigproperties[$row['name']] = $row;
            }
            xarCoreCache::setCached('Themes','Configurations', $allconfigproperties);
        }
        return $allconfigproperties;
    }
}
?>