<?php
/**
 *  Initialise meta block
 * @package modules
 * @subpackage themes module
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
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

        // Merge the submitted block info content into the existing block info.
        $data['content'] = $vars; //array_merge($blockinfo['content'], $vars);

        return $data;
    }
}
?>
