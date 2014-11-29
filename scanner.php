<?php

/*
 * Logic for the image scanner: Ajax hook that can be invoked from WP pages, and regularly scheduled event.
 */

namespace Autophoto;

if ( ! defined( 'ABSPATH' ) ) die( "Can't load this file directly" );

class Scanner 
{
  public function __construct() {
    // initialization 

    add_action('wp_ajax_autophoto-scan-ajax', array(&$this, 'scan_ajax'));

    // TODO
    //add_action('autophoto_scanner_event', array(&$this, 'scan'));

  }


  /**
   * Handler for ajax calls
   */
  public function scan_ajax() {
    $nonce = $_POST['autophotoNonce'];
    if(!wp_verify_nonce($nonce, 'autophoto-ajax-nonce'))
      die('Validation failed');

    header('Content-Type: application/json');
    $response = $this->scan();
    echo json_encode(array('result' => $response));

    exit;
  }

  /**
   * Perform a recursive scan of the folder configured in autophoto_options.
   *
   * @return 
   */
  public function scan() {
    $options = get_option('autophoto_options');
    $folder = $options["scan_folder"];
    $scan_result = new \StdClass();
    $scan_result->new_albums = 0;
    $scan_result->new_pictures = 0;
    try {
      Album::get_toplevel_album($folder)->scan_folder($folder, $scan_result);
    } catch(\Exception $e) {
      $scan_result->error = $e->getMessage();
    }
    return $scan_result;
  }

  public function setup_schedule() {
    wp_schedule_event(time(), 'hourly', 'autophoto_scanner_event');
  }

  public function remove_schedule() {
    wp_clear_scheduled_hook('autophoto_scanner_event');
  }


} // class Autophoto_Scanner
