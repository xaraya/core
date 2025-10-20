<?php

/**
 * Include the base class
 */
sys::import('modules.base.xarproperties.dropdown');
/**
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/68.html
 *
 * @author John Cox
 */

/**
 * This property displays a dropdown of countries
 */
class CountryListProperty extends SelectProperty
{
    public $id         = 42;
    public $name       = 'countrylisting';
    public $desc       = 'Country Dropdown';

    public function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->template  = 'countrylisting';
    }

    /**
     * Country list according to ISO 3166
     *
     * @author jojodee
     * Updated 2005-10-15 with ISO 3166 country codes
     * Credit to Pedro Innecco for corrections and updates
     */
    public function getOptions()
    {
        if (count($this->options) > 0) {
            return $this->options;
        }

        $options[] = ['id' => 'af', 'name' => $this->ml('Afghanistan')];
        $options[] = ['id' => 'ax', 'name' => $this->ml('&#197;land Islands')];
        $options[] = ['id' => 'al', 'name' => $this->ml('Albania')];
        $options[] = ['id' => 'dz', 'name' => $this->ml('Algeria')];
        $options[] = ['id' => 'as', 'name' => $this->ml('American Samoa')];
        $options[] = ['id' => 'ad', 'name' => $this->ml('Andorra')];
        $options[] = ['id' => 'ao', 'name' => $this->ml('Angola')];
        $options[] = ['id' => 'ai', 'name' => $this->ml('Anguilla')];
        $options[] = ['id' => 'aq', 'name' => $this->ml('Antarctica')];
        $options[] = ['id' => 'ag', 'name' => $this->ml('Antigua and Barbuda')];
        $options[] = ['id' => 'ar', 'name' => $this->ml('Argentina')];
        $options[] = ['id' => 'am', 'name' => $this->ml('Armenia')];
        $options[] = ['id' => 'aw', 'name' => $this->ml('Aruba')];
        $options[] = ['id' => 'au', 'name' => $this->ml('Australia')];
        $options[] = ['id' => 'at', 'name' => $this->ml('Austria')];
        $options[] = ['id' => 'az', 'name' => $this->ml('Azerbaijan')];
        $options[] = ['id' => 'bs', 'name' => $this->ml('Bahamas')];
        $options[] = ['id' => 'bh', 'name' => $this->ml('Bahrain')];
        $options[] = ['id' => 'bd', 'name' => $this->ml('Bangladesh')];
        $options[] = ['id' => 'bb', 'name' => $this->ml('Barbados')];
        $options[] = ['id' => 'by', 'name' => $this->ml('Belarus')];
        $options[] = ['id' => 'be', 'name' => $this->ml('Belgium')];
        $options[] = ['id' => 'bz', 'name' => $this->ml('Belize')];
        $options[] = ['id' => 'bj', 'name' => $this->ml('Benin')];
        $options[] = ['id' => 'bm', 'name' => $this->ml('Bermuda')];
        $options[] = ['id' => 'bt', 'name' => $this->ml('Bhutan')];
        $options[] = ['id' => 'bo', 'name' => $this->ml('Bolivia')];
        $options[] = ['id' => 'ba', 'name' => $this->ml('Bosnia and Herzegovina')];
        $options[] = ['id' => 'bw', 'name' => $this->ml('Botswana')];
        $options[] = ['id' => 'bv', 'name' => $this->ml('Bouvet Island')];
        $options[] = ['id' => 'br', 'name' => $this->ml('Brazil')];
        $options[] = ['id' => 'io', 'name' => $this->ml('British Indian Ocean Territory')];
        $options[] = ['id' => 'bn', 'name' => $this->ml('Brunei Darussalam')];
        $options[] = ['id' => 'bg', 'name' => $this->ml('Bulgaria')];
        $options[] = ['id' => 'bf', 'name' => $this->ml('Burkina Faso')];
        $options[] = ['id' => 'bi', 'name' => $this->ml('Burundi')];
        $options[] = ['id' => 'kh', 'name' => $this->ml('Cambodia')];
        $options[] = ['id' => 'cm', 'name' => $this->ml('Cameroon')];
        $options[] = ['id' => 'ca', 'name' => $this->ml('Canada')];
        $options[] = ['id' => 'cv', 'name' => $this->ml('Cape Verde')];
        $options[] = ['id' => 'ky', 'name' => $this->ml('Cayman Islands')];
        $options[] = ['id' => 'cf', 'name' => $this->ml('Central African Republic')];
        $options[] = ['id' => 'td', 'name' => $this->ml('Chad')];
        $options[] = ['id' => 'cl', 'name' => $this->ml('Chile')];
        $options[] = ['id' => 'cn', 'name' => $this->ml('China')];
        $options[] = ['id' => 'cx', 'name' => $this->ml('Christmas Island')];
        $options[] = ['id' => 'cc', 'name' => $this->ml('Cocos (Keeling) Islands')];
        $options[] = ['id' => 'co', 'name' => $this->ml('Colombia')];
        $options[] = ['id' => 'km', 'name' => $this->ml('Comoros')];
        $options[] = ['id' => 'cg', 'name' => $this->ml('Congo, Republic of')];
        $options[] = ['id' => 'cd', 'name' => $this->ml('Congo, Democratic Republic of (Zaire)')];
        $options[] = ['id' => 'ck', 'name' => $this->ml('Cook Islands')];
        $options[] = ['id' => 'cr', 'name' => $this->ml('Costa Rica')];
        $options[] = ['id' => 'ci', 'name' => $this->ml('C&#244;te D\'Ivoire')];
        $options[] = ['id' => 'hr', 'name' => $this->ml('Croatia')];
        $options[] = ['id' => 'cu', 'name' => $this->ml('Cuba')];
        $options[] = ['id' => 'cy', 'name' => $this->ml('Cyprus')];
        $options[] = ['id' => 'cz', 'name' => $this->ml('Czech Republic')];
        $options[] = ['id' => 'dk', 'name' => $this->ml('Denmark')];
        $options[] = ['id' => 'dj', 'name' => $this->ml('Djibouti')];
        $options[] = ['id' => 'dm', 'name' => $this->ml('Dominica')];
        $options[] = ['id' => 'do', 'name' => $this->ml('Dominican Republic')];
        $options[] = ['id' => 'ec', 'name' => $this->ml('Ecuador')];
        $options[] = ['id' => 'eg', 'name' => $this->ml('Egypt')];
        $options[] = ['id' => 'sv', 'name' => $this->ml('El Salvador')];
        $options[] = ['id' => 'gq', 'name' => $this->ml('Equatorial Guinea')];
        $options[] = ['id' => 'er', 'name' => $this->ml('Eritrea')];
        $options[] = ['id' => 'ee', 'name' => $this->ml('Estonia')];
        $options[] = ['id' => 'et', 'name' => $this->ml('Ethiopia')];
        $options[] = ['id' => 'fk', 'name' => $this->ml('Falkland Islands (Malvinas)')];
        $options[] = ['id' => 'fo', 'name' => $this->ml('Faroe Islands')];
        $options[] = ['id' => 'fj', 'name' => $this->ml('Fiji')];
        $options[] = ['id' => 'fi', 'name' => $this->ml('Finland')];
        $options[] = ['id' => 'fr', 'name' => $this->ml('France')];
        $options[] = ['id' => 'gf', 'name' => $this->ml('French Guiana')];
        $options[] = ['id' => 'pf', 'name' => $this->ml('French Polynesia')];
        $options[] = ['id' => 'tf', 'name' => $this->ml('French Southern Territories')];
        $options[] = ['id' => 'ga', 'name' => $this->ml('Gabon')];
        $options[] = ['id' => 'gm', 'name' => $this->ml('Gambia')];
        $options[] = ['id' => 'ge', 'name' => $this->ml('Georgia')];
        $options[] = ['id' => 'de', 'name' => $this->ml('Germany')];
        $options[] = ['id' => 'gh', 'name' => $this->ml('Ghana')];
        $options[] = ['id' => 'gi', 'name' => $this->ml('Gibraltar')];
        $options[] = ['id' => 'gr', 'name' => $this->ml('Greece')];
        $options[] = ['id' => 'gl', 'name' => $this->ml('Greenland')];
        $options[] = ['id' => 'gd', 'name' => $this->ml('Grenada')];
        $options[] = ['id' => 'gp', 'name' => $this->ml('Guadeloupe')];
        $options[] = ['id' => 'gu', 'name' => $this->ml('Guam')];
        $options[] = ['id' => 'gt', 'name' => $this->ml('Guatemala')];
        $options[] = ['id' => 'gn', 'name' => $this->ml('Guinea')];
        $options[] = ['id' => 'gw', 'name' => $this->ml('Guinea-Bissau')];
        $options[] = ['id' => 'gy', 'name' => $this->ml('Guyana')];
        $options[] = ['id' => 'ht', 'name' => $this->ml('Haiti')];
        $options[] = ['id' => 'hm', 'name' => $this->ml('Heard Island &#38; McDonald Islands')];
        $options[] = ['id' => 'hn', 'name' => $this->ml('Honduras')];
        $options[] = ['id' => 'hk', 'name' => $this->ml('Hong Kong')];
        $options[] = ['id' => 'hu', 'name' => $this->ml('Hungary')];
        $options[] = ['id' => 'is', 'name' => $this->ml('Iceland')];
        $options[] = ['id' => 'in', 'name' => $this->ml('India')];
        $options[] = ['id' => 'id', 'name' => $this->ml('Indonesia')];
        $options[] = ['id' => 'ir', 'name' => $this->ml('Iran')];
        $options[] = ['id' => 'iq', 'name' => $this->ml('Iraq')];
        $options[] = ['id' => 'ie', 'name' => $this->ml('Ireland')];
        $options[] = ['id' => 'il', 'name' => $this->ml('Israel')];
        $options[] = ['id' => 'it', 'name' => $this->ml('Italy')];
        $options[] = ['id' => 'jm', 'name' => $this->ml('Jamaica')];
        $options[] = ['id' => 'jp', 'name' => $this->ml('Japan')];
        $options[] = ['id' => 'jo', 'name' => $this->ml('Jordan')];
        $options[] = ['id' => 'kz', 'name' => $this->ml('Kazakhstan')];
        $options[] = ['id' => 'ke', 'name' => $this->ml('Kenya')];
        $options[] = ['id' => 'ki', 'name' => $this->ml('Kiribati')];
        $options[] = ['id' => 'kp', 'name' => $this->ml('Democratic People\'s Republic Korea')];
        $options[] = ['id' => 'kr', 'name' => $this->ml('Republic of Korea')];
        $options[] = ['id' => 'kw', 'name' => $this->ml('Kuwait')];
        $options[] = ['id' => 'kg', 'name' => $this->ml('Kyrgyzstan')];
        $options[] = ['id' => 'la', 'name' => $this->ml('Lao People\'s Democratic Republic')];
        $options[] = ['id' => 'lv', 'name' => $this->ml('Latvia')];
        $options[] = ['id' => 'lb', 'name' => $this->ml('Lebanon')];
        $options[] = ['id' => 'ls', 'name' => $this->ml('Lesotho')];
        $options[] = ['id' => 'lr', 'name' => $this->ml('Liberia')];
        $options[] = ['id' => 'ly', 'name' => $this->ml('Libya')];
        $options[] = ['id' => 'li', 'name' => $this->ml('Liechtenstein')];
        $options[] = ['id' => 'lt', 'name' => $this->ml('Lithuania')];
        $options[] = ['id' => 'lu', 'name' => $this->ml('Luxembourg')];
        $options[] = ['id' => 'mo', 'name' => $this->ml('Macao')];
        $options[] = ['id' => 'mk', 'name' => $this->ml('Macedonia, The Former Yugoslav Republic of')];
        $options[] = ['id' => 'mg', 'name' => $this->ml('Madagascar')];
        $options[] = ['id' => 'mw', 'name' => $this->ml('Malawi')];
        $options[] = ['id' => 'my', 'name' => $this->ml('Malaysia')];
        $options[] = ['id' => 'mv', 'name' => $this->ml('Maldives')];
        $options[] = ['id' => 'ml', 'name' => $this->ml('Mali')];
        $options[] = ['id' => 'mt', 'name' => $this->ml('Malta')];
        $options[] = ['id' => 'mh', 'name' => $this->ml('Marshall Islands')];
        $options[] = ['id' => 'mq', 'name' => $this->ml('Martinique')];
        $options[] = ['id' => 'mr', 'name' => $this->ml('Mauritania')];
        $options[] = ['id' => 'mu', 'name' => $this->ml('Mauritius')];
        $options[] = ['id' => 'yt', 'name' => $this->ml('Mayotte')];
        $options[] = ['id' => 'mx', 'name' => $this->ml('Mexico')];
        $options[] = ['id' => 'fm', 'name' => $this->ml('Micronesia, Federated States of')];
        $options[] = ['id' => 'md', 'name' => $this->ml('Moldova, Republic of')];
        $options[] = ['id' => 'mc', 'name' => $this->ml('Monaco')];
        $options[] = ['id' => 'mn', 'name' => $this->ml('Mongolia')];
        $options[] = ['id' => 'ms', 'name' => $this->ml('Montserrat')];
        $options[] = ['id' => 'ma', 'name' => $this->ml('Morocco')];
        $options[] = ['id' => 'mz', 'name' => $this->ml('Mozambique')];
        $options[] = ['id' => 'mm', 'name' => $this->ml('Myanmar')];
        $options[] = ['id' => 'na', 'name' => $this->ml('Namibia')];
        $options[] = ['id' => 'nr', 'name' => $this->ml('Nauru')];
        $options[] = ['id' => 'np', 'name' => $this->ml('Nepal')];
        $options[] = ['id' => 'nl', 'name' => $this->ml('Netherlands')];
        $options[] = ['id' => 'an', 'name' => $this->ml('Netherlands Antilles')];
        $options[] = ['id' => 'nc', 'name' => $this->ml('New Caledonia')];
        $options[] = ['id' => 'nz', 'name' => $this->ml('New Zealand')];
        $options[] = ['id' => 'ni', 'name' => $this->ml('Nicaragua')];
        $options[] = ['id' => 'ne', 'name' => $this->ml('Niger')];
        $options[] = ['id' => 'ng', 'name' => $this->ml('Nigeria')];
        $options[] = ['id' => 'nu', 'name' => $this->ml('Niue')];
        $options[] = ['id' => 'nf', 'name' => $this->ml('Norfolk Island')];
        $options[] = ['id' => 'mp', 'name' => $this->ml('Northern Mariana Islands')];
        $options[] = ['id' => 'no', 'name' => $this->ml('Norway')];
        $options[] = ['id' => 'om', 'name' => $this->ml('Oman')];
        $options[] = ['id' => 'pk', 'name' => $this->ml('Pakistan')];
        $options[] = ['id' => 'pw', 'name' => $this->ml('Palau')];
        $options[] = ['id' => 'ps', 'name' => $this->ml('Palestinian Territory')];
        $options[] = ['id' => 'pa', 'name' => $this->ml('Panama')];
        $options[] = ['id' => 'pg', 'name' => $this->ml('Papua New Guinea')];
        $options[] = ['id' => 'py', 'name' => $this->ml('Paraguay')];
        $options[] = ['id' => 'pe', 'name' => $this->ml('Peru')];
        $options[] = ['id' => 'ph', 'name' => $this->ml('Philippines')];
        $options[] = ['id' => 'pn', 'name' => $this->ml('Pitcairn')];
        $options[] = ['id' => 'pl', 'name' => $this->ml('Poland')];
        $options[] = ['id' => 'pt', 'name' => $this->ml('Portugal')];
        $options[] = ['id' => 'pr', 'name' => $this->ml('Puerto Rico')];
        $options[] = ['id' => 'qa', 'name' => $this->ml('Qatar')];
        $options[] = ['id' => 're', 'name' => $this->ml('R&#233;union')];
        $options[] = ['id' => 'ro', 'name' => $this->ml('Romania')];
        $options[] = ['id' => 'ru', 'name' => $this->ml('Russian Federation')];
        $options[] = ['id' => 'rw', 'name' => $this->ml('Rwanda')];
        $options[] = ['id' => 'sh', 'name' => $this->ml('St. Helena')];
        $options[] = ['id' => 'kn', 'name' => $this->ml('St. Kitts and Nevis')];
        $options[] = ['id' => 'lc', 'name' => $this->ml('St. Lucia')];
        $options[] = ['id' => 'pm', 'name' => $this->ml('St. Pierre and Miquelon')];
        $options[] = ['id' => 'vc', 'name' => $this->ml('St. Vincent and the Grenadines')];
        $options[] = ['id' => 'ws', 'name' => $this->ml('Samoa')];
        $options[] = ['id' => 'sm', 'name' => $this->ml('San Marino')];
        $options[] = ['id' => 'st', 'name' => $this->ml('S&#227;o Tom&#233; and Pr&#237;ncipe')];
        $options[] = ['id' => 'sa', 'name' => $this->ml('Saudi Arabia')];
        $options[] = ['id' => 'sn', 'name' => $this->ml('Senegal')];
        $options[] = ['id' => 'cs', 'name' => $this->ml('Serbia &#38; Montenegro')];
        $options[] = ['id' => 'sc', 'name' => $this->ml('Seychelles')];
        $options[] = ['id' => 'sl', 'name' => $this->ml('Sierra Leone')];
        $options[] = ['id' => 'sg', 'name' => $this->ml('Singapore')];
        $options[] = ['id' => 'sk', 'name' => $this->ml('Slovakia')];
        $options[] = ['id' => 'si', 'name' => $this->ml('Slovenia')];
        $options[] = ['id' => 'sb', 'name' => $this->ml('Solomon Islands')];
        $options[] = ['id' => 'so', 'name' => $this->ml('Somalia')];
        $options[] = ['id' => 'za', 'name' => $this->ml('South Africa')];
        $options[] = ['id' => 'gs', 'name' => $this->ml('Sth Georgia &#38; the South Sandwich Islands')];
        $options[] = ['id' => 'es', 'name' => $this->ml('Spain')];
        $options[] = ['id' => 'lk', 'name' => $this->ml('Sri Lanka')];
        $options[] = ['id' => 'sd', 'name' => $this->ml('Sudan')];
        $options[] = ['id' => 'sr', 'name' => $this->ml('Suriname')];
        $options[] = ['id' => 'sj', 'name' => $this->ml('Svalbard &#38; Jan Mayen')];
        $options[] = ['id' => 'sz', 'name' => $this->ml('Swaziland')];
        $options[] = ['id' => 'se', 'name' => $this->ml('Sweden')];
        $options[] = ['id' => 'ch', 'name' => $this->ml('Switzerland')];
        $options[] = ['id' => 'sy', 'name' => $this->ml('Syria')];
        $options[] = ['id' => 'tw', 'name' => $this->ml('Taiwan')];
        $options[] = ['id' => 'tj', 'name' => $this->ml('Tajikistan')];
        $options[] = ['id' => 'tz', 'name' => $this->ml('Tanzania, United Republic of')];
        $options[] = ['id' => 'th', 'name' => $this->ml('Thailand')];
        $options[] = ['id' => 'tl', 'name' => $this->ml('Timor-Leste (East Timor)')];
        $options[] = ['id' => 'tg', 'name' => $this->ml('Togo')];
        $options[] = ['id' => 'tk', 'name' => $this->ml('Tokelau')];
        $options[] = ['id' => 'to', 'name' => $this->ml('Tonga')];
        $options[] = ['id' => 'tt', 'name' => $this->ml('Trinidad and Tobago')];
        $options[] = ['id' => 'tn', 'name' => $this->ml('Tunisia')];
        $options[] = ['id' => 'tr', 'name' => $this->ml('Turkey')];
        $options[] = ['id' => 'tm', 'name' => $this->ml('Turkmenistan')];
        $options[] = ['id' => 'tc', 'name' => $this->ml('Turks and Caicos Islands')];
        $options[] = ['id' => 'tv', 'name' => $this->ml('Tuvalu')];
        $options[] = ['id' => 'ug', 'name' => $this->ml('Uganda')];
        $options[] = ['id' => 'ua', 'name' => $this->ml('Ukraine')];
        $options[] = ['id' => 'ae', 'name' => $this->ml('United Arab Emirates')];
        $options[] = ['id' => 'gb', 'name' => $this->ml('United Kingdom')];
        $options[] = ['id' => 'us', 'name' => $this->ml('United States')];
        $options[] = ['id' => 'um', 'name' => $this->ml('U.S. Minor Outlying Islands')];
        $options[] = ['id' => 'uy', 'name' => $this->ml('Uruguay')];
        $options[] = ['id' => 'uz', 'name' => $this->ml('Uzbekistan')];
        $options[] = ['id' => 'vu', 'name' => $this->ml('Vanuatu')];
        $options[] = ['id' => 'va', 'name' => $this->ml('Vatican City State (Holy See)')];
        $options[] = ['id' => 've', 'name' => $this->ml('Venezuela')];
        $options[] = ['id' => 'vn', 'name' => $this->ml('Vietnam')];
        $options[] = ['id' => 'vg', 'name' => $this->ml('Virgin Islands, British')];
        $options[] = ['id' => 'vi', 'name' => $this->ml('Virgin Islands, U.S.')];
        $options[] = ['id' => 'wf', 'name' => $this->ml('Wallis &#38; Futuna')];
        $options[] = ['id' => 'eh', 'name' => $this->ml('Western Sahara')];
        $options[] = ['id' => 'ye', 'name' => $this->ml('Yemen')];
        $options[] = ['id' => 'zm', 'name' => $this->ml('Zambia')];
        $options[] = ['id' => 'zw', 'name' => $this->ml('Zimbabwe')];
        return $options;
    }
}
