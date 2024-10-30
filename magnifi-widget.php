<?php
/*
Plugin Name: Magnifi Widget
Plugin URI: https://magnifi.io/
Description: A Customizable Video Plug-in for your Website or Application
Author: Magnifi
Author URI: https://magnifi.io/
Version: 0.1


*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function mgnf_add_dependencies()
{
  //Javascript Dependency for Widget
  wp_enqueue_script('mgnf-widget-script', 'https://widget.magnifi.io/magnifi/js/magnifi.js');

}


// Create Admin Page
function mgnf_add_admin_menu()
{
  add_menu_page('Magnifi Widget', 'Magnifi', 'manage_options', 'magnifi_widget_settings', 'mgnf_admin_index');
}

function mgnf_admin_index()
{
  require_once plugin_dir_path(__FILE__) . 'templates/admin.php';
}


// Shortcode System
function magnifi_widget_shortcode_fn($atts)
{

  // Attributes
  $atts = shortcode_atts(
    array(
      'id' => '1',
      'uid' => '',
      'first' => '',
      'last' => '',
      'email' => '',
      'preset' => '' //DEMO ONLY
    ),
    $atts
  );

  // DEMO ONLY
  if (!(empty(esc_attr($atts['preset'])))){
    global $wpdb;
    $preset_table = $wpdb->prefix . 'mgnf_preset';
    $preset = $wpdb->get_row("SELECT * FROM $preset_table WHERE id = " . esc_attr($atts['preset']));
    $preset_code = "<div id='widget-preset-" . esc_attr($atts['preset']) . "' style='text-align: center;'>".str_replace("widget-container","widget-preset-".esc_attr($atts['preset']),$preset->wiframe)."</div>";
    
    // Check if user management information was added
    if (!(empty(esc_attr($atts['uid'])) || empty(esc_attr($atts['first'])) || empty(esc_attr($atts['last'])) || empty(esc_attr($atts['email'])))) {
    $preset_code .= "<script>
    
    MagnifiSdk.widget = window.magnifi_widget 
    MagnifiSdk.organization_member = { unique_identifier:'" . esc_attr($atts['uid']) . "', first_name: '" . esc_attr($atts['first']) . "', last_name: '" . esc_attr($atts['last']) . "', email: '" . esc_attr($atts['email']) . "' };

    </script>";
    }
    return $preset_code;
  }


  //Database Details
  global $wpdb;
  $table_name = $wpdb->prefix . 'mgnf_widget';
  $results = $wpdb->get_row("SELECT * FROM $table_name WHERE id = " . esc_attr($atts['id']));
  if (!(empty(esc_attr($atts['uid'])) || empty(esc_attr($atts['first'])) || empty(esc_attr($atts['last'])) || empty(esc_attr($atts['email'])))) {
    return "
    <div id='widget-container-" . esc_attr($atts['id']) . "'></div>
    <script>
            document.addEventListener('DOMContentLoaded', function(event) {
              window.magnifi_widget = new Magnifi({
                settings_url: 'https://widget.magnifi.io/widgets/" . $results->wkey . "/load_settings',
                base_url: 'https://widget.magnifi.io',
                widget_key: '" . $results->wkey . "',
                widget_type: 'call',
                cable_url: 'wss://widget.magnifi.io/cable',
                target_container: document.getElementById('widget-container-" . esc_attr($atts['id']) . "') || document.querySelector('body'),
                capabilities: " . $results->wcapabilities . "
              })
            });

          MagnifiSdk.widget = window.magnifi_widget 
          MagnifiSdk.organization_member = { unique_identifier:'" . esc_attr($atts['uid']) . "', first_name: '" . esc_attr($atts['first']) . "', last_name: '" . esc_attr($atts['last']) . "', email: '" . esc_attr($atts['email']) . "' };

          </script>
    
    ";
  }
  return "
    <div id='widget-container-" . esc_attr($atts['id']) . "'></div>
    <script>
            console.log(" . $results->wcapabilities . ");
            document.addEventListener('DOMContentLoaded', function(event) {
              window.magnifi_widget = new Magnifi({
                settings_url: 'https://widget.magnifi.io/widgets/" . $results->wkey . "/load_settings',
                base_url: 'https://widget.magnifi.io',
                widget_key: '" . $results->wkey . "',
                widget_type: 'call',
                cable_url: 'wss://widget.magnifi.io/cable',
                target_container: document.getElementById('widget-container-" . esc_attr($atts['id']) . "') || document.querySelector('body'),
                capabilities: " . $results->wcapabilities . "
              })
            });
          </script>
    
    ";
}

function mgnf_remove_database()
{
  delete_option("mgnf_user_email");
  delete_option("mgnf_user_pass");
  delete_option("mgnf_user_error");
  global $wpdb;
  $table_name = $wpdb->prefix . 'mgnf_widget';
  $sql = "DROP TABLE IF EXISTS $table_name";
  $wpdb->query($sql);
  $preset_table = $wpdb->prefix . 'mgnf_preset';
  $sql2 = "DROP TABLE IF EXISTS $preset_table";
  $wpdb->query($sql2);
}

//Load Shortcode
add_shortcode('magnifi', 'magnifi_widget_shortcode_fn');

//Load Base Javascript into Pages
add_action('wp_enqueue_scripts', 'mgnf_add_dependencies');

// Create Admin Menu
add_action('admin_menu', 'mgnf_add_admin_menu');

//Create Table
include_once("db.php");
register_activation_hook(__FILE__, 'mgnf_create_table');
register_activation_hook(__FILE__, 'mgnf_create_preset_table');

register_deactivation_hook(__FILE__, 'mgnf_remove_database');
