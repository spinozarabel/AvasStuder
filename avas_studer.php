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
*   register and enque jquery scripts with nonce for ajax calls. Load only for desired page
*   called by add_action( 'wp_enqueue_scripts', 'add_my_scripts' );
*/
function avas_display_studer_settings()
{
  if (!is_user_logged_in())
	{
		return  'You need to be a registered user to access this page. Please register or login';

	}
  //
	return studer_main_page_render();

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

function studer_main_page_render()
{
  $studer_api = new studer_api();

  // top line displayed on page
  $output .= 'Studer Parameters for my installation ID: ' . "<b>" . $studer_api->installation_id . "</b>" . ' of User: ' . "<b>" . $studer_api->name . "</b>";

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
  $studer_api->paramId              = '1108';
  $studer_api->device               = 'XT1';
  $studer_api->paramPart            = 'Value';
  $battery_uv_1108                  = round($studer_api->get_parameter_value(), 2);
  $param_desc                       = "Battery Under Voltage Without Load (For LVD)";
  // update the user meta for this parameter
  update_param_meta($studer_api->paramId, $battery_uv_1108);

  // 1190 Battery undervoltage duration before turn off
  $studer_api->paramId              = '1190';
  $studer_api->device               = 'XT1';
  $studer_api->paramPart            = 'Value';
  $battery_uv_duration_1190         = $studer_api->get_parameter_value();
  // update the user meta for this parameter
  update_param_meta($studer_api->paramId, $battery_uv_duration_1190);
  $description                      = "Battery undervoltage @ duration, before turn off: Related to LVD";

  $output .= print_row_table('1108 @ 1190', $battery_uv_1108 . ' Vdc @' . $battery_uv_duration_1190 . ' mins', $description, 'Vdc @ 1min', '46.5 @ 1');

  // 1191 1532 battery under voltage dynamic compensation and if so type
  $studer_api->paramId              = '1191';
  $studer_api->device               = 'XT1';
  $studer_api->paramPart            = 'Value';
  $battery_uv_compensation          = $studer_api->get_parameter_value();
  // update the user meta for this parameter 1191
  update_param_meta($studer_api->paramId, $battery_uv_compensation);
  // display neatening
  if ($battery_uv_compensation == 1.0)
  {
    $battery_uv_compensation = "Yes";
  }

  $studer_api->paramId              = '1532';
  $studer_api->device               = 'XT1';
  $studer_api->paramPart            = 'Level';
  $battery_uv_compensation_type     = $studer_api->get_parameter_value();
  // update the user meta for this parameter 1532
  update_param_meta($studer_api->paramId, $battery_uv_compensation_type);
  $description                      = "Battery undervoltage Compensation enabled? if so type";
  $output .= print_row_table('1191 @ 1532', $battery_uv_compensation . ', ' . $battery_uv_compensation_type, $description, '', 'Yes, Automatic');

  // 1110 Restart voltage after batteries undervoltage
  $studer_api->paramId              = '1110';
  $studer_api->device               = 'XT1';
  $studer_api->paramPart            = 'Value';
  $battery_uv_1110                  = round($studer_api->get_parameter_value(), 2);
  $param_desc                       = "Restart voltage after batteries undervoltage";
  // update the user meta for this parameter 1110
  update_param_meta($studer_api->paramId, $battery_uv_1110);
  $output .= print_row_table('1110', $battery_uv_1110, 'Restart voltage level (after batteries undervoltage disconnect)', 'Vdc', '47.9');

  // 1126 SMart-Boost Allowed?
  $studer_api->paramId              = '1126';
  $studer_api->device               = 'XT1';
  $studer_api->paramPart            = 'Value';
  $inverter_smartboost              = $studer_api->get_parameter_value();
  // update the user meta for this parameter 1126
  update_param_meta($studer_api->paramId, $inverter_smartboost);
  if ($inverter_smartboost == 1.0)
  {
    $inverter_smartboost = "Yes";
  }
  $output .= print_row_table('1126', $inverter_smartboost, 'Inverter Smart-Boost Allowed?', 'Yes/No', 'Yes');

  // 1124 Inverter Allowed?
  $studer_api->paramId              = '1124';
  $studer_api->device               = 'XT1';
  $studer_api->paramPart            = 'Value';
  $inverter_allowed                 = $studer_api->get_parameter_value();
  // update the user meta for this parameter 1124
  update_param_meta($studer_api->paramId, $inverter_allowed);
  if ($inverter_allowed == 1.0)
  {
    $inverter_allowed = "Yes";
  }
  $output .= print_row_table('1124', $inverter_allowed, 'Inverter Allowed?', 'Yes/No', 'Yes');

  // 1125 Charger Allowed?
  $studer_api->paramId              = '1125';
  $studer_api->device               = 'XT1';
  $studer_api->paramPart            = 'Value';
  $charger_allowed                  = $studer_api->get_parameter_value();
  // update the user meta for this parameter 1125
  update_param_meta($studer_api->paramId, $charger_allowed);
  if ($charger_allowed == 1.0)
  {
    $charger_allowed = "Yes";
  }
  $output .= print_row_table('1125', $charger_allowed, 'Charger Allowed?', 'Yes/No', 'Yes');

  // 1128 Transfer relay Allowed?
  $studer_api->paramId              = '1128';
  $studer_api->device               = 'XT1';
  $studer_api->paramPart            = 'Value';
  $transfer_relay_allowed           = $studer_api->get_parameter_value();
  // update the user meta for this parameter 1128
  update_param_meta($studer_api->paramId, $transfer_relay_allowed);
  if ($transfer_relay_allowed == 1.0)
  {
    $transfer_relay_allowed = "Yes";
  }
  $output .= print_row_table('1126', $transfer_relay_allowed, 'Transfer Relay Allowed?', 'Yes/No', 'Yes');

  // 1140 - 1138 Battery Float Voltage and Charge CUrrent
  $studer_api->paramId              = '1140';
  $studer_api->device               = 'XT1';
  $studer_api->paramPart            = 'Value';
  $battery_float_voltage            = round($studer_api->get_parameter_value(), 2);
  // update the user meta for this parameter 1140
  update_param_meta($studer_api->paramId, $battery_float_voltage);

  $studer_api->paramId              = '1138';
  $studer_api->device               = 'XT1';
  $studer_api->paramPart            = 'Value';
  $Inverter_battery_charge_current  = round($studer_api->get_parameter_value(), 2);
  // update the user meta for this parameter 1138
  update_param_meta($studer_api->paramId, $Inverter_battery_charge_current);

  $param_desc                       = "Battery float voltage and Inverter Battery charging current";
  $output .= print_row_table('1140-1138', $battery_float_voltage . ' Vdc, ' . $Inverter_battery_charge_current . ' Adc', $param_desc, 'Vdc, Adc', '53 Vdc, 60 Adc');

  // 1202 Auxillary contact Operating Mode
  $studer_api->paramId              = '1202';
  $studer_api->device               = 'XT1';
  $studer_api->paramPart            = 'Value';
  $aux1_operating_mode              = $studer_api->get_parameter_value();
  // update the user meta for this parameter 1202
  update_param_meta($studer_api->paramId, $aux1_operating_mode);
  if ($aux1_operating_mode <= 1.0E-10)
  {
    $aux1_operating_mode = "Reversed Automatic";
  }
  $output .= print_row_table('1202', $aux1_operating_mode, 'Auxillary contact Operating Mode', 'Automatic/Reverse', 'Reverse Automatic');

  // 1246 AUX1 activate on Battery VOltage
  $studer_api->paramId              = '1246';
  $studer_api->device               = 'XT1';
  $studer_api->paramPart            = 'Value';
  $aux1_activate_battery_voltage    = $studer_api->get_parameter_value();
  // update the user meta for this parameter 1246
  update_param_meta($studer_api->paramId, $aux1_activate_battery_voltage);
  if ($aux1_operating_mode == 1.0)
  {
    $aux1_activate_battery_voltage = "Yes";
  }

  // 1288 AUX1 activate on Battery VOltage: Battery voltage dynamic compensation?
  $studer_api->paramId              = '1288';
  $studer_api->device               = 'XT1';
  $studer_api->paramPart            = 'Value';
  $aux1_activate_battery_voltagecomp    = $studer_api->get_parameter_value();
  // update the user meta for this parameter 1288
  update_param_meta($studer_api->paramId, $aux1_activate_battery_voltagecomp);
  if ($aux1_activate_battery_voltagecomp == 1.0)
  {
    $aux1_activate_battery_voltagecomp = "Yes";
  }
  $val = $aux1_activate_battery_voltage . " " . $aux1_activate_battery_voltagecomp;
  $output .= print_row_table('1246-1288', $val, 'Auxillary contact activated on battery voltage - Battery dynamic compensation?', 'Yes/No', 'Yes');

  // 1247-1254 Aux1 activate battery level conditions and associated times
  $studer_api->paramId              = '1247'; //battery voltage 1
  $studer_api->device               = 'XT1';
  $studer_api->paramPart            = 'Value';
  $aux1_battery_voltage_1           = round($studer_api->get_parameter_value(), 2);
  update_param_meta($studer_api->paramId, $aux1_battery_voltage_1);

  $studer_api->paramId              = '1248'; //Time for BV 1
  $studer_api->device               = 'XT1';
  $studer_api->paramPart            = 'Value';
  $aux1_battery_voltage_1_time      = round($studer_api->get_parameter_value(), 2);
  update_param_meta($studer_api->paramId, $aux1_battery_voltage_1_time);

  $studer_api->paramId              = '1250'; //battery voltage 2
  $studer_api->device               = 'XT1';
  $studer_api->paramPart            = 'Value';
  $aux1_battery_voltage_2           = round($studer_api->get_parameter_value(), 2);
  update_param_meta($studer_api->paramId, $aux1_battery_voltage_2);

  $studer_api->paramId              = '1251'; //Time for BV 2
  $studer_api->device               = 'XT1';
  $studer_api->paramPart            = 'Value';
  $aux1_battery_voltage_2_time      = round($studer_api->get_parameter_value(), 2);
  update_param_meta($studer_api->paramId, $aux1_battery_voltage_2_time);

  $studer_api->paramId              = 1253; //battery voltage 3
  $studer_api->device               = 'XT1';
  $studer_api->paramPart            = 'Value';
  $aux1_battery_voltage_3           = round($studer_api->get_parameter_value(), 2);

  $studer_api->paramId              = 1254; //Time for BV 3
  $studer_api->device               = 'XT1';
  $studer_api->paramPart            = 'Value';
  $aux1_battery_voltage_3_time      = round($studer_api->get_parameter_value(), 2);

  $val = $aux1_battery_voltage_1 . '@' . $aux1_battery_voltage_1_time . ', ' . $aux1_battery_voltage_2 . '@' . $aux1_battery_voltage_2_time;
  $val .= ', ' . $aux1_battery_voltage_3 . '@' . $aux1_battery_voltage_3_time;

  $param_desc                       = "AUX1 activate on battery voltages and times";

  $output .= print_row_table('1247-1254', $val, $param_desc, 'Vdc @ mins', 'Make sure these voltages are higher than for LVD');

  // 1545 Remote Entry Active (Open or CLosed?)
  $studer_api->paramId              = 1545;
  $studer_api->device               = 'XT1';
  $studer_api->paramPart            = 'Value';
  $remote_entry_active              = $studer_api->get_parameter_value();
  if ($remote_entry_active == 1.0)
  {
    $remote_entry_active = "Closed";
  }
  $output .= print_row_table('1545', $remote_entry_active, 'Remote Entry Active (Open or CLosed??', 'Open/CLosed', 'Closed');


  // 1538 Prohibits Transfer Relay?
  $studer_api->paramId              = 1538;
  $studer_api->device               = 'XT1';
  $studer_api->paramPart            = 'Value';
  $prohibits_transfer_relay         = $studer_api->get_parameter_value();
  if ($prohibits_transfer_relay == 1.0)
  {
    $prohibits_transfer_relay = "Yes";
  }
  $output .= print_row_table('1538', $prohibits_transfer_relay, 'Prohibits Transfer Relay?', 'Yes/No', 'Yes');

  // 1578 Activated by AUX1 state?
  $studer_api->paramId              = 1578;
  $studer_api->device               = 'XT1';
  $studer_api->paramPart            = 'Value';
  $activated_by_aux1                = $studer_api->get_parameter_value();
  if ($activated_by_aux1 == 1.0)
  {
    $activated_by_aux1 = "Yes";
  }

  $output .= print_row_table('1578', $activated_by_aux1, 'Activated by AUX1 state?', 'Yes/No', 'Yes');

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

  // begin VarioTrack readings
  // Synchronized to Xtender?
  $studer_api->paramId              = 10037;
  $studer_api->device               = 'VT_Group';
  $studer_api->paramPart            = 'Value';
  $param_value                      = $studer_api->get_parameter_value();
  $param_desc                       = "VaroTrac Synchronisation of battery cycle with Xtender";
  $param_units                      = "1=Yes, 0=No";
  $factory_default                  = '1=Yes';
  $output .= print_row_table($studer_api->paramId, $param_value, $param_desc, $param_units, $factory_default);

  // Battery Float Voltage
  $studer_api->paramId              = 10005;
  $studer_api->device               = 'VT_Group';
  $studer_api->paramPart            = 'Value';
  $param_value                      = $studer_api->get_parameter_value();
  $param_desc                       = "VaroTracc Battery Float Voltage";
  $param_units                      = "Vdc";
  $factory_default                  = 54.4;
  $output .= print_row_table($studer_api->paramId, $param_value, $param_desc, $param_units, $factory_default);

  // Battery underVoltage
  $studer_api->paramId              = 10334;
  $studer_api->device               = 'VT_Group';
  $studer_api->paramPart            = 'Value';
  $param_value                      = $studer_api->get_parameter_value();
  $param_desc                       = "VarioTrack Battery Under Voltage";
  $param_units                      = "Vdc";
  $factory_default                  = 40;
  $output .= print_row_table($studer_api->paramId, $param_value, $param_desc, $param_units, $factory_default);

  // Battery Charge Current
  $studer_api->paramId              = 10002;
  $studer_api->device               = 'VT_Group';
  $studer_api->paramPart            = 'Value';
  $param_value                      = $studer_api->get_parameter_value();
  $param_desc                       = "VarioTrack Battery Charge Current";
  $param_units                      = "Adc";
  $factory_default                  = 80;
  $output .= print_row_table($studer_api->paramId, $param_value, $param_desc, $param_units, $factory_default);

  // close the table tag after final entry above
  $output .= '</table>';

  return $output;
}

function studer_readings_page_render()
{
  if (!is_user_logged_in())
	{
		return  'You need to be a registered user to access this page. Please register or login';

	}

  $data = get_studer_readings();

  $script = '"' . $data->fontawesome_cdn . '"';

  $output = '<script src="' . $data->fontawesome_cdn . '"></script>';

  $output .=

    '<style>
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
            max-width: 65px;
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
                max-width: 80px;
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
                                  title="Refresh"

                                  >
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
                            <td style="text-align: right;">
                                <i class="' . $data->grid_input_arrow_class . '" id="power-arrow-grid-genset"></i>
                            </td>
                        </tr>
                        <tr>

                            <td style="text-align: left;">
                                <i class="' . $data->solar_arrow_class . '" id="power-arrow-solar"></i>
                            </td>
                        </tr>
                      </table>
                    </td>
                    <td
                        class="legend" id="power-solar" style="text-align: left;">' .
                        $data->psolar_kw . ' kW<br>
                        <font color="#D0D0D0">' .
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
                        <font color="#D0D0D0">' .
                        $data->battery_voltage_vdc . ' V<br>' .
                        abs($data->battery_charge_adc) . ' A
                    </td>
                    <td>
                            <table>
                                <tr>
                                    <td style="text-align: left;">
                                        <i class="' . $data->battery_charge_arrow_class . '" id="power-arrow-battery"></i>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="text-align: right;">
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
  $inverter_pout_arrow_class = "fa fa-long-arrow-right fa-rotate-45";

  // conditional class names for battery charge down or up arrow
  if ($battery_charge_adc > 0.0)
  {
    // current is positive so battery is charging so arrow is down and to left
    $battery_charge_arrow_class = "fa fa-long-arrow-down fa-rotate-45";

    // also good time to compensate for IR drop
    $battery_voltage_vdc = round($battery_voltage_vdc + abs($inverter_current_adc) * $Ra - abs(battery_charge_adc) * $Rb, 2);
  }
  else
  {
    // current is -ve so battery is discharging so arrow is up
    $battery_charge_arrow_class = "fa fa-long-arrow-up fa-rotate-45";

    // also good time to compensate for IR drop
    $battery_voltage_vdc = round($battery_voltage_vdc + abs($inverter_current_adc) * $Ra + abs(battery_charge_adc) * $Rb, 2);
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
    $solar_arrow_class = "fa fa-long-arrow-down fa-rotate-45";
  }
  else
  {
    // power is too small indicate a blank line vertically down from SOlar panel to Inverter in diagram
    $solar_arrow_class = "fa fa-minus fa-rotate-90";
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

// select battery icon based on charge level
  switch(true)
  {
    case ($battery_voltage_vdc < 48.0 ):
      $battery_icon_class = "fa fa-3x fa-battery-quarter fa-rotate-270";
    break;

    case ($battery_voltage_vdc >= 48 && $battery_voltage_vdc < 49.0 ):
      $battery_icon_class = "fa fa-3x fa-battery-half fa-rotate-270";
    break;

    case ($battery_voltage_vdc >= 49.0 && $battery_voltage_vdc < 51.0 ):
      $battery_icon_class = "fa fa-3x fa-battery-three-quarters fa-rotate-270";
    break;

    case ($battery_voltage_vdc >= 50.0 ):
      $battery_icon_class = "fa fa-3x fa-battery-full fa-rotate-270";
    break;
  }

  // update the object with battery data read
  $studer_readings_obj->battery_charge_adc          = abs($battery_charge_adc);
  $studer_readings_obj->pbattery_kw                 = abs($pbattery_kw);
  $studer_readings_obj->battery_voltage_vdc         = $battery_voltage_vdc;
  $studer_readings_obj->battery_charge_arrow_class  = $battery_charge_arrow_class;
  $studer_readings_obj->battery_icon_class          = $battery_icon_class;

  // update the object with SOlar data read
  $studer_readings_obj->psolar_kw                   = $psolar_kw;
  $studer_readings_obj->solar_pv_adc                = $solar_pv_adc;
  $studer_readings_obj->solar_pv_vdc                = $solar_pv_vdc;
  $studer_readings_obj->solar_arrow_class           = $solar_arrow_class;

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

function update_param_meta($paramId, $value)
{
  // get logged in user details
  $current_user 	= wp_get_current_user();
  $user_id 		    = $current_user->ID;

  update_user_meta( $user_id, $paramId, $value );

  return;
}
