<?php
/**
 *  Initialise meta block
 * @package modules
 * @copyright see the html/credits.html file in this release
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

    sys::import('modules.themes.xarblocks.meta');

class Themes_MetaBlockAdmin extends Themes_MetaBlock
{
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
        if (empty($data['authorpage'])) $data['authorpage'] = xarServer::getBaseURL();

        $data['blockid'] = $data['bid'];

        return $data;
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
        if (!xarVarFetch('authorpage', 'pre:trim:str:1:', $vars['authorpage'], $this->authorpage, XARVAR_NOT_REQUIRED)) return;
        // Merge the submitted block info content into the existing block info.
        $vars += $data['content'];
        $data['content'] = $vars;

        return $data;
    }
}
?>
