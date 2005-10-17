<?php
/**
 * Dynamic Country List Property
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 * @author John Cox
 */
/**
 * Include the base class
 */
include_once "modules/base/xarproperties/Dynamic_Select_Property.php";

/**
 * handle the userlist property
 *
 * @package dynamicdata
 *
 */
class Dynamic_CountryList_Property extends Dynamic_Select_Property
{

    function Dynamic_CountryList_Property($args)
    {
        $this->Dynamic_Select_Property($args);
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

//    function showInput($name = '', $value = null, $options = array(), $id = '', $tabindex = '')
    function showInput($args = array())
    {
        extract($args);
        
        $data=array();

        if (!isset($value)) {
            $value = $this->value;
        }
        if (empty($name)) {
            $name = 'dd_' . $this->id;
        }
        if (empty($id)) {
            $id = $name;
        }
        $data['value'] = $value;
        $data['name']  = $name;
        $data['id']    = $id;

       /* $out = '<select' .
       ' name="' . $name . '"' .
       ' id="'. $id . '"' .
       (!empty($tabindex) ? ' tabindex="'.$tabindex.'" ' : '') .
       '>';
       */
       /* Updated 2005-10-15 with ISO 3166 country codes and additional countries */
        $coptions = array();
        $coptions[] = array('id' =>'Please select', 'name' =>'Please select' );
        $coptions[] = array('id' =>'af', 'name'=>'Afghanistan');
        $coptions[] = array('id' =>'ax', 'name'=>'Aland Islands');
        $coptions[] = array('id' =>'al', 'name'=>'Albania');
        $coptions[] = array('id' =>'dz', 'name'=>'Algeria');
        $coptions[] = array('id' =>'as', 'name'=>'American Samoa');
        $coptions[] = array('id' =>'ad', 'name'=>'Andorra');
        $coptions[] = array('id' =>'ao', 'name'=>'Angola');
        $coptions[] = array('id' =>'ai', 'name'=>'Anguilla');
        $coptions[] = array('id' =>'aq', 'name'=>'Antarctica');
        $coptions[] = array('id' =>'ag', 'name'=>'Antigua and Barbuda');
        $coptions[] = array('id' =>'ar', 'name'=>'Argentina');
        $coptions[] = array('id' =>'am', 'name'=>'Armenia');
        $coptions[] = array('id' =>'aw', 'name'=>'Aruba');
        $coptions[] = array('id' =>'au', 'name'=>'Australia');
        $coptions[] = array('id' =>'at', 'name'=>'Austria');
        $coptions[] = array('id' =>'az', 'name'=>'Azerbaijan');
        $coptions[] = array('id' =>'bs', 'name'=>'Bahamas');
        $coptions[] = array('id' =>'bh', 'name'=>'Bahrain');
        $coptions[] = array('id' =>'bd', 'name'=>'Bangladesh');
        $coptions[] = array('id' =>'bb', 'name'=>'Barbados');
        $coptions[] = array('id' =>'by', 'name'=>'Belarus');
        $coptions[] = array('id' =>'be', 'name'=>'Belgium');
        $coptions[] = array('id' =>'be', 'name'=>'Belize');
        $coptions[] = array('id' =>'bj', 'name'=>'Benin');
        $coptions[] = array('id' =>'bm', 'name'=>'Bermuda');
        $coptions[] = array('id' =>'bt', 'name'=>'Bhutan');
        $coptions[] = array('id' =>'bo', 'name'=>'Bolivia');
        $coptions[] = array('id' =>'ba', 'name'=>'Bosnia and Herzegovina');
        $coptions[] = array('id' =>'bw', 'name'=>'Botswana');
        $coptions[] = array('id' =>'bv', 'name'=>'Bouvet Island');
        $coptions[] = array('id' =>'br', 'name'=>'Brazil');
        $coptions[] = array('id' =>'io', 'name'=>'British Indian Ocean Territory');
        $coptions[] = array('id' =>'bn', 'name'=>'Brunei Darussalam');
        $coptions[] = array('id' =>'bg', 'name'=>'Bulgaria');
        $coptions[] = array('id' =>'bf', 'name'=>'Burkina Faso');
        $coptions[] = array('id' =>'bi', 'name'=>'Burundi');
        $coptions[] = array('id' =>'kh', 'name'=>'Cambodia');
        $coptions[] = array('id' =>'cm', 'name'=>'Cameroon');
        $coptions[] = array('id' =>'ca', 'name'=>'Canada');
        $coptions[] = array('id' =>'cv', 'name'=>'Cape Verde');
        $coptions[] = array('id' =>'cy', 'name'=>'Cayman Islands');
        $coptions[] = array('id' =>'cf', 'name'=>'Central African Republic');
        $coptions[] = array('id' =>'td', 'name'=>'Chad');
        $coptions[] = array('id' =>'cl', 'name'=>'Chile');
        $coptions[] = array('id' =>'cn', 'name'=>'China');
        $coptions[] = array('id' =>'cx', 'name'=>'Christmas Island');
        $coptions[] = array('id' =>'cc', 'name'=>'Cocos (Keeling) Islands');
        $coptions[] = array('id' =>'co', 'name'=>'Colombia');
        $coptions[] = array('id' =>'km', 'name'=>'Comoros');
        $coptions[] = array('id' =>'cg', 'name'=>'Congo, Republic of');
        $coptions[] = array('id' =>'cd', 'name'=>'Congo, Democratic Republic of (Zaire)');
        $coptions[] = array('id' =>'ck', 'name'=>'Cook Islands');
        $coptions[] = array('id' =>'cr', 'name'=>'Costa Rica');
        $coptions[] = array('id' =>'ci', 'name'=>'Cote D\'Ivoire');
        $coptions[] = array('id' =>'hr', 'name'=>'Croatia');
        $coptions[] = array('id' =>'cu', 'name'=>'Cuba');
        $coptions[] = array('id' =>'cy', 'name'=>'Cyprus');
        $coptions[] = array('id' =>'cz', 'name'=>'Czech Republic');
        $coptions[] = array('id' =>'dk', 'name'=>'Denmark');
        $coptions[] = array('id' =>'dj', 'name'=>'Djibouti');
        $coptions[] = array('id' =>'dm', 'name'=>'Dominica');
        $coptions[] = array('id' =>'do', 'name'=>'Dominican Republic');
        $coptions[] = array('id' =>'ec', 'name'=>'Ecuador');
        $coptions[] = array('id' =>'eg', 'name'=>'Egypt');
        $coptions[] = array('id' =>'sv', 'name'=>'El Salvador ');
        $coptions[] = array('id' =>'gq', 'name'=>'Equatorial Guinea');
        $coptions[] = array('id' =>'er', 'name'=>'Eritrea');
        $coptions[] = array('id' =>'ee', 'name'=>'Estonia');
        $coptions[] = array('id' =>'et', 'name'=>'Ethiopia');
        $coptions[] = array('id' =>'fk', 'name'=>'Falkland Islands (Malvinas)');
        $coptions[] = array('id' =>'fo', 'name'=>'Faroe Islands');
        $coptions[] = array('id' =>'fj', 'name'=>'Fiji');
        $coptions[] = array('id' =>'fi', 'name'=>'Finland');
        $coptions[] = array('id' =>'fr', 'name'=>'France');
        $coptions[] = array('id' =>'gf', 'name'=>'French Guiana');
        $coptions[] = array('id' =>'pf', 'name'=>'French Polynesia');
        $coptions[] = array('id' =>'tf', 'name'=>'French Southern Territories');
        $coptions[] = array('id' =>'ga', 'name'=>'Gabon');
        $coptions[] = array('id' =>'gm', 'name'=>'Gambia');
        $coptions[] = array('id' =>'ge', 'name'=>'Georgia');
        $coptions[] = array('id' =>'de', 'name'=>'Germany');
        $coptions[] = array('id' =>'gh', 'name'=>'Ghana');
        $coptions[] = array('id' =>'gi', 'name'=>'Gibraltar');
        $coptions[] = array('id' =>'gr', 'name'=>'Greece');
        $coptions[] = array('id' =>'gl', 'name'=>'Greenland');
        $coptions[] = array('id' =>'gd', 'name'=>'Grenada');
        $coptions[] = array('id' =>'gp', 'name'=>'Guadeloupe');
        $coptions[] = array('id' =>'gt', 'name'=>'Guatemala');
        $coptions[] = array('id' =>'gn', 'name'=>'Guinea');
        $coptions[] = array('id' =>'gw', 'name'=>'Guinea Bissau');
        $coptions[] = array('id' =>'gy', 'name'=>'Guyana');
        $coptions[] = array('id' =>'ht', 'name'=>'Haiti');
        $coptions[] = array('id' =>'hm', 'name'=>'Heard Island & McDonald Islands');
        $coptions[] = array('id' =>'va', 'name'=>'Holy See (Vatican City State)');
        $coptions[] = array('id' =>'hn', 'name'=>'Honduras');
        $coptions[] = array('id' =>'hk', 'name'=>'Hong Kong');
        $coptions[] = array('id' =>'hu', 'name'=>'Hungary');
        $coptions[] = array('id' =>'is', 'name'=>'Iceland');
        $coptions[] = array('id' =>'in', 'name'=>'India');
        $coptions[] = array('id' =>'id', 'name'=>'Indonesia');
        $coptions[] = array('id' =>'ir', 'name'=>'Iran');
        $coptions[] = array('id' =>'iq', 'name'=>'Iraq');
        $coptions[] = array('id' =>'ie', 'name'=>'Ireland');
        $coptions[] = array('id' =>'il', 'name'=>'Israel');
        $coptions[] = array('id' =>'it', 'name'=>'Italy');
        $coptions[] = array('id' =>'jm', 'name'=>'Jamaica');
        $coptions[] = array('id' =>'jp', 'name'=>'Japan');
        $coptions[] = array('id' =>'jo', 'name'=>'Jordan');
        $coptions[] = array('id' =>'kz', 'name'=>'Kazakhstan');
        $coptions[] = array('id' =>'ke', 'name'=>'Kenya');
        $coptions[] = array('id' =>'ki', 'name'=>'Kiribati');
        $coptions[] = array('id' =>'kp', 'name'=>'Korea, Democratic People\'s Republic');
        $coptions[] = array('id' =>'kr', 'name'=>'Korea, Republic of');
        $coptions[] = array('id' =>'kw', 'name'=>'Kuwait');
        $coptions[] = array('id' =>'kg', 'name'=>'Kyrgyzstan');
        $coptions[] = array('id' =>'la', 'name'=>'Lao People\'s Democratic Republic');
        $coptions[] = array('id' =>'lv', 'name'=>'Latvia');
        $coptions[] = array('id' =>'lb', 'name'=>'Lebanon');
        $coptions[] = array('id' =>'ls', 'name'=>'Lesotho');
        $coptions[] = array('id' =>'lr', 'name'=>'Liberia');
        $coptions[] = array('id' =>'ly', 'name'=>'Libyan Arab Jamahiriya');
        $coptions[] = array('id' =>'li', 'name'=>'Liechtenstein');
        $coptions[] = array('id' =>'lt', 'name'=>'Lithuania');
        $coptions[] = array('id' =>'lu', 'name'=>'Luxembourg');
        $coptions[] = array('id' =>'mo', 'name'=>'Macao');
        $coptions[] = array('id' =>'mk', 'name'=>'Macedonia, The Former Yugoslav Republic of');
        $coptions[] = array('id' =>'mg', 'name'=>'Madagascar');
        $coptions[] = array('id' =>'mw', 'name'=>'Malawi');
        $coptions[] = array('id' =>'my', 'name'=>'Malaysia');
        $coptions[] = array('id' =>'mv', 'name'=>'Maldives');
        $coptions[] = array('id' =>'ml', 'name'=>'Mali');
        $coptions[] = array('id' =>'mt', 'name'=>'Malta');
        $coptions[] = array('id' =>'mh', 'name'=>'Marshall Islands');
        $coptions[] = array('id' =>'mq', 'name'=>'Martinique');
        $coptions[] = array('id' =>'mr', 'name'=>'Mauritania');
        $coptions[] = array('id' =>'mu', 'name'=>'Mauritius');
        $coptions[] = array('id' =>'yt', 'name'=>'Mayotte');
        $coptions[] = array('id' =>'mx', 'name'=>'Mexico');
        $coptions[] = array('id' =>'fm', 'name'=>'Micronesia, Federated States of');
        $coptions[] = array('id' =>'md', 'name'=>'Moldova, Republic of');
        $coptions[] = array('id' =>'mc', 'name'=>'Monaco');
        $coptions[] = array('id' =>'mn', 'name'=>'Mongolia');
        $coptions[] = array('id' =>'ms', 'name'=>'Montserrat');
        $coptions[] = array('id' =>'ma', 'name'=>'Morocco');
        $coptions[] = array('id' =>'mz', 'name'=>'Mozambique');
        $coptions[] = array('id' =>'mm', 'name'=>'Myanmar');
        $coptions[] = array('id' =>'na', 'name'=>'Namibia');
        $coptions[] = array('id' =>'nr', 'name'=>'Naru');
        $coptions[] = array('id' =>'np', 'name'=>'Nepal');
        $coptions[] = array('id' =>'nl', 'name'=>'Netherlands');
        $coptions[] = array('id' =>'an', 'name'=>'Netherlands Antilles');
        $coptions[] = array('id' =>'nc', 'name'=>'New Caledonia');
        $coptions[] = array('id' =>'nz', 'name'=>'New Zealand');
        $coptions[] = array('id' =>'ni', 'name'=>'Nicaragua');
        $coptions[] = array('id' =>'ni', 'name'=>'Niger');
        $coptions[] = array('id' =>'ng', 'name'=>'Nigeria');
        $coptions[] = array('id' =>'nu', 'name'=>'Niue');
        $coptions[] = array('id' =>'nf', 'name'=>'Norfolk Island');
        $coptions[] = array('id' =>'mp', 'name'=>'Northern Mariana Islands');
        $coptions[] = array('id' =>'no', 'name'=>'Norway');
        $coptions[] = array('id' =>'om', 'name'=>'Oman');
        $coptions[] = array('id' =>'pk', 'name'=>'Pakistan');
        $coptions[] = array('id' =>'pw', 'name'=>'Palau');
        $coptions[] = array('id' =>'ps', 'name'=>'Palestinian Territory, Occupied');
        $coptions[] = array('id' =>'pa', 'name'=>'Panama');
        $coptions[] = array('id' =>'pg', 'name'=>' Papua New Guinea');
        $coptions[] = array('id' =>'py', 'name'=>'Paraguay');
        $coptions[] = array('id' =>'pe', 'name'=>'Peru');
        $coptions[] = array('id' =>'ph', 'name'=>'Philippines');
        $coptions[] = array('id' =>'pn', 'name'=>'Pitcairn');
        $coptions[] = array('id' =>'pl', 'name'=>'Poland');
        $coptions[] = array('id' =>'pt', 'name'=>'Portugal');
        $coptions[] = array('id' =>'pr', 'name'=>'Puerto Rico');
        $coptions[] = array('id' =>'aq', 'name'=>'Qatar');
        $coptions[] = array('id' =>'re', 'name'=>'R&#233;union');
        $coptions[] = array('id' =>'ro', 'name'=>'Romania');
        $coptions[] = array('id' =>'ru', 'name'=>'Russian Federation');
        $coptions[] = array('id' =>'rw', 'name'=>'Rwanda');
        $coptions[] = array('id' =>'sh', 'name'=>'St. Helena');
        $coptions[] = array('id' =>'kn', 'name'=>'St. Kitts & Nevis');
        $coptions[] = array('id' =>'lc', 'name'=>'St. Lucia');
        $coptions[] = array('id' =>'pm', 'name'=>'St. Pierre & Miquelon');
        $coptions[] = array('id' =>'vc', 'name'=>'St. Vincent & the Grenadines');
        $coptions[] = array('id' =>'ws', 'name'=>'Samoa');
        $coptions[] = array('id' =>'sm', 'name'=>'San Marino');
        $coptions[] = array('id' =>'st', 'name'=>'SaoTome and Principe');
        $coptions[] = array('id' =>'sa', 'name'=>'Saudi Arabia');
        $coptions[] = array('id' =>'sn', 'name'=>'Senegal');
        $coptions[] = array('id' =>'cs', 'name'=>'Serbia & Montenegro');
        $coptions[] = array('id' =>'sc', 'name'=>'Seychelles');
        $coptions[] = array('id' =>'sl', 'name'=>'Sierra Leone');
        $coptions[] = array('id' =>'sg', 'name'=>'Singapore');
        $coptions[] = array('id' =>'sk', 'name'=>'Slovakia');
        $coptions[] = array('id' =>'si', 'name'=>'Slovenia');
        $coptions[] = array('id' =>'sb', 'name'=>'Solomon Islands');
        $coptions[] = array('id' =>'so', 'name'=>'Somalia');
        $coptions[] = array('id' =>'za', 'name'=>'South Africa');
        $coptions[] = array('id' =>'gs', 'name'=>'Sth Georgia & the South Sandwich Islands');
        $coptions[] = array('id' =>'es', 'name'=>'Spain');
        $coptions[] = array('id' =>'lk', 'name'=>'Sri Lanka');
        $coptions[] = array('id' =>'sd', 'name'=>'Sudan');
        $coptions[] = array('id' =>'sr', 'name'=>'Suriname');
        $coptions[] = array('id' =>'sj', 'name'=>'Svalbard & Jan Mayen');
        $coptions[] = array('id' =>'sz', 'name'=>'Swaziland');
        $coptions[] = array('id' =>'se', 'name'=>'Sweden');
        $coptions[] = array('id' =>'ch', 'name'=>'Switzerland');
        $coptions[] = array('id' =>'sy', 'name'=>'Syrian Arab Republic');
        $coptions[] = array('id' =>'tw', 'name'=>'Taiwan, Province of China');
        $coptions[] = array('id' =>'tj', 'name'=>'Tajikistan');
        $coptions[] = array('id' =>'tz', 'name'=>'Tanzania, United Republic of');
        $coptions[] = array('id' =>'th', 'name'=>'Thailand');
        $coptions[] = array('id' =>'tl', 'name'=>'Timor-Leste');
        $coptions[] = array('id' =>'tg', 'name'=>'Togo');
        $coptions[] = array('id' =>'tk', 'name'=>'Tokelau');
        $coptions[] = array('id' =>'to', 'name'=>'Tonga');
        $coptions[] = array('id' =>'tt', 'name'=>'Trinidad and Tobago');
        $coptions[] = array('id' =>'tn', 'name'=>'Tunisia');
        $coptions[] = array('id' =>'tr', 'name'=>'Turkey');
        $coptions[] = array('id' =>'tm', 'name'=>'Turkmenistan');
        $coptions[] = array('id' =>'tc', 'name'=>'Turks and Caicos Islands');
        $coptions[] = array('id' =>'tv', 'name'=>'Tuvalu');
        $coptions[] = array('id' =>'ug', 'name'=>'Uganda');
        $coptions[] = array('id' =>'ua', 'name'=>'Ukraine');
        $coptions[] = array('id' =>'ae', 'name'=>'United Arab Emirates');
        $coptions[] = array('id' =>'gb', 'name'=>'United Kingdom');
        $coptions[] = array('id' =>'us', 'name'=>'United States');
        $coptions[] = array('id' =>'um', 'name'=>'U.S. Minor Outlying Islands');
        $coptions[] = array('id' =>'uy', 'name'=>'Uruguay');
        $coptions[] = array('id' =>'uz', 'name'=>'Uzbekistan ');
        $coptions[] = array('id' =>'vu', 'name'=>'Vanuatu');
        $coptions[] = array('id' =>'ve', 'name'=>'Venezuela');
        $coptions[] = array('id' =>'vn', 'name'=>'Vietnam');
        $coptions[] = array('id' =>'vg', 'name'=>'Virgin Islands, British');
        $coptions[] = array('id' =>'vi', 'name'=>'Virgin Islands, U.S.');
        $coptions[] = array('id' =>'wf', 'name'=>'Wallis & Futuna');
        $coptions[] = array('id' =>'eh', 'name'=>'Western Sahara');
        $coptions[] = array('id' =>'ye', 'name'=>'Yemen');
        $coptions[] = array('id' =>'zm', 'name'=>'Zambia');
        $coptions[] = array('id' =>'zw', 'name'=>'Zimbabwe');


        /*
        for($i=0; isset($coptions[$i]); $i++) {
            $out .= '<option';
            $out .= ' value="'.$coptions[$i]['name'].'"';
            if ($value == $coptions[$i]['name']) {
                $out .= ' selected="selected">'.$coptions[$i]['name'].'</option>';
            } else {
                $out .= '>'.$coptions[$i]['name'].'</option>';
            }
        }
        */

        $data['coptions'] = $coptions;
        $data['invalid']  = !empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid) : '';
        $data['tabindex'] =! empty($tabindex) ? $tabindex: 0;

        $template="";
        return xarTplProperty('base', 'countrylist', 'showinput', $data);

    }

    function showOutput($args = array())
    {
         extract($args);
         $data=array();
         if (isset($value)) {
             $data['value']=xarVarPrepHTMLDisplay($value);
         } else {
             $data['value']=xarVarPrepHTMLDisplay($this->value);
         }
         if (isset($name)) {
           $data['name']=$name;
         }
         if (isset($id)) {
             $data['id']=$id;
         }
         $template="";

         return xarTplProperty('base', 'countrylist', 'showoutput', $data);
        /*if (isset($value)) {
            return xarVarPrepHTMLDisplay($value);
        } else {
            return xarVarPrepHTMLDisplay($this->value);
        }*/
    }


    /**
     * Get the base information for this property.
     *
     * @returns array
     * @return base information for this property
     **/
     function getBasePropertyInfo()
     {
         $args = array();
         $baseInfo = array(
                              'id'         => 42,
                              'name'       => 'countrylisting',
                              'label'      => 'Country Dropdown',
                              'format'     => '42',
                              'validation' => '',
                              'source'         => '',
                              'dependancies'   => '',
                              'requiresmodule' => '',
                              'aliases'        => '',
                              'args'           => serialize($args),
                            // ...
                           );
        return $baseInfo;
     }

}

?>