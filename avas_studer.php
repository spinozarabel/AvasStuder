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

  // add action to load the javascripts
  add_action( 'admin_enqueue_scripts', 'add_my_scripts' );

  // do the following only once, that too if you are an admin!
  $avas_studer_settings = new avas_studer_settings();
}


// add action to load the javascripts
//add_action( 'wp_enqueue_scripts',    'add_my_scripts' );

// add action for the ajax handler on server side.
// Once city is selected by JS the selected city is sent to handler
// the 1st argument is in update.js, action: "get_studer_readings"
// the 2nd argument is the local callback function as the ajax handler
add_action('wp_ajax_get_studer_readings', 'ajax_studer_readings_handler');

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
  // fill this in later
  return;
}

function studer_readings_page_render()
{

  $data = get_studer_readings();

  $script = '"' . $data->fontawesome_cdn . '"';

  ?>

    <!-- HTML begins again. Reference my fontawesome CDN sent to my email -->
    <script src=<?php echo $script; ?>></script>

    <style>
        /* xs (moins de 768px) */
        .lSAction>a {
            top: 100%;
            background-image: url('');
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
            width: 80% !important;
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
                width: 80% !important;
                height: 100%;
                border-collapse: collapse;
                overflow-x: auto;
                border-spacing: 0;
                font-size: 14px;
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
                max-width: 100px;
            }

            .img-pow-load {
                max-width: 59px;
            }
        }

    </style>
    <div class="col-xs-12 col-md-6">
    <div class="row">
        <div class="box box-primary">
            <div class="box-body">
                <ul id="lightSlider">
                    <li>
                        <div class="row-fluid quickoverview-title">
        Quick overview - Power flows
        <div class="box-tools pull-right">
    <button type="button" class="btn btn-box-tool refresh-button"  data-placement="right"
            data-toggle="tooltip"
            data-container="body"
            title="Refresh"
            onclick="refreshAll();"
            >
        <i class="fa fa-1x fa-spinner fa-spin" id="refresh-button" style="height: 15px; width: 15px;"></i>
    </button>
    <button type="button" class="btn btn-box-tool" data-placement="right"
            data-toggle="tooltip"
            data-container="body"
            title="Connected"
            onclick="refreshAll();"
            >
        <span class="fa studer-action-toolbox fa-circle text-green"></span>
    </button>
