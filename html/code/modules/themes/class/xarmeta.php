<?php
/**
 * Xaraya Meta class library
 *
 * @package modules
 * @subpackage themes module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/70.html
**/
/**
 * Base Meta class
**/
class xarMeta extends Object
{
    private static $instance;
    private static $meta;

    // prevent direct creation of this object
    private function __construct()
    {
        // Get list of tags from meta block and populate queue
        // NOTE: we CAN'T do this in the meta block when it's rendered, it's too
        // late to cater for content appended dynamically by other xar:meta tags
        $meta = @unserialize(xarModVars::get('themes', 'meta.tags'));
        if (!empty($meta)) {
            foreach ($meta as $tag) {
                $this->register($tag);
            }
        }
    }

/**
 * Get instance function
 *
 * @author Chris Powis <crisp@xaraya.com>
 * @access public
 * @params none
 * @return Object current instance
 * @throws none
 *
**/
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            $c = __CLASS__;
            self::$instance = new $c;
        }
        return self::$instance;
    }

/**
 * Register function
 *
 * Register meta data in queue for later rendering
 *
 * @author Chris Powis <crisp@xaraya.com>
 * @access public
 * @params array  $args array of optional parameters<br/>
 *         string $args[type] the type of meta tag, either name or http-equiv, required<br/>
 *         string $args[value] the value of the type, eg (author, rating, refresh, etc..), required<br/>
 *         string $args[content] the meta content, required<br/>
 *         string $args[lang] the ISO 639-1 language code for the content, optional<br/>
 *         string $args[dir] the text direction of the content (ltr|rtl), optional<br/>
 *         string $args[scheme] the scheme used to interpret the content, optional
 * @throws none
 * @return bool true on success
**/
    public function register(Array $args=array())
    {
        extract($args);

        // check for required parameters with valid data types
        if ((empty($content) || !is_string($content)) ||
            (empty($type) || !is_string($type)) ||
            (empty($value) || !is_string($value))) return;

        $type = strtolower($type);
        $value = strtolower($value);
        $content = strip_tags($content);

        // make sure we have a valid type
        $metatypes = $this->getTypes();
        if (!isset($metatypes[$type])) return;

        // make sure we have a valid language
        $metalangs = $this->getLanguages();
        if (empty($lang) || !is_string($lang) || !isset($metalangs[$lang]))
            $lang = '';

        // make sure we have a valid text direction
        $metadirs = $this->getDirs();
        if (empty($dir) || !is_string($dir) || !isset($metadirs[$dir]))
            $dir = '';

        // make sure we have a valid data type for scheme
        if (empty($scheme) || !is_string($scheme))
            $scheme = '';

        // flag to optionally append content to an existing tag
        // tag must have name or http-equiv attribute
        if (empty($append))
            $append = false;

        // build the tag
        $tag = array(
            'type'        => $type,    // name|http-equiv
            'value'       => $value,   // type value
            'content'     => $content, // meta content
            'lang'        => $lang,    // ISO 639-1 language code for content
            'dir'         => $dir,     // text direction of content (rtl, ltr)
            'scheme'      => $scheme,  // optional scheme used to interpret content
        );

        return $this->queue($type, $value, $tag, $append);
    }
/**
 * Render meta function
 *
 * Render queued meta tags
 *
 * @author Chris Powis <crisp@xaraya.com>
 * @access public
 * @param array   $args array of optional parameters (todo)
 * @return string templated output of meta tags to render
 * @throws none
**/
    public function render(Array $args=array())
    {
        if (empty(self::$meta)) return;
        $args['meta'] = self::$meta;
        return xarTpl::module('themes', 'meta', 'render', $args);
    }

/**
 * Queue meta data for later rendering
 *
 * @author Chris Powis <crisp@xaraya.com>
 * @param  string $type one of http-equiv or name, required
 * @param  string $value the value of the http-equiv or name attribute, required
 * @param  array  $tag array of tag attributes, required
 * @param  bool   $append flag to optionally append content to an existing tag (if exists)
 * @throws none
 * @return bool true on success
**/
    public function queue($type, $value, $tag, $append=false)
    {
        if (empty($type) || empty($value) || empty($tag)) return;

        // init the queue
        if (!isset(self::$meta)) {
            self::$meta = array(
                'http-equiv' => array(),
                'name' => array(),
            );
        }

        // don't queue tags with invalid type attributes
        if (!isset(self::$meta[$type])) return;

        // set unique index for this tag based on type, value and language
        $index = "$tag[type]:$tag[value]:$tag[lang]";

        // see if we're appending and we have a tag with the same language
        if ($append && isset(self::$meta[$type][$value][$index])) {
            // get the queued tag
            $q = self::$meta[$type][$value][$index];
            // append content
            $q['content'] .= "; $tag[content]";
            // merge any data not already populated from the incoming tag
            foreach ($tag as $k => $v) {
                if ($k != 'content' && empty($q[$k])) {
                    $q[$k] = $v;
                }
            }
            $tag = $q;
        }

        // queue the tag
        self::$meta[$type][$value][$index] = $tag;

        return true;
    }

