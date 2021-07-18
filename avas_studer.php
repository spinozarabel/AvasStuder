<?php
/**
*Plugin Name: Avasarala Studer WebApp
*Plugin URI:
*Description: Avasarala Web Application to display STuder Settings
*Version: 2021020500
*Author: Madhu Avasarala
*Author URI: http://sritoni.org
*Text Domain: avas_studer
*Domain Path:
*/
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

require_once(__DIR__."/avas_studer_settings.php"); // file containing class for settings

require_once(__DIR__."/studer_api.php");         // contains studer api class


if ( is_admin() )
{
 // add sub-menu for a
 add_action('admin_menu', 'add_studer_menu');

 // add support for SVG file types
 add_filter('upload_mimes', 'add_file_types_to_uploads');

 // do the following only once, that too if you are an admin!
 $avas_studer_settings = new avas_studer_settings();
}

// upon form submit run the call back function update_usermeta_from_form
add_action( 'wpforms_process_complete', 'update_usermeta_from_form', 10, 4 );

// add action to load the javascripts on non-admin page
add_action( 'wp_enqueue_scripts', 'add_my_scripts' );

// register shortcode for pages. This is for showing the page with studer settings
add_shortcode( 'avas-display-studer-settings', 'avas_display_studer_settings' );

// register shortcode for pages. This is for showing the page with studer readings
add_shortcode( 'avas-display-studer-readings', 'studer_readings_page_render' );

// add action to load the javascripts
//add_action( 'wp_enqueue_scripts',    'add_my_scripts' );

// add action for the ajax handler on server side.
// the 1st argument is in update.js, action: "get_studer_readings"
// the 2nd argument is the local callback function as the ajax handler
add_action('wp_ajax_get_studer_readings', 'ajax_studer_readings_handler');





/**
* This will fire at the very end of a (successful) form entry.
*
* @link  https://wpforms.com/developers/wpforms_process_complete/
*
* @param array  $fields    Sanitized entry field values/properties.
* @param array  $entry     Original $_POST global.
* @param array  $form_data Form data and settings.
* @param int    $entry_id  Entry ID. Will return 0 if entry storage is disabled or using WPForms Lite.
*/
function update_usermeta_from_form($fields, $entry, $form_data, $entry_id)
{
 // restricted to only form #93
 if ( absint( $form_data['id'] ) !== 93 ) {
     return;
 }
 // get the user login in order to update user meta
 // get logged in user details
 $current_user 	= wp_get_current_user();
 $user_id 		    = $current_user->ID;

 foreach ($fields as $ind => $item)
 {
   switch (true)
   {
     case($item["name"] == "Email"):
       $uhash		= hash('sha256', $item["value"]);
       update_user_meta( $user_id, 'uhash', $uhash );
     break;

     case($item["name"] == "Studer account password"):
       $phash		= md5($item["value"]);
       update_user_meta( $user_id, 'phash', $phash );
     break;

     case($item["name"] == "battery_vdc_25p"):
       $battery_vdc_state["25p"]		= $item["value"];
     break;

     case($item["name"] == "battery_vdc_50p"):
       $battery_vdc_state["50p"]		= $item["value"];
     break;

     case($item["name"] == "battery_vdc_75p"):
       $battery_vdc_state["75p"]		= $item["value"];
     break;

     case($item["name"] == "battery_vdc_100p"):
       $battery_vdc_state["100p"]		= $item["value"];
     break;
   }
   // update user meta with JSON encoded
   update_user_meta( $user_id, 'json_battery_voltage_state', json_encode($battery_vdc_state) );
 }


}

/**
*   register and enque jquery scripts with nonce for ajax calls. Load only for desired page
*   called by add_action( 'wp_enqueue_scripts', 'add_my_scripts' );
*/
function avas_display_studer_settings()
{
 // check that user is logged in and has valid studer api credentials
 login_and_studer_check();

 return studer_main_page_render();

}
/**
*  check that user is logged in and has valid studer api credentials
*  if user is not logged in just return to display message and empty page
*  if user has not set her studer API access credentials then redirect to form page
*/
function login_and_studer_check()
{
 if (!is_user_logged_in())
 {
   return  'You need to be a registered user to access this page. Please register or login';
 }
 // check if user meta for Studer API access is empty
 // if so redirect user to Studer Account form page
 $current_user_ID  = wp_get_current_user()->ID;
 // get user meta for uhash and phash
 $phash		= get_user_meta($current_user_ID, 'phash', true);
 $uhash		= get_user_meta($current_user_ID, 'uhash', true);

 if (empty($uhash) || empty($phash))
 {
   $url_studeraccountform = "https://sritoni.org/6076/my-studer-account/";
   nocache_headers();
   // We don't know for sure whether this is a URL for this site,
   // so we use wp_safe_redirect() to avoid an open redirect.
   // redirect user to form page to fill studer api credentials
   wp_safe_redirect( $url_studeraccountform );
   exit;
 }
}

/**
*   register and enque jquery scripts with nonce for ajax calls. Load only for desired page
*   called by add_action( 'wp_enqueue_scripts', 'add_my_scripts' );
*/
function add_my_scripts($hook)
// register and enque jquery scripts wit nonce for ajax calls
{
 // load script only on desired page-otherwise script looks for non-existent entities and creates errors
 if ($hook == 'studer-readings' || $hook == 'Readings')
 {
   // https://developer.wordpress.org/plugins/javascript/enqueuing/
     //wp_register_script($handle            , $src                                 , $deps         , $ver, $in_footer)
   wp_register_script('my_studer_app_script', plugins_url('update.js', __FILE__), array('jquery'),'1.0', true);

   wp_enqueue_script('my_studer_app_script');

   $commonapp_nonce = wp_create_nonce('my_studer_app_script');
   // note the key here is the global my_ajax_obj that will be referenced by our Jquery in update.js
   //  wp_localize_script( string $handle,       string $object_name, associative array )
   wp_localize_script('my_studer_app_script', 'my_ajax_obj', array(
                                                                   'ajax_url' => admin_url( 'admin-ajax.php' ),
                                                                   'nonce'    => $commonapp_nonce,
                                                                   )
                      );
 }
 else
 {

   wp_register_script('my_studer_app_script', plugins_url('update.js', __FILE__), array('jquery'),'1.0', true);

   wp_enqueue_script('my_studer_app_script');

   $commonapp_nonce = wp_create_nonce('my_studer_app_script');
   // note the key here is the global my_ajax_obj that will be referenced by our Jquery in update.js
   //  wp_localize_script( string $handle,       string $object_name, associative array )
   wp_localize_script('my_studer_app_script', 'my_ajax_obj', array(
                                                                   'ajax_url' => admin_url( 'admin-ajax.php' ),
                                                                   'nonce'    => $commonapp_nonce,
                                                                   )
                      );

   return;
 }
}



/**
**  Ajax call from JQ to server being handled here. These values will be sent back to user's browser
**  using wp_send_json() function and will be used by JQ that called this AJAX, to refresh page locally
*/
function ajax_studer_readings_handler()
{
 // Ensures nonce is correct for security. It dies automatically if check is false. This is a WP core function
 check_ajax_referer('my_studer_app_script');

 // get adctual readings as object
 $studer_readings_obj = get_studer_readings();

 wp_send_json($studer_readings_obj);	// This will be used by JQ to update page

 //All ajax handlers should die when finished
 wp_die();
}

