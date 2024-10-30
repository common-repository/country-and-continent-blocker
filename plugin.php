<?php
/*
Plugin Name:  Country and Continent Blocker
Description:  Block ips by country and/or continent
Version:      1.0
Author:       Jack 
*/


$ccfbj_countries = "Afghanistan
Albania
Algeria
Andorra
Angola
Antigua and Barbuda
Argentina
Armenia
Australia
Austria
Azerbaijan
Baden
The Bahamas
Bahrain
Bangladesh
Barbados
Bavaria
Belarus
Belgium
Belize
Benin
Bolivia
Bosnia and Herzegovina
Botswana
Brazil
Brunei
Brunswick and Lüneburg
Bulgaria
Burkina Faso
Burma
Burundi
Cabo Verde
Cambodia
Cameroon
Canada
The Cayman Islands
Central African Republic
Central American Federation
Chad
Chile
China
Colombia
Comoros
The Congo Free State
Costa Rica
Cote d’Ivoire
Croatia
Cuba
Cyprus
Czechia
Czechoslovakia
Democratic Republic of the Congo
Denmark
Djibouti
Dominica
Dominican Republic
Ecuador
Egypt
El Salvador
Equatorial Guinea
Eritrea
Estonia
Eswatini
Ethiopia
Fiji
Finland
France
Gabon
Gambia, The
Georgia
Germany
Ghana
The Grand Duchy of Tuscany
Greece
Grenada
Guatemala
Guinea
Guinea-Bissau
Guyana
Haiti
Hanover
Hanseatic Republics
Hesse
Holy See
Honduras
Hungary
Iceland
India
Indonesia
Iran
Iraq
Ireland
Israel
Italy
Jamaica
Japan
Jordan
Kazakhstan
Kenya
Kiribati
Korea
Kosovo
Kuwait
Kyrgyzstan
Laos
Latvia
Lebanon
Lesotho
Lew Chew
Liberia
Libya
Liechtenstein
Lithuania
Luxembourg
Madagascar
Malawi
Malaysia
Maldives
Mali
Malta
Marshall Islands
Mauritania
Mauritius
Mecklenburg-Schwerin
Mecklenburg-Strelitz
Mexico
Micronesia
Moldova
Monaco
Mongolia
Montenegro
Morocco
Mozambique
Namibia
Nassau
Nauru
Nepal
The Netherlands
New Zealand
Nicaragua
Niger
Nigeria
North Macedonia
Norway
Oldenburg
Oman
Pakistan
Palau
Panama
Papal States
Papua New Guinea
Paraguay
Peru
Philippines
Piedmont-Sardinia
Poland
Portugal
Qatar
Republic of Genoa
Republic of Korea
Republic of the Congo
Romania
Russia
Rwanda
Saint Kitts and Nevis
Saint Lucia
Samoa
San Marino
Sao Tome and Principe
Saudi Arabia
Schaumburg-Lippe
Senegal
Serbia
Seychelles
Sierra Leone
Singapore
Slovakia
Slovenia
The Solomon Islands
Somalia
South Africa
South Sudan
Spain
Sri Lanka
Sudan
Suriname
Sweden
Switzerland
Syria
Tajikistan
Tanzania
Texas
Thailand
Timor-Leste
Togo
Tonga
Trinidad and Tobago
Tunisia
Turkey
Turkmenistan
Tuvalu
Two Sicilies
Uganda
Ukraine
United Arab Emirates
United Kingdom
United States
Uruguay
Uzbekistan
Vanuatu
Venezuela
Vietnam
Württemberg
Yemen
Zambia
Zimbabwe";

$ccfbj_continents = "Africa
Antarctica
Asia
Australia
Europe
North America
South America";

function ccfbj_ip_info($ip = NULL, $purpose = "location", $deep_detect = TRUE) {
    $output = NULL;
    if (filter_var($ip, FILTER_VALIDATE_IP) === FALSE) {
        $ip = sanitize_text_field($_SERVER["REMOTE_ADDR"]);
        if ($deep_detect) {
            if (filter_var(@$_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP))
                $ip = sanitize_text_field($_SERVER['HTTP_X_FORWARDED_FOR']);
            if (filter_var(@$_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP))
                $ip = sanitize_text_field($_SERVER['HTTP_CLIENT_IP']);
        }
    }
    $purpose    = str_replace(array("name", "\n", "\t", " ", "-", "_"), NULL, strtolower(trim($purpose)));
    $support    = array("country", "countrycode", "state", "region", "city", "location", "address");
    $continents = array(
        "AF" => "Africa",
        "AN" => "Antarctica",
        "AS" => "Asia",
        "EU" => "Europe",
        "OC" => "Australia (Oceania)",
        "NA" => "North America",
        "SA" => "South America"
    );
    if (filter_var($ip, FILTER_VALIDATE_IP) && in_array($purpose, $support)) {
        $ipdat = sanitize_text_field(json_decode(wp_remote_get("http://www.geoplugin.net/json.gp?ip=" . $ip)));
        if (@strlen(trim($ipdat->geoplugin_countryCode)) == 2) {
            switch ($purpose) {
                case "location":
                    $output = array(
                        "city"           => @$ipdat->geoplugin_city,
                        "state"          => @$ipdat->geoplugin_regionName,
                        "country"        => @$ipdat->geoplugin_countryName,
                        "country_code"   => @$ipdat->geoplugin_countryCode,
                        "continent"      => @$continents[strtoupper($ipdat->geoplugin_continentCode)],
                        "continent_code" => @$ipdat->geoplugin_continentCode
                    );
                    break;
                case "address":
                    $address = array($ipdat->geoplugin_countryName);
                    if (@strlen($ipdat->geoplugin_regionName) >= 1)
                        $address[] = $ipdat->geoplugin_regionName;
                    if (@strlen($ipdat->geoplugin_city) >= 1)
                        $address[] = $ipdat->geoplugin_city;
                    $output = implode(", ", array_reverse($address));
                    break;
                case "city":
                    $output = @$ipdat->geoplugin_city;
                    break;
                case "state":
                    $output = @$ipdat->geoplugin_regionName;
                    break;
                case "region":
                    $output = @$ipdat->geoplugin_regionName;
                    break;
                case "country":
                    $output = @$ipdat->geoplugin_countryName;
                    break;
                case "countrycode":
                    $output = @$ipdat->geoplugin_countryCode;
                    break;
            }
        }
    }
    return $output;
}

