<?php
/**
 *  Initialise meta block
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Themes module
 * @link http://xaraya.com/index.php/release/70.html
 */
/**
 * initialise block
 * @author  John Cox
 * @author  Carl Corliss
 * @access  public
 * @param   none
 * @return  nothing
 * @throws  no exceptions
 * @todo    nothing
*/

sys::import('xaraya.structures.containers.blocks.basicblock');

class MetaBlock extends BasicBlock
{
    public $no_cache            = 1;

    public $name                = 'MetaBlock';
    public $module              = 'themes';
    public $text_type           = 'Meta';
    public $text_type_long      = 'Meta';
    public $allow_multiple      = false;
    public $show_preview        = true;
    public $usershared          = true;

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
        if (isset($vars['copyrightpage'])){
            $meta['copyrightpage'] = $vars['copyrightpage'];
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

/**
 * Modify Function to the Blocks Admin
 * @param $data array containing title,content
 */
    public function modify(Array $data=array())
    {
        $data = parent::modify($data);

        if (!isset($data['metakeywords'])) $data['metakeywords'] = $this->metakeywords;
        if (!isset($data['metadescription'])) $data['metadescription'] = $this->metadescription;
        if (!isset($data['usegeo'])) $data['usegeo'] = $this->usegeo;
        if (!isset($data['usedk'])) $data['usedk'] = $this->usedk;
        if (!isset($data['longitude'])) $data['longitude'] = $this->longitude;
        if (!isset($data['latitude'])) $data['latitude'] = $this->latitude;
        if (!isset($data['copyrightpage'])) $data['copyrightpage'] = $this->copyrightpage;
        if (!isset($data['helppage'])) $data['helppage'] = $this->helppage;
        if (!isset($data['glossary'])) $data['glossary'] = $this->glossary;

        $data['blockid'] = $data['bid'];
        $content = xarTplBlock('themes', 'metaAdmin', $data);

        return $content;
    }

/**
 * Updates the Block config from the Blocks Admin
 * @param $data array containing title,content
 */
    public function update(Array $data=array())
    {
        $data = parent::update($data);

        // FIXME: use better validation on these parameters.
        $vars = array();
        if (!xarVarFetch('metakeywords',    'notempty', $vars['metakeywords'],    $this->metakeywords, XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('metadescription', 'notempty', $vars['metadescription'], $this->metadescription, XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('usegeo',          'int:0:1',  $vars['usegeo'],          $this->usegeo,  XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('longitude',       'notempty', $vars['longitude'],       $this->longitude, XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('latitude',        'notempty', $vars['latitude'],        $this->latitude, XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('usedk',           'notempty', $vars['usedk'],           $this->usedk, XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('copyrightpage',   'notempty', $vars['copyrightpage'],   $this->copyrightpage, XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('helppage',        'notempty', $vars['helppage'],        $this->helppage, XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('glossary',        'notempty', $vars['glossary'],        $this->glossary, XARVAR_NOT_REQUIRED)) return;

        // Merge the submitted block info content into the existing block info.
        $data['content'] = $vars; //array_merge($blockinfo['content'], $vars);

        return $data;
    }

}
?>