/**
** Function to add SVG file type support
**
*/
function add_file_types_to_uploads($file_types)
{
 $new_filetypes = array();
 $new_filetypes['svg'] = 'image/svg+xml';
 $file_types = array_merge($file_types, $new_filetypes );
 return $file_types;
}

function add_studer_menu()
{
 // add_menu_page( $page_title,    $menu_title,    $capability,      $menu_slug, $function,      $icon_url, $position )
    add_menu_page( 'Studer Main',  'Studer Main',  'manage_options', 'studer',   'studer_main_page_render' );

    /*
  add_submenu_page( string $parent_slug, string $page_title, string $menu_title, string $capability, string $menu_slug, callable $function = '' )
  *					        parent slug		 newsubmenupage	 submenu title  	  capability         new submenu slug      callback for display page
  */
  add_submenu_page( 'studer',      'VarioTrac',      'VarioTrac',     'manage_options',   'studer-variotrac',    'studer_variotrac_page_render' );

  add_submenu_page( 'studer',      'Readings',       'Readings',      'manage_options',   'studer-readings',     'studer_readings_page_render' );

 return;
}

/**
**  This function gets the present settings from your Studer installation using Studer API
**
*/
function get_studer_settings_using_api()
{
 // create a new API instance for communicating with your Studer
 $studer_api = new studer_api();

 $studer_settings_arr = [];  // initialize to empty array

 // 1187 Inverter STandby level
 $studer_api->paramId              = '1187';
 $studer_api->device               = 'XT1';
 $studer_api->paramPart            = 'Value';
 $studer_settings_arr[$studer_api->paramId]["value"] = $studer_api->get_parameter_value();
 $studer_settings_arr[$studer_api->paramId]["name"]  = "Inverter Standby level";

 // 1108 Battery Under Voltage Without Load (For LVD)
 $studer_api->paramId              = '1108';
 $studer_api->device               = 'XT1';
 $studer_api->paramPart            = 'Value';
 $studer_settings_arr[$studer_api->paramId]["value"] = round($studer_api->get_parameter_value(), 2);
 $studer_settings_arr[$studer_api->paramId]["name"]  = "Battery undervoltage level without load";

 // 1190 Duration of Battery undervoltage without load before turn-off
 $studer_api->paramId              = '1190';
 $studer_api->device               = 'XT1';
 $studer_api->paramPart            = 'Value';
 $studer_settings_arr[$studer_api->paramId]["value"] = $studer_api->get_parameter_value();
 $studer_settings_arr[$studer_api->paramId]["name"]  = "Duration of Battery undervoltage without load before turn-off";

 // 1191 and 1532 battery under voltage dynamic compensation and if so type
 $studer_api->paramId              = '1191';
 $studer_api->device               = 'XT1';
 $studer_api->paramPart            = 'Value';
 $studer_settings_arr[$studer_api->paramId]["value"] = $studer_api->get_parameter_value();
 $studer_settings_arr[$studer_api->paramId]["name"]  = "battery under voltage dynamic compensation?";

 $studer_api->paramId              = '1532';
 $studer_api->device               = 'XT1';
 $studer_api->paramPart            = 'Level';
 $studer_settings_arr[$studer_api->paramId]["value"] = $studer_api->get_parameter_value();
 $studer_settings_arr[$studer_api->paramId]["name"]  = "battery under voltage dynamic compensation type";

 // 1110 Restart voltage after batteries undervoltage
 $studer_api->paramId              = '1110';
 $studer_api->device               = 'XT1';
 $studer_api->paramPart            = 'Value';
 $studer_settings_arr[$studer_api->paramId]["value"] = round($studer_api->get_parameter_value(), 2);
 $studer_settings_arr[$studer_api->paramId]["name"]  = "Restart voltage after batteries undervoltage disconnect";

 // 1126 SMart-Boost Allowed?
 $studer_api->paramId              = '1126';
 $studer_api->device               = 'XT1';
 $studer_api->paramPart            = 'Value';
 $studer_settings_arr[$studer_api->paramId]["value"] = $studer_api->get_parameter_value();
 $studer_settings_arr[$studer_api->paramId]["name"]  = "Inverter Smart-Boost Allowed?";

 // 1124 Inverter Allowed?
 $studer_api->paramId              = '1124';
 $studer_api->device               = 'XT1';
 $studer_api->paramPart            = 'Value';
 $studer_settings_arr[$studer_api->paramId]["value"] = $studer_api->get_parameter_value();
 $studer_settings_arr[$studer_api->paramId]["name"]  = "Inverter function Allowed?";

 // 1125 Charger Allowed?
 $studer_api->paramId              = '1125';
 $studer_api->device               = 'XT1';
 $studer_api->paramPart            = 'Value';
 $studer_settings_arr[$studer_api->paramId]["value"] = $studer_api->get_parameter_value();
 $studer_settings_arr[$studer_api->paramId]["name"]  = "Inverter Battery Charger Allowed?";

 // 1128 Transfer relay Allowed?
 $studer_api->paramId              = '1128';
 $studer_api->device               = 'XT1';
 $studer_api->paramPart            = 'Value';
 $studer_settings_arr[$studer_api->paramId]["value"] = $studer_api->get_parameter_value();
 $studer_settings_arr[$studer_api->paramId]["name"]  = "Transfer Relay Allowed?";

 // 1140 - 1138 Battery Float Voltage and Charge CUrrent
 $studer_api->paramId              = '1140';
 $studer_api->device               = 'XT1';
 $studer_api->paramPart            = 'Value';
 $studer_settings_arr[$studer_api->paramId]["value"] = round($studer_api->get_parameter_value(), 2);
 $studer_settings_arr[$studer_api->paramId]["name"]  = "Battery Float Voltage for Charging by Inverter";

 $studer_api->paramId              = '1138';
 $studer_api->device               = 'XT1';
 $studer_api->paramPart            = 'Value';
 $studer_settings_arr[$studer_api->paramId]["value"] = round($studer_api->get_parameter_value(), 2);
 $studer_settings_arr[$studer_api->paramId]["name"]  = "Max Inverter Battery Charge Current";

 // 1202 AUX1 contact Operating Mode
 $studer_api->paramId              = '1202';
 $studer_api->device               = 'XT1';
 $studer_api->paramPart            = 'Value';
 $studer_settings_arr[$studer_api->paramId]["value"] = $studer_api->get_parameter_value();
 $studer_settings_arr[$studer_api->paramId]["name"]  = "AUX1 Contact Operating Mode";

 // 1246 AUX1 Activate on Battery Voltage Level1?
 $studer_api->paramId              = '1246';
 $studer_api->device               = 'XT1';
 $studer_api->paramPart            = 'Value';
 $studer_settings_arr[$studer_api->paramId]["value"] = $studer_api->get_parameter_value();
 $studer_settings_arr[$studer_api->paramId]["name"]  = "AUX1 Activate on Battery Voltage Level1?";

 // 1247-1254 Aux1 activate battery level conditions and associated times
 $studer_api->paramId              = '1247'; //battery voltage 1
 $studer_api->device               = 'XT1';
 $studer_api->paramPart            = 'Value';
 $studer_settings_arr[$studer_api->paramId]["value"] = round($studer_api->get_parameter_value(), 2);
 $studer_settings_arr[$studer_api->paramId]["name"]  = "Battery Voltage Level1 for AUX1 activation";

 $studer_api->paramId              = '1248'; //delay for BV 1
 $studer_api->device               = 'XT1';
 $studer_api->paramPart            = 'Value';
 $studer_settings_arr[$studer_api->paramId]["value"] = round($studer_api->get_parameter_value(), 2);
 $studer_settings_arr[$studer_api->paramId]["name"]  = "Delay 1 for AUX1 Activate on below Battery Voltage Level1";

 $studer_api->paramId              = '1249';
 $studer_api->device               = 'XT1';
 $studer_api->paramPart            = 'Value';
 $studer_settings_arr[$studer_api->paramId]["value"] = $studer_api->get_parameter_value();
 $studer_settings_arr[$studer_api->paramId]["name"]  = "AUX1 Activate on Battery Voltage Level2?";

 $studer_api->paramId              = '1250'; //battery voltage 2
 $studer_api->device               = 'XT1';
 $studer_api->paramPart            = 'Value';
 $studer_settings_arr[$studer_api->paramId]["value"] = round($studer_api->get_parameter_value(), 2);
 $studer_settings_arr[$studer_api->paramId]["name"]  = "Battery Voltage Level2 for AUX1 activation";

 $studer_api->paramId              = '1251'; //delay for BV 2
 $studer_api->device               = 'XT1';
 $studer_api->paramPart            = 'Value';
 $studer_settings_arr[$studer_api->paramId]["value"] = round($studer_api->get_parameter_value(), 2);
 $studer_settings_arr[$studer_api->paramId]["name"]  = "Delay 2 for AUX1 Activate on below Battery Voltage Level1";

 $studer_api->paramId              = '1252';
 $studer_api->device               = 'XT1';
 $studer_api->paramPart            = 'Value';
 $studer_settings_arr[$studer_api->paramId]["value"] = $studer_api->get_parameter_value();
 $studer_settings_arr[$studer_api->paramId]["name"]  = "AUX1 Activate on Battery Voltage Level3?";

 $studer_api->paramId              = '1253'; //battery voltage 3
 $studer_api->device               = 'XT1';
 $studer_api->paramPart            = 'Value';
 $studer_settings_arr[$studer_api->paramId]["value"] = round($studer_api->get_parameter_value(), 2);
 $studer_settings_arr[$studer_api->paramId]["name"]  = "Battery Voltage Level3 for AUX1 activation";

 $studer_api->paramId              = '1254'; //delay for BV 3
 $studer_api->device               = 'XT1';
 $studer_api->paramPart            = 'Value';
 $studer_settings_arr[$studer_api->paramId]["value"] = round($studer_api->get_parameter_value(), 2);
 $studer_settings_arr[$studer_api->paramId]["name"]  = "Delay 3 for AUX1 Activate on below Battery Voltage Level1";

 // 1255 Battery Voltage Level for AUX1 deactivation
 $studer_api->paramId              = '1255'; //battery voltage  for deactivation of AUX1 after activation
 $studer_api->device               = 'XT1';
 $studer_api->paramPart            = 'Value';
 $studer_settings_arr[$studer_api->paramId]["value"] = round($studer_api->get_parameter_value(), 2);
 $studer_settings_arr[$studer_api->paramId]["name"]  = "Battery Voltage Level for AUX1 deactivation";

 // 1256 delay for deactivation of AUX1
 $studer_api->paramId              = '1256'; //delay for deactivation of AUX1 after 1255
 $studer_api->device               = 'XT1';
 $studer_api->paramPart            = 'Value';
 $studer_settings_arr[$studer_api->paramId]["value"] = round($studer_api->get_parameter_value(), 2);
 $studer_settings_arr[$studer_api->paramId]["name"]  = "Delay for AUX1 deActivate on Battery Voltage Level";

 // 1516 AUX1 deActivate on Battery FLOAT phase?
 $studer_api->paramId              = '1516';
 $studer_api->device               = 'XT1';
 $studer_api->paramPart            = 'Value';
 $studer_settings_arr[$studer_api->paramId]["value"] = $studer_api->get_parameter_value();
 $studer_settings_arr[$studer_api->paramId]["name"]  = "AUX1 deActivate on Battery FLOAT phase?";

 // 1288 AUX1 activate on Battery VOltage: Battery voltage dynamic compensation?
 $studer_api->paramId              = '1288';
 $studer_api->device               = 'XT1';
 $studer_api->paramPart            = 'Value';
 $studer_settings_arr[$studer_api->paramId]["value"] = $studer_api->get_parameter_value();
 $studer_settings_arr[$studer_api->paramId]["name"]  = "Battery Voltage Dynamic Compensation for AUX1 Activate on Battery Voltage Level?";

 // 1545 Remote Entry Active (Open or CLosed?)
 $studer_api->paramId              = "1545";
 $studer_api->device               = 'XT1';
 $studer_api->paramPart            = 'Value';
 $studer_settings_arr[$studer_api->paramId]["value"] = $studer_api->get_parameter_value();
 $studer_settings_arr[$studer_api->paramId]["name"]  = "Remote Entry Active: Open ? Closed ?";

 // 1538 Remote Entry Active does what? Prohibits Transfer Relay?
 $studer_api->paramId              = "1538";
 $studer_api->device               = 'XT1';
 $studer_api->paramPart            = 'Value';
 $studer_settings_arr[$studer_api->paramId]["value"] = $studer_api->get_parameter_value();
 $studer_settings_arr[$studer_api->paramId]["name"]  = "Remote Entry Active: Prohibit Transfer Relay?";

 // 1578 Remote Entry Activated by AUX1 state?
 $studer_api->paramId              = "1578";
 $studer_api->device               = 'XT1';
 $studer_api->paramPart            = 'Value';
 $studer_settings_arr[$studer_api->paramId]["value"] = $studer_api->get_parameter_value();
 $studer_settings_arr[$studer_api->paramId]["name"]  = "Remote Entry Activated by AUX1 state?";

 // begin VarioTrack readings
 // Synchronized to Xtender?
 $studer_api->paramId              = "10037";
 $studer_api->device               = 'VT_Group';
 $studer_api->paramPart            = 'Value';
 $studer_settings_arr[$studer_api->paramId]["value"] = $studer_api->get_parameter_value();
 $studer_settings_arr[$studer_api->paramId]["name"]  = "Variotrac synchronization of battery cycle with Xtender?";

 // 10005 Variotrac Battery Float Voltage
 $studer_api->paramId              = "10005";
 $studer_api->device               = 'VT_Group';
 $studer_api->paramPart            = 'Value';
 $studer_settings_arr[$studer_api->paramId]["value"] = $studer_api->get_parameter_value();
 $studer_settings_arr[$studer_api->paramId]["name"]  = "Variotrac Battery Float Voltage";

 // 10334 VarioTrac Battery Charging Under Voltage
 $studer_api->paramId              = "10334";
 $studer_api->device               = 'VT_Group';
 $studer_api->paramPart            = 'Value';
 $studer_settings_arr[$studer_api->paramId]["value"] = $studer_api->get_parameter_value();
 $studer_settings_arr[$studer_api->paramId]["name"]  = "Variotrac Battery Under Voltage";

 // 10002 Max. Variotrac Battery Charge current
 $studer_api->paramId              = "10002";
 $studer_api->device               = 'VT_Group';
 $studer_api->paramPart            = 'Value';
 $studer_settings_arr[$studer_api->paramId]["value"] = $studer_api->get_parameter_value();
 $studer_settings_arr[$studer_api->paramId]["name"]  = "Max. Variotrac Battery Charge current";

 // update user meta with JSON encoded studer settings data
 update_param_meta("studer_settings", json_encode($studer_settings_arr));

 return $studer_settings_arr;
}

