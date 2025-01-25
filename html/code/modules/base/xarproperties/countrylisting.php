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

    function __construct(ObjectDescriptor $descriptor)
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
   function getOptions()
   {
        if (count($this->options) > 0) {
            return $this->options;
        }

        $options[] = array('id' =>'af', 'name'=>$this->ml('Afghanistan'));
        $options[] = array('id' =>'ax', 'name'=>$this->ml('&#197;land Islands'));
        $options[] = array('id' =>'al', 'name'=>$this->ml('Albania'));
        $options[] = array('id' =>'dz', 'name'=>$this->ml('Algeria'));
        $options[] = array('id' =>'as', 'name'=>$this->ml('American Samoa'));
        $options[] = array('id' =>'ad', 'name'=>$this->ml('Andorra'));
        $options[] = array('id' =>'ao', 'name'=>$this->ml('Angola'));
        $options[] = array('id' =>'ai', 'name'=>$this->ml('Anguilla'));
        $options[] = array('id' =>'aq', 'name'=>$this->ml('Antarctica'));
        $options[] = array('id' =>'ag', 'name'=>$this->ml('Antigua and Barbuda'));
        $options[] = array('id' =>'ar', 'name'=>$this->ml('Argentina'));
        $options[] = array('id' =>'am', 'name'=>$this->ml('Armenia'));
        $options[] = array('id' =>'aw', 'name'=>$this->ml('Aruba'));
        $options[] = array('id' =>'au', 'name'=>$this->ml('Australia'));
        $options[] = array('id' =>'at', 'name'=>$this->ml('Austria'));
        $options[] = array('id' =>'az', 'name'=>$this->ml('Azerbaijan'));
        $options[] = array('id' =>'bs', 'name'=>$this->ml('Bahamas'));
        $options[] = array('id' =>'bh', 'name'=>$this->ml('Bahrain'));
        $options[] = array('id' =>'bd', 'name'=>$this->ml('Bangladesh'));
        $options[] = array('id' =>'bb', 'name'=>$this->ml('Barbados'));
        $options[] = array('id' =>'by', 'name'=>$this->ml('Belarus'));
        $options[] = array('id' =>'be', 'name'=>$this->ml('Belgium'));
        $options[] = array('id' =>'bz', 'name'=>$this->ml('Belize'));
        $options[] = array('id' =>'bj', 'name'=>$this->ml('Benin'));
        $options[] = array('id' =>'bm', 'name'=>$this->ml('Bermuda'));
        $options[] = array('id' =>'bt', 'name'=>$this->ml('Bhutan'));
        $options[] = array('id' =>'bo', 'name'=>$this->ml('Bolivia'));
        $options[] = array('id' =>'ba', 'name'=>$this->ml('Bosnia and Herzegovina'));
        $options[] = array('id' =>'bw', 'name'=>$this->ml('Botswana'));
        $options[] = array('id' =>'bv', 'name'=>$this->ml('Bouvet Island'));
        $options[] = array('id' =>'br', 'name'=>$this->ml('Brazil'));
        $options[] = array('id' =>'io', 'name'=>$this->ml('British Indian Ocean Territory'));
        $options[] = array('id' =>'bn', 'name'=>$this->ml('Brunei Darussalam'));
        $options[] = array('id' =>'bg', 'name'=>$this->ml('Bulgaria'));
        $options[] = array('id' =>'bf', 'name'=>$this->ml('Burkina Faso'));
        $options[] = array('id' =>'bi', 'name'=>$this->ml('Burundi'));
        $options[] = array('id' =>'kh', 'name'=>$this->ml('Cambodia'));
        $options[] = array('id' =>'cm', 'name'=>$this->ml('Cameroon'));
        $options[] = array('id' =>'ca', 'name'=>$this->ml('Canada'));
        $options[] = array('id' =>'cv', 'name'=>$this->ml('Cape Verde'));
        $options[] = array('id' =>'ky', 'name'=>$this->ml('Cayman Islands'));
        $options[] = array('id' =>'cf', 'name'=>$this->ml('Central African Republic'));
        $options[] = array('id' =>'td', 'name'=>$this->ml('Chad'));
        $options[] = array('id' =>'cl', 'name'=>$this->ml('Chile'));
        $options[] = array('id' =>'cn', 'name'=>$this->ml('China'));
        $options[] = array('id' =>'cx', 'name'=>$this->ml('Christmas Island'));
        $options[] = array('id' =>'cc', 'name'=>$this->ml('Cocos (Keeling) Islands'));
        $options[] = array('id' =>'co', 'name'=>$this->ml('Colombia'));
        $options[] = array('id' =>'km', 'name'=>$this->ml('Comoros'));
        $options[] = array('id' =>'cg', 'name'=>$this->ml('Congo, Republic of'));
        $options[] = array('id' =>'cd', 'name'=>$this->ml('Congo, Democratic Republic of (Zaire)'));
        $options[] = array('id' =>'ck', 'name'=>$this->ml('Cook Islands'));
        $options[] = array('id' =>'cr', 'name'=>$this->ml('Costa Rica'));
        $options[] = array('id' =>'ci', 'name'=>$this->ml('C&#244;te D\'Ivoire'));
        $options[] = array('id' =>'hr', 'name'=>$this->ml('Croatia'));
        $options[] = array('id' =>'cu', 'name'=>$this->ml('Cuba'));
        $options[] = array('id' =>'cy', 'name'=>$this->ml('Cyprus'));
        $options[] = array('id' =>'cz', 'name'=>$this->ml('Czech Republic'));
        $options[] = array('id' =>'dk', 'name'=>$this->ml('Denmark'));
        $options[] = array('id' =>'dj', 'name'=>$this->ml('Djibouti'));
        $options[] = array('id' =>'dm', 'name'=>$this->ml('Dominica'));
        $options[] = array('id' =>'do', 'name'=>$this->ml('Dominican Republic'));
        $options[] = array('id' =>'ec', 'name'=>$this->ml('Ecuador'));
        $options[] = array('id' =>'eg', 'name'=>$this->ml('Egypt'));
        $options[] = array('id' =>'sv', 'name'=>$this->ml('El Salvador'));
        $options[] = array('id' =>'gq', 'name'=>$this->ml('Equatorial Guinea'));
        $options[] = array('id' =>'er', 'name'=>$this->ml('Eritrea'));
        $options[] = array('id' =>'ee', 'name'=>$this->ml('Estonia'));
        $options[] = array('id' =>'et', 'name'=>$this->ml('Ethiopia'));
        $options[] = array('id' =>'fk', 'name'=>$this->ml('Falkland Islands (Malvinas)'));
        $options[] = array('id' =>'fo', 'name'=>$this->ml('Faroe Islands'));
        $options[] = array('id' =>'fj', 'name'=>$this->ml('Fiji'));
        $options[] = array('id' =>'fi', 'name'=>$this->ml('Finland'));
        $options[] = array('id' =>'fr', 'name'=>$this->ml('France'));
        $options[] = array('id' =>'gf', 'name'=>$this->ml('French Guiana'));
        $options[] = array('id' =>'pf', 'name'=>$this->ml('French Polynesia'));
        $options[] = array('id' =>'tf', 'name'=>$this->ml('French Southern Territories'));
        $options[] = array('id' =>'ga', 'name'=>$this->ml('Gabon'));
        $options[] = array('id' =>'gm', 'name'=>$this->ml('Gambia'));
        $options[] = array('id' =>'ge', 'name'=>$this->ml('Georgia'));
        $options[] = array('id' =>'de', 'name'=>$this->ml('Germany'));
        $options[] = array('id' =>'gh', 'name'=>$this->ml('Ghana'));
        $options[] = array('id' =>'gi', 'name'=>$this->ml('Gibraltar'));
        $options[] = array('id' =>'gr', 'name'=>$this->ml('Greece'));
        $options[] = array('id' =>'gl', 'name'=>$this->ml('Greenland'));
        $options[] = array('id' =>'gd', 'name'=>$this->ml('Grenada'));
        $options[] = array('id' =>'gp', 'name'=>$this->ml('Guadeloupe'));
        $options[] = array('id' =>'gu', 'name'=>$this->ml('Guam'));
        $options[] = array('id' =>'gt', 'name'=>$this->ml('Guatemala'));
        $options[] = array('id' =>'gn', 'name'=>$this->ml('Guinea'));
        $options[] = array('id' =>'gw', 'name'=>$this->ml('Guinea-Bissau'));
        $options[] = array('id' =>'gy', 'name'=>$this->ml('Guyana'));
        $options[] = array('id' =>'ht', 'name'=>$this->ml('Haiti'));
        $options[] = array('id' =>'hm', 'name'=>$this->ml('Heard Island &#38; McDonald Islands'));
        $options[] = array('id' =>'hn', 'name'=>$this->ml('Honduras'));
        $options[] = array('id' =>'hk', 'name'=>$this->ml('Hong Kong'));
        $options[] = array('id' =>'hu', 'name'=>$this->ml('Hungary'));
        $options[] = array('id' =>'is', 'name'=>$this->ml('Iceland'));
        $options[] = array('id' =>'in', 'name'=>$this->ml('India'));
        $options[] = array('id' =>'id', 'name'=>$this->ml('Indonesia'));
        $options[] = array('id' =>'ir', 'name'=>$this->ml('Iran'));
        $options[] = array('id' =>'iq', 'name'=>$this->ml('Iraq'));
        $options[] = array('id' =>'ie', 'name'=>$this->ml('Ireland'));
        $options[] = array('id' =>'il', 'name'=>$this->ml('Israel'));
        $options[] = array('id' =>'it', 'name'=>$this->ml('Italy'));
        $options[] = array('id' =>'jm', 'name'=>$this->ml('Jamaica'));
        $options[] = array('id' =>'jp', 'name'=>$this->ml('Japan'));
        $options[] = array('id' =>'jo', 'name'=>$this->ml('Jordan'));
        $options[] = array('id' =>'kz', 'name'=>$this->ml('Kazakhstan'));
        $options[] = array('id' =>'ke', 'name'=>$this->ml('Kenya'));
        $options[] = array('id' =>'ki', 'name'=>$this->ml('Kiribati'));
        $options[] = array('id' =>'kp', 'name'=>$this->ml('Democratic People\'s Republic Korea'));
        $options[] = array('id' =>'kr', 'name'=>$this->ml('Republic of Korea'));
        $options[] = array('id' =>'kw', 'name'=>$this->ml('Kuwait'));
        $options[] = array('id' =>'kg', 'name'=>$this->ml('Kyrgyzstan'));
        $options[] = array('id' =>'la', 'name'=>$this->ml('Lao People\'s Democratic Republic'));
        $options[] = array('id' =>'lv', 'name'=>$this->ml('Latvia'));
        $options[] = array('id' =>'lb', 'name'=>$this->ml('Lebanon'));
        $options[] = array('id' =>'ls', 'name'=>$this->ml('Lesotho'));
        $options[] = array('id' =>'lr', 'name'=>$this->ml('Liberia'));
        $options[] = array('id' =>'ly', 'name'=>$this->ml('Libya'));
        $options[] = array('id' =>'li', 'name'=>$this->ml('Liechtenstein'));
        $options[] = array('id' =>'lt', 'name'=>$this->ml('Lithuania'));
        $options[] = array('id' =>'lu', 'name'=>$this->ml('Luxembourg'));
        $options[] = array('id' =>'mo', 'name'=>$this->ml('Macao'));
        $options[] = array('id' =>'mk', 'name'=>$this->ml('Macedonia, The Former Yugoslav Republic of'));
        $options[] = array('id' =>'mg', 'name'=>$this->ml('Madagascar'));
        $options[] = array('id' =>'mw', 'name'=>$this->ml('Malawi'));
        $options[] = array('id' =>'my', 'name'=>$this->ml('Malaysia'));
        $options[] = array('id' =>'mv', 'name'=>$this->ml('Maldives'));
        $options[] = array('id' =>'ml', 'name'=>$this->ml('Mali'));
        $options[] = array('id' =>'mt', 'name'=>$this->ml('Malta'));
        $options[] = array('id' =>'mh', 'name'=>$this->ml('Marshall Islands'));
        $options[] = array('id' =>'mq', 'name'=>$this->ml('Martinique'));
        $options[] = array('id' =>'mr', 'name'=>$this->ml('Mauritania'));
        $options[] = array('id' =>'mu', 'name'=>$this->ml('Mauritius'));
        $options[] = array('id' =>'yt', 'name'=>$this->ml('Mayotte'));
        $options[] = array('id' =>'mx', 'name'=>$this->ml('Mexico'));
        $options[] = array('id' =>'fm', 'name'=>$this->ml('Micronesia, Federated States of'));
        $options[] = array('id' =>'md', 'name'=>$this->ml('Moldova, Republic of'));
        $options[] = array('id' =>'mc', 'name'=>$this->ml('Monaco'));
        $options[] = array('id' =>'mn', 'name'=>$this->ml('Mongolia'));
        $options[] = array('id' =>'ms', 'name'=>$this->ml('Montserrat'));
        $options[] = array('id' =>'ma', 'name'=>$this->ml('Morocco'));
        $options[] = array('id' =>'mz', 'name'=>$this->ml('Mozambique'));
        $options[] = array('id' =>'mm', 'name'=>$this->ml('Myanmar'));
        $options[] = array('id' =>'na', 'name'=>$this->ml('Namibia'));
        $options[] = array('id' =>'nr', 'name'=>$this->ml('Nauru'));
        $options[] = array('id' =>'np', 'name'=>$this->ml('Nepal'));
        $options[] = array('id' =>'nl', 'name'=>$this->ml('Netherlands'));
        $options[] = array('id' =>'an', 'name'=>$this->ml('Netherlands Antilles'));
        $options[] = array('id' =>'nc', 'name'=>$this->ml('New Caledonia'));
        $options[] = array('id' =>'nz', 'name'=>$this->ml('New Zealand'));
        $options[] = array('id' =>'ni', 'name'=>$this->ml('Nicaragua'));
        $options[] = array('id' =>'ne', 'name'=>$this->ml('Niger'));
        $options[] = array('id' =>'ng', 'name'=>$this->ml('Nigeria'));
        $options[] = array('id' =>'nu', 'name'=>$this->ml('Niue'));
        $options[] = array('id' =>'nf', 'name'=>$this->ml('Norfolk Island'));
        $options[] = array('id' =>'mp', 'name'=>$this->ml('Northern Mariana Islands'));
        $options[] = array('id' =>'no', 'name'=>$this->ml('Norway'));
        $options[] = array('id' =>'om', 'name'=>$this->ml('Oman'));
        $options[] = array('id' =>'pk', 'name'=>$this->ml('Pakistan'));
        $options[] = array('id' =>'pw', 'name'=>$this->ml('Palau'));
        $options[] = array('id' =>'ps', 'name'=>$this->ml('Palestinian Territory'));
        $options[] = array('id' =>'pa', 'name'=>$this->ml('Panama'));
        $options[] = array('id' =>'pg', 'name'=>$this->ml('Papua New Guinea'));
        $options[] = array('id' =>'py', 'name'=>$this->ml('Paraguay'));
        $options[] = array('id' =>'pe', 'name'=>$this->ml('Peru'));
        $options[] = array('id' =>'ph', 'name'=>$this->ml('Philippines'));
        $options[] = array('id' =>'pn', 'name'=>$this->ml('Pitcairn'));
        $options[] = array('id' =>'pl', 'name'=>$this->ml('Poland'));
        $options[] = array('id' =>'pt', 'name'=>$this->ml('Portugal'));
        $options[] = array('id' =>'pr', 'name'=>$this->ml('Puerto Rico'));
        $options[] = array('id' =>'qa', 'name'=>$this->ml('Qatar'));
        $options[] = array('id' =>'re', 'name'=>$this->ml('R&#233;union'));
        $options[] = array('id' =>'ro', 'name'=>$this->ml('Romania'));
        $options[] = array('id' =>'ru', 'name'=>$this->ml('Russian Federation'));
        $options[] = array('id' =>'rw', 'name'=>$this->ml('Rwanda'));
        $options[] = array('id' =>'sh', 'name'=>$this->ml('St. Helena'));
        $options[] = array('id' =>'kn', 'name'=>$this->ml('St. Kitts and Nevis'));
        $options[] = array('id' =>'lc', 'name'=>$this->ml('St. Lucia'));
        $options[] = array('id' =>'pm', 'name'=>$this->ml('St. Pierre and Miquelon'));
        $options[] = array('id' =>'vc', 'name'=>$this->ml('St. Vincent and the Grenadines'));
        $options[] = array('id' =>'ws', 'name'=>$this->ml('Samoa'));
        $options[] = array('id' =>'sm', 'name'=>$this->ml('San Marino'));
        $options[] = array('id' =>'st', 'name'=>$this->ml('S&#227;o Tom&#233; and Pr&#237;ncipe'));
        $options[] = array('id' =>'sa', 'name'=>$this->ml('Saudi Arabia'));
        $options[] = array('id' =>'sn', 'name'=>$this->ml('Senegal'));
        $options[] = array('id' =>'cs', 'name'=>$this->ml('Serbia &#38; Montenegro'));
        $options[] = array('id' =>'sc', 'name'=>$this->ml('Seychelles'));
        $options[] = array('id' =>'sl', 'name'=>$this->ml('Sierra Leone'));
        $options[] = array('id' =>'sg', 'name'=>$this->ml('Singapore'));
        $options[] = array('id' =>'sk', 'name'=>$this->ml('Slovakia'));
        $options[] = array('id' =>'si', 'name'=>$this->ml('Slovenia'));
        $options[] = array('id' =>'sb', 'name'=>$this->ml('Solomon Islands'));
        $options[] = array('id' =>'so', 'name'=>$this->ml('Somalia'));
        $options[] = array('id' =>'za', 'name'=>$this->ml('South Africa'));
        $options[] = array('id' =>'gs', 'name'=>$this->ml('Sth Georgia &#38; the South Sandwich Islands'));
        $options[] = array('id' =>'es', 'name'=>$this->ml('Spain'));
        $options[] = array('id' =>'lk', 'name'=>$this->ml('Sri Lanka'));
        $options[] = array('id' =>'sd', 'name'=>$this->ml('Sudan'));
        $options[] = array('id' =>'sr', 'name'=>$this->ml('Suriname'));
        $options[] = array('id' =>'sj', 'name'=>$this->ml('Svalbard &#38; Jan Mayen'));
        $options[] = array('id' =>'sz', 'name'=>$this->ml('Swaziland'));
        $options[] = array('id' =>'se', 'name'=>$this->ml('Sweden'));
        $options[] = array('id' =>'ch', 'name'=>$this->ml('Switzerland'));
        $options[] = array('id' =>'sy', 'name'=>$this->ml('Syria'));
        $options[] = array('id' =>'tw', 'name'=>$this->ml('Taiwan'));
        $options[] = array('id' =>'tj', 'name'=>$this->ml('Tajikistan'));
        $options[] = array('id' =>'tz', 'name'=>$this->ml('Tanzania, United Republic of'));
        $options[] = array('id' =>'th', 'name'=>$this->ml('Thailand'));
        $options[] = array('id' =>'tl', 'name'=>$this->ml('Timor-Leste (East Timor)'));
        $options[] = array('id' =>'tg', 'name'=>$this->ml('Togo'));
        $options[] = array('id' =>'tk', 'name'=>$this->ml('Tokelau'));
        $options[] = array('id' =>'to', 'name'=>$this->ml('Tonga'));
        $options[] = array('id' =>'tt', 'name'=>$this->ml('Trinidad and Tobago'));
        $options[] = array('id' =>'tn', 'name'=>$this->ml('Tunisia'));
        $options[] = array('id' =>'tr', 'name'=>$this->ml('Turkey'));
        $options[] = array('id' =>'tm', 'name'=>$this->ml('Turkmenistan'));
        $options[] = array('id' =>'tc', 'name'=>$this->ml('Turks and Caicos Islands'));
        $options[] = array('id' =>'tv', 'name'=>$this->ml('Tuvalu'));
        $options[] = array('id' =>'ug', 'name'=>$this->ml('Uganda'));
        $options[] = array('id' =>'ua', 'name'=>$this->ml('Ukraine'));
        $options[] = array('id' =>'ae', 'name'=>$this->ml('United Arab Emirates'));
        $options[] = array('id' =>'gb', 'name'=>$this->ml('United Kingdom'));
        $options[] = array('id' =>'us', 'name'=>$this->ml('United States'));
        $options[] = array('id' =>'um', 'name'=>$this->ml('U.S. Minor Outlying Islands'));
        $options[] = array('id' =>'uy', 'name'=>$this->ml('Uruguay'));
        $options[] = array('id' =>'uz', 'name'=>$this->ml('Uzbekistan'));
        $options[] = array('id' =>'vu', 'name'=>$this->ml('Vanuatu'));
        $options[] = array('id' =>'va', 'name'=>$this->ml('Vatican City State (Holy See)'));
        $options[] = array('id' =>'ve', 'name'=>$this->ml('Venezuela'));
        $options[] = array('id' =>'vn', 'name'=>$this->ml('Vietnam'));
        $options[] = array('id' =>'vg', 'name'=>$this->ml('Virgin Islands, British'));
        $options[] = array('id' =>'vi', 'name'=>$this->ml('Virgin Islands, U.S.'));
        $options[] = array('id' =>'wf', 'name'=>$this->ml('Wallis &#38; Futuna'));
        $options[] = array('id' =>'eh', 'name'=>$this->ml('Western Sahara'));
        $options[] = array('id' =>'ye', 'name'=>$this->ml('Yemen'));
        $options[] = array('id' =>'zm', 'name'=>$this->ml('Zambia'));
        $options[] = array('id' =>'zw', 'name'=>$this->ml('Zimbabwe'));
        return $options;
   }
}
