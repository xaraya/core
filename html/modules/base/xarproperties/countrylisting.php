<?php
/**
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage base
 * @link http://xaraya.com/index.php/release/68.html
 * @author John Cox
 */
/**
 * Include the base class
 */
sys::import('modules.base.xarproperties.dropdown');
/**
 * Handle the country list property
 */
class CountryListProperty extends SelectProperty
{
    public $id         = 42;
    public $name       = 'countrylisting';
    public $desc       = 'Country Dropdown';

   /**
    * Country list according to ISO 3166
    *
    * @author jojodee
    * Updated 2005-10-15 with ISO 3166 country codes
    * Credit to Pedro Innecco for corrections and updates
    */
   function getOptions()
   {
        $options = $this->getFirstline();
        if (count($this->options) > 0) {
            if (!empty($firstline)) $this->options = array_merge($options,$this->options);
            return $this->options;
        }

        $options[] = array('id' =>'af', 'name'=>xarML('Afghanistan'));
        $options[] = array('id' =>'ax', 'name'=>xarML('&#197;land Islands'));
        $options[] = array('id' =>'al', 'name'=>xarML('Albania'));
        $options[] = array('id' =>'dz', 'name'=>xarML('Algeria'));
        $options[] = array('id' =>'as', 'name'=>xarML('American Samoa'));
        $options[] = array('id' =>'ad', 'name'=>xarML('Andorra'));
        $options[] = array('id' =>'ao', 'name'=>xarML('Angola'));
        $options[] = array('id' =>'ai', 'name'=>xarML('Anguilla'));
        $options[] = array('id' =>'aq', 'name'=>xarML('Antarctica'));
        $options[] = array('id' =>'ag', 'name'=>xarML('Antigua and Barbuda'));
        $options[] = array('id' =>'ar', 'name'=>xarML('Argentina'));
        $options[] = array('id' =>'am', 'name'=>xarML('Armenia'));
        $options[] = array('id' =>'aw', 'name'=>xarML('Aruba'));
        $options[] = array('id' =>'au', 'name'=>xarML('Australia'));
        $options[] = array('id' =>'at', 'name'=>xarML('Austria'));
        $options[] = array('id' =>'az', 'name'=>xarML('Azerbaijan'));
        $options[] = array('id' =>'bs', 'name'=>xarML('Bahamas'));
        $options[] = array('id' =>'bh', 'name'=>xarML('Bahrain'));
        $options[] = array('id' =>'bd', 'name'=>xarML('Bangladesh'));
        $options[] = array('id' =>'bb', 'name'=>xarML('Barbados'));
        $options[] = array('id' =>'by', 'name'=>xarML('Belarus'));
        $options[] = array('id' =>'be', 'name'=>xarML('Belgium'));
        $options[] = array('id' =>'bz', 'name'=>xarML('Belize'));
        $options[] = array('id' =>'bj', 'name'=>xarML('Benin'));
        $options[] = array('id' =>'bm', 'name'=>xarML('Bermuda'));
        $options[] = array('id' =>'bt', 'name'=>xarML('Bhutan'));
        $options[] = array('id' =>'bo', 'name'=>xarML('Bolivia'));
        $options[] = array('id' =>'ba', 'name'=>xarML('Bosnia and Herzegovina'));
        $options[] = array('id' =>'bw', 'name'=>xarML('Botswana'));
        $options[] = array('id' =>'bv', 'name'=>xarML('Bouvet Island'));
        $options[] = array('id' =>'br', 'name'=>xarML('Brazil'));
        $options[] = array('id' =>'io', 'name'=>xarML('British Indian Ocean Territory'));
        $options[] = array('id' =>'bn', 'name'=>xarML('Brunei Darussalam'));
        $options[] = array('id' =>'bg', 'name'=>xarML('Bulgaria'));
        $options[] = array('id' =>'bf', 'name'=>xarML('Burkina Faso'));
        $options[] = array('id' =>'bi', 'name'=>xarML('Burundi'));
        $options[] = array('id' =>'kh', 'name'=>xarML('Cambodia'));
        $options[] = array('id' =>'cm', 'name'=>xarML('Cameroon'));
        $options[] = array('id' =>'ca', 'name'=>xarML('Canada'));
        $options[] = array('id' =>'cv', 'name'=>xarML('Cape Verde'));
        $options[] = array('id' =>'ky', 'name'=>xarML('Cayman Islands'));
        $options[] = array('id' =>'cf', 'name'=>xarML('Central African Republic'));
        $options[] = array('id' =>'td', 'name'=>xarML('Chad'));
        $options[] = array('id' =>'cl', 'name'=>xarML('Chile'));
        $options[] = array('id' =>'cn', 'name'=>xarML('China'));
        $options[] = array('id' =>'cx', 'name'=>xarML('Christmas Island'));
        $options[] = array('id' =>'cc', 'name'=>xarML('Cocos (Keeling) Islands'));
        $options[] = array('id' =>'co', 'name'=>xarML('Colombia'));
        $options[] = array('id' =>'km', 'name'=>xarML('Comoros'));
        $options[] = array('id' =>'cg', 'name'=>xarML('Congo, Republic of'));
        $options[] = array('id' =>'cd', 'name'=>xarML('Congo, Democratic Republic of (Zaire)'));
        $options[] = array('id' =>'ck', 'name'=>xarML('Cook Islands'));
        $options[] = array('id' =>'cr', 'name'=>xarML('Costa Rica'));
        $options[] = array('id' =>'ci', 'name'=>xarML('C&#244;te D\'Ivoire'));
        $options[] = array('id' =>'hr', 'name'=>xarML('Croatia'));
        $options[] = array('id' =>'cu', 'name'=>xarML('Cuba'));
        $options[] = array('id' =>'cy', 'name'=>xarML('Cyprus'));
        $options[] = array('id' =>'cz', 'name'=>xarML('Czech Republic'));
        $options[] = array('id' =>'dk', 'name'=>xarML('Denmark'));
        $options[] = array('id' =>'dj', 'name'=>xarML('Djibouti'));
        $options[] = array('id' =>'dm', 'name'=>xarML('Dominica'));
        $options[] = array('id' =>'do', 'name'=>xarML('Dominican Republic'));
        $options[] = array('id' =>'ec', 'name'=>xarML('Ecuador'));
        $options[] = array('id' =>'eg', 'name'=>xarML('Egypt'));
        $options[] = array('id' =>'sv', 'name'=>xarML('El Salvador'));
        $options[] = array('id' =>'gq', 'name'=>xarML('Equatorial Guinea'));
        $options[] = array('id' =>'er', 'name'=>xarML('Eritrea'));
        $options[] = array('id' =>'ee', 'name'=>xarML('Estonia'));
        $options[] = array('id' =>'et', 'name'=>xarML('Ethiopia'));
        $options[] = array('id' =>'fk', 'name'=>xarML('Falkland Islands (Malvinas)'));
        $options[] = array('id' =>'fo', 'name'=>xarML('Faroe Islands'));
        $options[] = array('id' =>'fj', 'name'=>xarML('Fiji'));
        $options[] = array('id' =>'fi', 'name'=>xarML('Finland'));
        $options[] = array('id' =>'fr', 'name'=>xarML('France'));
        $options[] = array('id' =>'gf', 'name'=>xarML('French Guiana'));
        $options[] = array('id' =>'pf', 'name'=>xarML('French Polynesia'));
        $options[] = array('id' =>'tf', 'name'=>xarML('French Southern Territories'));
        $options[] = array('id' =>'ga', 'name'=>xarML('Gabon'));
        $options[] = array('id' =>'gm', 'name'=>xarML('Gambia'));
        $options[] = array('id' =>'ge', 'name'=>xarML('Georgia'));
        $options[] = array('id' =>'de', 'name'=>xarML('Germany'));
        $options[] = array('id' =>'gh', 'name'=>xarML('Ghana'));
        $options[] = array('id' =>'gi', 'name'=>xarML('Gibraltar'));
        $options[] = array('id' =>'gr', 'name'=>xarML('Greece'));
        $options[] = array('id' =>'gl', 'name'=>xarML('Greenland'));
        $options[] = array('id' =>'gd', 'name'=>xarML('Grenada'));
        $options[] = array('id' =>'gp', 'name'=>xarML('Guadeloupe'));
        $options[] = array('id' =>'gu', 'name'=>xarML('Guam'));
        $options[] = array('id' =>'gt', 'name'=>xarML('Guatemala'));
        $options[] = array('id' =>'gn', 'name'=>xarML('Guinea'));
        $options[] = array('id' =>'gw', 'name'=>xarML('Guinea-Bissau'));
        $options[] = array('id' =>'gy', 'name'=>xarML('Guyana'));
        $options[] = array('id' =>'ht', 'name'=>xarML('Haiti'));
        $options[] = array('id' =>'hm', 'name'=>xarML('Heard Island &#38; McDonald Islands'));
        $options[] = array('id' =>'hn', 'name'=>xarML('Honduras'));
        $options[] = array('id' =>'hk', 'name'=>xarML('Hong Kong'));
        $options[] = array('id' =>'hu', 'name'=>xarML('Hungary'));
        $options[] = array('id' =>'is', 'name'=>xarML('Iceland'));
        $options[] = array('id' =>'in', 'name'=>xarML('India'));
        $options[] = array('id' =>'id', 'name'=>xarML('Indonesia'));
        $options[] = array('id' =>'ir', 'name'=>xarML('Iran'));
        $options[] = array('id' =>'iq', 'name'=>xarML('Iraq'));
        $options[] = array('id' =>'ie', 'name'=>xarML('Ireland'));
        $options[] = array('id' =>'il', 'name'=>xarML('Israel'));
        $options[] = array('id' =>'it', 'name'=>xarML('Italy'));
        $options[] = array('id' =>'jm', 'name'=>xarML('Jamaica'));
        $options[] = array('id' =>'jp', 'name'=>xarML('Japan'));
        $options[] = array('id' =>'jo', 'name'=>xarML('Jordan'));
        $options[] = array('id' =>'kz', 'name'=>xarML('Kazakhstan'));
        $options[] = array('id' =>'ke', 'name'=>xarML('Kenya'));
        $options[] = array('id' =>'ki', 'name'=>xarML('Kiribati'));
        $options[] = array('id' =>'kp', 'name'=>xarML('Korea, Democratic People\'s Republic'));
        $options[] = array('id' =>'kr', 'name'=>xarML('Korea, Republic of'));
        $options[] = array('id' =>'kw', 'name'=>xarML('Kuwait'));
        $options[] = array('id' =>'kg', 'name'=>xarML('Kyrgyzstan'));
        $options[] = array('id' =>'la', 'name'=>xarML('Lao People\'s Democratic Republic'));
        $options[] = array('id' =>'lv', 'name'=>xarML('Latvia'));
        $options[] = array('id' =>'lb', 'name'=>xarML('Lebanon'));
        $options[] = array('id' =>'ls', 'name'=>xarML('Lesotho'));
        $options[] = array('id' =>'lr', 'name'=>xarML('Liberia'));
        $options[] = array('id' =>'ly', 'name'=>xarML('Libya'));
        $options[] = array('id' =>'li', 'name'=>xarML('Liechtenstein'));
        $options[] = array('id' =>'lt', 'name'=>xarML('Lithuania'));
        $options[] = array('id' =>'lu', 'name'=>xarML('Luxembourg'));
        $options[] = array('id' =>'mo', 'name'=>xarML('Macao'));
        $options[] = array('id' =>'mk', 'name'=>xarML('Macedonia, The Former Yugoslav Republic of'));
        $options[] = array('id' =>'mg', 'name'=>xarML('Madagascar'));
        $options[] = array('id' =>'mw', 'name'=>xarML('Malawi'));
        $options[] = array('id' =>'my', 'name'=>xarML('Malaysia'));
        $options[] = array('id' =>'mv', 'name'=>xarML('Maldives'));
        $options[] = array('id' =>'ml', 'name'=>xarML('Mali'));
        $options[] = array('id' =>'mt', 'name'=>xarML('Malta'));
        $options[] = array('id' =>'mh', 'name'=>xarML('Marshall Islands'));
        $options[] = array('id' =>'mq', 'name'=>xarML('Martinique'));
        $options[] = array('id' =>'mr', 'name'=>xarML('Mauritania'));
        $options[] = array('id' =>'mu', 'name'=>xarML('Mauritius'));
        $options[] = array('id' =>'yt', 'name'=>xarML('Mayotte'));
        $options[] = array('id' =>'mx', 'name'=>xarML('Mexico'));
        $options[] = array('id' =>'fm', 'name'=>xarML('Micronesia, Federated States of'));
        $options[] = array('id' =>'md', 'name'=>xarML('Moldova, Republic of'));
        $options[] = array('id' =>'mc', 'name'=>xarML('Monaco'));
        $options[] = array('id' =>'mn', 'name'=>xarML('Mongolia'));
        $options[] = array('id' =>'ms', 'name'=>xarML('Montserrat'));
        $options[] = array('id' =>'ma', 'name'=>xarML('Morocco'));
        $options[] = array('id' =>'mz', 'name'=>xarML('Mozambique'));
        $options[] = array('id' =>'mm', 'name'=>xarML('Myanmar'));
        $options[] = array('id' =>'na', 'name'=>xarML('Namibia'));
        $options[] = array('id' =>'nr', 'name'=>xarML('Nauru'));
        $options[] = array('id' =>'np', 'name'=>xarML('Nepal'));
        $options[] = array('id' =>'nl', 'name'=>xarML('Netherlands'));
        $options[] = array('id' =>'an', 'name'=>xarML('Netherlands Antilles'));
        $options[] = array('id' =>'nc', 'name'=>xarML('New Caledonia'));
        $options[] = array('id' =>'nz', 'name'=>xarML('New Zealand'));
        $options[] = array('id' =>'ni', 'name'=>xarML('Nicaragua'));
        $options[] = array('id' =>'ne', 'name'=>xarML('Niger'));
        $options[] = array('id' =>'ng', 'name'=>xarML('Nigeria'));
        $options[] = array('id' =>'nu', 'name'=>xarML('Niue'));
        $options[] = array('id' =>'nf', 'name'=>xarML('Norfolk Island'));
        $options[] = array('id' =>'mp', 'name'=>xarML('Northern Mariana Islands'));
        $options[] = array('id' =>'no', 'name'=>xarML('Norway'));
        $options[] = array('id' =>'om', 'name'=>xarML('Oman'));
        $options[] = array('id' =>'pk', 'name'=>xarML('Pakistan'));
        $options[] = array('id' =>'pw', 'name'=>xarML('Palau'));
        $options[] = array('id' =>'ps', 'name'=>xarML('Palestinian Territory'));
        $options[] = array('id' =>'pa', 'name'=>xarML('Panama'));
        $options[] = array('id' =>'pg', 'name'=>xarML('Papua New Guinea'));
        $options[] = array('id' =>'py', 'name'=>xarML('Paraguay'));
        $options[] = array('id' =>'pe', 'name'=>xarML('Peru'));
        $options[] = array('id' =>'ph', 'name'=>xarML('Philippines'));
        $options[] = array('id' =>'pn', 'name'=>xarML('Pitcairn'));
        $options[] = array('id' =>'pl', 'name'=>xarML('Poland'));
        $options[] = array('id' =>'pt', 'name'=>xarML('Portugal'));
        $options[] = array('id' =>'pr', 'name'=>xarML('Puerto Rico'));
        $options[] = array('id' =>'qa', 'name'=>xarML('Qatar'));
        $options[] = array('id' =>'re', 'name'=>xarML('R&#233;union'));
        $options[] = array('id' =>'ro', 'name'=>xarML('Romania'));
        $options[] = array('id' =>'ru', 'name'=>xarML('Russian Federation'));
        $options[] = array('id' =>'rw', 'name'=>xarML('Rwanda'));
        $options[] = array('id' =>'sh', 'name'=>xarML('St. Helena'));
        $options[] = array('id' =>'kn', 'name'=>xarML('St. Kitts and Nevis'));
        $options[] = array('id' =>'lc', 'name'=>xarML('St. Lucia'));
        $options[] = array('id' =>'pm', 'name'=>xarML('St. Pierre and Miquelon'));
        $options[] = array('id' =>'vc', 'name'=>xarML('St. Vincent and the Grenadines'));
        $options[] = array('id' =>'ws', 'name'=>xarML('Samoa'));
        $options[] = array('id' =>'sm', 'name'=>xarML('San Marino'));
        $options[] = array('id' =>'st', 'name'=>xarML('S&#227;o Tom&#233; and Pr&#237;ncipe'));
        $options[] = array('id' =>'sa', 'name'=>xarML('Saudi Arabia'));
        $options[] = array('id' =>'sn', 'name'=>xarML('Senegal'));
        $options[] = array('id' =>'cs', 'name'=>xarML('Serbia &#38; Montenegro'));
        $options[] = array('id' =>'sc', 'name'=>xarML('Seychelles'));
        $options[] = array('id' =>'sl', 'name'=>xarML('Sierra Leone'));
        $options[] = array('id' =>'sg', 'name'=>xarML('Singapore'));
        $options[] = array('id' =>'sk', 'name'=>xarML('Slovakia'));
        $options[] = array('id' =>'si', 'name'=>xarML('Slovenia'));
        $options[] = array('id' =>'sb', 'name'=>xarML('Solomon Islands'));
        $options[] = array('id' =>'so', 'name'=>xarML('Somalia'));
        $options[] = array('id' =>'za', 'name'=>xarML('South Africa'));
        $options[] = array('id' =>'gs', 'name'=>xarML('Sth Georgia &#38; the South Sandwich Islands'));
        $options[] = array('id' =>'es', 'name'=>xarML('Spain'));
        $options[] = array('id' =>'lk', 'name'=>xarML('Sri Lanka'));
        $options[] = array('id' =>'sd', 'name'=>xarML('Sudan'));
        $options[] = array('id' =>'sr', 'name'=>xarML('Suriname'));
        $options[] = array('id' =>'sj', 'name'=>xarML('Svalbard &#38; Jan Mayen'));
        $options[] = array('id' =>'sz', 'name'=>xarML('Swaziland'));
        $options[] = array('id' =>'se', 'name'=>xarML('Sweden'));
        $options[] = array('id' =>'ch', 'name'=>xarML('Switzerland'));
        $options[] = array('id' =>'sy', 'name'=>xarML('Syria'));
        $options[] = array('id' =>'tw', 'name'=>xarML('Taiwan'));
        $options[] = array('id' =>'tj', 'name'=>xarML('Tajikistan'));
        $options[] = array('id' =>'tz', 'name'=>xarML('Tanzania, United Republic of'));
        $options[] = array('id' =>'th', 'name'=>xarML('Thailand'));
        $options[] = array('id' =>'tl', 'name'=>xarML('Timor-Leste (East Timor)'));
        $options[] = array('id' =>'tg', 'name'=>xarML('Togo'));
        $options[] = array('id' =>'tk', 'name'=>xarML('Tokelau'));
        $options[] = array('id' =>'to', 'name'=>xarML('Tonga'));
        $options[] = array('id' =>'tt', 'name'=>xarML('Trinidad and Tobago'));
        $options[] = array('id' =>'tn', 'name'=>xarML('Tunisia'));
        $options[] = array('id' =>'tr', 'name'=>xarML('Turkey'));
        $options[] = array('id' =>'tm', 'name'=>xarML('Turkmenistan'));
        $options[] = array('id' =>'tc', 'name'=>xarML('Turks and Caicos Islands'));
        $options[] = array('id' =>'tv', 'name'=>xarML('Tuvalu'));
        $options[] = array('id' =>'ug', 'name'=>xarML('Uganda'));
        $options[] = array('id' =>'ua', 'name'=>xarML('Ukraine'));
        $options[] = array('id' =>'ae', 'name'=>xarML('United Arab Emirates'));
        $options[] = array('id' =>'gb', 'name'=>xarML('United Kingdom'));
        $options[] = array('id' =>'us', 'name'=>xarML('United States'));
        $options[] = array('id' =>'um', 'name'=>xarML('U.S. Minor Outlying Islands'));
        $options[] = array('id' =>'uy', 'name'=>xarML('Uruguay'));
        $options[] = array('id' =>'uz', 'name'=>xarML('Uzbekistan'));
        $options[] = array('id' =>'vu', 'name'=>xarML('Vanuatu'));
        $options[] = array('id' =>'va', 'name'=>xarML('Vatican City State (Holy See)'));
        $options[] = array('id' =>'ve', 'name'=>xarML('Venezuela'));
        $options[] = array('id' =>'vn', 'name'=>xarML('Vietnam'));
        $options[] = array('id' =>'vg', 'name'=>xarML('Virgin Islands, British'));
        $options[] = array('id' =>'vi', 'name'=>xarML('Virgin Islands, U.S.'));
        $options[] = array('id' =>'wf', 'name'=>xarML('Wallis &#38; Futuna'));
        $options[] = array('id' =>'eh', 'name'=>xarML('Western Sahara'));
        $options[] = array('id' =>'ye', 'name'=>xarML('Yemen'));
        $options[] = array('id' =>'zm', 'name'=>xarML('Zambia'));
        $options[] = array('id' =>'zw', 'name'=>xarML('Zimbabwe'));
        return $options;
   }
}
?>