function ccfbj_block_area() {
global $ccfbj_usercountry;

$ccfbj_usercountry = get_option('user_country');
//if just started create user country
$ip = sanitize_text_field($_SERVER['REMOTE_ADDR']);
if (!$ccfbj_usercountry || $ccfbj_usercountry == "") {
$n = ccfbj_ip_info($ip, "Location");
if (gettype($d) != "null") {
$d = $n['continent'];
$c = $n['country'];
add_option('user_country', $ip . "," . $c . "," . $d);
}
register_setting( 'jfirewall_options_group', 'user_country', 'myplugin_callback' );
//settings_fields( 'jfirewall_options_group' );
$ccfbj_usercountry = get_option('user_country');
}
//if ip changes get ip info

else if ($ip != explode(",", $ccfbj_usercountry)[0]) {
    $n = ccfbj_ip_info($ip, "Location");
    $d = $n['continent'];
    $c = $n['country'];
update_option('user_country', $ip . "," . $c . "," . $d);

$ccfbj_usercountry = get_option('user_country');
}

//check values of usercountry and compare

$cntry = explode(",", $ccfbj_usercountry)[1];
$cont = explode(",", $ccfbj_usercountry)[2];

$options = get_option('jfirewall_countries');
if (isset($options) && preg_match('/[a-zA-Z]/', $options) && preg_match('/[a-zA-Z]/', $cntry)) {
if (str_contains($options, $cntry)) {
    header("Location: about:blank");
    exit(1);
}
}
$options = get_option('jfirewall_continents');
if (isset($options) && preg_match('/[a-zA-Z]/', $options) && preg_match('/[a-zA-Z]/', $cont)) {
if (str_contains($options, $cont)) {
    header("Location: about:blank");
exit(1);
}
}

}



