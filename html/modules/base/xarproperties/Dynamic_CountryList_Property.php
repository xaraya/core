<?php
/**
 * Dynamic Country List Property
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 * @link http://xaraya.com/index.php/release/68.html
 * @author John Cox
 */
/**
 * Include the base class
 */
sys::import('modules.base.xarproperties.Dynamic_Select_Property');

/**
 * handle the userlist property
 *
 * @package dynamicdata
 *
 */
class Dynamic_CountryList_Property extends Dynamic_Select_Property
{
    public $id         = 42;
    public $name       = 'countrylisting';
    public $desc       = 'Country Dropdown';

    function __construct($args)
    {
        parent::__construct($args);
        $this->tplmodule = 'base';
        $this->template  = 'countrylist';
    }

    function validateValue($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }
        if (!empty($value)) {
            if (is_string($value)) {
                $this->value = $value;
            } else {
                $this->invalid = xarML('Country Listing');
                $this->value = null;
                return false;
            }
        } else {
            $this->value = '';
        }

        return true;
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
        $coptions = array();
        $coptions[] = array('id' =>'--', 'name' =>xarML('Please select'));
        $coptions[] = array('id' =>'af', 'name'=>xarML('Afghanistan'));
        $coptions[] = array('id' =>'ax', 'name'=>xarML('&#197;land Islands'));
        $coptions[] = array('id' =>'al', 'name'=>xarML('Albania'));
        $coptions[] = array('id' =>'dz', 'name'=>xarML('Algeria'));
        $coptions[] = array('id' =>'as', 'name'=>xarML('American Samoa'));
        $coptions[] = array('id' =>'ad', 'name'=>xarML('Andorra'));
        $coptions[] = array('id' =>'ao', 'name'=>xarML('Angola'));
        $coptions[] = array('id' =>'ai', 'name'=>xarML('Anguilla'));
        $coptions[] = array('id' =>'aq', 'name'=>xarML('Antarctica'));
        $coptions[] = array('id' =>'ag', 'name'=>xarML('Antigua and Barbuda'));
        $coptions[] = array('id' =>'ar', 'name'=>xarML('Argentina'));
        $coptions[] = array('id' =>'am', 'name'=>xarML('Armenia'));
        $coptions[] = array('id' =>'aw', 'name'=>xarML('Aruba'));
        $coptions[] = array('id' =>'au', 'name'=>xarML('Australia'));
        $coptions[] = array('id' =>'at', 'name'=>xarML('Austria'));
        $coptions[] = array('id' =>'az', 'name'=>xarML('Azerbaijan'));
        $coptions[] = array('id' =>'bs', 'name'=>xarML('Bahamas'));
        $coptions[] = array('id' =>'bh', 'name'=>xarML('Bahrain'));
        $coptions[] = array('id' =>'bd', 'name'=>xarML('Bangladesh'));
        $coptions[] = array('id' =>'bb', 'name'=>xarML('Barbados'));
        $coptions[] = array('id' =>'by', 'name'=>xarML('Belarus'));
        $coptions[] = array('id' =>'be', 'name'=>xarML('Belgium'));
        $coptions[] = array('id' =>'bz', 'name'=>xarML('Belize'));
        $coptions[] = array('id' =>'bj', 'name'=>xarML('Benin'));
        $coptions[] = array('id' =>'bm', 'name'=>xarML('Bermuda'));
        $coptions[] = array('id' =>'bt', 'name'=>xarML('Bhutan'));
        $coptions[] = array('id' =>'bo', 'name'=>xarML('Bolivia'));
        $coptions[] = array('id' =>'ba', 'name'=>xarML('Bosnia and Herzegovina'));
        $coptions[] = array('id' =>'bw', 'name'=>xarML('Botswana'));
        $coptions[] = array('id' =>'bv', 'name'=>xarML('Bouvet Island'));
        $coptions[] = array('id' =>'br', 'name'=>xarML('Brazil'));
        $coptions[] = array('id' =>'io', 'name'=>xarML('British Indian Ocean Territory'));
        $coptions[] = array('id' =>'bn', 'name'=>xarML('Brunei Darussalam'));
        $coptions[] = array('id' =>'bg', 'name'=>xarML('Bulgaria'));
        $coptions[] = array('id' =>'bf', 'name'=>xarML('Burkina Faso'));
        $coptions[] = array('id' =>'bi', 'name'=>xarML('Burundi'));
        $coptions[] = array('id' =>'kh', 'name'=>xarML('Cambodia'));
        $coptions[] = array('id' =>'cm', 'name'=>xarML('Cameroon'));
        $coptions[] = array('id' =>'ca', 'name'=>xarML('Canada'));
        $coptions[] = array('id' =>'cv', 'name'=>xarML('Cape Verde'));
        $coptions[] = array('id' =>'ky', 'name'=>xarML('Cayman Islands'));
        $coptions[] = array('id' =>'cf', 'name'=>xarML('Central African Republic'));
        $coptions[] = array('id' =>'td', 'name'=>xarML('Chad'));
        $coptions[] = array('id' =>'cl', 'name'=>xarML('Chile'));
        $coptions[] = array('id' =>'cn', 'name'=>xarML('China'));
        $coptions[] = array('id' =>'cx', 'name'=>xarML('Christmas Island'));
        $coptions[] = array('id' =>'cc', 'name'=>xarML('Cocos (Keeling) Islands'));
        $coptions[] = array('id' =>'co', 'name'=>xarML('Colombia'));
        $coptions[] = array('id' =>'km', 'name'=>xarML('Comoros'));
        $coptions[] = array('id' =>'cg', 'name'=>xarML('Congo, Republic of'));
        $coptions[] = array('id' =>'cd', 'name'=>xarML('Congo, Democratic Republic of (Zaire)'));
        $coptions[] = array('id' =>'ck', 'name'=>xarML('Cook Islands'));
        $coptions[] = array('id' =>'cr', 'name'=>xarML('Costa Rica'));
        $coptions[] = array('id' =>'ci', 'name'=>xarML('C&#244;te D\'Ivoire'));
        $coptions[] = array('id' =>'hr', 'name'=>xarML('Croatia'));
        $coptions[] = array('id' =>'cu', 'name'=>xarML('Cuba'));
        $coptions[] = array('id' =>'cy', 'name'=>xarML('Cyprus'));
        $coptions[] = array('id' =>'cz', 'name'=>xarML('Czech Republic'));
        $coptions[] = array('id' =>'dk', 'name'=>xarML('Denmark'));
        $coptions[] = array('id' =>'dj', 'name'=>xarML('Djibouti'));
        $coptions[] = array('id' =>'dm', 'name'=>xarML('Dominica'));
        $coptions[] = array('id' =>'do', 'name'=>xarML('Dominican Republic'));
        $coptions[] = array('id' =>'ec', 'name'=>xarML('Ecuador'));
        $coptions[] = array('id' =>'eg', 'name'=>xarML('Egypt'));
        $coptions[] = array('id' =>'sv', 'name'=>xarML('El Salvador'));
        $coptions[] = array('id' =>'gq', 'name'=>xarML('Equatorial Guinea'));
        $coptions[] = array('id' =>'er', 'name'=>xarML('Eritrea'));
        $coptions[] = array('id' =>'ee', 'name'=>xarML('Estonia'));
        $coptions[] = array('id' =>'et', 'name'=>xarML('Ethiopia'));
        $coptions[] = array('id' =>'fk', 'name'=>xarML('Falkland Islands (Malvinas)'));
        $coptions[] = array('id' =>'fo', 'name'=>xarML('Faroe Islands'));
        $coptions[] = array('id' =>'fj', 'name'=>xarML('Fiji'));
        $coptions[] = array('id' =>'fi', 'name'=>xarML('Finland'));
        $coptions[] = array('id' =>'fr', 'name'=>xarML('France'));
        $coptions[] = array('id' =>'gf', 'name'=>xarML('French Guiana'));
        $coptions[] = array('id' =>'pf', 'name'=>xarML('French Polynesia'));
        $coptions[] = array('id' =>'tf', 'name'=>xarML('French Southern Territories'));
        $coptions[] = array('id' =>'ga', 'name'=>xarML('Gabon'));
        $coptions[] = array('id' =>'gm', 'name'=>xarML('Gambia'));
        $coptions[] = array('id' =>'ge', 'name'=>xarML('Georgia'));
        $coptions[] = array('id' =>'de', 'name'=>xarML('Germany'));
        $coptions[] = array('id' =>'gh', 'name'=>xarML('Ghana'));
        $coptions[] = array('id' =>'gi', 'name'=>xarML('Gibraltar'));
        $coptions[] = array('id' =>'gr', 'name'=>xarML('Greece'));
        $coptions[] = array('id' =>'gl', 'name'=>xarML('Greenland'));
        $coptions[] = array('id' =>'gd', 'name'=>xarML('Grenada'));
        $coptions[] = array('id' =>'gp', 'name'=>xarML('Guadeloupe'));
        $coptions[] = array('id' =>'gu', 'name'=>xarML('Guam'));
        $coptions[] = array('id' =>'gt', 'name'=>xarML('Guatemala'));
        $coptions[] = array('id' =>'gn', 'name'=>xarML('Guinea'));
        $coptions[] = array('id' =>'gw', 'name'=>xarML('Guinea-Bissau'));
        $coptions[] = array('id' =>'gy', 'name'=>xarML('Guyana'));
        $coptions[] = array('id' =>'ht', 'name'=>xarML('Haiti'));
        $coptions[] = array('id' =>'hm', 'name'=>xarML('Heard Island &#38; McDonald Islands'));
        $coptions[] = array('id' =>'hn', 'name'=>xarML('Honduras'));
        $coptions[] = array('id' =>'hk', 'name'=>xarML('Hong Kong'));
        $coptions[] = array('id' =>'hu', 'name'=>xarML('Hungary'));
        $coptions[] = array('id' =>'is', 'name'=>xarML('Iceland'));
        $coptions[] = array('id' =>'in', 'name'=>xarML('India'));
        $coptions[] = array('id' =>'id', 'name'=>xarML('Indonesia'));
        $coptions[] = array('id' =>'ir', 'name'=>xarML('Iran'));
        $coptions[] = array('id' =>'iq', 'name'=>xarML('Iraq'));
        $coptions[] = array('id' =>'ie', 'name'=>xarML('Ireland'));
        $coptions[] = array('id' =>'il', 'name'=>xarML('Israel'));
        $coptions[] = array('id' =>'it', 'name'=>xarML('Italy'));
        $coptions[] = array('id' =>'jm', 'name'=>xarML('Jamaica'));
        $coptions[] = array('id' =>'jp', 'name'=>xarML('Japan'));
        $coptions[] = array('id' =>'jo', 'name'=>xarML('Jordan'));
        $coptions[] = array('id' =>'kz', 'name'=>xarML('Kazakhstan'));
        $coptions[] = array('id' =>'ke', 'name'=>xarML('Kenya'));
        $coptions[] = array('id' =>'ki', 'name'=>xarML('Kiribati'));
        $coptions[] = array('id' =>'kp', 'name'=>xarML('Korea, Democratic People\'s Republic'));
        $coptions[] = array('id' =>'kr', 'name'=>xarML('Korea, Republic of'));
        $coptions[] = array('id' =>'kw', 'name'=>xarML('Kuwait'));
        $coptions[] = array('id' =>'kg', 'name'=>xarML('Kyrgyzstan'));
        $coptions[] = array('id' =>'la', 'name'=>xarML('Lao People\'s Democratic Republic'));
        $coptions[] = array('id' =>'lv', 'name'=>xarML('Latvia'));
        $coptions[] = array('id' =>'lb', 'name'=>xarML('Lebanon'));
        $coptions[] = array('id' =>'ls', 'name'=>xarML('Lesotho'));
        $coptions[] = array('id' =>'lr', 'name'=>xarML('Liberia'));
        $coptions[] = array('id' =>'ly', 'name'=>xarML('Libya'));
        $coptions[] = array('id' =>'li', 'name'=>xarML('Liechtenstein'));
        $coptions[] = array('id' =>'lt', 'name'=>xarML('Lithuania'));
        $coptions[] = array('id' =>'lu', 'name'=>xarML('Luxembourg'));
        $coptions[] = array('id' =>'mo', 'name'=>xarML('Macao'));
        $coptions[] = array('id' =>'mk', 'name'=>xarML('Macedonia, The Former Yugoslav Republic of'));
        $coptions[] = array('id' =>'mg', 'name'=>xarML('Madagascar'));
        $coptions[] = array('id' =>'mw', 'name'=>xarML('Malawi'));
        $coptions[] = array('id' =>'my', 'name'=>xarML('Malaysia'));
        $coptions[] = array('id' =>'mv', 'name'=>xarML('Maldives'));
        $coptions[] = array('id' =>'ml', 'name'=>xarML('Mali'));
        $coptions[] = array('id' =>'mt', 'name'=>xarML('Malta'));
        $coptions[] = array('id' =>'mh', 'name'=>xarML('Marshall Islands'));
        $coptions[] = array('id' =>'mq', 'name'=>xarML('Martinique'));
        $coptions[] = array('id' =>'mr', 'name'=>xarML('Mauritania'));
        $coptions[] = array('id' =>'mu', 'name'=>xarML('Mauritius'));
        $coptions[] = array('id' =>'yt', 'name'=>xarML('Mayotte'));
        $coptions[] = array('id' =>'mx', 'name'=>xarML('Mexico'));
        $coptions[] = array('id' =>'fm', 'name'=>xarML('Micronesia, Federated States of'));
        $coptions[] = array('id' =>'md', 'name'=>xarML('Moldova, Republic of'));
        $coptions[] = array('id' =>'mc', 'name'=>xarML('Monaco'));
        $coptions[] = array('id' =>'mn', 'name'=>xarML('Mongolia'));
        $coptions[] = array('id' =>'ms', 'name'=>xarML('Montserrat'));
        $coptions[] = array('id' =>'ma', 'name'=>xarML('Morocco'));
        $coptions[] = array('id' =>'mz', 'name'=>xarML('Mozambique'));
        $coptions[] = array('id' =>'mm', 'name'=>xarML('Myanmar'));
        $coptions[] = array('id' =>'na', 'name'=>xarML('Namibia'));
        $coptions[] = array('id' =>'nr', 'name'=>xarML('Nauru'));
        $coptions[] = array('id' =>'np', 'name'=>xarML('Nepal'));
        $coptions[] = array('id' =>'nl', 'name'=>xarML('Netherlands'));
        $coptions[] = array('id' =>'an', 'name'=>xarML('Netherlands Antilles'));
        $coptions[] = array('id' =>'nc', 'name'=>xarML('New Caledonia'));
        $coptions[] = array('id' =>'nz', 'name'=>xarML('New Zealand'));
        $coptions[] = array('id' =>'ni', 'name'=>xarML('Nicaragua'));
        $coptions[] = array('id' =>'ne', 'name'=>xarML('Niger'));
        $coptions[] = array('id' =>'ng', 'name'=>xarML('Nigeria'));
        $coptions[] = array('id' =>'nu', 'name'=>xarML('Niue'));
        $coptions[] = array('id' =>'nf', 'name'=>xarML('Norfolk Island'));
        $coptions[] = array('id' =>'mp', 'name'=>xarML('Northern Mariana Islands'));
        $coptions[] = array('id' =>'no', 'name'=>xarML('Norway'));
        $coptions[] = array('id' =>'om', 'name'=>xarML('Oman'));
        $coptions[] = array('id' =>'pk', 'name'=>xarML('Pakistan'));
        $coptions[] = array('id' =>'pw', 'name'=>xarML('Palau'));
        $coptions[] = array('id' =>'ps', 'name'=>xarML('Palestinian Territory'));
        $coptions[] = array('id' =>'pa', 'name'=>xarML('Panama'));
        $coptions[] = array('id' =>'pg', 'name'=>xarML('Papua New Guinea'));
        $coptions[] = array('id' =>'py', 'name'=>xarML('Paraguay'));
        $coptions[] = array('id' =>'pe', 'name'=>xarML('Peru'));
        $coptions[] = array('id' =>'ph', 'name'=>xarML('Philippines'));
        $coptions[] = array('id' =>'pn', 'name'=>xarML('Pitcairn'));
        $coptions[] = array('id' =>'pl', 'name'=>xarML('Poland'));
        $coptions[] = array('id' =>'pt', 'name'=>xarML('Portugal'));
        $coptions[] = array('id' =>'pr', 'name'=>xarML('Puerto Rico'));
        $coptions[] = array('id' =>'qa', 'name'=>xarML('Qatar'));
        $coptions[] = array('id' =>'re', 'name'=>xarML('R&#233;union'));
        $coptions[] = array('id' =>'ro', 'name'=>xarML('Romania'));
        $coptions[] = array('id' =>'ru', 'name'=>xarML('Russian Federation'));
        $coptions[] = array('id' =>'rw', 'name'=>xarML('Rwanda'));
        $coptions[] = array('id' =>'sh', 'name'=>xarML('St. Helena'));
        $coptions[] = array('id' =>'kn', 'name'=>xarML('St. Kitts and Nevis'));
        $coptions[] = array('id' =>'lc', 'name'=>xarML('St. Lucia'));
        $coptions[] = array('id' =>'pm', 'name'=>xarML('St. Pierre and Miquelon'));
        $coptions[] = array('id' =>'vc', 'name'=>xarML('St. Vincent and the Grenadines'));
        $coptions[] = array('id' =>'ws', 'name'=>xarML('Samoa'));
        $coptions[] = array('id' =>'sm', 'name'=>xarML('San Marino'));
        $coptions[] = array('id' =>'st', 'name'=>xarML('S&#227;o Tom&#233; and Pr&#237;ncipe'));
        $coptions[] = array('id' =>'sa', 'name'=>xarML('Saudi Arabia'));
        $coptions[] = array('id' =>'sn', 'name'=>xarML('Senegal'));
        $coptions[] = array('id' =>'cs', 'name'=>xarML('Serbia &#38; Montenegro'));
        $coptions[] = array('id' =>'sc', 'name'=>xarML('Seychelles'));
        $coptions[] = array('id' =>'sl', 'name'=>xarML('Sierra Leone'));
        $coptions[] = array('id' =>'sg', 'name'=>xarML('Singapore'));
        $coptions[] = array('id' =>'sk', 'name'=>xarML('Slovakia'));
        $coptions[] = array('id' =>'si', 'name'=>xarML('Slovenia'));
        $coptions[] = array('id' =>'sb', 'name'=>xarML('Solomon Islands'));
        $coptions[] = array('id' =>'so', 'name'=>xarML('Somalia'));
        $coptions[] = array('id' =>'za', 'name'=>xarML('South Africa'));
        $coptions[] = array('id' =>'gs', 'name'=>xarML('Sth Georgia &#38; the South Sandwich Islands'));
        $coptions[] = array('id' =>'es', 'name'=>xarML('Spain'));
        $coptions[] = array('id' =>'lk', 'name'=>xarML('Sri Lanka'));
        $coptions[] = array('id' =>'sd', 'name'=>xarML('Sudan'));
        $coptions[] = array('id' =>'sr', 'name'=>xarML('Suriname'));
        $coptions[] = array('id' =>'sj', 'name'=>xarML('Svalbard &#38; Jan Mayen'));
        $coptions[] = array('id' =>'sz', 'name'=>xarML('Swaziland'));
        $coptions[] = array('id' =>'se', 'name'=>xarML('Sweden'));
        $coptions[] = array('id' =>'ch', 'name'=>xarML('Switzerland'));
        $coptions[] = array('id' =>'sy', 'name'=>xarML('Syria'));
        $coptions[] = array('id' =>'tw', 'name'=>xarML('Taiwan'));
        $coptions[] = array('id' =>'tj', 'name'=>xarML('Tajikistan'));
        $coptions[] = array('id' =>'tz', 'name'=>xarML('Tanzania, United Republic of'));
        $coptions[] = array('id' =>'th', 'name'=>xarML('Thailand'));
        $coptions[] = array('id' =>'tl', 'name'=>xarML('Timor-Leste (East Timor)'));
        $coptions[] = array('id' =>'tg', 'name'=>xarML('Togo'));
        $coptions[] = array('id' =>'tk', 'name'=>xarML('Tokelau'));
        $coptions[] = array('id' =>'to', 'name'=>xarML('Tonga'));
        $coptions[] = array('id' =>'tt', 'name'=>xarML('Trinidad and Tobago'));
        $coptions[] = array('id' =>'tn', 'name'=>xarML('Tunisia'));
        $coptions[] = array('id' =>'tr', 'name'=>xarML('Turkey'));
        $coptions[] = array('id' =>'tm', 'name'=>xarML('Turkmenistan'));
        $coptions[] = array('id' =>'tc', 'name'=>xarML('Turks and Caicos Islands'));
        $coptions[] = array('id' =>'tv', 'name'=>xarML('Tuvalu'));
        $coptions[] = array('id' =>'ug', 'name'=>xarML('Uganda'));
        $coptions[] = array('id' =>'ua', 'name'=>xarML('Ukraine'));
        $coptions[] = array('id' =>'ae', 'name'=>xarML('United Arab Emirates'));
        $coptions[] = array('id' =>'gb', 'name'=>xarML('United Kingdom'));
        $coptions[] = array('id' =>'us', 'name'=>xarML('United States'));
        $coptions[] = array('id' =>'um', 'name'=>xarML('U.S. Minor Outlying Islands'));
        $coptions[] = array('id' =>'uy', 'name'=>xarML('Uruguay'));
        $coptions[] = array('id' =>'uz', 'name'=>xarML('Uzbekistan'));
        $coptions[] = array('id' =>'vu', 'name'=>xarML('Vanuatu'));
        $coptions[] = array('id' =>'va', 'name'=>xarML('Vatican City State (Holy See)'));
        $coptions[] = array('id' =>'ve', 'name'=>xarML('Venezuela'));
        $coptions[] = array('id' =>'vn', 'name'=>xarML('Vietnam'));
        $coptions[] = array('id' =>'vg', 'name'=>xarML('Virgin Islands, British'));
        $coptions[] = array('id' =>'vi', 'name'=>xarML('Virgin Islands, U.S.'));
        $coptions[] = array('id' =>'wf', 'name'=>xarML('Wallis &#38; Futuna'));
        $coptions[] = array('id' =>'eh', 'name'=>xarML('Western Sahara'));
        $coptions[] = array('id' =>'ye', 'name'=>xarML('Yemen'));
        $coptions[] = array('id' =>'zm', 'name'=>xarML('Zambia'));
        $coptions[] = array('id' =>'zw', 'name'=>xarML('Zimbabwe'));
        $this->options = $coptions;
        return $this->options;
   }
}
?>