/**
**
*/
function get_studer_settings_user_meta()
{
 $studer_settings_arr  = [];

 $current_user_ID      = wp_get_current_user()->ID;

 // extract JSON encoded settings from user meta
 $json_studer_settings = get_user_meta($current_user_ID, "studer_settings", true);

 if (empty($json_studer_settings))
 {
   // the user meta for studer settings is empty signifying a new user. So get settings using API for 1st time and update user meta
   // also return data already as array so no need for json decode
   $studer_settings_arr = get_studer_settings_using_api();
 }
 else
 {
   // decode JSON data into associative array
   $studer_settings_arr  = json_decode($json_studer_settings, true);
 }

 return $studer_settings_arr;
}

/**
** @param boolval:$from_user_meta: When true (default) data is extracted from user meta, when false from studer API
**
*/
function studer_main_page_render($from_user_meta = true)
{

 // proceed with generating page HTML string variable to be returned

 $studer_api = new studer_api();

// if option is true then get object formed using user_meta, else get object from Studer API
 $studer_settings_arr = get_option( 'studer_settings')["force_Read_Config_from_Studer"] ?
                        get_studer_settings_using_api() : get_studer_settings_user_meta();

 // top line displayed on page
 $output .= 'Studer Parameters for my installation ID: ' . "<b>"
             . $studer_api->installation_id . "</b>" . ' of User: ' . "<b>"
             . $studer_api->name . "</b>";

 $output .=
 '<style>
   table {
   border-collapse: collapse;
   }
   th, td {
   border: 1px solid orange;
   padding: 10px;
   text-align: left;
   }
   .rediconcolor {color:red;}
   .greeniconcolor {color:green;}
</style>
 <table style="width:100%">
   <tr>
     <th>Parameter ID</th>
     <th>Description</th>
     <th>Value</th>
     <th>Units</th>
     <th>Installer val</th>
   </tr>';

 // 1108 Battery Under Voltage Without Load (For LVD)
 $description = "Battery undervoltage @ duration, before turn off: Related to LVD";

 $output .= print_row_table('1108 @ 1190', $studer_settings_arr["1108"]["value"]
             . ' Vdc @' . $studer_settings_arr["1190"]["value"] . ' mins', $description, 'Vdc @ 1min', '46.5 @ 1');

 // 1191 1532 battery under voltage dynamic compensation and if so type
 // display neatening
 if ($studer_settings_arr["1191"]["value"] == 1.0)
 {
   $battery_uv_compensation = "Yes";
 }
 $battery_uv_compensation_type     = $studer_settings_arr["1532"]["value"];

 $description                      = "Battery undervoltage Compensation enabled? if so type";
 $output .= print_row_table('1191 @ 1532', $battery_uv_compensation . ', ' . $battery_uv_compensation_type, $description, '', 'Yes, Automatic');

 // 1110 Restart voltage after batteries undervoltage disconnect
 $output .= print_row_table('1110', $studer_settings_arr["1110"]["value"], $studer_settings_arr["1110"]["name"], 'Vdc', '47.9');

 // 1126 Inverter Smart-Boost Allowed?
 if ($studer_settings_arr["1126"]["value"] == 1.0)
 {
   $inverter_smartboost = "Yes";
 }
 $output .= print_row_table('1126', $inverter_smartboost, 'Inverter Smart-Boost Allowed?', 'Yes/No', 'Yes');

 // 1124 Inverter Allowed?
 if ($studer_settings_arr["1124"]["value"] == 1.0)
 {
   $inverter_allowed = "Yes";
 }
 $output .= print_row_table('1124', $inverter_allowed, 'Inverter Allowed?', 'Yes/No', 'Yes');

 // 1125 Inverter Battery Charging Allowed?
 if ($studer_settings_arr["1125"]["value"] == 1.0)
 {
   $charger_allowed = "Yes";
 }
 $output .= print_row_table('1125', $charger_allowed, 'Inverter Charging of Battery Allowed?', 'Yes/No', 'Yes');

 // 1126 Transfer Relay Allowed?
 if ($studer_settings_arr["1126"]["value"] == 1.0)
 {
   $transfer_relay_allowed = "Yes";
 }
 $output .= print_row_table('1126', $transfer_relay_allowed, 'Transfer Relay Allowed?', 'Yes/No', 'Yes');


 // 1140 baatery float voltage for inverter charging
 $output .= print_row_table('1140', $studer_settings_arr["1140"]["value"], $studer_settings_arr["1140"]["name"], 'Vdc','53');

 // 1138 Max Batery Charge Current of Inverter
 $output .= print_row_table('1138', $studer_settings_arr["1138"]["value"], $studer_settings_arr["1138"]["name"], 'Adc','30');

 // 1202 AUX1 contact Operating Mode: Automatic? Reverse-Automatic?
 $aux1_operating_mode              = $studer_settings_arr["1202"]["value"];
 if ($aux1_operating_mode <= 1.0E-10)
 {
   $aux1_operating_mode = "Reversed Automatic";
 }
 $output .= print_row_table('1202', $aux1_operating_mode, 'AUX1 contact Operating Mode', 'Automatic/Reverse', 'Reverse Automatic');


 // 1246 AUX1 activate on Battery VOltage Level1? and 1288 battery voltage dynamic compensation for this?
 $aux1_activate_battery_voltagecomp    = $studer_settings_arr["1288"]["value"];
 if ($aux1_activate_battery_voltagecomp == 1.0)
 {
   $aux1_activate_battery_voltagecomp = "Yes";
 }

 if ($studer_settings_arr["1246"]["value"] == 1.0)
 {
   $aux1_activate_battery_voltage1 = "Yes";
 }

 $val = $aux1_activate_battery_voltage1 . " " . $aux1_activate_battery_voltagecomp;
 $output .= print_row_table('1246-1288', $val, 'AUX1 contact activated on battery voltage level? Battery dynamic compensation?', 'Yes/No', 'Yes');


 // 1247 and 1248 BV1 for AUX1 activate and its duration only if 1246 for BV1 activation is true
 if ($studer_settings_arr["1246"]["value"] == 1.0)
 {
   $val =  $studer_settings_arr["1247"]["value"] . '@' . $studer_settings_arr["1248"]["value"]. ' mins';
   $param_desc = "Battery Voltage1 Level1 for AUX1 activation and its Duration";
   $output .= print_row_table('1247-1248', $val, $param_desc, 'Vdc @ mins', 'Make sure these voltages are higher than for LVD');
 }


 // 1249 1250 1251 AUX1 activate on Battery VOltage Level2? Value, and duration
 if ($studer_settings_arr["1249"]["value"] == 1.0)
 {
   $val = 'Yes' . $studer_settings_arr["1250"]["value"] . '@' . $studer_settings_arr["1251"]["value"]. ' mins';
   $param_desc = "AUX1 activation for Battery Voltage Level2";
   $output .= print_row_table('1249-1251', $val, $param_desc, 'Vdc @ mins', 'Make sure these voltages are higher than for LVD');
 }
 else
 {
   $val = 'No' . $studer_settings_arr["1250"]["value"] . '@' . $studer_settings_arr["1251"]["value"]. ' mins';
   $param_desc = "AUX1 activation for Battery Voltage Level2";
   $output .= print_row_table('1249-1251', $val, $param_desc, 'Vdc @ mins', 'Make sure these voltages are higher than for LVD');
 }


 // 1252 1253 1254 AUX1 activate on Battery VOltage Level3? Value, and duration
 if ($studer_settings_arr["1252"]["value"] == 1.0)
 {
   $val = 'Yes' . $studer_settings_arr["1253"]["value"] . '@' . $studer_settings_arr["1254"]["value"]. ' mins';
   $param_desc = "AUX1 activation for Battery Voltage Level3";
   $output .= print_row_table('1252-1254', $val, $param_desc, 'Vdc @ mins', 'Make sure these voltages are higher than for LVD');
 }
 else
 {
   $val = 'No' . $studer_settings_arr["1253"]["value"] . '@' . $studer_settings_arr["1254"]["value"]. ' mins';
   $param_desc = "AUX1 activation for Battery Voltage Level3";
   $output .= print_row_table('1252-1254', $val, $param_desc, 'Vdc @ mins', 'Make sure these voltages are higher than for LVD');
 }


 // 1545
 if ($studer_settings_arr["1545"]["value"] == 1.0)
 {
   $remote_entry_active = "Closed";
   $output .= print_row_table('1545', $studer_settings_arr["1545"]["value"], 'Remote Entry Active (Open or CLosed??', 'Open/CLosed', 'Closed');
 }
 else
 {
   $remote_entry_active = "Open";
   $output .= print_row_table('1545', $studer_settings_arr["1545"]["value"], 'Remote Entry Active (Open or CLosed??', 'Open/CLosed', 'Closed');
 }



 // 1538 Prohibits Transfer Relay?
 if ($studer_settings_arr["1538"]["value"] == 1.0)
 {
   $prohibits_transfer_relay = "Yes";
   $output .= print_row_table('1538', $prohibits_transfer_relay, 'Prohibits Transfer Relay?', 'Yes/No', 'Yes');
 }
 else
 {
   $prohibits_transfer_relay = "No";
   $output .= print_row_table('1538', $prohibits_transfer_relay, 'Prohibits Transfer Relay?', 'Yes/No', 'Yes');
 }


 // 1578 Activated by AUX1 state?
 if ($studer_settings_arr["1578"]["value"] == 1.0)
 {
   $activated_by_aux1 = "Yes";
   $output .= print_row_table('1578', $activated_by_aux1, 'Activated by AUX1 state?', 'Yes/No', 'Yes');
 }
 else
 {
   $activated_by_aux1 = "No";
   $output .= print_row_table('1578', $activated_by_aux1, 'Activated by AUX1 state?', 'Yes/No', 'Yes');
 }


 // check to see if all conditions for off-grid mode satisfied
 if (
     ($activated_by_aux1)              &&
     ($prohibits_transfer_relay)       &&
     ($remote_entry_active == 0)       &&
     ($aux1_activate_battery_voltage)  &&
     ($aux1_operating_mode <= 1.0E-10)
    )
 {
   $conditions_offgridmode        = 'Yes';
 }
 else {
   $conditions_offgridmode        = "No";
 }
 $output .= print_row_table('', $conditions_offgridmode, 'All conditions for Off-Grid mode Satisfied?', 'Yes/No', 'Yes');


 // 10037 Variotrac synchronization of battery cycle with Xtender?
 $param_desc                       = "VaroTrac Synchronisation of battery cycle with Xtender";
 $param_units                      = "1=Yes, 0=No";
 $factory_default                  = '1=Yes';
 $output .= print_row_table("10037", $studer_settings_arr["10037"]["value"], $param_desc, $param_units, $factory_default);


 // 10005 VarioTrac Battery Float Voltage
 $param_desc                       = "VaroTracc Battery Float Voltage";
 $param_units                      = "Vdc";
 $factory_default                  = 54.4;
 $output .= print_row_table("10005", $studer_settings_arr["10005"]["value"], $param_desc, $param_units, $factory_default);


 // 10334 VarioTrac Battery Charging underVoltage
 $param_desc                       = "VarioTrack Battery Charging: Under Voltage";
 $param_units                      = "Vdc";
 $factory_default                  = 40;
 $output .= print_row_table("10334", $studer_settings_arr["10334"]["value"], $param_desc, $param_units, $factory_default);


 // 10002 Battery Charge Current
 $param_desc                       = "VarioTrack Battery Charge Current";
 $param_units                      = "Adc";
 $factory_default                  = 80;
 $output .= print_row_table("10002", $studer_settings_arr["10002"]["value"], $param_desc, $param_units, $factory_default);

 // close the table tag after final entry above
 $output .= '</table>';

 return $output;
}

