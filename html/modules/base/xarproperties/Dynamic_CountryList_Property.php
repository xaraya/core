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
        // credit to jojodee for the array.  You can expect me to type this can you?
        // <jojodee> no but you could do the capital city list for me :)
        $coptions = array();
        $coptions[] = array('id' =>'Please select', 'name' =>'Please select' );
        $coptions[] = array('id' =>'Afghanistan', 'name' =>'Afghanistan' );
        $coptions[] = array('id' =>'Albania', 'name' =>'Albania');
        $coptions[] = array('id' =>'Algeria', 'name' =>'Algeria');
        $coptions[] = array('id' =>'Australia', 'name' =>'Australia');
        $coptions[] = array('id' =>'Afghanistan', 'name'=>'Afghanistan');
        $coptions[] = array('id' =>'Albania', 'name'=>'Albania');
        $coptions[] = array('id' =>'Algeria', 'name'=>'Algeria');
        $coptions[] = array('id' =>'American Samoa','name'=>'American Samoa');
        $coptions[] = array('id' =>'Andorra', 'name'=>'Andorra');
        $coptions[] = array('id' =>'Angola', 'name'=>'Angola');
        $coptions[] = array('id' =>'Anguilla', 'name'=>'Anguilla');
        $coptions[] = array('id' =>'Antigua and Barbuda', 'name'=>'Antigua and Barbuda');
        $coptions[] = array('id' =>'Argentina', 'name'=>'Argentina');
        $coptions[] = array('id' =>'Armenia', 'name'=>'Armenia');
        $coptions[] = array('id' =>'Aruba', 'name'=>'Aruba');
        $coptions[] = array('id' =>'Australia', 'name'=>'Australia');
        $coptions[] = array('id' =>'Austria', 'name'=>'Austria');
        $coptions[] = array('id' =>'Azerbaijan', 'name'=>'Azerbaijan');
        $coptions[] = array('id' =>'Bahamas', 'name'=>'Bahamas');
        $coptions[] = array('id' =>'Bahrain', 'name'=>'Bahrain');
        $coptions[] = array('id' =>'Bangladesh', 'name'=>'Bangladesh');
        $coptions[] = array('id' =>'Barbados', 'name'=>'Barbados');
        $coptions[] = array('id' =>'Belarus', 'name'=>'Belarus');
        $coptions[] = array('id' =>'Belgium', 'name'=>'Belgium');
        $coptions[] = array('id' =>'Belize', 'name'=>'Belize');
        $coptions[] = array('id' =>'Benin', 'name'=>'Benin');
        $coptions[] = array('id' =>'Bermuda', 'name'=>'Bermuda');
        $coptions[] = array('id' =>'Bhutan', 'name'=>'Bhutan');
        $coptions[] = array('id' =>'Bolivia', 'name'=>'Bolivia');
        $coptions[] = array('id' =>'Bosnia and Herzegovina', 'name'=>'Bosnia and Herzegovina');
        $coptions[] = array('id' =>'Botswana', 'name'=>'Botswana');
        $coptions[] = array('id' =>'Brazil', 'name'=>'Brazil');
        $coptions[] = array('id' =>'British Virgin Islands', 'name'=>'British Virgin Islands');
        $coptions[] = array('id' =>'Bulgaria', 'name'=>'Bulgaria');
        $coptions[] = array('id' =>'Burkina Faso', 'name'=>'Burkina Faso');
        $coptions[] = array('id' =>'Burundi', 'name'=>'Burundi');
        $coptions[] = array('id' =>'Cambodia', 'name'=>'Cambodia');
        $coptions[] = array('id' =>'Cameroon', 'name'=>'Cameroon');
        $coptions[] = array('id' =>'Canada', 'name'=>'Canada');
        $coptions[] = array('id' =>'Cape Verde', 'name'=>'Cape Verde');
        $coptions[] = array('id' =>'Cayman Islands', 'name'=>'Cayman Islands');
        $coptions[] = array('id' =>'Central African Republic', 'name'=>'Central African Republic');
        $coptions[] = array('id' =>'Chad', 'name'=>'Chad');
        $coptions[] = array('id' =>'Chile', 'name'=>'Chile');
        $coptions[] = array('id' =>'China', 'name'=>'China');
        $coptions[] = array('id' =>'Colombia', 'name'=>'Colombia');
        $coptions[] = array('id' =>'Comoros', 'name'=>'Comoros');
        $coptions[] = array('id' =>'Congo', 'name'=>'Congo, Republic of');
        $coptions[] = array('id' =>'Zaire', 'name'=>'Congo, Democratic Republic of');
        $coptions[] = array('id' =>'Cook Islands', 'name'=>'Cook Islands');
        $coptions[] = array('id' =>'Costa Rica', 'name'=>'Costa Rica');
        $coptions[] = array('id' =>'Ivory Coast', 'name'=>'Cote D\'Ivoire');
        $coptions[] = array('id' =>'Croatia', 'name'=>'Croatia');
        $coptions[] = array('id' =>'Cuba', 'name'=>'Cuba');
        $coptions[] = array('id' =>'Cyprus', 'name'=>'Cyprus');
        $coptions[] = array('id' =>'Czech Republic', 'name'=>'Czech Republic');
        $coptions[] = array('id' =>'Denmark', 'name'=>'Denmark');
        $coptions[] = array('id' =>'Djibouti', 'name'=>'Djibouti');
        $coptions[] = array('id' =>'Dominica', 'name'=>'Dominica');
        $coptions[] = array('id' =>'Dominican Republic', 'name'=>'Dominican Republic');
        $coptions[] = array('id' =>'Ecuador', 'name'=>'Ecuador');
        $coptions[] = array('id' =>'Egypt', 'name'=>'Egypt');
        $coptions[] = array('id' =>'El Salvador', 'name'=>'El Salvador ');
        $coptions[] = array('id' =>'Equatorial Guinea', 'name'=>'Equatorial Guinea');
        $coptions[] = array('id' =>'Eritrea', 'name'=>'Eritrea');
        $coptions[] = array('id' =>'Estonia', 'name'=>'Estonia');
        $coptions[] = array('id' =>'Ethiopia', 'name'=>'Ethiopia');
        $coptions[] = array('id' =>'Falkland Islands', 'name'=>'Falkland Islands');
        $coptions[] = array('id' =>'Faroe Islands', 'name'=>'Faroe Islands');
        $coptions[] = array('id' =>'Fiji', 'name'=>'Fiji');
        $coptions[] = array('id' =>'Finland', 'name'=>'Finland');
        $coptions[] = array('id' =>'France', 'name'=>'France');
        $coptions[] = array('id' =>'French Guiana', 'name'=>'French Guiana');
        $coptions[] = array('id' =>'French Polynesia', 'name'=>'French Polynesia');
        $coptions[] = array('id' =>'Gabon', 'name'=>'Gabon');
        $coptions[] = array('id' =>'Gambia', 'name'=>'Gambia');
        $coptions[] = array('id' =>'Georgia', 'name'=>'Georgia');
        $coptions[] = array('id' =>'Germany', 'name'=>'Germany');
        $coptions[] = array('id' =>'Ghana', 'name'=>'Ghana');
        $coptions[] = array('id' =>'Gibraltar', 'name'=>'Gibraltar');
        $coptions[] = array('id' =>'Greece', 'name'=>'Greece');
        $coptions[] = array('id' =>'Greenland', 'name'=>'Greenland');
        $coptions[] = array('id' =>'Grenada', 'name'=>'Grenada');
        $coptions[] = array('id' =>'Guadeloupe', 'name'=>'Guadeloupe');
        $coptions[] = array('id' =>'Guatemala', 'name'=>'Guatemala');
        $coptions[] = array('id' =>'Guinea', 'name'=>'Guinea');
        $coptions[] = array('id' =>'Guinea Bissau', 'name'=>'Guinea Bissau');
        $coptions[] = array('id' =>'Guyana', 'name'=>'Guyana');
        $coptions[] = array('id' =>'Haiti', 'name'=>'Haiti');
        $coptions[] = array('id' =>'Honduras', 'name'=>'Honduras');
        $coptions[] = array('id' =>'Hungary', 'name'=>'Hungary');
        $coptions[] = array('id' =>'Iceland', 'name'=>'Iceland');
        $coptions[] = array('id' =>'Samoa', 'name'=>'Independent State of Samoa');
        $coptions[] = array('id' =>'India', 'name'=>'India');
        $coptions[] = array('id' =>'Indonesia', 'name'=>'Indonesia');
        $coptions[] = array('id' =>'Iran', 'name'=>'Iran');
        $coptions[] = array('id' =>'Iraq', 'name'=>'Iraq');
        $coptions[] = array('id' =>'Ireland', 'name'=>'Ireland');
        $coptions[] = array('id' =>'Israel', 'name'=>'Israel');
        $coptions[] = array('id' =>'Italy', 'name'=>'Italy');
        $coptions[] = array('id' =>'Jamaica', 'name'=>'Jamaica');
        $coptions[] = array('id' =>'Japan', 'name'=>'Japan');
        $coptions[] = array('id' =>'Jordan', 'name'=>'Jordan');
        $coptions[] = array('id' =>'Kazakhstan', 'name'=>'Kazakhstan');
        $coptions[] = array('id' =>'Kenya', 'name'=>'Kenya');
        $coptions[] = array('id' =>'Kiribati', 'name'=>'Kiribati');
        $coptions[] = array('id' =>'Kuwait', 'name'=>'Kuwait');
        $coptions[] = array('id' =>'Kyrgyzstan', 'name'=>'Kyrgyzstan');
        $coptions[] = array('id' =>'Laos', 'name'=>'Laos');
        $coptions[] = array('id' =>'Latvia', 'name'=>'Latvia');
        $coptions[] = array('id' =>'Lebanon', 'name'=>'Lebanon');
        $coptions[] = array('id' =>'Lesotho', 'name'=>'Lesotho');
        $coptions[] = array('id' =>'Liberia', 'name'=>'Liberia');
        $coptions[] = array('id' =>'Libya', 'name'=>'Libya');
        $coptions[] = array('id' =>'Liechtenstein', 'name'=>'Liechtenstein');
        $coptions[] = array('id' =>'Lithuania', 'name'=>'Lithuania');
        $coptions[] = array('id' =>'Luxembourg', 'name'=>'Luxembourg');
        $coptions[] = array('id' =>'Macau', 'name'=>'Macau');
        $coptions[] = array('id' =>'Macedonia', 'name'=>'Macedonia');
        $coptions[] = array('id' =>'Madagascar', 'name'=>'Madagascar');
        $coptions[] = array('id' =>'Malawi', 'name'=>'Malawi');
        $coptions[] = array('id' =>'Malaysia', 'name'=>'Malaysia');
        $coptions[] = array('id' =>'Maldives', 'name'=>'Maldives');
        $coptions[] = array('id' =>'Mali', 'name'=>'Mali');
        $coptions[] = array('id' =>'Malta', 'name'=>'Malta');
        $coptions[] = array('id' =>'Martinique', 'name'=>'Martinique');
        $coptions[] = array('id' =>'Marshall Islands', 'name'=>'Marshall Islands');
        $coptions[] = array('id' =>'Mauritania', 'name'=>'Mauritania');
        $coptions[] = array('id' =>'Mauritius', 'name'=>'Mauritius');
        $coptions[] = array('id' =>'Mexico', 'name'=>'Mexico');
        $coptions[] = array('id' =>'Micronesia', 'name'=>'Micronesia, Federated States of');
        $coptions[] = array('id' =>'Moldova', 'name'=>'Moldova');
        $coptions[] = array('id' =>'Monaco', 'name'=>'Monaco');
        $coptions[] = array('id' =>'Mongolia', 'name'=>'Mongolia');
        $coptions[] = array('id' =>'Montserrat', 'name'=>'Montserrat');
        $coptions[] = array('id' =>'Morocco', 'name'=>'Morocco');
        $coptions[] = array('id' =>'Mozambique', 'name'=>'Mozambique');
        $coptions[] = array('id' =>'Myanmar', 'name'=>'Myanmar');
        $coptions[] = array('id' =>'Namibia', 'name'=>'Namibia');
        $coptions[] = array('id' =>'Nepal', 'name'=>'Nepal');
        $coptions[] = array('id' =>'Netherlands', 'name'=>'Netherlands');
        $coptions[] = array('id' =>'Netherlands Antilles', 'name'=>'Netherlands Antilles');
        $coptions[] = array('id' =>'New Caledonia', 'name'=>'New Caledonia');
        $coptions[] = array('id' =>'New Zealand', 'name'=>'New Zealand');
        $coptions[] = array('id' =>'Nicaragua', 'name'=>'Nicaragua');
        $coptions[] = array('id' =>'Niger', 'name'=>'Niger');
        $coptions[] = array('id' =>'Nigeria', 'name'=>'Nigeria');
        $coptions[] = array('id' =>'Norfolk Island', 'name'=>'Norfolk Island');
        $coptions[] = array('id' =>'North Korea', 'name'=>'North Korea');
        $coptions[] = array('id' =>'Northern Mariana Islands', 'name'=>'Northern Mariana Islands');
        $coptions[] = array('id' =>'Norway', 'name'=>'Norway');
        $coptions[] = array('id' =>'Oman', 'name'=>'Oman');
        $coptions[] = array('id' =>'Pakistan', 'name'=>'Pakistan');
        $coptions[] = array('id' =>'Palau', 'name'=>'Palau');
        $coptions[] = array('id' =>'Panama', 'name'=>'Panama');
        $coptions[] = array('id' =>'Papua New Guinea', 'name'=>' Papua New Guinea');
        $coptions[] = array('id' =>'Paraguay', 'name'=>'Paraguay');
        $coptions[] = array('id' =>'Peru', 'name'=>'Peru');
        $coptions[] = array('id' =>'Philippines', 'name'=>'Philippines');
        $coptions[] = array('id' =>'Poland', 'name'=>'Poland');
        $coptions[] = array('id' =>'Portugal', 'name'=>'Portugal');
        $coptions[] = array('id' =>'Puerto Rico', 'name'=>'Puerto Rico');
        $coptions[] = array('id' =>'Qatar', 'name'=>'Qatar');
        $coptions[] = array('id' =>'Reunion', 'name'=>'R&#233;union');
        $coptions[] = array('id' =>'Romania', 'name'=>'Romania');
        $coptions[] = array('id' =>'Russia', 'name'=>'Russia');
        $coptions[] = array('id' =>'Rwanda', 'name'=>'Rwanda');
        $coptions[] = array('id' =>'Saint Helena', 'name'=>'St. Helena');
        $coptions[] = array('id' =>'Saint Kitts and Nevis', 'name'=>'St. Kitts and Nevis');
        $coptions[] = array('id' =>'Saint Lucia', 'name'=>'St. Lucia');
        $coptions[] = array('id' =>'Saint Pierre and Miquelon', 'name'=>' St. Pierre and Miquelon');
        $coptions[] = array('id' =>'Saint Vincent/Grenadines', 'name'=>' St. Vincent and the Grenadines');
        $coptions[] = array('id' =>'San Marino', 'name'=>' San Marino');
        $coptions[] = array('id' =>'Saotome and Principe', 'name'=>'SaoTome and Principe');
        $coptions[] = array('id' =>'Saudi Arabia', 'name'=>'Saudi Arabia');
        $coptions[] = array('id' =>'Senegal', 'name'=>'Senegal');
        $coptions[] = array('id' =>'Seychelles', 'name'=>'Seychelles');
        $coptions[] = array('id' =>'Sierra Leone', 'name'=>'Sierra Leone');
        $coptions[] = array('id' =>'Singapore', 'name'=>'Singapore');
        $coptions[] = array('id' =>'Slovak Republic', 'name'=>'Slovakia');
        $coptions[] = array('id' =>'Slovenia', 'name'=>'Slovenia');
        $coptions[] = array('id' =>'Solomon Islands', 'name'=>'Solomon Islands');
        $coptions[] = array('id' =>'Somalia', 'name'=>'Somalia');
        $coptions[] = array('id' =>'South Africa', 'name'=>'South Africa');
        $coptions[] = array('id' =>'South Korea', 'name'=>'South Korea');
        $coptions[] = array('id' =>'Spain', 'name'=>'Spain');
        $coptions[] = array('id' =>'Sri Lanka', 'name'=>'Sri Lanka');
        $coptions[] = array('id' =>'Sudan', 'name'=>'Sudan');
        $coptions[] = array('id' =>'Suriname', 'name'=>'Suriname');
        $coptions[] = array('id' =>'Swaziland', 'name'=>'Swaziland');
        $coptions[] = array('id' =>'Sweden', 'name'=>'Sweden');
        $coptions[] = array('id' =>'Switzerland', 'name'=>'Switzerland');
        $coptions[] = array('id' =>'Syria', 'name'=>'Syria');
        $coptions[] = array('id' =>'Taiwan', 'name'=>'Taiwan');
        $coptions[] = array('id' =>'Tajikistan', 'name'=>'Tajikistan');
        $coptions[] = array('id' =>'Tanzania', 'name'=>'Tanzania');
        $coptions[] = array('id' =>'Thailand', 'name'=>'Thailand');
        $coptions[] = array('id' =>'Togo', 'name'=>'Togo');
        $coptions[] = array('id' =>'Tokelau', 'name'=>'Tokelau');
        $coptions[] = array('id' =>'Tonga', 'name'=>'Tonga');
        $coptions[] = array('id' =>'Trinidad and Tobago', 'name'=>'Trinidad and Tobago');
        $coptions[] = array('id' =>'Tunisia', 'name'=>'Tunisia');
        $coptions[] = array('id' =>'Turkey', 'name'=>'Turkey');
        $coptions[] = array('id' =>'Turkmenistan', 'name'=>'Turkmenistan');
        $coptions[] = array('id' =>'Turks and Caicos Islands', 'name'=>'Turks and Caicos Islands');
        $coptions[] = array('id' =>'Uganda', 'name'=>'Uganda');
        $coptions[] = array('id' =>'Ukraine', 'name'=>'Ukraine');
        $coptions[] = array('id' =>'United Arab Emirates', 'name'=>'United Arab Emirates');
        $coptions[] = array('id' =>'Great Britain', 'name'=>'United Kingdom');
        $coptions[] = array('id' =>'United States', 'name'=>'United States');
        $coptions[] = array('id' =>'United States Virgin Islands', 'name'=>'U.S. Virgin Islands');
        $coptions[] = array('id' =>'Uruguay', 'name'=>'Uruguay');
        $coptions[] = array('id' =>'Uzbekistan', 'name'=>'Uzbekistan ');
        $coptions[] = array('id' =>'Vanuatu', 'name'=>'Vanuatu');
        $coptions[] = array('id' =>'Vatican City', 'name'=>'Vatican City');
        $coptions[] = array('id' =>'Venezuela', 'name'=>'Venezuela');
        $coptions[] = array('id' =>'Vietnam', 'name'=>'Vietnam');
        $coptions[] = array('id' =>'Western Sahara', 'name'=>'Western Sahara');
        $coptions[] = array('id' =>'Yemen', 'name'=>'Yemen');
        $coptions[] = array('id' =>'Yugoslavia', 'name'=>'Yugoslavia');
        $coptions[] = array('id' =>'Zambia', 'name'=>'Zambia');
        $coptions[] = array('id' =>'Zimbabwe', 'name'=>'Zimbabwe');


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