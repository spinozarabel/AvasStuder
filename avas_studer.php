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

  // add a new submenu for sritoni cashfree plugin settings in Woocommerce. This is to be done only once!!!!
  $avas_studer_settings = new avas_studer_settings();
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

  // top line displayed on page
  echo nl2br('Studer System Readings of my installation ID: ' . "<b>" . $studer_api->installation_id . "</b>" . ' of User: ' . "<b>" . $studer_api->name . "</b>\n");

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
      <th>Comments</th>
    </tr>
  <?php

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
                );
  $studer_api->body   = $body;

  // POST curl request to Studer
  $curlResponse_json  = $studer_api->get_user_values();

  // decode JSON response to an object
  $user_values        = json_decode($curlResponse_json, false);

  foreach ($user_values as $user_value)
  {
    switch (true)
  	{
      case ( $user_value->reference == 3000 ) :
        $battery_voltage_vdc = $user_value->value;
        print_row_table(3136, $battery_voltage_vdc, 'Battery Voltage', 'Vdc', '');
      break;

  		case ( $user_value->reference == 3137 ) :
        $grid_pin_ac_kw = $user_value->value;
        print_row_table(3137, $grid_pin_ac_kw, 'Grid Acitive power input', 'kW', '');
      break;

      case ( $user_value->reference == 3136 ) :
        $pout_inverter_ac_kw = $user_value->value;
        print_row_table(3136, $pout_inverter_ac_kw, 'AC power delivered by inverter', 'kW', '');
      break;
    }
  }




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
