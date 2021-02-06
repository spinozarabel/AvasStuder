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
    echo 'My Studer Parameters for my installation ID: ' . "<b>" . $this->installation_id . "</b>" . ' of User: ' . "<b>" . $this->$user_display_name . "</b>";


  $studer_api->paramId              = 1107;
  $studer_api->device               = 'XT1';
  $studer_api->paramPart            = 'Value';

  $paramValue       = $studer_api->get_parameter_value();

  esc_html_e( 'AC input current maximum value: ' . $paramValue);
}
