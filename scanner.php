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

    require_once("photo_post_type.php");
    require_once("album_post_type.php");
    $this->photo_post_type = new PhotoPostType();
    $this->album_post_type = new AlbumPostType();
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
    $scan_result = new \StdClass();
    $scan_result->new_albums = 0;
    $scan_result->new_pictures = 0;
    try {
      $this->do_scan_recursive($options["scan_folder"], 0, $scan_result);
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

  /**
   * Look for images and subfolders (sub-albums) in the designated folder.
   *
   * @param String $folder 
   * @param int $parent_album  Album to create items under.  If 0, then we will be looking for / creating top level album.
   * @param $result Object to accumulate result
   */
  private function do_scan_recursive($folder, $parent_album, $result) {
    $album = $this->album_post_type->find_album(basename($folder), $parent_album);
    if(!$album){
      $album = $this->album_post_type->create_album_post(basename($folder), $parent_album);
      $result->new_albums++;
      $photos = null;
    } else {
      $photos = $this->photo_post_type->get_album_photo_names($album);
    }
    $dh = opendir($folder);
    if(!$dh) {
      throw new \Exception("Unable to open folder: $folder");
    }
    while(($file = readdir($dh)) !== false) {
      $file = "$folder/$file";
      if(is_dir($file) && basename($file)[0] != ".") {
        $this->do_scan_recursive($file, $album, $result);
      } else if(is_file($file) && $this->is_picture($file) ) {
        if(!isset($photos[$this->photo_post_type->get_photo_name($album, $file)])) {
          $this->photo_post_type->create_photo_post($file, $album);
          $result->new_pictures++;
        }
      }
    }
    closedir($dh);
    $this->album_post_type->update_album_date($album);
  }

  private function is_picture($file) {
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    return $ext == "jpeg" || $ext == "jpg";
  }


} // class Autophoto_Scanner
