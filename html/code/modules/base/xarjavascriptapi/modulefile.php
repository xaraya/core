<?php
/**
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/68.html
 */
/**
 * Base JavaScript management functions
 * Include a module JavaScript link in a page.
 *
 * @author Jason Judge
 * 
 * @param $args['module'] module name; or
 * @param $args['moduleid'] module ID
 * @param $args['filename'] file name list (comma-separated or array)
 * @param $args['position'] position on the page; generally 'head' or 'body'
 * @return boolean Returns true on success, false on failure
 */
function base_javascriptapi_modulefile(Array $args=array())
{
    extract($args);

    $result = true;

    // Default the position to the head.
    if (empty($position)) {
        $position = 'head';
    }

    // Filename can be an array of files to include, or a
    // comma-separated list. This allows a bunch of files
    // to be included from a source module in one go.
    if (!is_array($args['filename'])) {
        $files = explode(',', $args['filename']);
    }

    foreach ($files as $file) {
        $args['filename'] = $file;
        $filePath = xarMod::apiFunc('base', 'javascript', '_findfile', $args);

        // A failure to find a file is recorded, but does not stop subsequent files.
        if (!empty($filePath)) {
            $result = $result & xarTplAddJavaScript($position, 'src', xarServer::getBaseURL() . $filePath, $filePath);
        } else {
            $result = false;
        }
    }

    return $result;
}

?>