function studer_readings_page_render()
{
 // check for valid logged in user with studer api credentials already set
 login_and_studer_check();

 $data = get_studer_readings();

 $script = '"' . $data->fontawesome_cdn . '"';

 $output = '<script src="' . $data->fontawesome_cdn . '"></script>';

 $output .=

   '<style>
       .rediconcolor {color:red;}

       .greeniconcolor {color:green;}

       .arrowSliding_nw_se {
         position: relative;
         -webkit-animation: slide_nw_se 2s linear infinite;
                 animation: slide_nw_se 2s linear infinite;
       }

       .arrowSliding_ne_sw {
         position: relative;
         -webkit-animation: slide_ne_sw 2s linear infinite;
                 animation: slide_ne_sw 2s linear infinite;
       }

       .arrowSliding_sw_ne {
         position: relative;
         -webkit-animation: slide_ne_sw 2s linear infinite reverse;
                 animation: slide_ne_sw 2s linear infinite reverse;
       }

       @-webkit-keyframes slide_ne_sw {
           0% { opacity:0; transform: translate(20%, -20%); }
          20% { opacity:1; transform: translate(10%, -10%); }
          80% { opacity:1; transform: translate(-10%, 10%); }
         100% { opacity:0; transform: translate(-20%, 20%); }
       }
       @keyframes slide_ne_sw {
           0% { opacity:0; transform: translate(20%, -20%); }
          20% { opacity:1; transform: translate(10%, -10%); }
          80% { opacity:1; transform: translate(-10%, 10%); }
         100% { opacity:0; transform: translate(-20%, 20%); }
       }

       @-webkit-keyframes slide_nw_se {
           0% { opacity:0; transform: translate(-20%, -20%); }
          20% { opacity:1; transform: translate(-10%, -10%); }
          80% { opacity:1; transform: translate(10%, 10%);   }
         100% { opacity:0; transform: translate(20%, 20%);   }
       }
       @keyframes slide_nw_se {
           0% { opacity:0; transform: translate(-20%, -20%); }
          20% { opacity:1; transform: translate(-10%, -10%); }
          80% { opacity:1; transform: translate(10%, 10%);   }
         100% { opacity:0; transform: translate(20%, 20%);   }
       }

       .fa-rotate-45 {
           -webkit-transform: rotate(45deg);
           -moz-transform: rotate(45deg);
           -ms-transform: rotate(45deg);
           -o-transform: rotate(45deg);
           transform: rotate(45deg);
         }

       .fa-rotate-135 {
           -webkit-transform: rotate(135deg);
           -moz-transform: rotate(135deg);
           -ms-transform: rotate(135deg);
           -o-transform: rotate(135deg);
           transform: rotate(135deg);
         }

       .fa-rotate-225 {
           -webkit-transform: rotate(225deg);
           -moz-transform: rotate(225deg);
           -ms-transform: rotate(225deg);
           -o-transform: rotate(225deg);
           transform: rotate(225deg);
         }

       .fa-rotate-315 {
           -webkit-transform: rotate(315deg);
           -moz-transform: rotate(315deg);
           -ms-transform: rotate(315deg);
           -o-transform: rotate(315deg);
           transform: rotate(315deg);
       }

       /* xs (moins de 768px) */
       .lSAction>a {
           top: 100%;
           background-image: url("");
       }

       .center-thumbs .lslide {
           margin: 0 auto;
           text-align: center;
       }

       .mod-install {
           font-size: 15px;
           margin: 0 0 10px;
       }

       .img-stuser {
           max-height: 420px;
           max-width: 100%;
       }

       .img-logo {
           max-height: 100px;
       }

       .legend {
           font-weight: bold;
       }

       .display {
           font-size: large;
           font-weight: bold;
       }

       .synoptic-fixed-height {
           height: 450px;
       }

       .synoptic-table {
           margin: auto;
           width: 95% !important;
           height: auto;
           border-collapse: collapse;
           overflow-x: auto;
           border-spacing: 0;
           font-size: 12px;
       }

       .synoptic-table td {
           padding: 2px;
       }

       .arrow-table-horizontal {
           text-align: center;
           width: 100%;
       }
       .arrow-table-horizontal td {
           width: 33%;
       }

       .arrow-table-vertical {
           text-align: center;
           width: 100%;
       }
       .refresh-button {
           background-color: transparent;
           background-repeat: no-repeat;
           border: none;
           cursor: pointer;
           overflow: hidden;
           outline: none;
           text-align: center !important;
       }
       .quickoverview-title {
           padding-bottom: 10px;
           padding-left: 10px;
           border-bottom: 1px solid #f4f4f4;
       }

       .installation-details-fixed {
           height: 440px;
       }

       .installation-details-title {
           font-size: large;
           font-weight: bold;
           padding-bottom: 10px;
           margin-bottom: 10px;
           border-bottom: 1px solid #f4f4f4;
       }

       .installer-title {
           font-size: large;
           font-weight: bold;
           padding-bottom: 10px;
           margin-bottom: 10px;
           border-bottom: 1px solid #f4f4f4;
       }

       .img-pow-pv {
           max-width: 59px;
       }

       .img-pow-genset {
           max-width: 59px;
       }

       .img-pow-logo {
           max-width: 80px;
           border: 1px;
       }

       .img-pow-load {
           max-width: 59px;
       }

       /* xs (plus de 768px */
       @media (min-width: 768px) {
           .synoptic-table {
               margin: auto;
               width: 95% !important;
               height: 100%;
               border-collapse: collapse;
               overflow-x: auto;
               border-spacing: 0;
               font-size: 22px;
           }

           .synoptic-table td {
               padding: 2px;
           }

           .synoptic-fixed-height {
               height: 450px;
               width: auto;
           }

           .quickoverview-title {
               padding-left: 0;
           }

           .img-pow-genset {
               max-width: 59px;
           }

           .img-pow-logo {
               max-width: 59px;
           }

           .img-pow-load {
               max-width: 59px;
           }
       }

   </style>';

   $output .=
   '<div class="col-12">
     <div class="row">
       <div class="box w-100 box-primary">
         <div class="box-body">

             <div class="box-tools pull-right">
                         <button type="button" class="btn btn-box-tool refresh-button"  data-placement="right"
                                 data-toggle="tooltip"
                                 data-container="body"
                                 title="Refresh">
                             <i class="fa fa-1x fa-refresh fa-spin" id="refresh-button" style="height: 15px; width: 15px;"></i>
                         </button>
             </div>

         </div>';
         $output .= '
         <div class="row-fluid">
           <div class="table-responsive synoptic-fixed-height">
             <table class="synoptic-table">
               <tr>
                   <td>
                       <img id="pow-genset-img" src="https://sritoni.org/6076/wp-content/uploads/sites/14/2021/02/grid_genset.svg" class="img-pow-genset"/>
                   </td>
                   <td></td>
                   <td style="text-align: left">
                       <img id="pow-pv-img" src="https://sritoni.org/6076/wp-content/uploads/sites/14/2021/02/simple_pv.svg" class="img-pow-pv"/>
                   </td>
               </tr>
               <!-- 2nd row with values and arrows -->
               <tr>
                   <td class="legend" id="power-grid-genset" style="text-align: right;">' .
                     $data->grid_pin_ac_kw . ' kW<br>
                     <font color="#D0D0D0">' .
                     $data->grid_input_vac . ' V
                   </td>
                   <td>
                     <table>
                       <tr>
                           <td style="text-align: left;">
                               <i class="' . $data->grid_input_arrow_class . '" id="power-arrow-grid-genset"></i>
                           </td>
                           <td class="' . $data->solar_arrow_animation_class . '" id="power-arrow-solar-animation" style="text-align: right;">
                               <i class="' . $data->solar_arrow_class . '" id="power-arrow-solar"></i>
                           </td>
                       </tr>
                     </table>
                   </td>
                   <td
                       class="legend" id="power-solar" style="text-align: left;">' .
                       $data->psolar_kw . ' kW<br>
                       <font color="#990000">' .
                       $data->solar_pv_adc . ' A
                   </td>
               </tr>

               <!-- 3rd row with only studer logo in the middle column all else blank -->

               <tr>
                   <td></td>
                   <td style="text-align: center;">
                       <img src="https://sritoni.org/6076/wp-content/uploads/sites/14/2021/02/studer_innotec_logo_blue.png" class="img-pow-logo" id="power-img-logo"/>
                   </td>
                   <td></td>
               </tr>

               <!-- 4th row with only values and arrows similar to 2nd row -->
               <tr>
                   <td style="text-align: right;"
                       class="legend" id="power-battery">' .
                       abs($data->pbattery_kw) . ' kW<br>
                       <font color="#990000">' .
                       $data->battery_voltage_vdc . ' V<br>' .
                       abs($data->battery_charge_adc) . ' A
                   </td>
                   <td>
                           <table>
                               <tr>
                                   <td class="' . $data->battery_charge_animation_class . '" id="battery-arrow-load-animation" style="text-align: left;">
                                       <i class="' . $data->battery_charge_arrow_class . '" id="power-arrow-battery"></i>
                                   </td>
                                   <td class="arrowSliding_nw_se" id="power-arrow-load-animation" style="text-align: right;">
                                       <i class="' . $data->inverter_pout_arrow_class . '" id="power-arrow-load"></i>
                                   </td>
                               </tr>
                           </table>
                   </td>
                   <td class="legend" id="power-load" style="text-align: left;">' .
                       $data->pout_inverter_ac_kw . ' kW
                   </td>
                 </tr>

                 <!-- 5th row with only images of battery and home on extreme columns -->

                 <tr>
                     <td style="text-align: left">
                            <i class="' . $data->battery_icon_class . '" id="power_battery-icon"></i>
                     </td>
                     <td></td>
                     <td>
                         <img id="pow-load-img" src="https://sritoni.org/6076/wp-content/uploads/sites/14/2021/02/house.svg" class="img-pow-load"/>
                     </td>
                 </tr>
       </table>
   </div>
   </div>';
   return $output;
}