/* Static helper methods */

/**
 * Get Types
 *
 * Returns a list of meta tag types formatted for dropdown lists
 *
 * @author Chris Powis <crisp@xaraya.com>
 * @params none
 * @return array list of types
 * @throws none
**/
    public static function getTypes()
    {
        $types = array(
            'name' => array('id' => 'name', 'name' => 'name'),
            'http-equiv' => array('id' => 'http-equiv', 'name' => 'http-equiv'),
        );
        return $types;
    }

/**
 * Get Directions
 *
 * Returns a list of text directions formatted for dropdown lists
 *
 * @author Chris Powis <crisp@xaraya.com>
 * @params none
 * @return array list of directions
 * @throws none
**/
    public static function getDirs()
    {
        $dirs = array(
            'ltr' => array('id' => 'ltr', 'name' => 'ltr'),
            'rtl' => array('id' => 'rtl', 'name' => 'rtl'),
        );
        return $dirs;
    }

/**
 * Get Languages
 *
 * Returns a list of ISO 639-1 language codes formatted for dropdown lists
 *
 * @author Chris Powis <crisp@xaraya.com>
 * @param  bool $short optionally return the short description as name default true
 * @return array list of language codes
 * @throws none
**/
    public static function getLanguages($short=true)
    {
        $codes = array(
            'ab' => array('id' => 'ab', 'name' => $short ? 'ab' : 'Abkhazian (ab)'),
            'aa' => array('id' => 'aa', 'name' => $short ? 'aa' : 'Afar (aa)'),
            'af' => array('id' => 'af', 'name' => $short ? 'af' : 'Afrikaans (af)'),
            'sq' => array('id' => 'sq', 'name' => $short ? 'sq' : 'Albanian (sq)'),
            'am' => array('id' => 'am', 'name' => $short ? 'am' : 'Amharic (am)'),
            'ar' => array('id' => 'ar', 'name' => $short ? 'ar' : 'Arabic (ar)'),
            'hy' => array('id' => 'hy', 'name' => $short ? 'hy' : 'Armenian (hy)'),
            'as' => array('id' => 'as', 'name' => $short ? 'as' : 'Assamese (as)'),
            'ay' => array('id' => 'ay', 'name' => $short ? 'ay' : 'Aymara (ay)'),
            'az' => array('id' => 'az', 'name' => $short ? 'az' : 'Azerbaijani (az)'),
            'ba' => array('id' => 'ba', 'name' => $short ? 'ba' : 'Bashkir (ba)'),
            'eu' => array('id' => 'eu', 'name' => $short ? 'eu' : 'Basque (eu)'),
            'bn' => array('id' => 'bn', 'name' => $short ? 'bn' : 'Bengali (bn)'),
            'dz' => array('id' => 'dz', 'name' => $short ? 'dz' : 'Bhutani (dz)'),
            'bh' => array('id' => 'bh', 'name' => $short ? 'bh' : 'Bihari (bh)'),
            'bi' => array('id' => 'bi', 'name' => $short ? 'bi' : 'Bislama (bi)'),
            'br' => array('id' => 'br', 'name' => $short ? 'br' : 'Breton (br)'),
            'bg' => array('id' => 'bg', 'name' => $short ? 'bg' : 'Bulgarian (bg)'),
            'my' => array('id' => 'my', 'name' => $short ? 'my' : 'Burmese (my)'),
            'be' => array('id' => 'be', 'name' => $short ? 'be' : 'Byelorussion (be)'),
            'km' => array('id' => 'km', 'name' => $short ? 'km' : 'Cambodian (km)'),
            'ca' => array('id' => 'ca', 'name' => $short ? 'ca' : 'Catalan (ca)'),
            'zh' => array('id' => 'zh', 'name' => $short ? 'zh' : 'Chinese (zh)'),
            'co' => array('id' => 'co', 'name' => $short ? 'co' : 'Corsican (co)'),
            'hr' => array('id' => 'hr', 'name' => $short ? 'hr' : 'Croatian (hr)'),
            'cs' => array('id' => 'cs', 'name' => $short ? 'cs' : 'Czech (cs)'),
            'da' => array('id' => 'da', 'name' => $short ? 'da' : 'Danish (da)'),
            'nl' => array('id' => 'nl', 'name' => $short ? 'nl' : 'Dutch (nl)'),
            'en' => array('id' => 'en', 'name' => $short ? 'en' : 'English (en)'),
            'eo' => array('id' => 'eo', 'name' => $short ? 'eo' : 'Esperanto (eo)'),
            'et' => array('id' => 'et', 'name' => $short ? 'et' : 'Estonian (et)'),
            'fo' => array('id' => 'fo', 'name' => $short ? 'fo' : 'Faeroese (fo)'),
            'fa' => array('id' => 'fa', 'name' => $short ? 'fa' : 'Farsi (fa)'),
            'fj' => array('id' => 'fj', 'name' => $short ? 'fj' : 'Fiji (fj)'),
            'fi' => array('id' => 'fi', 'name' => $short ? 'fi' : 'Finnish (fi)'),
            'fr' => array('id' => 'fr', 'name' => $short ? 'fr' : 'French (fr)'),
            'fy' => array('id' => 'fy', 'name' => $short ? 'fy' : 'Frisian (fy)'),
            'gl' => array('id' => 'gl', 'name' => $short ? 'gl' : 'Galician (gl)'),
            'gd' => array('id' => 'gd', 'name' => $short ? 'gd' : 'Gaelic (Scottish) (gd)'),
            'gv' => array('id' => 'gv', 'name' => $short ? 'gv' : 'Gaelic (Manx) (gv)'),
            'ka' => array('id' => 'ka', 'name' => $short ? 'ka' : 'Georgian (ka)'),
            'de' => array('id' => 'de', 'name' => $short ? 'de' : 'German (de)'),
            'el' => array('id' => 'el', 'name' => $short ? 'el' : 'Greek (el)'),
            'kl' => array('id' => 'kl', 'name' => $short ? 'kl' : 'Greenlandic (kl)'),
            'gn' => array('id' => 'gn', 'name' => $short ? 'gn' : 'Guarani (gn)'),
            'gu' => array('id' => 'gu', 'name' => $short ? 'gu' : 'Gujarati (gu)'),
            'ha' => array('id' => 'ha', 'name' => $short ? 'ha' : 'Hausa (ha)'),
            'he' => array('id' => 'he', 'name' => $short ? 'he' : 'Hebrew (he)'),
            'iw' => array('id' => 'iw', 'name' => $short ? 'iw' : 'Hebrew (iw)'),
            'hi' => array('id' => 'hi', 'name' => $short ? 'hi' : 'Hindi (hi)'),
            'hu' => array('id' => 'hu', 'name' => $short ? 'hu' : 'Hungarian (hu)'),
            'is' => array('id' => 'is', 'name' => $short ? 'is' : 'Icelandic (is)'),
            'id' => array('id' => 'id', 'name' => $short ? 'id' : 'Indonesian (id)'),
            'in' => array('id' => 'in', 'name' => $short ? 'in' : 'Indonesian (in)'),
            'ia' => array('id' => 'ia', 'name' => $short ? 'ia' : 'Interlingua (ia)'),
            'ie' => array('id' => 'ie', 'name' => $short ? 'ie' : 'Interlingua (ie)'),
            'iu' => array('id' => 'iu', 'name' => $short ? 'iu' : 'Inuktitut (iu)'),
            'ik' => array('id' => 'ik', 'name' => $short ? 'ik' : 'Inupiak (ik)'),
            'ga' => array('id' => 'ga', 'name' => $short ? 'ga' : 'Irish (ga)'),
            'it' => array('id' => 'it', 'name' => $short ? 'it' : 'Italian (it)'),
            'ja' => array('id' => 'ja', 'name' => $short ? 'ja' : 'Japanese (ja)'),
            'jv' => array('id' => 'jv', 'name' => $short ? 'jv' : 'Javanese (jv)'),
            'kn' => array('id' => 'kn', 'name' => $short ? 'kn' : 'Kannada (kn)'),
            'ks' => array('id' => 'ks', 'name' => $short ? 'ks' : 'Kashmiri (ks)'),
            'kk' => array('id' => 'kk', 'name' => $short ? 'kk' : 'Kazakh (kk)'),
            'rw' => array('id' => 'rw', 'name' => $short ? 'rw' : 'Kinyarwanda (rw)'),
            'ky' => array('id' => 'ky', 'name' => $short ? 'ky' : 'Kirghiz (ky)'),
            'rn' => array('id' => 'rn', 'name' => $short ? 'rn' : 'Kirundi (rn)'),
            'ko' => array('id' => 'ko', 'name' => $short ? 'ko' : 'Korean (ko)'),
            'ku' => array('id' => 'ku', 'name' => $short ? 'ku' : 'Kurdish (ku)'),
            'lo' => array('id' => 'lo', 'name' => $short ? 'lo' : 'Laothian (lo)'),
            'la' => array('id' => 'la', 'name' => $short ? 'la' : 'Latin (la)'),
            'lv' => array('id' => 'lv', 'name' => $short ? 'lv' : 'Latvian (lv)'),
            'li' => array('id' => 'li', 'name' => $short ? 'li' : 'Limburgish (li)'),
            'ln' => array('id' => 'ln', 'name' => $short ? 'ln' : 'Lingala (ln)'),
            'lt' => array('id' => 'lt', 'name' => $short ? 'lt' : 'Lithuanian (lt)'),
            'mk' => array('id' => 'mk', 'name' => $short ? 'mk' : 'Macedonian (mk)'),
            'mg' => array('id' => 'mg', 'name' => $short ? 'mg' : 'Malagasay (mg)'),
            'ms' => array('id' => 'ms', 'name' => $short ? 'ms' : 'Malay (ms)'),
            'ml' => array('id' => 'ml', 'name' => $short ? 'ml' : 'Malayalam (ml)'),
            'mt' => array('id' => 'mt', 'name' => $short ? 'mt' : 'Maltese (mt)'),
            'mi' => array('id' => 'mi', 'name' => $short ? 'mi' : 'Maori (mi)'),
            'mr' => array('id' => 'mr', 'name' => $short ? 'mr' : 'Marathi (mr)'),
            'mo' => array('id' => 'mo', 'name' => $short ? 'mo' : 'Moldovian (mo)'),
            'mn' => array('id' => 'mn', 'name' => $short ? 'mn' : 'Mongolian (mn)'),
            'na' => array('id' => 'na', 'name' => $short ? 'na' : 'Nauru (na)'),
            'ne' => array('id' => 'ne', 'name' => $short ? 'ne' : 'Nepali (ne)'),
            'no' => array('id' => 'no', 'name' => $short ? 'no' : 'Norwegian (no)'),
            'oc' => array('id' => 'oc', 'name' => $short ? 'oc' : 'Occitan (oc)'),
            'or' => array('id' => 'or', 'name' => $short ? 'or' : 'Oriya (or)'),
            'om' => array('id' => 'om', 'name' => $short ? 'om' : 'Oromo (om)'),
            'ps' => array('id' => 'ps', 'name' => $short ? 'ps' : 'Pashto (ps)'),
            'pl' => array('id' => 'pl', 'name' => $short ? 'pl' : 'Polish (pl)'),
            'pt' => array('id' => 'pt', 'name' => $short ? 'pt' : 'Portuguese (pt)'),
            'pa' => array('id' => 'pa', 'name' => $short ? 'pa' : 'Punjabi (pa)'),
            'qu' => array('id' => 'qu', 'name' => $short ? 'qu' : 'Quechua (qu)'),
            'rm' => array('id' => 'rm', 'name' => $short ? 'rm' : 'Rhaeto-Romance (rm)'),
            'ro' => array('id' => 'ro', 'name' => $short ? 'ro' : 'Romanian (ro)'),
            'ru' => array('id' => 'ru', 'name' => $short ? 'ru' : 'Russian (ru)'),
            'sm' => array('id' => 'sm', 'name' => $short ? 'sm' : 'Samoan (sm)'),
            'sg' => array('id' => 'sg', 'name' => $short ? 'sg' : 'Sangro (sg)'),
            'sa' => array('id' => 'sa', 'name' => $short ? 'sa' : 'Sanskrit (sa)'),
            'sr' => array('id' => 'sr', 'name' => $short ? 'sr' : 'Serbian (sr)'),
            'sh' => array('id' => 'sh', 'name' => $short ? 'sh' : 'Serbo-Croatian (sh)'),
            'st' => array('id' => 'st', 'name' => $short ? 'st' : 'Sesotho (st)'),
            'tn' => array('id' => 'tn', 'name' => $short ? 'tn' : 'Setswana (tn)'),
            'sn' => array('id' => 'sn', 'name' => $short ? 'sn' : 'Shona (sn)'),
            'sd' => array('id' => 'sd', 'name' => $short ? 'sd' : 'Sindhi (sd)'),
            'si' => array('id' => 'si', 'name' => $short ? 'si' : 'Sinhalese (si)'),
            'ss' => array('id' => 'ss', 'name' => $short ? 'ss' : 'Siswati (ss)'),
            'sk' => array('id' => 'sk', 'name' => $short ? 'sk' : 'Slovak (sk)'),
            'sl' => array('id' => 'sl', 'name' => $short ? 'sl' : 'Slovenian (sl)'),
            'so' => array('id' => 'so', 'name' => $short ? 'so' : 'Somali (so)'),
            'es' => array('id' => 'es', 'name' => $short ? 'es' : 'Spanish (es)'),
            'su' => array('id' => 'su', 'name' => $short ? 'su' : 'Sundanese (su)'),
            'sw' => array('id' => 'sw', 'name' => $short ? 'sw' : 'Swahili (sw)'),
            'sv' => array('id' => 'sv', 'name' => $short ? 'sv' : 'Swedish (sv)'),
            'tl' => array('id' => 'tl', 'name' => $short ? 'tl' : 'Tagalog (tl)'),
            'tg' => array('id' => 'tg', 'name' => $short ? 'tg' : 'Tajik (tg)'),
            'ta' => array('id' => 'ta', 'name' => $short ? 'ta' : 'Tamil (ta)'),
            'tt' => array('id' => 'tt', 'name' => $short ? 'tt' : 'Tatar (tt)'),
            'te' => array('id' => 'te', 'name' => $short ? 'te' : 'Telugu (te)'),
            'th' => array('id' => 'th', 'name' => $short ? 'th' : 'Thai (th)'),
            'bo' => array('id' => 'bo', 'name' => $short ? 'bo' : 'Tibetan (bo)'),
            'ti' => array('id' => 'ti', 'name' => $short ? 'ti' : 'Tigrinya (ti)'),
            'to' => array('id' => 'to', 'name' => $short ? 'to' : 'Tonga (to)'),
            'ts' => array('id' => 'ts', 'name' => $short ? 'ts' : 'Tsonga (ts)'),
            'tr' => array('id' => 'tr', 'name' => $short ? 'tr' : 'Turkish (tr)'),
            'tk' => array('id' => 'tk', 'name' => $short ? 'tk' : 'Turkmen (tk)'),
            'tw' => array('id' => 'tw', 'name' => $short ? 'tw' : 'Twi (tw)'),
            'ug' => array('id' => 'ug', 'name' => $short ? 'ug' : 'Uighur (ug)'),
            'uk' => array('id' => 'uk', 'name' => $short ? 'uk' : 'Ukrainian (uk)'),
            'ur' => array('id' => 'ur', 'name' => $short ? 'ur' : 'Urdu (ur)'),
            'uz' => array('id' => 'uz', 'name' => $short ? 'uz' : 'Uzbek (uz)'),
            'vi' => array('id' => 'vi', 'name' => $short ? 'vi' : 'Vietnamese (vi)'),
            'vo' => array('id' => 'vo', 'name' => $short ? 'vo' : 'Volapük (vo)'),
            'cy' => array('id' => 'cy', 'name' => $short ? 'cy' : 'Welsh (cy)'),
            'wo' => array('id' => 'wo', 'name' => $short ? 'wo' : 'Wolof (wo)'),
            'xh' => array('id' => 'xh', 'name' => $short ? 'xh' : 'Xhosa (xh)'),
            'yi' => array('id' => 'yi', 'name' => $short ? 'yi' : 'Yiddish (yi)'),
            'ji' => array('id' => 'ji', 'name' => $short ? 'ji' : 'Yiddish (ji)'),
            'yo' => array('id' => 'yo', 'name' => $short ? 'yo' : 'Yoruba (yo)'),
            'zu' => array('id' => 'zu', 'name' => $short ? 'zu' : 'Zulu (zu)'),
        );
        if ($short) ksort($codes);
        return $codes;
    }
}
?>