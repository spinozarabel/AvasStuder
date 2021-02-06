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
     add_menu_page( 'Studer',    'Studer',    'manage_options', 'studer',   'studer_menu' );

  return;
}

function studer_menu()
{
  $studer_api = new studer_api();

  // top line displayed on page
  echo nl2br('My Studer Parameters for my installation ID: ' . "<b>" . $studer_api->installation_id . "</b>" . ' of User: ' . "<b>" . $studer_api->name . "</b>\n");

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
  $param_desc                       = "Battery undervoltage level without load";
  $param_units                      = "Vdc";
  $factory_default                  = 46.3;
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
