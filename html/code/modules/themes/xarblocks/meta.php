<?php
/**
 *  Initialise meta block
 * @package modules
 * @subpackage themes module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/70.html
 */
/**
 * initialise block
 * @author  John Cox
 * @author  Carl Corliss
 * @access  public
 * @param   none
 * @return  nothing
*/

sys::import('xaraya.structures.containers.blocks.basicblock');

class Themes_MetaBlock extends BasicBlock
{
    public $name                = 'MetaBlock';
    public $module              = 'themes';
    public $text_type           = 'Meta';
    public $text_type_long      = 'Meta Keywords';
    public $show_preview        = true;
    public $usershared          = true;
    public $pageshared          = false;

    public $metakeywords        = '';
    public $metadescription     = '';
    public $usedk               = '';
    public $usegeo              = '';
    public $longitude           = '';
    public $latitude            = '';
    public $copyrightpage       = '';
    public $helppage            = '';
    public $glossary            = '';

/**
 * Display func.
 * @param $data array containing title,content
 */
    function display(Array $data=array())
    {
        $data = parent::display($data);
        if (empty($data)) return;
        
        $meta = array();

        // Description
        $incomingdesc = xarVarGetCached('Blocks.articles', 'summary');

        $data['usedk'] = isset($data['usedk']) ? $data['usedk'] : $this->usedk;
        if (!empty($incomingdesc) and $data['usedk'] >= 1) {
            // Strip -all- html
            $htmlless = strip_tags($incomingdesc);
            $meta['description'] = $htmlless;
        } else {
            $meta['description'] = isset($data['metadescription']) ? $data['metadescription'] : $this->metadescription;
        }

        // Dynamic Keywords
        $incomingkey = xarVarGetCached('Blocks.articles', 'body');
        $incomingkeys = xarVarGetCached('Blocks.keywords', 'keys');

        if (!empty($incomingkey) and $data['usedk'] == 1) {
            // Keywords generated from articles module
            $meta['keywords'] = $incomingkey;
        } elseif ((!empty($incomingkeys)) and ($data['usedk'] == 2)){
            // Keywords generated from keywords module
            $meta['keywords'] = $incomingkeys;
        } elseif ((!empty($incomingkeys)) and ($data['usedk'] == 3)){
            $meta['keywords'] = $incomingkeys.','.$incomingkey;
        } else {
            $meta['keywords'] = isset($data['metakeywords']) ? $data['metakeywords'] : $this->metakeywords;
        }

        // Character Set
        $meta['charset'] = xarMLSGetCharsetFromLocale(xarMLSGetCurrentLocale());
        $meta['generator'] = xarConfigVars::get(null, 'System.Core.VersionId');
        $meta['generator'] .= ' :: ';
        $meta['generator'] .= xarConfigVars::get(null, 'System.Core.VersionNum');

        // Geo Url
        $meta['longitude'] = isset($data['longitude']) ? $data['longitude'] : $this->longitude;
        $meta['latitude'] = isset($data['latitude']) ? $data['latitude'] : $this->latitude;

        // Active Page
        $meta['activepagerss'] = xarServer::getCurrentURL(array('theme' => 'rss'));
        $meta['activepageatom'] = xarServer::getCurrentURL(array('theme' => 'atom'));
        $meta['activepageprint'] = xarServer::getCurrentURL(array('theme' => 'print'));

        $meta['baseurl'] = xarServer::getBaseURL();
        if (isset($data['copyrightpage'])){
            $meta['copyrightpage'] = $data['copyrightpage'];
        } else {
            $meta['copyrightpage'] = '';
        }

        if (isset($data['helppage'])){
            $meta['helppage'] = $data['helppage'];
        } else {
            $meta['helppage'] = '';
        }

        if (isset($data['glossary'])){
            $meta['glossary'] = $data['glossary'];
        } else {
            $meta['glossary'] = '';
        }

        //Pager Buttons
        $meta['refreshurl']     = xarVarGetCached('Meta.refresh','url');
        $meta['refreshtime']    = xarVarGetCached('Meta.refresh','time');
        $meta['first']          = xarVarGetCached('Pager.first','leftarrow');
        $meta['last']           = xarVarGetCached('Pager.last','rightarrow');

        $data['content'] = $meta;
        return $data;

    }
}
?>