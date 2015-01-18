<?php
namespace Autophoto;

class Photo {
  private $photo_id;

  const THUMB_WIDTH = 200;

  function __construct($photo_id){
    $this->photo_id = $photo_id;
  }

  /**
   * Update the post's data based on the specified picture file.
   */
  public function update_photo_metadata($path){
    #    do we want to update the post date here? 
    #    This would be useful if e.g. dates are manually updated on existing pictures... not super likely, and it adds an update query to the scan.
#    $post_data = array("ID" => $this->photo_id);
#
#    if($d) {
#      $post_data["post_date"] = $d;
#    }
#    if(count($post_data) > 1){
#      wp_update_post($post_data);
#    }
    $thumb = $this->generate_thumb($path);
    add_post_meta($this->photo_id, "_autophoto_path", $path, true);
    add_post_meta($this->photo_id, "_autophoto_thumb", $thumb, true) || update_post_meta($this->photo_id, "_autophoto_thumb", $thumb);

  }

  /**
   * Generate base post data based on the specified file.
   */
  public static function get_post_data($file) {
    $d = self::get_photo_date($file);
    return array("post_date" => $d);
  }

  /**
   * Attempt to extract the date taken from the specified file.
   */
  private static function get_photo_date($file) {
    $exif = exif_read_data($file);
    if($exif && $exif["DateTime"]) {
      $time = strtotime($exif["DateTime"]);
    } else {
      $time = filectime($file);
    }
    return date("Y-m-d H:i:s", $time);
  }

  /**
   * Generate thumbnail data and return it as a base64 encoded string
   */
  private function generate_thumb($file) {
    $img = imagecreatefromjpeg($file);
    $width = imagesx($img);
    $height = imagesy($img);
    $new_width = self::THUMB_WIDTH;
    $new_height = floor( $height * ( $new_width / $width ) );

    $tmp_img = imagecreatetruecolor($new_width, $new_height);
    if(!$tmp_img)
      throw new \Exception("Could not create image: " . error_get_last()["message"]);
    imagecopyresized($tmp_img, $img, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
    ob_start();
    if(!imagejpeg($tmp_img))
      throw new \Exception("Thumbnail generation failed: " . error_get_last()["message"]);
    $thumb_content = ob_get_contents();
    ob_end_clean();
    return base64_encode($thumb_content);
  }

}
