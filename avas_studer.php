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

  // add a new submenu for sritoni cashfree plugin settings in Woocommerce. This is to be done only once!!!!
  $avas_studer_settings = new avas_studer_settings();
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

function studer_readings_page_render()
{

  $studer_api = new studer_api();

  // top line on page
  // echo nl2br('Studer System Readings of my installation ID: ' . "<b>" . $studer_api->installation_id . "</b>" . ' of User: ' . "<b>" . $studer_api->name . "</b>\n");

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
        $inverter_current_adc = round($user_value->value, 0);
      break;

  		case ( $user_value->reference == 3137 ) :
        $grid_pin_ac_kw = round($user_value->value, 2);

      break;

      case ( $user_value->reference == 3136 ) :
        $pout_inverter_ac_kw = round($user_value->value, 2);

      break;

      case ( $user_value->reference == 11001 ) :
        // we have to accumulate values form 2 cases:VT1 and VT2 so we have used accumulation below
        $solar_pv_adc += round($user_value->value, 0);

      break;

      case ( $user_value->reference == 11002 ) :
        $solar_pv_vdc = $user_value->value;

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

  // calculate the current into/out of battery
  $battery_charge_adc  = $solar_pv_adc + $inverter_current_adc; // + is charge, - is discharge
  $pbattery_kw         = $psolar_kw - $pout_inverter_ac_kw;

  $inverter_pout_arrow_class = "fa fa-long-arrow-right";

  // conditional class names for battery charge down or up arrow
  if ($battery_charge_adc > 0.0)
  {
    // current is positive so battery is charging so arrow is down
    $battery_charge_arrow_class = "fa fa-long-arrow-down";
  }
  else
  {
    // current is -ve so battery is discharging so arrow is up
    $battery_charge_arrow_class = "fa fa-long-arrow-up";
  }

  switch(true)
  {
    case (abs($battery_charge_adc) < 10 ) :
      $battery_charge_arrow_class .= " fa-1x";
    break;

    case (abs($battery_charge_adc) < 20 ) :
      $battery_charge_arrow_class .= " fa-2x";
    break;

    case (abs($battery_charge_adc) < 40 ) :
      $battery_charge_arrow_class .= " fa-3x";
    break;

    case (abs($battery_charge_adc) < 60 ) :
      $battery_charge_arrow_class .= " fa-4x";
    break;

    case (abs($battery_charge_adc) < 80 ) :
      $battery_charge_arrow_class .= " fa-5x";
    break;
  }

  // conditional for solar pv arrow
  if ($psolar_kw > 0.2)
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

  ?>
    <!-- HTML begins again. Reference my fontawesome CDN sent to my email -->
    <script src="https://use.fontawesome.com/7982b10e46.js"></script>

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
            border: 5px solid #555;
        }

        .img-pow-genset {
            max-width: 59px;
            border: 5px solid #555;
        }

        .img-pow-logo {
            max-width: 65px;
        }

        .img-pow-load {
            max-width: 59px;
            border: 5px solid #555;
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
                                <i class="<?php echo htmlspecialchars($solar_arrow_class); ?>" id="power-arrow-solar"></i>
                            </td>
                            <td
                                class="legend" id="power-solar">
                                <?php echo htmlspecialchars($psolar_kw); ?> kW<br>
                                <font color="#D0D0D0">
                                <?php echo htmlspecialchars($solar_pv_adc); ?> Adc
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
                              <?php echo htmlspecialchars($grid_pin_ac_kw); ?> kW<br>
                              <font color="#D0D0D0">
                              <?php echo htmlspecialchars($grid_input_vac); ?> Vac<br>
                              <?php echo htmlspecialchars($grid_input_aac); ?> Aac
                            </td>
                        </tr>
                        <tr>
                            <td height="33">
                                <i class="fa fa-3x fa-minus" id="power-arrow-grid-genset"></i>
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
                              <?php echo htmlspecialchars($pout_inverter_ac_kw); ?> kW
                            </td>
                        </tr>
                        <tr>
                            <td height="33">
                                <i class="<?php echo htmlspecialchars($inverter_pout_arrow_class); ?>" id="power-arrow-load"></i>
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
                                <i class="<?php echo htmlspecialchars($battery_charge_arrow_class); ?>" id="power-arrow-battery"></i>
                            </td>
                            <td
                                class="legend" id="power-battery">
                                <?php echo htmlspecialchars($pbattery_kw); ?> kW<br>
                                <font color="#D0D0D0">
                                <?php echo htmlspecialchars($battery_voltage_vdc); ?> Vdc<br>
                                <?php echo htmlspecialchars($battery_charge_adc); ?> Adc
                            </td>
                        </tr>
                    </table>
                </td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td colspan="5" style="text-align: center">
					             <i class="fa fa-3x fa-battery-full fa-rotate-270" id="power_battery-icon"></i>
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
