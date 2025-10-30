<?php

/**
 * Xaraya Meta class library
 *
 * @package modules\themes
 * @subpackage themes
 * @copyright see the html/credits.html file in this release
 * @category Xaraya Web Applications Framework
 * @version 2.8.4
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/70.html
**/

sys::import('xaraya.services.xar');
use Xaraya\Services\xar;
use Xaraya\Services\WithServicesClass;

/**
 * Base Meta class
**/
class xarMeta extends xarObject
{
    use WithServicesClass;

    public const CACHE_SCOPE = 'Themes.Meta';
    // this singleton instance belongs with static services class (or service in it)
    private static $instance;
    // the queue of meta belongs to the instance
    private $meta;

    // prevent direct creation of this object
    private function __construct()
    {
        // Get list of tags from meta block and populate queue
        // NOTE: we CAN'T do this in the meta block when it's rendered, it's too
        // late to cater for content appended dynamically by other xar:meta tags
        $xar = $this->getServicesClass();
        $meta = @unserialize($xar->mod('themes')->getVar('meta.tags') ?? '');
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
     * @return object current instance
     *
    **/
    public static function getInstance()
    {
        $xar = xar::getServicesClass();
        if ($xar->mem()->has(self::CACHE_SCOPE, 'instance')) {
            $instance = $xar->mem()->get(self::CACHE_SCOPE, 'instance');
        } else {
            $c = __CLASS__;
            $instance = new $c();
            $xar->mem()->set(self::CACHE_SCOPE, 'instance', $instance);
        }
        return $instance;
    }

    /**
     * Register function
     *
     * Register meta data in queue for later rendering
     *
     * @author Chris Powis <crisp@xaraya.com>
     * @access public
     * @param array<string, mixed> $args array of optional parameters<br/>
     *         string $args[type] the type of meta tag, either name or http-equiv, required<br/>
     *         string $args[value] the value of the type, eg (author, rating, refresh, etc..), required<br/>
     *         string $args[content] the meta content, required<br/>
     *         string $args[lang] the ISO 639-1 language code for the content, optional<br/>
     *         string $args[dir] the text direction of the content (ltr|rtl), optional<br/>
     *         string $args[scheme] the scheme used to interpret the content, optional
     * @return bool|void true on success
    **/
    public function register(array $args = [])
    {
        extract($args);

        // check for required parameters with valid data types
        if ((empty($content) || !is_string($content))
            || (empty($type) || !is_string($type))
            || (empty($value) || !is_string($value))) {
            return;
        }

        $type = strtolower($type);
        $value = strtolower($value);
        $content = strip_tags($content);

        // make sure we have a valid type
        $metatypes = $this->getTypes();
        if (!isset($metatypes[$type])) {
            return;
        }

        // make sure we have a valid language
        $metalangs = $this->getLanguages();
        if (empty($lang) || !is_string($lang) || !isset($metalangs[$lang])) {
            $lang = '';
        }

        // make sure we have a valid text direction
        $metadirs = $this->getDirs();
        if (empty($dir) || !is_string($dir) || !isset($metadirs[$dir])) {
            $dir = '';
        }

        // make sure we have a valid data type for scheme
        if (empty($scheme) || !is_string($scheme)) {
            $scheme = '';
        }

        // flag to optionally append content to an existing tag
        // tag must have name or http-equiv attribute
        if (empty($append)) {
            $append = false;
        }

        // build the tag
        $tag = [
            'type'        => $type,    // name|http-equiv
            'value'       => $value,   // type value
            'content'     => $content, // meta content
            'lang'        => $lang,    // ISO 639-1 language code for content
            'dir'         => $dir,     // text direction of content (rtl, ltr)
            'scheme'      => $scheme,  // optional scheme used to interpret content
        ];

        return $this->queue($type, $value, $tag, $append);
    }
    /**
     * Render meta function
     *
     * Render queued meta tags
     *
     * @author Chris Powis <crisp@xaraya.com>
     * @access public
     * @param array<string, mixed> $args array of optional parameters (todo)
     * @return string templated output of meta tags to render
    **/
    public function render(array $args = [])
    {
        if (empty($this->meta)) {
            return '';
        }
        $xar = $this->getServicesClass();
        $args['meta'] = $this->meta;
        return $xar->tpl()->module('themes', 'meta', 'render', $args);
    }

    /**
     * Queue meta data for later rendering
     *
     * @author Chris Powis <crisp@xaraya.com>
     * @param  string $type one of http-equiv or name, required
     * @param  string $value the value of the http-equiv or name attribute, required
     * @param array<mixed> $tag array of tag attributes, required
     * @param  bool   $append flag to optionally append content to an existing tag (if exists)
     * @return bool|void true on success
    **/
    public function queue($type, $value, $tag, $append = false)
    {
        if (empty($type) || empty($value) || empty($tag)) {
            return;
        }
        $xar = $this->getServicesClass();

        // keep track of meta tags when we're caching
        $xar->cache()->addMeta($tag);

        // init the queue
        if (!isset($this->meta)) {
            $this->meta = [
                'http-equiv' => [],
                'name' => [],
            ];
        }

        // don't queue tags with invalid type attributes
        if (!isset($this->meta[$type])) {
            return;
        }

        // set unique index for this tag based on type, value and language
        $index = "$tag[type]:$tag[value]:$tag[lang]";

        // see if we're appending and we have a tag with the same language
        if ($append && isset($this->meta[$type][$value][$index])) {
            // get the queued tag
            $q = $this->meta[$type][$value][$index];
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
        $this->meta[$type][$value][$index] = $tag;

        return true;
    }

    /* Static helper methods */

    /**
     * Get Types
     *
     * Returns a list of meta tag types formatted for dropdown lists
     *
     * @author Chris Powis <crisp@xaraya.com>
     * @return array<mixed> list of types
    **/
    public static function getTypes()
    {
        $types = [
            'name' => ['id' => 'name', 'name' => 'name'],
            'http-equiv' => ['id' => 'http-equiv', 'name' => 'http-equiv'],
        ];
        return $types;
    }

    /**
     * Get Directions
     *
     * Returns a list of text directions formatted for dropdown lists
     *
     * @author Chris Powis <crisp@xaraya.com>
     * @return array<mixed> list of directions
    **/
    public static function getDirs()
    {
        $dirs = [
            'ltr' => ['id' => 'ltr', 'name' => 'ltr'],
            'rtl' => ['id' => 'rtl', 'name' => 'rtl'],
        ];
        return $dirs;
    }

    /**
     * Get Languages
     *
     * Returns a list of ISO 639-1 language codes formatted for dropdown lists
     *
     * @author Chris Powis <crisp@xaraya.com>
     * @param  bool $short optionally return the short description as name default true
     * @return array<mixed> list of language codes
    **/
    public static function getLanguages($short = true)
    {
        $codes = [
            'ab' => ['id' => 'ab', 'name' => $short ? 'ab' : 'Abkhazian (ab)'],
            'aa' => ['id' => 'aa', 'name' => $short ? 'aa' : 'Afar (aa)'],
            'af' => ['id' => 'af', 'name' => $short ? 'af' : 'Afrikaans (af)'],
            'sq' => ['id' => 'sq', 'name' => $short ? 'sq' : 'Albanian (sq)'],
            'am' => ['id' => 'am', 'name' => $short ? 'am' : 'Amharic (am)'],
            'ar' => ['id' => 'ar', 'name' => $short ? 'ar' : 'Arabic (ar)'],
            'hy' => ['id' => 'hy', 'name' => $short ? 'hy' : 'Armenian (hy)'],
            'as' => ['id' => 'as', 'name' => $short ? 'as' : 'Assamese (as)'],
            'ay' => ['id' => 'ay', 'name' => $short ? 'ay' : 'Aymara (ay)'],
            'az' => ['id' => 'az', 'name' => $short ? 'az' : 'Azerbaijani (az)'],
            'ba' => ['id' => 'ba', 'name' => $short ? 'ba' : 'Bashkir (ba)'],
            'eu' => ['id' => 'eu', 'name' => $short ? 'eu' : 'Basque (eu)'],
            'bn' => ['id' => 'bn', 'name' => $short ? 'bn' : 'Bengali (bn)'],
            'dz' => ['id' => 'dz', 'name' => $short ? 'dz' : 'Bhutani (dz)'],
            'bh' => ['id' => 'bh', 'name' => $short ? 'bh' : 'Bihari (bh)'],
            'bi' => ['id' => 'bi', 'name' => $short ? 'bi' : 'Bislama (bi)'],
            'br' => ['id' => 'br', 'name' => $short ? 'br' : 'Breton (br)'],
            'bg' => ['id' => 'bg', 'name' => $short ? 'bg' : 'Bulgarian (bg)'],
            'my' => ['id' => 'my', 'name' => $short ? 'my' : 'Burmese (my)'],
            'be' => ['id' => 'be', 'name' => $short ? 'be' : 'Byelorussion (be)'],
            'km' => ['id' => 'km', 'name' => $short ? 'km' : 'Cambodian (km)'],
            'ca' => ['id' => 'ca', 'name' => $short ? 'ca' : 'Catalan (ca)'],
            'zh' => ['id' => 'zh', 'name' => $short ? 'zh' : 'Chinese (zh)'],
            'co' => ['id' => 'co', 'name' => $short ? 'co' : 'Corsican (co)'],
            'hr' => ['id' => 'hr', 'name' => $short ? 'hr' : 'Croatian (hr)'],
            'cs' => ['id' => 'cs', 'name' => $short ? 'cs' : 'Czech (cs)'],
            'da' => ['id' => 'da', 'name' => $short ? 'da' : 'Danish (da)'],
            'nl' => ['id' => 'nl', 'name' => $short ? 'nl' : 'Dutch (nl)'],
            'en' => ['id' => 'en', 'name' => $short ? 'en' : 'English (en)'],
            'eo' => ['id' => 'eo', 'name' => $short ? 'eo' : 'Esperanto (eo)'],
            'et' => ['id' => 'et', 'name' => $short ? 'et' : 'Estonian (et)'],
            'fo' => ['id' => 'fo', 'name' => $short ? 'fo' : 'Faeroese (fo)'],
            'fa' => ['id' => 'fa', 'name' => $short ? 'fa' : 'Farsi (fa)'],
            'fj' => ['id' => 'fj', 'name' => $short ? 'fj' : 'Fiji (fj)'],
            'fi' => ['id' => 'fi', 'name' => $short ? 'fi' : 'Finnish (fi)'],
            'fr' => ['id' => 'fr', 'name' => $short ? 'fr' : 'French (fr)'],
            'fy' => ['id' => 'fy', 'name' => $short ? 'fy' : 'Frisian (fy)'],
            'gl' => ['id' => 'gl', 'name' => $short ? 'gl' : 'Galician (gl)'],
            'gd' => ['id' => 'gd', 'name' => $short ? 'gd' : 'Gaelic (Scottish) (gd)'],
            'gv' => ['id' => 'gv', 'name' => $short ? 'gv' : 'Gaelic (Manx) (gv)'],
            'ka' => ['id' => 'ka', 'name' => $short ? 'ka' : 'Georgian (ka)'],
            'de' => ['id' => 'de', 'name' => $short ? 'de' : 'German (de)'],
            'el' => ['id' => 'el', 'name' => $short ? 'el' : 'Greek (el)'],
            'kl' => ['id' => 'kl', 'name' => $short ? 'kl' : 'Greenlandic (kl)'],
            'gn' => ['id' => 'gn', 'name' => $short ? 'gn' : 'Guarani (gn)'],
            'gu' => ['id' => 'gu', 'name' => $short ? 'gu' : 'Gujarati (gu)'],
            'ha' => ['id' => 'ha', 'name' => $short ? 'ha' : 'Hausa (ha)'],
            'he' => ['id' => 'he', 'name' => $short ? 'he' : 'Hebrew (he)'],
            'iw' => ['id' => 'iw', 'name' => $short ? 'iw' : 'Hebrew (iw)'],
            'hi' => ['id' => 'hi', 'name' => $short ? 'hi' : 'Hindi (hi)'],
            'hu' => ['id' => 'hu', 'name' => $short ? 'hu' : 'Hungarian (hu)'],
            'is' => ['id' => 'is', 'name' => $short ? 'is' : 'Icelandic (is)'],
            'id' => ['id' => 'id', 'name' => $short ? 'id' : 'Indonesian (id)'],
            'in' => ['id' => 'in', 'name' => $short ? 'in' : 'Indonesian (in)'],
            'ia' => ['id' => 'ia', 'name' => $short ? 'ia' : 'Interlingua (ia)'],
            'ie' => ['id' => 'ie', 'name' => $short ? 'ie' : 'Interlingua (ie)'],
            'iu' => ['id' => 'iu', 'name' => $short ? 'iu' : 'Inuktitut (iu)'],
            'ik' => ['id' => 'ik', 'name' => $short ? 'ik' : 'Inupiak (ik)'],
            'ga' => ['id' => 'ga', 'name' => $short ? 'ga' : 'Irish (ga)'],
            'it' => ['id' => 'it', 'name' => $short ? 'it' : 'Italian (it)'],
            'ja' => ['id' => 'ja', 'name' => $short ? 'ja' : 'Japanese (ja)'],
            'jv' => ['id' => 'jv', 'name' => $short ? 'jv' : 'Javanese (jv)'],
            'kn' => ['id' => 'kn', 'name' => $short ? 'kn' : 'Kannada (kn)'],
            'ks' => ['id' => 'ks', 'name' => $short ? 'ks' : 'Kashmiri (ks)'],
            'kk' => ['id' => 'kk', 'name' => $short ? 'kk' : 'Kazakh (kk)'],
            'rw' => ['id' => 'rw', 'name' => $short ? 'rw' : 'Kinyarwanda (rw)'],
            'ky' => ['id' => 'ky', 'name' => $short ? 'ky' : 'Kirghiz (ky)'],
            'rn' => ['id' => 'rn', 'name' => $short ? 'rn' : 'Kirundi (rn)'],
            'ko' => ['id' => 'ko', 'name' => $short ? 'ko' : 'Korean (ko)'],
            'ku' => ['id' => 'ku', 'name' => $short ? 'ku' : 'Kurdish (ku)'],
            'lo' => ['id' => 'lo', 'name' => $short ? 'lo' : 'Laothian (lo)'],
            'la' => ['id' => 'la', 'name' => $short ? 'la' : 'Latin (la)'],
            'lv' => ['id' => 'lv', 'name' => $short ? 'lv' : 'Latvian (lv)'],
            'li' => ['id' => 'li', 'name' => $short ? 'li' : 'Limburgish (li)'],
            'ln' => ['id' => 'ln', 'name' => $short ? 'ln' : 'Lingala (ln)'],
            'lt' => ['id' => 'lt', 'name' => $short ? 'lt' : 'Lithuanian (lt)'],
            'mk' => ['id' => 'mk', 'name' => $short ? 'mk' : 'Macedonian (mk)'],
            'mg' => ['id' => 'mg', 'name' => $short ? 'mg' : 'Malagasay (mg)'],
            'ms' => ['id' => 'ms', 'name' => $short ? 'ms' : 'Malay (ms)'],
            'ml' => ['id' => 'ml', 'name' => $short ? 'ml' : 'Malayalam (ml)'],
            'mt' => ['id' => 'mt', 'name' => $short ? 'mt' : 'Maltese (mt)'],
            'mi' => ['id' => 'mi', 'name' => $short ? 'mi' : 'Maori (mi)'],
            'mr' => ['id' => 'mr', 'name' => $short ? 'mr' : 'Marathi (mr)'],
            'mo' => ['id' => 'mo', 'name' => $short ? 'mo' : 'Moldovian (mo)'],
            'mn' => ['id' => 'mn', 'name' => $short ? 'mn' : 'Mongolian (mn)'],
            'na' => ['id' => 'na', 'name' => $short ? 'na' : 'Nauru (na)'],
            'ne' => ['id' => 'ne', 'name' => $short ? 'ne' : 'Nepali (ne)'],
            'no' => ['id' => 'no', 'name' => $short ? 'no' : 'Norwegian (no)'],
            'oc' => ['id' => 'oc', 'name' => $short ? 'oc' : 'Occitan (oc)'],
            'or' => ['id' => 'or', 'name' => $short ? 'or' : 'Oriya (or)'],
            'om' => ['id' => 'om', 'name' => $short ? 'om' : 'Oromo (om)'],
            'ps' => ['id' => 'ps', 'name' => $short ? 'ps' : 'Pashto (ps)'],
            'pl' => ['id' => 'pl', 'name' => $short ? 'pl' : 'Polish (pl)'],
            'pt' => ['id' => 'pt', 'name' => $short ? 'pt' : 'Portuguese (pt)'],
            'pa' => ['id' => 'pa', 'name' => $short ? 'pa' : 'Punjabi (pa)'],
            'qu' => ['id' => 'qu', 'name' => $short ? 'qu' : 'Quechua (qu)'],
            'rm' => ['id' => 'rm', 'name' => $short ? 'rm' : 'Rhaeto-Romance (rm)'],
            'ro' => ['id' => 'ro', 'name' => $short ? 'ro' : 'Romanian (ro)'],
            'ru' => ['id' => 'ru', 'name' => $short ? 'ru' : 'Russian (ru)'],
            'sm' => ['id' => 'sm', 'name' => $short ? 'sm' : 'Samoan (sm)'],
            'sg' => ['id' => 'sg', 'name' => $short ? 'sg' : 'Sangro (sg)'],
            'sa' => ['id' => 'sa', 'name' => $short ? 'sa' : 'Sanskrit (sa)'],
            'sr' => ['id' => 'sr', 'name' => $short ? 'sr' : 'Serbian (sr)'],
            'sh' => ['id' => 'sh', 'name' => $short ? 'sh' : 'Serbo-Croatian (sh)'],
            'st' => ['id' => 'st', 'name' => $short ? 'st' : 'Sesotho (st)'],
            'tn' => ['id' => 'tn', 'name' => $short ? 'tn' : 'Setswana (tn)'],
            'sn' => ['id' => 'sn', 'name' => $short ? 'sn' : 'Shona (sn)'],
            'sd' => ['id' => 'sd', 'name' => $short ? 'sd' : 'Sindhi (sd)'],
            'si' => ['id' => 'si', 'name' => $short ? 'si' : 'Sinhalese (si)'],
            'ss' => ['id' => 'ss', 'name' => $short ? 'ss' : 'Siswati (ss)'],
            'sk' => ['id' => 'sk', 'name' => $short ? 'sk' : 'Slovak (sk)'],
            'sl' => ['id' => 'sl', 'name' => $short ? 'sl' : 'Slovenian (sl)'],
            'so' => ['id' => 'so', 'name' => $short ? 'so' : 'Somali (so)'],
            'es' => ['id' => 'es', 'name' => $short ? 'es' : 'Spanish (es)'],
            'su' => ['id' => 'su', 'name' => $short ? 'su' : 'Sundanese (su)'],
            'sw' => ['id' => 'sw', 'name' => $short ? 'sw' : 'Swahili (sw)'],
            'sv' => ['id' => 'sv', 'name' => $short ? 'sv' : 'Swedish (sv)'],
            'tl' => ['id' => 'tl', 'name' => $short ? 'tl' : 'Tagalog (tl)'],
            'tg' => ['id' => 'tg', 'name' => $short ? 'tg' : 'Tajik (tg)'],
            'ta' => ['id' => 'ta', 'name' => $short ? 'ta' : 'Tamil (ta)'],
            'tt' => ['id' => 'tt', 'name' => $short ? 'tt' : 'Tatar (tt)'],
            'te' => ['id' => 'te', 'name' => $short ? 'te' : 'Telugu (te)'],
            'th' => ['id' => 'th', 'name' => $short ? 'th' : 'Thai (th)'],
            'bo' => ['id' => 'bo', 'name' => $short ? 'bo' : 'Tibetan (bo)'],
            'ti' => ['id' => 'ti', 'name' => $short ? 'ti' : 'Tigrinya (ti)'],
            'to' => ['id' => 'to', 'name' => $short ? 'to' : 'Tonga (to)'],
            'ts' => ['id' => 'ts', 'name' => $short ? 'ts' : 'Tsonga (ts)'],
            'tr' => ['id' => 'tr', 'name' => $short ? 'tr' : 'Turkish (tr)'],
            'tk' => ['id' => 'tk', 'name' => $short ? 'tk' : 'Turkmen (tk)'],
            'tw' => ['id' => 'tw', 'name' => $short ? 'tw' : 'Twi (tw)'],
            'ug' => ['id' => 'ug', 'name' => $short ? 'ug' : 'Uighur (ug)'],
            'uk' => ['id' => 'uk', 'name' => $short ? 'uk' : 'Ukrainian (uk)'],
            'ur' => ['id' => 'ur', 'name' => $short ? 'ur' : 'Urdu (ur)'],
            'uz' => ['id' => 'uz', 'name' => $short ? 'uz' : 'Uzbek (uz)'],
            'vi' => ['id' => 'vi', 'name' => $short ? 'vi' : 'Vietnamese (vi)'],
            'vo' => ['id' => 'vo', 'name' => $short ? 'vo' : 'Volapük (vo)'],
            'cy' => ['id' => 'cy', 'name' => $short ? 'cy' : 'Welsh (cy)'],
            'wo' => ['id' => 'wo', 'name' => $short ? 'wo' : 'Wolof (wo)'],
            'xh' => ['id' => 'xh', 'name' => $short ? 'xh' : 'Xhosa (xh)'],
            'yi' => ['id' => 'yi', 'name' => $short ? 'yi' : 'Yiddish (yi)'],
            'ji' => ['id' => 'ji', 'name' => $short ? 'ji' : 'Yiddish (ji)'],
            'yo' => ['id' => 'yo', 'name' => $short ? 'yo' : 'Yoruba (yo)'],
            'zu' => ['id' => 'zu', 'name' => $short ? 'zu' : 'Zulu (zu)'],
        ];
        if ($short) {
            ksort($codes);
        }
        return $codes;
    }
}
