<?php
namespace Autophoto;

class Album {
  private $album_id;
  private $photo_names;
  
  public function __construct($album_id){
    $this->album_id = $album_id;
    $this->photo_names = null;
  }

  /**
   * Get the top level album corresponding to the specified folder.
   */
  public static function get_toplevel_album($folder){
    $folder_name = basename($folder);
    $root = new Album(0);
    $album = $root->find_album($folder_name);
    if(!$album){
      $album = $root->create_album($folder_name);
    }
  }

  /**
   * Find child album 
   */
  private function find_album($name){
    $args = array("post_parent" => $parent_album, "post_type" => "autophoto-album", "name" => $name);
    $matches = get_posts($args);
    if(count($matches) > 0){
      return new Album($matches[0]->ID);
    }
    return null;
  }

  /**
   * Create child album
   */
  private function create_album($name){
    $args = array(
      'post_name' => $name,
      'post_title' => ucwords($name),
      'post_status' => 'publish',
      'post_type' => 'autophoto-album',
      'post_parent' => $this->album_id
    );
    $album = wp_insert_post($args, true);
    if(is_wp_error($album))
      throw new \Exception("Album creation failed: " + $album->get_message());
    return new Album($album);
  }

  /**
   * Create a child picture
   */
  private function create_photo_post($path){
  }

  public function scan_folder($folder, $result){
    $dh = opendir($folder);
    if(!$dh) {
      throw new \Exception("Unable to open folder: $folder");
    }
    while(($file = readdir($dh)) !== false) {
      $full_path = "$folder/$file";
      if(is_dir($full_path) && $file[0] != ".") {
        $album = $this->find_album($file);
        if(!$album) {
          $result->new_albums++;
          $album = $this->create_album($file);
        }
        $album->scan_folder($full_path, $result);
      } else if(is_file($full_path) && $this->is_picture($full_path) ) {
        if(!$this->has_photo_file($full_path)){
          $this->create_photo_post($full_path);
          $result->new_pictures++;
        }
      }
    }
    closedir($dh);
  }

  /**
   * Check if the album contains the designated picture.
   */
  private function has_photo($path){
    if(!$this->photo_names) {
      $this->photo_names = $this->get_album_photo_names();
    }
    return isset($this->photo_names[get_photo_name($path)]);
  }

  /**
   * Form name for the specified photo file.
   * The name must be unique among ALL photos, and it must be easily inferred from the path.  
   * Therefore we use the album id as a prefix.
   */
  private function get_photo_name($path) {
    return sanitize_title("$this->album_id-" . strtolower(basename($file)));
  }

  /**
   * Retrieve photos under the specified album, indexed by name.
   *
   * @return array Array of String -> id
   */
  private function get_album_photo_names(){
    $args = array(
      "post_parent" => $this->album_id, 
      "post_type" => AlbumPostType::POST_TYPE, 
      "posts_per_page" => -1
    );
    $matches = get_posts($args);
    $result = array();
    foreach($matches as $post){
      $result[$post->post_name] = $post->ID;
    }
    return $result;
  }


}
