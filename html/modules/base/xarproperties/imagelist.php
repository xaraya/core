<?php
/**
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage base
 * @link http://xaraya.com/index.php/release/68.html
 * @author mikespub <mikespub@xaraya.com>
 */
sys::import('modules.base.xarproperties.dropdown');
/**
 * Handle the imagelist property
 * @package dynamicdata
 */
class ImageListProperty extends SelectProperty
{
    public $id         = 35;
    public $name       = 'imagelist';
    public $desc       = 'Image List';

    public $basedir = '';
    public $baseurl = null;
    public $filetype = '(gif|jpg|jpeg|png|bmp)';

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->tplmodule = 'base';
        $this->template  = 'imagelist';

//        if (empty($this->basedir) && !empty($this->configuration)) {
//            $this->parseConfiguration($this->configuration);
//        }
        // Note : {theme} will be replaced by the current theme directory - e.g. {theme}/images -> themes/Xaraya_Classic/images
        if (!empty($this->basedir) && preg_match('/\{theme\}/',$this->basedir)) {
            $curtheme = xarTplGetThemeDir();
            $this->basedir = preg_replace('/\{theme\}/',$curtheme,$this->basedir);
            if (isset($this->baseurl)) {
                $this->baseurl = preg_replace('/\{theme\}/',$curtheme,$this->baseurl);
            }
        }
    }

    public function validateValue($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }
        $basedir = $this->basedir;
        $filetype = $this->filetype;
        if (!empty($value) &&
            //slight change to allow spaces
            preg_match('/^[a-zA-Z0-9_\/.\-\040]+$/',$value) &&
            preg_match("/$filetype$/",$value) &&
            file_exists($basedir.'/'.$value) &&
            is_file($basedir.'/'.$value)) {
            $this->value = $value;
            return true;
        } elseif (empty($value)) {
            $this->value = $value;
            return true;
        }
        $this->invalid = xarML('selection: #(1)', $this->name);
        $this->value = null;
        return false;
    }

    public function showInput(Array $data = array())
    {
        if (!isset($data['value'])) {
            $data['value'] = $this->value;
        }
/*        if (!isset($data['options']) || count($data['options']) == 0) {
            $data['options'] = $this->getOptions();
        }
        if (count($data['options']) == 0 && !empty($this->basedir)) {
            $files = xarModAPIFunc('dynamicdata','admin','browse',
                                   array('basedir' => $this->basedir,
                                         'filetype' => $this->filetype));
            if (!isset($files)) {
               $files = array();
            }
            natsort($files);
            array_unshift($files,'');
            foreach ($files as $file) {
                $data['options'][] = array('id' => $file,
                                   'name' => $file);
            }
            unset($files);
        }
*/
        $data['basedir'] = $this->basedir;
        $data['baseurl'] = isset($this->baseurl) ? $this->baseurl : $this->basedir;

        return parent::showInput($data);
    }

    public function showOutput(Array $data = array())
    {
        extract($data);

        if (!isset($value)) $value = $this->value;

        $basedir = $this->basedir;
        $baseurl = isset($this->baseurl) ? $this->baseurl : $basedir;
        $filetype = $this->filetype;

        if (!empty($value) &&
            preg_match('/^[a-zA-Z0-9_\/.\-\040]+$/',$value) &&
            preg_match("/$filetype$/",$value) &&
            file_exists($basedir.'/'.$value) &&
            is_file($basedir.'/'.$value)) {
            $srcpath=$baseurl.'/'.$value;
        } else {
            $srcpath='';
        }

        $data['value']    = $value;
        $data['basedir']  = $basedir;
        $data['baseurl']  = $baseurl;
        $data['filetype'] = $filetype;
        $data['srcpath']  = $srcpath;
        return parent::showOutput($data);
    }

    public function getOptions()
    {
        $options = parent::getOptions();
        if (count($options) == 0 && !empty($this->basedir)) {
            $files = xarModAPIFunc('dynamicdata','admin','browse',
                                   array('basedir' => $this->basedir,
                                         'filetype' => $this->filetype));
            if (!isset($files)) {
               $files = array();
            }
            natsort($files);
            array_unshift($files,'');
            foreach ($files as $file) {
                $options[] = array('id' => $file,
                                   'name' => $file);
            }
            unset($files);
        }
        return $options;
    }

    public function parseConfiguration($validation = '')
    {
        if (empty($validation)) return;
        // specify base directory in validation field, or basedir|baseurl (not ; to avoid conflicts with old behaviour)
        if (strpos($validation,'|') !== false) {
            $parts = split('\|',$validation);
            if (count($parts) < 2) return;
            $this->basedir = array_shift($parts);
            $this->baseurl = array_shift($parts);
            if (count($parts) > 0) {
                $this->filetype = '(' . join('|',$parts) . ')';
            }
        } else {
            $this->basedir = $validation;
        }
    }

    public function showConfiguration(Array $args = array())
    {
        extract($args);

        $data = array();
        $data['name']       = !empty($name) ? $name : 'dd_'.$this->id;
        $data['id']         = !empty($id)   ? $id   : 'dd_'.$this->id;
        $data['tabindex']   = !empty($tabindex) ? $tabindex : 0;
        $data['invalid']    = !empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid) :'';

        $data['size']       = !empty($size) ? $size : 50;
        $data['maxlength']  = !empty($maxlength) ? $maxlength : 254;

        if (isset($validation)) {
            $this->configuration = $validation;
            $this->parseConfiguration($validation);
        }

        $data['basedir'] = $this->basedir;
        $data['baseurl'] = isset($this->baseurl) ? $this->baseurl : $this->basedir;
        if (!empty($this->filetype)) {
            $this->filetype = strtr($this->filetype, array('(' => '', ')' => ''));
            $data['filetype'] = explode('|',$this->filetype);
        } else {
            $data['filetype'] = array();
        }
        $numtypes = count($data['filetype']);
        if ($numtypes < 4) {
            for ($i = $numtypes; $i < 4; $i++) {
                $data['filetype'][] = '';
            }
        }
        $data['other'] = '';

        // allow template override by child classes
        $template  = empty($template) ? $this->getTemplate() : $template;

        return xarTplProperty('base', $template, 'configuration', $data);
    }

    public function updateConfiguration(Array $args = array())
    {
        extract($args);

        // in case we need to process additional input fields based on the name
        $name = empty($name) ? 'dd_'.$this->id : $name;
        // do something with the validation and save it in $this->configuration
        if (isset($validation)) {
            if (is_array($validation)) {
                if (!empty($validation['other'])) {
                    $this->configuration = $validation['other'];

                } else {
                    $this->configuration = '';
                    if (!empty($validation['basedir'])) {
                        $this->configuration = $validation['basedir'];
                    }
                    if (!empty($validation['baseurl'])) {
                        $this->configuration .= '|' . $validation['baseurl'];
                    }
                    if (!empty($validation['filetype'])) {
                        $todo = array();
                        foreach ($validation['filetype'] as $ext) {
                            if (empty($ext)) continue;
                            $todo[] = $ext;
                        }
                        if (count($todo) > 0) {
                            $this->configuration .= '|(';
                            $this->configuration .= join('|',$todo);
                            $this->configuration .= ')';
                        }
                    }
                }
            } else {
                $this->configuration = $validation;
            }
        }

        // tell the calling function that everything is OK
        return true;
    }
}

?>
