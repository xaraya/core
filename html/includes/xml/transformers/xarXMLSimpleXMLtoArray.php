<?php

include_once('./includes/xarXML.php');

/**
 * This handler was made to handle ONLY Simple XML (+attributes), creating
 * an array with indexes mimicking the xpath location and value with:
 * - An array with the paths to children (if parent, simple xml == tags have children or value)
 * - The value corresponding to the cdata in the tag (this tag should not have children)
 * - NULL if no cdata is present
 * - The value in case of an attribute
 *
 * @package xml
 *
 */

class xarXMLSimpleXMLtoArrayTransformer extends xarAbstractXmlHandler
{
    var $_tree = array();
    var $_path = array();
    var $_extra_tree = array('/'=>0);
    var $_clean_path = array();
    var $_data = NULL;

    /**
     * Character data handler is added as 'data' for the current tag
     *
     * @param object $parser the parser to which this handler is attached
     * @param string $data   character data found
     */
    function character_data($parser, $data)
    {
        // this handler can be called multiple times, so make sure we're not
        // overwriting ourselves, trust the depth to put things in the right place
        if ($this->_data) {
            $this->_data .= trim($data);
        } else {
            $this->_data = trim($data);
        }
    }

    /**
     * Start element handler
     *
     * This gets called when the start of a new <tag> is encountered
     * the tagname and its attributes are passed in as parameters.
     *
     * @param $parser  object the parser which this handler is attached to
     * @param $tagname string the start tag found
     * @param $attribs array  array of attributes with [attribname] => value pairs
     * @todo the ID attribute should be unique, check for that somehow
     *
     */
    function open_tag($parser, $tagName, $attribs, $type=XML_ELEMENT_NODE)
    {
        //Simple XML doesnt have tags mixed with text
        if (!empty($this->_data))
        {
            //Error in the Simple XML
//             echo "Error in the Simple XML: ";
//             echo "Data left: ". $this->_data.'<br/>';
        }

        $this->addTag($tagName);

        if ($attribs)
        {
            foreach ($attribs as $name => $value)
            {
                $this->setNode("/@$name", $value);
            }
        }
    }

    /**
     * Close element handler
     *
     * This handler is called when a closing </tag> is found. As tags in xml
     * should be properly nested we can count on these functions to be
     * called in order
     *
     * @param $parser object the parser to which handler is attached
     * @param $tagnam string tag which is closing
     *
     */
    function close_tag($parser, $tagname)
    {
        if (!empty($this->_data))
        {
            $this->setNode ('', $this->_data);
            $this->_data = NULL;
        } else {
            if (isset($this->_tree[$this->getPath()])) {
                //Ok, has children
            } else {
                //Mark as an empty node
                $this->setNode ('', array());
            }
        }

        $tagName = array_pop($this->_path);

        array_pop($this->_clean_path);
        if (count($this->_clean_path)) {
            //Remove the ending index pointer
            $string = preg_replace("/\\[[^\\]]\\]/", '', $this->_clean_path[count($this->_clean_path)-1]);
            $this->_clean_path[count($this->_clean_path)-1] = $string;
        }

        $fullPath = $this->getPath();

        //Node already closed -> new path = parent's path.
        if (isset($this->_tree[$fullPath]))
        {
            $this->_tree[$fullPath][] = $tagName;
        } else {
            $this->_tree[$fullPath] = array($tagName);
        }
    }

    function getPath ()
    {
        return '/'.implode('/', $this->_path);
    }

    function getCleanPath()
    {
        return '/'.implode('/', $this->_clean_path);
    }

    function getIndex ()
    {
        $cleanPath = $this->getCleanPath();
        return $this->_extra_tree[$cleanPath];
    }

    function addTag ($tagName)
    {
        $this->_clean_path = $this->_path;
        array_push($this->_clean_path, $tagName);

        $cleanPath = $this->getCleanPath();

        if (!isset($this->_extra_tree[$cleanPath]))
        {
            $this->_extra_tree[$cleanPath] = 0;
        } else {
            $this->_extra_tree[$cleanPath]++;
        }

        array_push($this->_path, $tagName.'['. $this->getIndex() .']');
    }

    function setNode ($extraPath, $value)
    {
        $fullPath = $this->getPath();
        $this->_tree[$fullPath.$extraPath] = $value;
    }

    /**
     * Handler reset
     *
     * @access protected
     */
    function _reset()
    {
        $this->_tree=array();
        $this->_path=array();
        $this->_extra_tree = array('/'=>0);
        $this->_clean_path = array();
        $this->_data=NULL;
    }
}

function xarXMLnormalizeSMLArray ($array)
{
    $notNormalized = true;
    while ($notNormalized)
    {
        $notNormalized = false;

        $keys = array_keys($array);
        foreach ($keys as $key)
        {
            if (substr($key, -3) == '[0]')
            {
                $find = substr($key, 0, -3) . '[1]';
                $aux_keys = array_keys($array);
                $found = false;

                foreach ($aux_keys as $aux_key)
                {
                    if ($aux_key == $find) {
                        $found = true;
                        break 1;
                    }
                }

                if (!$found)
                {
                    //Copy eveything downstairs
                    $key_size = strlen($key);
                    $new_array = $array;
//                     echo "<br/>[1] Not Found for $key<br/>";
                    foreach ($array as $path => $value)
                    {
                        if (substr($path, 0, $key_size) == $key) {
//                             echo "<br/>Found for $key :: $path<br/>";
                            $new_array[substr($path, 0, $key_size-3).substr($path, $key_size, strlen($path))] = $value;
                            unset($new_array[$path]);
//                             echo "<br/>Path deleted $path -=> Dirname: ".dirname($path)."<br/>";
                        }
                    }

                    $exploded = explode('/', $key);
                    $node_name = array_pop($exploded);

                    if (count($exploded) == 1) {
                        $parent_path = '/';
                    } else {
                        $parent_path = implode('/', $exploded);
//                      $parent_path = dirname($path);
                    }

                    $index = array_search($node_name, $new_array[$parent_path]);
                    if ($index === false)
                    {
//                         echo "<br/><br/>!!!! Error -> <br/>";
                    }

                    $new_array[$parent_path][$index] = substr($node_name, 0, -3);

                    $array = $new_array;
                    $notNormalized = true;
                    break 1;
                }
            }
        }
    }

    return $array;
}

function xarXMLParseSMLtoArray ($xml_file)
{
    $handler = new xarXMLSimpleXMLtoArrayTransformer();
    $parser = new xarXmlParser(XARXML_CHARSET_DEFAULT, $handler);
    $parser->parseFile($xml_file);

    return xarXMLnormalizeSMLArray($parser->tree);
//    ksort($parse->tree);
}

?>