function studer_variotrac_page_render()
{
 login_and_studer_check();

 $studer_api = new studer_api();

 // top line displayed on page
 echo nl2br('Studer VarioTrac Parameters for my installation ID: ' . "<b>" . $studer_api->installation_id . "</b>" . ' of User: ' . "<b>" . $studer_api->name . "</b>\n");

 ?>
 <style>
   table {
   border-collapse: collapse;
   }
   th, td {
   border: 1px solid orange;
   padding: 10px;
   text-align: left;
   }
</style>
 <table style="width:100%">
   <tr>
     <th>Parameter ID</th>
     <th>Description</th>
     <th>Value</th>
     <th>Units</th>
     <th>Installer val</th>
   </tr>
 <?php

 // Synchronized to Xtender?
 $studer_api->paramId              = 10037;
 $studer_api->device               = 'VT_Group';
 $studer_api->paramPart            = 'Value';
 $param_value                      = $studer_api->get_parameter_value();
 $param_desc                       = "Synchronisation battery cycle with Xtender";
 $param_units                      = "1=Yes, 0=No";
 $factory_default                  = '1=Yes';
 print_row_table($studer_api->paramId, $param_value, $param_desc, $param_units, $factory_default);

 // Battery Float Voltage
 $studer_api->paramId              = 10005;
 $studer_api->device               = 'VT_Group';
 $studer_api->paramPart            = 'Value';
 $param_value                      = $studer_api->get_parameter_value();
 $param_desc                       = "Battery Float Voltage";
 $param_units                      = "Vdc";
 $factory_default                  = 54.4;
 print_row_table($studer_api->paramId, $param_value, $param_desc, $param_units, $factory_default);

 // Battery underVoltage
 $studer_api->paramId              = 10334;
 $studer_api->device               = 'VT_Group';
 $studer_api->paramPart            = 'Value';
 $param_value                      = $studer_api->get_parameter_value();
 $param_desc                       = "Battery Under Voltage";
 $param_units                      = "Vdc";
 $factory_default                  = 40;
 print_row_table($studer_api->paramId, $param_value, $param_desc, $param_units, $factory_default);

 // Battery Charge Current
 $studer_api->paramId              = 10002;
 $studer_api->device               = 'VT_Group';
 $studer_api->paramPart            = 'Value';
 $param_value                      = $studer_api->get_parameter_value();
 $param_desc                       = "Battery Charge Current";
 $param_units                      = "Adc";
 $factory_default                  = 80;
 print_row_table($studer_api->paramId, $param_value, $param_desc, $param_units, $factory_default);
}

