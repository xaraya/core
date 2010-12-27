<?php
/**
 * Class for handling theme configuration options
 *
 * @package modules
 * @subpackage themes module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/70.html
 */
class Configurations extends Object
{
    private $reader;

    public $directory_tree    = array();
    public $configurations    = array();
    public $files             = array();

    function __construct()
    {
        $this->reader = new XMLReader();
    }
    
    function getConfigurations()
    {
        return $this->configurations;
    }

    function parseTheme($themeID, $pattern="")
    {
        if (empty($pattern)) return false;
        if ($themeID == 0) return false;
        $items = array(xarThemeGetInfo($themeID));

        $checked_files = array();
        foreach ($items as $item) {
            $basedir = 'themes/' . $item['name'];
            $files = $this->get_theme_files($basedir,'xt');
            foreach ($files as $file) {
                $this->parse_theme_template($file,$pattern);
                $checked_files[] = $file;
            }
        }
        $this->reader->close();
        $this->files = $checked_files;
        return true; 
    }
    
    function get_theme_files($directory, $filter=FALSE)
    {
        $directory_tree = array();

        // if the path has a slash at the end we remove it here
         if(substr($directory,-1) == '/') $directory = substr($directory,0,-1);

         // if the path is not valid or is not a directory ...
         if(!file_exists($directory) || !is_dir($directory)) return array();

         if(is_readable($directory)) {
             // we open the directory
             $directory_list = opendir($directory);

             // and scan through the items inside
             while (FALSE !== ($file = readdir($directory_list))) {
                 // if the filepointer is not the current directory
                 // or the parent directory
                 if($file != '.' && $file != '..')
                 {
                     // we build the new path to scan
                     $path = $directory.'/'.$file;

                     // if the path is readable
                     if(is_readable($path)) {
                         // we split the new path by directories
                         $subdirectories = explode('/',$path);

                         // if the new path is a directory
                         if(is_dir($path)) {
                             // add the directory details to the file list
                             $dirs = $this->get_theme_files($path, $filter);
                             $directory_tree = array_merge($directory_tree, $dirs);  

                         // if the new path is a file
                         } elseif(is_file($path)) {
                             // get the file extension by taking everything after the last dot
                             $f = explode('.',end($subdirectories));
                             $extension = end($f);

                             // if there is no filter set or the filter is set and matches
                             if($filter === FALSE || $filter == $extension) {
                                 // add the file details to the file list
                                 $directory_tree[] = $path;
                             }
                         }
                     }
                 }
             }
             // close the directory
             closedir($directory_list); 

             // return file list
             return $directory_tree;

         // if the path is not readable ...
         } else {
             return array();    
         }
    }
    
    function parse_theme_template($filename,$pattern="")
    {
        if (!file_exists($filename)) return false;
        $this->filename = $filename;
        $this->_fd = fopen($filename, 'r');
        if (!$this->_fd) {
            $msg = xarML('Cannot open the file #(1)',$filename);
            throw new Exception($msg);
        }

        $filestring = file_get_contents($filename);
//        $filestring = preg_replace("/&xar([\-A-Za-z\d.]{2,41});/","xar-entity",$filestring);
        $this->reader->xml($filestring);
        $nodes = array();
        $i = 0;
        
        while ($this->reader->read()) {
            $i++;
            
        // Ignore certain nodes            
            if ($this->reader->name == "xar:comment") {
                if (!$this->reader->next()) break;
                $i++;
            }
            
            if ($this->reader->nodeType == XMLReader::TEXT) {
               $string = $this->reader->value;
            } else {
                continue;
            }
            preg_match_all($pattern, $string, $matches);
            foreach ($matches as $match) {
                $this->configurations[$match][] = array('line' => $i, 'file' => $this->filename);
            }            
        }
        return true;
    }
}
?>