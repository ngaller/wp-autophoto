<?php

namespace Autophoto;

class Settings {
  public function __construct() {
    add_action('admin_init', array(&$this, 'admin_init'));
    add_action('admin_menu', array(&$this, 'admin_menu'));
  }

  public function admin_init(){
    register_setting('autophoto-settings-group', 'autophoto_options', array(&$this, 'sanitize_options'));

    add_settings_section('default', 'Auto Photo Settings', null, 'autophoto-settings');
    add_settings_field('autophoto_scan_folder', 'Scan Folder', function() {
      $options = get_option('autophoto_options');
      echo "<input id='autophoto_scan_folder' name='autophoto_options[scan_folder]' size='40' type='text' value='{$options["scan_folder"]}' />";
    }, 'autophoto-settings');

    add_settings_field('autophoto_scan_interval', 'Scan Interval (minutes)', function() {
      $options = get_option('autophoto_options');
      echo "<input id='autophoto_scan_interval' name='autophoto_options[scan_interval]' size='40' type='text' value='{$options["scan_interval"]}' />";
    }, 'autophoto-settings');
  }

  public function admin_menu(){
    add_options_page('Autophoto Settings Page', 'Autophoto Settings', 'manage_options', 'autophoto-settings', array(&$this, 'plugin_settings_page'));
  }

  public function plugin_settings_page() {
    include(sprintf("%s/templates/settings.php", dirname(__FILE__)));
  }

  public function sanitize_options($options) {
    if(intval($options["scan_interval"]) < 15)
      $options["scan_interval"] = 15; 

    return $options;
  }
}