function ccfbj_options_page_html() {
global $ccfbj_countries;
global $ccfbj_usercountry;
global $ccfbj_continents;

if ($ccfbj_usercountry) {
$cntry = explode(",", $ccfbj_usercountry)[1];
$cont = explode(",", $ccfbj_usercountry)[2];
}




    //esc_attr($options['continent']);
    if (!get_option('j_firewall_options')) {
        add_option( 'jfirewall_countries', 'Empty');
        add_option( 'jfirewall_continents', 'Empty');
        register_setting( 'jfirewall_options_group', 'jfirewall_countries', 'myplugin_callback' );
        register_setting( 'jfirewall_options_group', 'jfirewall_continents', 'myplugin_callback' );



       // register_setting("j_firewall_options", "j_firewall_options");
        //add_settings_section( 'j_firewall_settings', 'Firewall Settings', 'dbi_plugin_section_text', 'j_firewall_plugin' );
        //add_settings_field( 'dbi_jfirewall_continent', 'Continents To Block', 'dbi_jfirewall_continent', 'j_firewall_plugin', 'j_firewall_settings' );
        //add_settings_field( 'dbi_jfirewall_country', 'Countries To Block', 'dbi_jfirewall_country', 'j_firewall_plugin', 'j_firewall_settings' );
        //$options = get_option('j_firewall_options');
        //$options['continent'] = "Test";
    }

    //settings_fields( 'jfirewall_options_group' );
//update_option('jfirewall_countries', 'Test');

    $options = get_option('jfirewall_countries');
//    $opts = explode(",", $options['country']);


//$options['country'] = "hi";

$coptions = "";
if(isset($_POST['continent'])){
    foreach($_POST['continent'] as $continent){
        if (!str_contains($coptions, sanitize_text_field($continent))) update_option('jfirewall_continents', $coptions . sanitize_text_field($continent) . ',');
        $coptions = get_option('jfirewall_continents');

    }
}
else {
    if (isset($_POST["pageloaded"])) update_option('jfirewall_continents', '');
    else $coptions = get_option('jfirewall_continents');

}

echo "<script>function confirmCountry(countries) { let arr = countries.split(','); for (const x of arr) { if (x === '111') confirm('Are you sure you want to block the country you are in?'); } }</script>";

echo '
    <script>
    window.addEventListener("load", function() {
    var checkboxesC = document.getElementsByClassName("continent");
    for (var i = 0; i < checkboxesC.length; i++) {
    if (checkboxesC[i].checked === false) break;
    if (i === checkboxesC.length - 1) document.getElementById("togglecont").innerHTML = "Unselect All"
    }
    var checkboxesCo = document.getElementsByClassName("country");
    for (var i = 0; i < checkboxesCo.length; i++) {
    if (checkboxesCo[i].checked === false) break;
    if (i === checkboxesCo.length - 1) document.getElementById("togglecountry").innerHTML = "Unselect All"
    }
    })

    function togglec() {
    var checkboxes = document.getElementsByClassName("continent");
    for (var i = 0; i < checkboxes.length; i++) {
    if (document.getElementById("togglecont").innerHTML == "Select All" && checkboxes[i].disabled != true) checkboxes[i].checked = true;
    else checkboxes[i].checked = false;
    }

    if (document.getElementById("togglecont").innerHTML === "Select All") document.getElementById("togglecont").innerHTML = "Unselect All";
    else if (document.getElementById("togglecont").innerHTML === "Unselect All") document.getElementById("togglecont").innerHTML = "Select All";
    }

    function toggleco() {
    var checkboxes = document.getElementsByClassName("country");
    for (var i = 0; i < checkboxes.length; i++) {
    if (document.getElementById("togglecountry").innerHTML == "Select All" && checkboxes[i].disabled != true) checkboxes[i].checked = true;
    else checkboxes[i].checked = false;
    }
    if (document.getElementById("togglecountry").innerHTML === "Select All") document.getElementById("togglecountry").innerHTML = "Unselect All";
    else if (document.getElementById("togglecountry").innerHTML === "Unselect All") document.getElementById("togglecountry").innerHTML = "Select All";
    }
    </script>
    <div class="wrap">
    <h1>Country and Continent Blocker</h1>
    <form action="" method="post">
    <h4>Block Continent: </h4>
    <input type="hidden" name="pageloaded" value="1"> 
    <a onclick="togglec()" id="togglecont" style="border-style: double; cursor:grab">Select All</a><br/>
    ';
      foreach (explode("\n", $ccfbj_continents) as $continent) {
        echo '<input type="checkbox" class="continent" id="' . esc_attr($continent) .  '" name="continent[]" value="' . esc_attr($continent) . '"';
        if (str_contains($coptions, $continent)) echo 'checked';
        if ($continent == $cont) echo ' disabled="true"';
        echo '>' . esc_attr($continent);
        echo '<br/>';
    }

    
    echo "<h4>Block Country:</h4>";
    echo "<a onclick='toggleco()' id='togglecountry' style='border-style: double; cursor:grab'>Select All</a><br/>";
    

    $options = "";
    if(isset($_POST['country'])){
        foreach($_POST['country'] as $country){
            if (!str_contains($options, sanitize_text_field($country))) update_option('jfirewall_countries', $options . sanitize_text_field($country) . ',');
            $options = get_option('jfirewall_countries');

        }
    }
    else {
        if (isset($_POST["pageloaded"])) update_option('jfirewall_countries', '');
        else $options = get_option('jfirewall_continents');
    
    }
    
    $options = get_option('jfirewall_countries');

    foreach (explode("\n", $ccfbj_countries) as $country) {
        if (isset($POST['country'])) {

            //if (in_array($country, $_POST['country']) && !str_contains($options, $country)) { 
             //   update_option('jfirewall_countries', $options . $country . ',');
             //   echo "IN ARRAY";
           // }
            }
        echo '<input type="checkbox" class="country" id="' . esc_attr($country) .  '" name="country[]" value="' . esc_attr($country) . '"';
        if (str_contains($options, $country)) echo 'checked';
        if ($country === $cntry) echo ' disabled="true"';
        
        echo '>' . esc_attr ($country);
        echo '<br/>';
        //if (isset($POST[$country])) update_option('jfirewall_countries', $options['jfirewall_countries'] . $country . ",");
    }

    //if (isset($POST['country'])) $other_attributes = array( 'onClick' => 'confirmCountry("' . implode(",", sanitize_text_field($_POST['country'])) . '")' );

    //submit_button( __( 'Save Settings', 'textdomain' ), 'primary', '', true, $other_attributes );
    submit_button( __( 'Save Settings', 'textdomain' ));

    echo "</form></div>";
    
}




//add_action('wp', 'ccfbj_block_area');
add_action('wp_loaded', 'ccfbj_block_area');



add_action( 'admin_menu', 'ccfbj_options_page' );
function ccfbj_options_page() {
    add_menu_page(
        'WPOrg',
        'C&C Blocker',
        'manage_options',
        'wporg',
        'ccfbj_options_page_html',
        plugin_dir_url(__FILE__) . 'images/icon_wporg.png',
        20
    );
}

?>