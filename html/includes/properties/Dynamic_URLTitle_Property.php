<?php
/**
 *
 * Dynamic Data Dynamic URL and Title Property
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
 * Include the base class
 *
 */
include_once "includes/properties/Dynamic_TextBox_Property.php";

/**
 * handle the URL + Title property
 *
 * @package dynamicdata
 *
 */
class Dynamic_URLTitle_Property extends Dynamic_TextBox_Property
{
    function validateValue($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }
        if (!empty($value)) {
            if (is_array($value)) {
                if (isset($value['title'])) {
                    $title = $value['title'];
                }
                if (empty($title)) {
                    $title = '';
                }
                if (isset($value['link'])) {
                    $link = $value['link'];
                }
                if (empty($link) || $link == 'http://') {
                    $link = '';
            // TODO: add some URL validation routine !
                } elseif (preg_match('/[<>"]/',$link)) {
                    $this->invalid = xarML('URL');
                    $this->value = null;
                    return false;
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
        $data=array();

/*        return '<input type="text" name="' . $name . '[title]" value="'. xarVarPrepForDisplay($title) . '" size="'. $size . '" maxlength="'. $maxlength . '"' .
               ' id="'. $id . '"' .
               (!empty($tabindex) ? ' tabindex="'.$tabindex.'"' : '') .
               ' /> <br />' .
               '<input type="text" name="' . $name . '[link]" value="'. xarVarPrepForDisplay($link) . '" size="'. $size . '" maxlength="'. $maxlength . '" />' .
               (!empty($link) && $link != 'http://' ? ' [ <a href="'.$link.'" target="preview">'.xarML('check').'</a> ]' : '') .
               (!empty($this->invalid) ? ' <span class="xar-error">'.xarML('Invalid #(1)', $this->invalid) .'</span>' : '');
*/
        $data['name']     = $name;
        $data['id']       = $id;
        $data['title']    = xarVarPrepForDisplay($title);
        $data['value']    = isset($value) ? xarVarPrepForDisplay($value) : xarVarPrepForDisplay($this->value);
        $data['tabindex'] = !empty($tabindex) ? $tabindex=$tabindex : '';
        $data['invalid']  = !empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid) :'';
        $data['maxlength']= !empty($maxlength) ? $maxlength : $this->maxlength;
        $data['size']     = !empty($size) ? $size : $this->size;
        $data['link']     = xarVarPrepForDisplay($link);

        $template="urltitle";
        return xarTplModule('dynamicdata', 'admin', 'showinput', $data , $template);
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
            //return '';
        } elseif (empty($link)) {
            $title = xarVarPrepForDisplay($title);
            //return  $title;
        } elseif (empty($title)) {
            $link = xarVarPrepForDisplay($link);
            //return '<a href="'.$link.'">'.$link.'</a>';
        } else {
            $title = xarVarPrepForDisplay($title);
            $link = xarVarPrepForDisplay($link);
            //return '<a href="'.$link.'">'.$title.'</a>';
        }

        $data['value']   = $this->value;
        $data['link']    = $link;
        $data['name']    = $this->name;
        $data['id']      = $this->id;
        $data['title']   = $title;

        $template="urltitle";
        return xarTplModule('dynamicdata', 'user', 'showoutput', $data ,$template);
    }
}
?>