</div>
</div>

    <div class="row-fluid">
    <div class="table-responsive synoptic-fixed-height">
        <table class="synoptic-table">
            <tr>
                <td colspan="5" style="text-align: center">
                    <img id="pow-pv-img" src="https://sritoni.org/6076/wp-content/uploads/sites/14/2021/02/simple_pv.svg" class="img-pow-pv"/>
                </td>
            </tr>
            <tr>
                <td></td>
                <td></td>
                <td>
                    <table class="arrow-table-horizontal">
                        <tr>
                            <td></td>
                            <td>
                                <i class="<?php echo htmlspecialchars($data->solar_arrow_class); ?>" id="power-arrow-solar"></i>
                            </td>
                            <td
                                class="legend" id="power-solar">
                                <?php echo htmlspecialchars($data->psolar_kw); ?> kW<br>
                                <font color="#D0D0D0">
                                <?php echo htmlspecialchars($data->solar_pv_adc); ?> Adc
                            </td>
                        </tr>
                    </table>
                </td>
                <td></td>
                <td></td>
            </tr>
            <tr>

                <td>
                    <img id="pow-genset-img" src="https://sritoni.org/6076/wp-content/uploads/sites/14/2021/02/grid_genset.svg" class="img-pow-genset"/>
                </td>

                <td>
                    <table class="arrow-table-vertical" height="100">
                        <tr>
                            <td height="33" class="legend" id="power-grid-genset">
                              <?php echo htmlspecialchars($data->grid_pin_ac_kw); ?> kW<br>
                              <font color="#D0D0D0">
                              <?php echo htmlspecialchars($data->grid_input_vac); ?> Vac<br>
                              <?php echo htmlspecialchars($data->grid_input_aac); ?> Aac
                            </td>
                        </tr>
                        <tr>
                            <td height="33">
                                <i class="<?php echo htmlspecialchars($data->grid_input_arrow_class); ?>" id="power-arrow-grid-genset"></i>
                            </td>
                        </tr>
                        <tr>
                            <td>
                            </td>
                        </tr>
                    </table>
                </td>
                <td style="text-align: center;">
                    <img src="https://sritoni.org/6076/wp-content/uploads/sites/14/2021/02/studer_innotec_logo_blue.png" class="img-pow-logo" id="power-img-logo"/>
                </td>
                <td>
                    <table class="arrow-table-vertical" height="100">
                        <tr>
                            <td height="33" class="legend" id="power-load">
                              <?php echo htmlspecialchars($data->pout_inverter_ac_kw); ?> kW
                            </td>
                        </tr>
                        <tr>
                            <td height="33">
                                <i class="<?php echo htmlspecialchars($data->inverter_pout_arrow_class); ?>" id="power-arrow-load"></i>
                            </td>
                        </tr>
                        <tr>
                            <td>
                            </td>
                        </tr>
                    </table>
                </td>
                <td>
                    <img id="pow-load-img" src="https://sritoni.org/6076/wp-content/uploads/sites/14/2021/02/house.svg" class="img-pow-load"/>
                </td>
            </tr>
            <tr>
                <td></td>
                <td></td>
                <td>
                    <table class="arrow-table-horizontal">
                        <tr>
                            <td></td>
                            <td>
                                <i class="<?php echo htmlspecialchars($data->battery_charge_arrow_class); ?>" id="power-arrow-battery"></i>
                            </td>
                            <td
                                class="legend" id="power-battery">
                                <?php echo htmlspecialchars(abs($data->pbattery_kw)); ?> kW<br>
                                <font color="#D0D0D0">
                                <?php echo htmlspecialchars($data->battery_voltage_vdc); ?> Vdc<br>
                                <?php echo htmlspecialchars(abs($data->battery_charge_adc)); ?> Adc
                            </td>
                        </tr>
                    </table>
                </td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td colspan="5" style="text-align: center">
					             <i class="<?php echo htmlspecialchars($data->battery_icon_class); ?>" id="power_battery-icon"></i>
                </td>
            </tr>
        </table>
    </div>
    </div>

  <?php


  /*

  ?>
    <table style="width:100%">
      <tr>
        <th>Parameter ID</th>
        <th>Description</th>
        <th>Value</th>
        <th>Units</th>
        <th>Comments</th>
      </tr>
  <?php


  print_row_table(3000, $battery_voltage_vdc, 'Battery Voltage', 'Vdc', '');
  print_row_table(3005, $inverter_current_adc, 'Inverter DC current', 'Adc', '+ from Inverter, - into Inverter');
  print_row_table(11001, $solar_pv_adc, 'Solar panels DC current at battery interface', 'Adc', '');
  $string = ($battery_charge_adc > 0 ? 'Battery Charging Current' : 'Battery Discharging Current');
  print_row_table(11001, $battery_charge_adc, $string, 'Adc', '+ is charge, - is discharge');
  print_row_table(3137, $grid_pin_ac_kw, 'Grid Acitive power input', 'kW', '');
  print_row_table(3136, $pout_inverter_ac_kw, 'Inverter AC power output', 'kW', '');
  print_row_table(11004, $psolar_kw, 'Solar Power', 'kW', 'Solar PV array power generated'); //
  $string = ($pbattery_kw > 0 ? 'Battery Charging Power' : 'Battery Discharging Power');
  print_row_table('Calc',    $pbattery_kw, $string, 'kW', '+ means to battery, - means from battery');
  print_row_table(11002, $solar_pv_vdc, 'Solar PV Voltage', 'Vdc', 'Solar PV array Voltage');
  print_row_table(11038, $phase_battery_charge, 'Battery charging phase', 'Status', 'One of: Bulk, Floating, Discharge?');
  */
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

