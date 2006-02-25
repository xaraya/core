<?php
/**
 * Dynamic URL Title Property
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 */
/*
 * @author mikespub <mikespub@xaraya.com>
*/
include_once "modules/base/xarproperties/Dynamic_TextBox_Property.php";

/**
 * handle the URL + Title property
 *
 * @package dynamicdata
 *
 */
class Dynamic_URLTitle_Property extends Dynamic_TextBox_Property
{
    public $requiresmodule = 'base';
    
    public $id     = 41;
    public $name   = 'urltitle';
    public $label  = 'URL + Title';
    public $format = '41';

    function checkInput($name='', $value = null)
    {
        if (empty($name)) {
            $name = 'dd_'.$this->id;
        }
        // store the fieldname for validations who need them (e.g. file uploads)
        $this->fieldname = $name;
        if (!isset($value)) {
            if (!xarVarFetch($name, 'isset', $value,  NULL, XARVAR_DONT_SET)) {return;}
        }
        return $this->validateValue($value);
    }

    function validateValue($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }
        if (!empty($value)) {
            if (is_array($value)) {

                if (isset($value['title'])) {
                    $title = $value['title'];
                } else {
                    $title = '';
                }

                if (isset($value['link'])) {
                    $link = $value['link'];
                } else {
                    $link = '';
                }
                // Make sure $value['title'] is set and has a length > 0
                if (strlen(trim($title))) {
                    $title = $value['title'];
                } else {
                    $title = '';
                }

                // Make sure $value['link'] is set, has a length > 0 and does not equal simply 'http://'
                if (strlen(trim($link)) && trim($link) != 'http://') {
                        $link = $value['link'];
                } else {
                    // If we have a scheme but nothing following it,
                    // then consider the link empty :-)
                    if (eregi('^[a-z]+\:\/\/$', trim($link))) {
                        $link = '';
                    } else {

                        // Do some URL validation below - make sure the url
                        // has at least a scheme (http/ftp/etc) and a host (domain.tld)
                        $uri = parse_url($value['link']);

                        if ( (!isset($uri['scheme']) || empty($uri['scheme'])) ||
                            (!isset($uri['host']) || empty($uri['host']))) {
                                $this->invalid = xarML('URL');
                                $this->value = null;
                                return false;
                        }
                    }
                }
                $value = array('link' => $link, 'title' => $title);
                $this->value = serialize($value);
            } else {
            // TODO: do we need to check the serialized content here ?
                $this->value = $value;
            }
        } else {
            $this->value = '';
        }
        return true;
    }

//    function showInput($name = '', $value = null, $size = 0, $maxlength = 0, $id = '', $tabindex = '')
    function showInput($args = array())
    {
        extract($args);
        // empty value is allowed here
        if (!isset($value)) {
            $value = $this->value;
        }
        // empty fields are not allowed here
        if (empty($name)) {
            $name = 'dd_' . $this->id;
        }
        if (empty($id)) {
            $id = $name;
        }
        if (empty($size)) {
            $size = $this->size;
        }
        if (empty($maxlength)) {
            $maxlength = $this->maxlength;
        }
        // extract the link and title information
        if (empty($value)) {
        } elseif (is_array($value)) {
            if (isset($value['link'])) {
                $link = $value['link'];
            }
            if (isset($value['title'])) {
                $title = $value['title'];
            }
        } elseif (is_string($value) && substr($value,0,2) == 'a:') {
            $newval = unserialize($value);
            if (isset($newval['link'])) {
                $link = $newval['link'];
            }
            if (isset($newval['title'])) {
                $title = $newval['title'];
            }
        }
        if (empty($link)) {
            $link = 'http://';
        }
        if (empty($title)) {
            $title = '';
        }
        $data = array();

        $data['name']     = $name;
        $data['id']       = $id;
        $data['title']    = xarVarPrepForDisplay($title);
        $data['value']    = isset($value) ? xarVarPrepForDisplay($value) : xarVarPrepForDisplay($this->value);
        $data['tabindex'] = !empty($tabindex) ? $tabindex : 0;
        $data['invalid']  = !empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid) :'';
        $data['maxlength']= !empty($maxlength) ? $maxlength : $this->maxlength;
        $data['size']     = !empty($size) ? $size : $this->size;
        $data['link']     = xarVarPrepForDisplay($link);

        if (empty($module)) {
            $module = $this->getModule();
        }
        if (empty($template)) {
            $template = $this->getTemplate();
        }

        return xarTplProperty($module, $template, 'showinput', $data);
    }

    function showOutput($args = array())
    {
         extract($args);
        if (!isset($value)) {
            $value = $this->value;
        }
        if (empty($value)) {
            $returndata= '';
        }
        if (is_array($value)) {
            if (isset($value['link'])) {
                $link = $value['link'];
            }
            if (isset($value['title'])) {
                $title = $value['title'];
            }
        } elseif (is_string($value) && substr($value,0,2) == 'a:') {
            $newval = unserialize($value);
            if (isset($newval['link'])) {
                $link = $newval['link'];
            }
            if (isset($newval['title'])) {
                $title = $newval['title'];
            }
        }
        $data=array();

        if (empty($link) && empty($title)) {
        } elseif (empty($link)) {
            $title = xarVarPrepForDisplay($title);
        } elseif (empty($title)) {
            $link = xarVarPrepForDisplay($link);
        } else {
            $title = xarVarPrepForDisplay($title);
            $link = xarVarPrepForDisplay($link);
        }

        $data['value']   = $this->value;
        $data['link']    = (!empty($link) && $link != 'http://') ? $link : '';
        $data['title']   = (!empty($title)) ? $title : '';

        if (empty($module)) {
            $module = $this->getModule();
        }
        if (empty($template)) {
            $template = $this->getTemplate();
        }

        return xarTplProperty($module, $template, 'showoutput', $data);
    }
}
?>
