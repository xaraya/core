<?php
/**
 * File: $Id$
 *
 * Dynamic Text Upload Property (TODO: work with uploads module)
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata properties
 * @author mikespub <mikespub@xaraya.com>
*/

/**
 * Handle text upload property
 *
 * @package dynamicdata
 *
 */
class Dynamic_TextUpload_Property extends Dynamic_Property
{
    var $rows = 8;
    var $cols = 50;

    var $size = 40;
    var $maxsize = 1000000;
//    var $basedir;
//    var $filetype;

    // this is used by Dynamic_Property_Master::addProperty() to set the $object->upload flag
    var $upload = true;

    function Dynamic_TextUpload_Property($args)
    {
        $this->Dynamic_Property($args);
        // TODO: do we want any other verifications here, like file type ?
    }

    function validateValue($value = null)
    {
        // the variable corresponding to the file upload field is no longer set in PHP 4.2.1+
        // but we're using a textarea field to keep track of any previously uploaded file here
        if (!isset($value)) {
            $value = $this->value;
        }
        if (isset($this->fieldname)) {
            $name = $this->fieldname;
        } else {
            $name = 'dd_'.$this->id;
        }

        // retrieve new value for preview + new/modify combinations
        if (xarVarIsCached('DynamicData.TextUpload',$name)) {
            $this->value = xarVarGetCached('DynamicData.TextUpload',$name);
            return true;
        }

        // if the uploads module is hooked (to be verified and set by the calling module)
        // any uploaded files will be referenced in the text as #...:NN# for transform hooks
        if (xarVarGetCached('Hooks.uploads','ishooked')) {
            list( , $methods) = xarModAPIFunc('uploads', 'admin', 'dd_configure', $this->validation);
            $return = xarModAPIFunc('uploads','admin','validatevalue',
                                    array('id' => $name, // not $this->id
                                          'value' => null, // we don't keep track of values here
                                          'multiple' => FALSE, // not relevant here
                                          'methods' => $methods,
                                          'format' => 'textupload',
                                          'maxsize' => $this->maxsize));
            if (!isset($return) || !is_array($return) || count($return) < 2) {
                $this->value = null;
            // CHECKME: copied from autolinks :)
                // 'text' rendering will return an array
                $errorstack = xarErrorGet();
                $errorstack = array_shift($errorstack);
                $this->invalid = $errorstack['short'];
                xarErrorHandled();
                return false;
            }
            if (empty($return[0])) {
                $this->value = null;
                $this->invalid = xarML('value');
                return false;
            }
            // show magic link #...:NN# to file in text (cfr. transform hook in uploads module)
            $magiclinks = '';
            if (!empty($return[1])) {
                $magiclinks = xarModAPIFunc('uploads','user','showoutput',
                                            array('value' => $return[1],
                                                  'format' => 'textupload',
                                                  'style' => 'icon'));
                // strip template comments if necessary
                $magiclinks = preg_replace('/<\!--.*?-->/','',$magiclinks);
                $magiclinks = trim($magiclinks);
            }
            if (!empty($value) && !empty($magiclinks)) {
                $value .= ' ' . $magiclinks;
            } elseif (!empty($magiclinks)) {
                $value = $magiclinks;
            }
            $this->value = $value;
            // save new value for preview + new/modify combinations
            xarVarSetCached('DynamicData.TextUpload',$name,$value);
            return true;
        }

        $upname = $name .'_upload';
        if (!empty($_FILES) && !empty($_FILES[$upname]) && !empty($_FILES[$upname]['tmp_name'])
            // is_uploaded_file() : PHP 4 >= 4.0.3
            && is_uploaded_file($_FILES[$upname]['tmp_name']) && $_FILES[$upname]['size'] > 0 && $_FILES[$upname]['size'] < 1000000) {

            // this doesn't work on some configurations
            //$this->value = join('', @file($_FILES[$upname]['tmp_name']));
            $tmpdir = xarCoreGetVarDirPath();
            $tmpdir .= '/cache/templates';
            $tmpfile = tempnam($tmpdir, 'dd');
        // no verification of file types here
            if (move_uploaded_file($_FILES[$upname]['tmp_name'], $tmpfile) && file_exists($tmpfile)) {
                $this->value = join('', file($tmpfile));
                unlink($tmpfile);
            }
            // save new value for preview + new/modify combinations
            xarVarSetCached('DynamicData.TextUpload',$name,$this->value);
        // retrieve new value for preview + new/modify combinations
        } elseif (xarVarIsCached('DynamicData.TextUpload',$name)) {
            $this->value = xarVarGetCached('DynamicData.TextUpload',$name);
        } elseif (!empty($value)) {
            $this->value = $value;
        } else {
            $this->value = '';
        }
        return true;
    }

//    function showInput($name = '', $value = null, $size = 0, $maxsize = 0, $id = '', $tabindex = '')
    function showInput($args = array())
    {
        extract($args);

        $data = array();

        if (empty($name)) {
            $name = 'dd_'.$this->id;
        }
        if (empty($id)) {
            $id = $name;
        }
        if (!isset($value)) {
            $value = $this->value;
        }
        $upname = $name .'_upload';

        // inform anyone that we're showing a file upload field, and that they need to use
        // <form ... enctype="multipart/form-data" ... > in their input form
        xarVarSetCached('Hooks.dynamicdata','withupload',1);

        if (xarVarGetCached('Hooks.uploads','ishooked')) {
            list(, $methods) = xarModAPIFunc('uploads', 'admin', 'dd_configure', $this->validation);

            // relevant input fields are handled directly by the uploads module
            //$extensions = xarModGetVar('uploads','allowed_types');
            $data['extensions']= '';
            $allowed = '';
            $uploads = xarModAPIFunc('uploads','admin','showinput',
                                     array('id' => $name, // not $this->id
                                           'value' => null, // we don't keep track of values here
                                           'multiple' => FALSE, // not relevant here
                                           'format' => 'textupload',
                                           'methods' => $methods));
            if (!empty($uploads)) {
                $data['uploads_hooked'] = $uploads;
            }
        } else {
            // no verification of file types here
            $data['extensions']= '';
            $allowed = '';
        }
        $data['allowed']   =$allowed;
        $data['upname']    =$upname;
        // we're using a textarea field to keep track of any previously uploaded file here
        /*return '<textarea' .
               ' name="' . $name . '"' .
               ' rows="'. (!empty($rows) ? $rows : $this->rows) . '"' .
               ' cols="'. (!empty($cols) ? $cols : $this->cols) . '"' .
               ' wrap="'. (!empty($wrap) ? $wrap : $this->wrap) . '"' .
               ' id="'. $id . '"' .
               (!empty($tabindex) ? ' tabindex="'.$tabindex.'"' : '') .
               '>' . xarVarPrepForDisplay($value) . '</textarea><br /><br />' .
               '<input type="hidden" name="MAX_FILE_SIZE"'.
               ' value="'. (!empty($maxsize) ? $maxsize : $this->maxsize) .'" />' .
               '<input type="file"'.
               ' name="'.$upname.'"' .
               ' size="'. (!empty($size) ? $size : $this->size) . '"' .
               (!empty($id) ? ' id="'.$id.'_upload"' : '') .
               (!empty($tabindex) ? ' tabindex="'.$tabindex.'"' : '') .
               ' /> ' . $allowed .
               (!empty($this->invalid) ? ' <span class="xar-error">'.xarML('Invalid #(1)', $this->invalid) .'</span>' : '');
        */
        $data['name']     = $name;
        $data['id']       = $id;
        $data['upid']     = !empty($id) ? $id.'_upload' : '';
        $data['rows']     = !empty($rows) ? $rows : $this->rows;
        $data['cols']     = !empty($cols) ? $cols : $this->cols;
        $data['value']    = isset($value) ? xarVarPrepForDisplay($value) : xarVarPrepForDisplay($this->value);
        $data['tabindex'] = !empty($tabindex) ? $tabindex : 0;
        $data['invalid']  = !empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid) :'';
        $data['maxsize']  = !empty($maxsize) ? $maxsize: $this->maxsize;
        $data['size']     = !empty($size) ? $size : $this->size;

        $template="textupload";
        return xarTplModule('dynamicdata', 'admin', 'showinput', $data , $template);

    }

    function showOutput($args = array())
    {
        extract($args);
        $data = array();

        // no uploads-specific code here - cfr. transform hook in uploads module

        if (!isset($value)) {
            $data['value'] = $this->value;
        }
        if (!empty($value)) {
            $data['value'] = xarVarPrepHTMLDisplay($value);
        } else {
            $data['value'] ='';
        }

        $template="textupload";
        return xarTplModule('dynamicdata', 'user', 'showoutput', $data ,$template);

    }


    /**
     * Get the base information for this property.
     *
     * @returns array
     * @return base information for this property
     **/
     function getBasePropertyInfo()
     {
        $args['rows'] = 20;
     
         $baseInfo = array(
                              'id'         => 38,
                              'name'       => 'textupload',
                              'label'      => 'Text Upload',
                              'format'     => '38',
                              'validation' => '',
                            'source'     => '',
                            'dependancies' => '',
                            'requiresmodule' => '',
                            'aliases' => '',
                            'args' => serialize( $args ),
                            'args'         => '',
                            // ...
                           );
        return $baseInfo;
     }

}

?>