function print_row_table($paramId, $param_value, $param_desc, $param_units, $factory_default = null)
{

  if (stripos($param_value, "yes") !== false)
  {
    // the 2 strings are equal. So it means a Yes! so colour it Green
    $param_value = '<font color="green">' . $param_value;
  }
  elseif (stripos($param_value, "no") !== false)
  {
    $param_value = '<font color="red">' . $param_value;
  }
  else
  {
    // no class applied so do nothing
  }

 $returnstring =
 '<tr>' .
    '<td>' . $paramId .          '</td>' .
    '<td>' . $param_desc .       '</td>' .
    '<td>' . $param_value .      '</td>' .
    '<td>' . $param_units .      '</td>' .
    '<td>' . $factory_default .  '</td>' .
 '</tr>';
 return $returnstring;
}

/**
** This function returns an object that comprises data read form user's installtion
*/
function get_studer_readings()
{
 $Ra = 0.0;       // value of resistance from DC junction to Inverter
 $Rb = 0.0;       // value of resistance from DC junction to Battery terminals

 $studer_api = new studer_api();

 $studer_readings_obj = new stdClass;

 $body = [];

 // get the input AC active power value
 $body = array(array(
                       "userRef"       =>  3136,   // AC active power delivered by inverter
                       "infoAssembly"  => "Master"
                    ),
               array(
                        "userRef"       =>  3137,   // Grid AC input Active power
                        "infoAssembly"  => "Master"
                    ),
               array(
                        "userRef"       =>  3020,   // State of Transfer Relay
                        "infoAssembly"  => "Master"
                     ),
               array(
                        "userRef"       =>  3031,   // State of AUX1 relay
                        "infoAssembly"  => "Master"
                     ),
               array(
                       "userRef"       =>  3000,   // Battery Voltage
                       "infoAssembly"  => "Master"
                     ),
               array(
                       "userRef"       =>  3011,   // Grid AC in Voltage Vac
                       "infoAssembly"  => "Master"
                     ),
               array(
                       "userRef"       =>  3012,   // Grid AC in Current Aac
                       "infoAssembly"  => "Master"
                     ),
               array(
                       "userRef"       =>  3005,   // Battery Voltage
                       "infoAssembly"  => "Master"
                     ),
                     
               array(
                       "userRef"       =>  11001,   // Battery charge current from VT1
                       "infoAssembly"  => "1"
                     ),
               array(
                       "userRef"       =>  11001,   // Battery charge current from VT2
                       "infoAssembly"  => "2"
                     ),
               array(
                       "userRef"       =>  11002,   // solar pv Voltage to variotrac
                       "infoAssembly"  => "Master"
                     ),
               array(
                       "userRef"       =>  11004,   // Psolkw from VT1
                       "infoAssembly"  => "1"
                     ),
               array(
                       "userRef"       =>  11004,   // Psolkw from VT2
                       "infoAssembly"  => "2"
                     ),
                     
               array(
                       "userRef"       =>  3010,   // Phase of battery charge
                       "infoAssembly"  => "Master"
                     ),
               );
 $studer_api->body   = $body;

 // POST curl request to Studer
 $user_values  = $studer_api->get_user_values();

 $solar_pv_adc = 0;
 $psolar_kw    = 0;


 foreach ($user_values as $user_value)
 {
   switch (true)
   {
     case ( $user_value->reference == 3031 ) :
       $aux1_relay_state = $user_value->value;
     break;

     case ( $user_value->reference == 3020 ) :
       $transfer_relay_state = $user_value->value;
     break;

     case ( $user_value->reference == 3011 ) :
       $grid_input_vac = round($user_value->value, 0);
     break;

     case ( $user_value->reference == 3012 ) :
       $grid_input_aac = round($user_value->value, 1);
     break;

     case ( $user_value->reference == 3000 ) :
       $battery_voltage_vdc = round($user_value->value, 2);
     break;

     case ( $user_value->reference == 3005 ) :
       $inverter_current_adc = round($user_value->value, 1);
     break;

     case ( $user_value->reference == 3137 ) :
       $grid_pin_ac_kw = round($user_value->value, 2);

     break;

     case ( $user_value->reference == 3136 ) :
       $pout_inverter_ac_kw = round($user_value->value, 2);

     break;

     case ( $user_value->reference == 11001 ) :
       // we have to accumulate values form 2 cases:VT1 and VT2 so we have used accumulation below
       $solar_pv_adc += $user_value->value;

     break;

     case ( $user_value->reference == 11002 ) :
       $solar_pv_vdc = round($user_value->value, 1);

     break;

     case ( $user_value->reference == 11004 ) :
       // we have to accumulate values form 2 cases so we have used accumulation below
       $psolar_kw += round($user_value->value, 2);

     break;

     case ( $user_value->reference == 3010 ) :
       $phase_battery_charge = $user_value->value;

     break;
   }
 }

 $solar_pv_adc = round($solar_pv_adc, 1);

 // calculate the current into/out of battery
 $battery_charge_adc  = round($solar_pv_adc + $inverter_current_adc, 1); // + is charge, - is discharge
 $pbattery_kw         = round($battery_voltage_vdc * $battery_charge_adc * 0.001, 2); //$psolar_kw - $pout_inverter_ac_kw;


 // inverter's output always goes to load never the other way around :-)
 $inverter_pout_arrow_class = "fa fa-long-arrow-right fa-rotate-45 rediconcolor";

 // conditional class names for battery charge down or up arrow
 if ($battery_charge_adc > 0.0)
 {
   // current is positive so battery is charging so arrow is down and to left. Also arrow shall be green to indicate charging
   $battery_charge_arrow_class = "fa fa-long-arrow-down fa-rotate-45 rediconcolor";
   // battery animation class is from ne-sw
   $battery_charge_animation_class = "arrowSliding_ne_sw";

   // also good time to compensate for IR drop
   $battery_voltage_vdc = round($battery_voltage_vdc + abs($inverter_current_adc) * $Ra - abs($battery_charge_adc) * $Rb, 2);
 }
 else
 {
   // current is -ve so battery is discharging so arrow is up and icon color shall be red
   $battery_charge_arrow_class = "fa fa-long-arrow-up fa-rotate-45 greeniconcolor";
   $battery_charge_animation_class = "arrowSliding_sw_ne";

   // also good time to compensate for IR drop
   $battery_voltage_vdc = round($battery_voltage_vdc + abs($inverter_current_adc) * $Ra + abs($battery_charge_adc) * $Rb, 2);
 }

 switch(true)
 {
   case (abs($battery_charge_adc) < 27 ) :
     $battery_charge_arrow_class .= " fa-1x";
   break;

   case (abs($battery_charge_adc) < 54 ) :
     $battery_charge_arrow_class .= " fa-2x";
   break;

   case (abs($battery_charge_adc) >=54 ) :
     $battery_charge_arrow_class .= " fa-3x";
   break;
 }

 // conditional for solar pv arrow
 if ($psolar_kw > 0.1)
 {
   // power is greater than 0.2kW so indicate down arrow
   $solar_arrow_class = "fa fa-long-arrow-down fa-rotate-45 greeniconcolor";
   $solar_arrow_animation_class = "arrowSliding_ne_sw";
 }
 else
 {
   // power is too small indicate a blank line vertically down from Solar panel to Inverter in diagram
   $solar_arrow_class = "fa fa-minus fa-rotate-90";
   $solar_arrow_animation_class = "";
 }

 switch(true)
 {
   case (abs($psolar_kw) < 0.5 ) :
     $solar_arrow_class .= " fa-1x";
   break;

   case (abs($psolar_kw) < 2.0 ) :
     $solar_arrow_class .= " fa-2x";
   break;

   case (abs($psolar_kw) >= 2.0 ) :
     $solar_arrow_class .= " fa-3x";
   break;
 }

 switch(true)
 {
   case (abs($pout_inverter_ac_kw) < 1.0 ) :
     $inverter_pout_arrow_class .= " fa-1x";
   break;

   case (abs($pout_inverter_ac_kw) < 2.0 ) :
     $inverter_pout_arrow_class .= " fa-2x";
   break;

   case (abs($pout_inverter_ac_kw) >=2.0 ) :
     $inverter_pout_arrow_class .= " fa-3x";
   break;
 }

 // conditional for Grid input arrow
 if ($transfer_relay_state)
 {
   // Transfer Relay is closed so grid input is possible
   $grid_input_arrow_class = "fa fa-long-arrow-right fa-rotate-45";
 }
 else
 {
   // Transfer relay is open and grid input is not possible
   $grid_input_arrow_class = "fa fa-times-circle fa-2x";
 }

 switch(true)
 {
   case (abs($grid_pin_ac_kw) < 1.0 ) :
     $grid_input_arrow_class .= " fa-1x";
   break;

   case (abs($grid_pin_ac_kw) < 2.0 ) :
     $grid_input_arrow_class .= " fa-2x";
   break;

   case (abs($grid_pin_ac_kw) < 3.5 ) :
     $grid_input_arrow_class .= " fa-3x";
   break;

   case (abs($grid_pin_ac_kw) < 4 ) :
     $grid_input_arrow_class .= " fa-4x";
   break;
 }

$current_user           = wp_get_current_user();
$current_user_ID        = $current_user->ID;
$battery_vdc_state_json = get_user_meta($current_user_ID, "json_battery_voltage_state", true);
$battery_vdc_state      = json_decode($battery_vdc_state_json, true);

// select battery icon based on charge level
 switch(true)
 {
   case ($battery_voltage_vdc < $battery_vdc_state["25p"] ):
     $battery_icon_class = "fa fa-3x fa-battery-quarter fa-rotate-270";
   break;

   case ($battery_voltage_vdc >= $battery_vdc_state["25p"] && $battery_voltage_vdc < $$battery_vdc_state["50p"] ):
     $battery_icon_class = "fa fa-3x fa-battery-half fa-rotate-270";
   break;

   case ($battery_voltage_vdc >= $$battery_vdc_state["50p"] && $battery_voltage_vdc < $battery_vdc_state["75p"] ):
     $battery_icon_class = "fa fa-3x fa-battery-three-quarters fa-rotate-270";
   break;

   case ($battery_voltage_vdc >= $battery_vdc_state["75p"] ):
     $battery_icon_class = "fa fa-3x fa-battery-full fa-rotate-270";
   break;
 }

 // update the object with battery data read
 $studer_readings_obj->battery_charge_adc          = abs($battery_charge_adc);
 $studer_readings_obj->pbattery_kw                 = abs($pbattery_kw);
 $studer_readings_obj->battery_voltage_vdc         = $battery_voltage_vdc;
 $studer_readings_obj->battery_charge_arrow_class  = $battery_charge_arrow_class;
 $studer_readings_obj->battery_icon_class          = $battery_icon_class;
 $studer_readings_obj->battery_charge_animation_class = $battery_charge_animation_class;

 // update the object with SOlar data read
 $studer_readings_obj->psolar_kw                   = $psolar_kw;
 $studer_readings_obj->solar_pv_adc                = $solar_pv_adc;
 $studer_readings_obj->solar_pv_vdc                = $solar_pv_vdc;
 $studer_readings_obj->solar_arrow_class           = $solar_arrow_class;
 $studer_readings_obj->solar_arrow_animation_class = $solar_arrow_animation_class;

 //update the object with Inverter Load details
 $studer_readings_obj->pout_inverter_ac_kw         = $pout_inverter_ac_kw;
 $studer_readings_obj->inverter_pout_arrow_class   = $inverter_pout_arrow_class;

 // update the Grid input values
 $studer_readings_obj->transfer_relay_state        = $transfer_relay_state;
 $studer_readings_obj->grid_pin_ac_kw              = $grid_pin_ac_kw;
 $studer_readings_obj->grid_input_vac              = $grid_input_vac;
 $studer_readings_obj->grid_input_arrow_class      = $grid_input_arrow_class;
 $studer_readings_obj->aux1_relay_state            = $aux1_relay_state;


 // update the object with the fontawesome cdn from Studer API object
 $studer_readings_obj->fontawesome_cdn             = $studer_api->fontawesome_cdn;

 return $studer_readings_obj;
}

/**
**  @param string $paramId is the user meta field label
**  @param string $value is the value of the user meta in the update
*/
function update_param_meta($paramId, $value)
{
 // get logged in user details
 $current_user 	= wp_get_current_user();
 $user_id 		    = $current_user->ID;

 update_user_meta( $user_id, $paramId, $value );

 return;
}
