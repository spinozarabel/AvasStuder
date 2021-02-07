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
  // add_menu_page( $page_title, $menu_title, $capability,      $menu_slug, $function,      $icon_url, $position )
     add_menu_page( 'Studer',    'Studer',    'manage_options', 'studer',   'studer_main' );

     /*
   add_submenu_page( string $parent_slug, string $page_title, string $menu_title, string $capability, string $menu_slug, callable $function = '' )
   *					        parent slug		 newsubmenupage	 submenu title  	  capability         new submenu slug      callback for display page
   */
   add_submenu_page( 'studer',      'VarioTrac',      'VarioTrac',     'manage_options',   'studer-variotrac',    'studer_variotrac_callback' );

  return;
}

function studer_main()
{
  $studer_api = new studer_api();

  // top line displayed on page
  echo nl2br('Studer Main Parameters for my installation ID: ' . "<b>" . $studer_api->installation_id . "</b>" . ' of User: ' . "<b>" . $studer_api->name . "</b>\n");

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
      <th>Installer Val</th>
    </tr>
  <?php

  // get the AC voltage level
  $studer_api->paramId              = 1286;
  $studer_api->device               = 'XT1';
  $studer_api->paramPart            = 'Value';
  $param_value                      = $studer_api->get_parameter_value();
  $param_desc                       = "AC output Voltage";
  $param_units                      = "Vac";
  $factory_default                  = 230;
  print_row_table($studer_api->paramId, $param_value, $param_desc, $param_units, $factory_default);

  // get Maximum allowed input AC voltage level
  $studer_api->paramId              = 1432;
  $studer_api->device               = 'XT1';
  $studer_api->paramPart            = 'Value';
  $param_value                      = $studer_api->get_parameter_value();
  $param_desc                       = "Maximum allowed input AC Voltage";
  $param_units                      = "Vac";
  $factory_default                  = 270;
  print_row_table($studer_api->paramId, $param_value, $param_desc, $param_units, $factory_default);

  // Input voltage giving an opening of the transfer relay with delay
  $studer_api->paramId              = 1199;
  $studer_api->device               = 'XT1';
  $studer_api->paramPart            = 'Value';
  $param_value                      = $studer_api->get_parameter_value();
  $param_desc                       = "Input voltage giving an opening of the transfer relay with delay";
  $param_units                      = "Vac";
  $factory_default                  = 200;
  print_row_table($studer_api->paramId, $param_value, $param_desc, $param_units, $factory_default);

  // Input voltage giving an opening of the transfer relay with delay
  $studer_api->paramId              = 1200;
  $studer_api->device               = 'XT1';
  $studer_api->paramPart            = 'Value';
  $param_value                      = $studer_api->get_parameter_value();
  $param_desc                       = "Input voltage giving an immediate opening of the transfer relay (UPS)";
  $param_units                      = "Vac";
  $factory_default                  = 180;
  print_row_table($studer_api->paramId, $param_value, $param_desc, $param_units, $factory_default);

  $studer_api->paramId              = 1107;
  $studer_api->device               = 'XT1';
  $studer_api->paramPart            = 'Value';
  $param_value                      = $studer_api->get_parameter_value();
  $param_desc                       = "Maximum current of AC source (Input limit)";
  $param_units                      = "Aac";
  $factory_default                  = 32;
  print_row_table($studer_api->paramId, $param_value, $param_desc, $param_units, $factory_default);

  $studer_api->paramId              = 1138;
  $studer_api->device               = 'XT1';
  $studer_api->paramPart            = 'Value';
  $param_value                      = $studer_api->get_parameter_value();
  $param_desc                       = "Battery Charge Current";
  $param_units                      = "Adc";
  $factory_default                  = 60;
  print_row_table($studer_api->paramId, $param_value, $param_desc, $param_units, $factory_default);

  $studer_api->paramId              = 1126;
  $studer_api->device               = 'XT1';
  $studer_api->paramPart            = 'Value';
  $param_value                      = $studer_api->get_parameter_value();
  $param_desc                       = "Smart Boost Allowed?";
  $param_units                      = "1/0";
  $factory_default                  = "Yes";
  print_row_table($studer_api->paramId, $param_value, $param_desc, $param_units, $factory_default);

  $studer_api->paramId              = 1124;
  $studer_api->device               = 'XT1';
  $studer_api->paramPart            = 'Value';
  $param_value                      = $studer_api->get_parameter_value();
  $param_desc                       = "Inverter Allowed?";
  $param_units                      = "1=Yes, 0=No";
  $factory_default                  = "Yes";
  print_row_table($studer_api->paramId, $param_value, $param_desc, $param_units, $factory_default);

  $studer_api->paramId              = 1125;
  $studer_api->device               = 'XT1';
  $studer_api->paramPart            = 'Value';
  $param_value                      = $studer_api->get_parameter_value();
  $param_desc                       = "Charger Allowed?";
  $param_units                      = "1=Yes, 0=No";
  $factory_default                  = "Yes";
  print_row_table($studer_api->paramId, $param_value, $param_desc, $param_units, $factory_default);

  $studer_api->paramId              = 1128;
  $studer_api->device               = 'XT1';
  $studer_api->paramPart            = 'Value';
  $param_value                      = $studer_api->get_parameter_value();
  $param_desc                       = "Transfer Relay Allowed?";
  $param_units                      = "1=Yes, 0=No";
  $factory_default                  = "Yes";
  print_row_table($studer_api->paramId, $param_value, $param_desc, $param_units, $factory_default);

  $studer_api->paramId              = 1187;
  $studer_api->device               = 'XT1';
  $studer_api->paramPart            = 'Value';
  $param_value                      = $studer_api->get_parameter_value();
  $param_desc                       = "Standby Level";
  $param_units                      = "%";
  $factory_default                  = 10;
  print_row_table($studer_api->paramId, $param_value, $param_desc, $param_units, $factory_default);

  $studer_api->paramId              = 1139;
  $studer_api->device               = 'XT1';
  $studer_api->paramPart            = 'Value';
  $param_value                      = $studer_api->get_parameter_value();
  $param_desc                       = "Temperature compensation";
  $param_units                      = "mV/degC/cell";
  $factory_default                  = -3;
  print_row_table($studer_api->paramId, $param_value, $param_desc, $param_units, $factory_default);

  $studer_api->paramId              = 1108;
  $studer_api->device               = 'XT1';
  $studer_api->paramPart            = 'Value';
  $param_value                      = $studer_api->get_parameter_value();
  $param_desc                       = "Battery undervoltage level without load - Coslight recommends 47.8 for 80% DOD. Boiler plate says 42V!";
  $param_units                      = "Vdc";
  $factory_default                  = 45.8;
  print_row_table($studer_api->paramId, $param_value, $param_desc, $param_units, $factory_default);

  $studer_api->paramId              = 1190;
  $studer_api->device               = 'XT1';
  $studer_api->paramPart            = 'Value';
  $param_value                      = $studer_api->get_parameter_value();
  $param_desc                       = "Battery undervoltage Duration before cut-off";
  $param_units                      = "mins";
  $factory_default                  = 3;
  print_row_table($studer_api->paramId, $param_value, $param_desc, $param_units, $factory_default);

  $studer_api->paramId              = 1110;
  $studer_api->device               = 'XT1';
  $studer_api->paramPart            = 'Value';
  $param_value                      = $studer_api->get_parameter_value();
  $param_desc                       = "Restart voltage after batteries undervoltage";
  $param_units                      = "Vdc";
  $factory_default                  = 48;
  print_row_table($studer_api->paramId, $param_value, $param_desc, $param_units, $factory_default);

  $studer_api->paramId              = 1121;
  $studer_api->device               = 'XT1';
  $studer_api->paramPart            = 'Value';
  $param_value                      = $studer_api->get_parameter_value();
  $param_desc                       = "Battery overvoltage level";
  $param_units                      = "Vdc";
  $factory_default                  = 68.2;
  print_row_table($studer_api->paramId, $param_value, $param_desc, $param_units, $factory_default);

  $studer_api->paramId              = 1140;
  $studer_api->device               = 'XT1';
  $studer_api->paramPart            = 'Value';
  $param_value                      = $studer_api->get_parameter_value();
  $param_desc                       = "Battery Floating Voltage - Coslight recommends 54V";
  $param_units                      = "Vdc";
  $factory_default                  = 53.3;
  print_row_table($studer_api->paramId, $param_value, $param_desc, $param_units, $factory_default);

}

function studer_variotrac_callback()
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

  // Battery Float Voltage
  $studer_api->paramId              = 10005;
  $studer_api->device               = 'VT_Group';
  $studer_api->paramPart            = 'Value';
  $param_value                      = $studer_api->get_parameter_value();
  $param_desc                       = "Battery Float Voltage";
  $param_units                      = "Vdc";
  $factory_default                  = 54;
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