function print_row_table($paramId, $param_value, $param_desc, $param_units, $factory_default)
{
  ?>
  <tr>
    <td><?php echo htmlspecialchars($paramId);          ?></td>
    <td><?php echo htmlspecialchars($param_desc);       ?></td>
    <td><?php echo htmlspecialchars($param_value);      ?></td>
    <td><?php echo htmlspecialchars($param_units);      ?></td>
    <td><?php echo htmlspecialchars($factory_default);  ?></td>
  </tr>
  <?php
  return;
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
  $inverter_pout_arrow_class = "fa fa-long-arrow-right";

  // conditional class names for battery charge down or up arrow
  if ($battery_charge_adc > 0.0)
  {
    // current is positive so battery is charging so arrow is down
    $battery_charge_arrow_class = "fa fa-long-arrow-down";

    // also good time to compensate for IR drop
    $battery_voltage_vdc = round($battery_voltage_vdc + abs($inverter_current_adc) * Ra - abs(battery_charge_adc) * Rb, 2);
  }
  else
  {
    // current is -ve so battery is discharging so arrow is up
    $battery_charge_arrow_class = "fa fa-long-arrow-up";

    // also good time to compensate for IR drop
    $battery_voltage_vdc = round($battery_voltage_vdc + abs($inverter_current_adc) * Ra + abs(battery_charge_adc) * Rb, 2);
  }

  switch(true)
  {
    case (abs($battery_charge_adc) < 20 ) :
      $battery_charge_arrow_class .= " fa-1x";
    break;

    case (abs($battery_charge_adc) < 40 ) :
      $battery_charge_arrow_class .= " fa-2x";
    break;

    case (abs($battery_charge_adc) < 60 ) :
      $battery_charge_arrow_class .= " fa-3x";
    break;

    case (abs($battery_charge_adc) < 80 ) :
      $battery_charge_arrow_class .= " fa-4x";
    break;
  }

  // conditional for solar pv arrow
  if ($psolar_kw > 0.1)
  {
    // power is greater than 0.2kW so indicate down arrow
    $solar_arrow_class = "fa fa-long-arrow-down";
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

    case (abs($psolar_kw) < 3.5 ) :
      $solar_arrow_class .= " fa-3x";
    break;

    case (abs($psolar_kw) < 4.0 ) :
      $solar_arrow_class .= " fa-4x";
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

    case (abs($pout_inverter_ac_kw) < 3.5 ) :
      $inverter_pout_arrow_class .= " fa-3x";
    break;

    case (abs($pout_inverter_ac_kw) < 4 ) :
      $inverter_pout_arrow_class .= " fa-4x";
    break;
  }

  // conditional for Grid input arrow
  if ($grid_pin_ac_kw > 0.1)
  {
    // power is greater than 0.2kW so indicate down arrow
    $grid_input_arrow_class = "fa fa-long-arrow-right";
  }
  else
  {
    // power is too small indicate a blank line vertically down from SOlar panel to Inverter in diagram
    $grid_input_arrow_class = "fa fa-minus";
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

    case ($battery_voltage_vdc >= 48 && $battery_voltage_vdc < 49 ):
      $battery_icon_class = "fa fa-3x fa-battery-half fa-rotate-270";
    break;

    case ($battery_voltage_vdc >= 49 && $battery_voltage_vdc < 50.0 ):
      $battery_icon_class = "fa fa-3x fa-battery-three-quarters fa-rotate-270";
    break;

    case ($battery_voltage_vdc >= 51.0 ):
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
  $studer_readings_obj->grid_pin_ac_kw              = $grid_pin_ac_kw;
  $studer_readings_obj->grid_input_vac              = $grid_input_vac;
  $studer_readings_obj->grid_input_arrow_class      = $grid_input_arrow_class;

  // update the object with the fontawesome cdn from Studer API object
  $studer_readings_obj->fontawesome_cdn             = $studer_api->fontawesome_cdn;

  return $studer_readings_obj;
}
