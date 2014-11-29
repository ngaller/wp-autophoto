<?php
/*
Plugin Name: Auto Photo
Description: Automatically create a photo gallery from a folder
Version: 1.0
Author: Nic Galler
License: GPLv2
 */


namespace Autophoto;

if ( ! defined( 'ABSPATH' ) ) die( "Can't load this file directly" );

if(!class_exists('\Autophoto\Autophoto')){
  class Autophoto {
    public function __construct() {
      require_once __DIR__ . "/settings.php";
      $this->settings = new Settings();

      require_once __DIR__ . "/scanner.php";
      $this->scanner = new Scanner();

      require_once __DIR__ . "/album_post_type.php";
      new AlbumPostType();
      require_once __DIR__ . "/photo_post_type.php";
      new PhotoPostType();

    }

    public function activate() {
      $options = array(
        'scan_folder' => '/home/pictures',
        'scan_interval' => 15
      );

      update_option('autophoto_options', $options);

      $this->scanner->setup_schedule();
    }

    public function deactivate() {
      $this->scanner->remove_schedule();
    }
  }
  register_activation_hook(__FILE__, array('\Autophoto\Autophoto', 'activate'));
  register_deactivation_hook(__FILE__, array('\Autophoto\Autophoto', 'deactivate'));
  $autophoto = new Autophoto();
}
