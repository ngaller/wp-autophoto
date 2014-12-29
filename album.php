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
    $root = new Album(0);
    $post_name = $root->get_album_name($folder);
    $album = $root->find_album($post_name);
    if(!$album){
      $album = new Album($root->create_child_post($post_name));
    }
    return $album;
  }

  /**
   * Determine if the specified post is an album under this one.
   */
  public static function is_album($post){
    return 0 === strpos($post->post_name, "album-");
  }

  /**
   * Find child album 
   */
  private function find_album($name){
    $args = array("post_parent" => $this->album_id, "post_type" => AutophotoPostType::POST_TYPE, "name" => $name);
    $matches = get_posts($args);
    if(count($matches) > 0){
      return new Album($matches[0]->ID);
    }
    return null;
  }

  /**
   * Create child post, return post id
   */
  private function create_child_post($name, $post_data=array()){
    $args = array(
      'post_name' => $name,
      'post_title' => ucwords($name),
      'post_status' => 'publish',
      'post_type' => AutophotoPostType::POST_TYPE,
      'post_parent' => $this->album_id
    );
    $args = array_merge($args, $post_data);
    $post = wp_insert_post($args, true);
    if(is_wp_error($post))
      throw new \Exception("Post creation failed: " + $post->get_message());
    return $post;
  }

  /**
   * Look for images and subfolders (sub-albums) in the designated folder.
   *
   * @param String $folder 
   * @param $result Object to accumulate result
   */
  public function scan_folder($folder, $result){
    $dh = opendir($folder);
    if(!$dh) {
      throw new \Exception("Unable to open folder: $folder");
    }
    while(($file = readdir($dh)) !== false) {
      $full_path = "$folder/$file";
      if(is_dir($full_path) && $file[0] != ".") {
        $album_post_name = $this->get_album_name($file);
        $album = $this->find_album($album_post_name);
        if(!$album) {
          $result->new_albums++;
          $album = new Album($this->create_child_post($album_post_name));
        }
        $album->scan_folder($full_path, $result);
      } else if(is_file($full_path) && $this->is_picture($full_path) ) {
        if(!$this->has_photo($full_path)){
          $photo = new Photo($this->create_child_post($this->get_photo_name($file), Photo::get_post_data($full_path)));
          $photo->update_photo_metadata($full_path);
          $result->new_pictures++;
        }
      }
    }
    closedir($dh);
    $this->update_album_date();
  }

  /**
   * Check if the album contains the designated picture.
   */
  private function has_photo($path){
    if(!$this->photo_names) {
      $this->photo_names = $this->get_album_photo_names();
    }
    return isset($this->photo_names[$this->get_photo_name($path)]);
  }

  /**
   * Form name for the specified photo file.
   * The name must be unique among ALL photos, and it must be easily inferred from the path.  
   * Therefore we use the album id as a prefix.
   */
  private function get_photo_name($path) {
    return sanitize_title("$this->album_id-" . strtolower(basename($path)));
  }

  /**
   * Create name for the specified album folder.
   * The name must be unique and easily inferred from the path.
   * Additionally we use the name to distinguish between photo and album posts.
   */
  private function get_album_name($path) {
    return sanitize_title("album-$this->album_id-" . strtolower(basename($path)));
  }

  /**
   * Retrieve photos under the specified album, indexed by name.
   *
   * @return array Array of String -> id
   */
  private function get_album_photo_names(){
    $args = array(
      "post_parent" => $this->album_id, 
      "post_type" => AutophotoPostType::POST_TYPE, 
      "posts_per_page" => -1
    );
    $matches = get_posts($args);
    $result = array();
    foreach($matches as $post){
      $result[$post->post_name] = $post->ID;
    }
    return $result;
  }

  private function is_picture($file) {
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    return $ext == "jpeg" || $ext == "jpg";
  }

  /** 
   * Recalculate the album date as a function of all photos contained within the album
   */
  private function update_album_date(){
    global $wpdb;
    $d = $wpdb->get_var($wpdb->prepare("select min(post_date) from $wpdb->posts where post_parent=%d and post_type like %s", $this->album_id, AutophotoPostType::POST_TYPE));
    if($d){
      wp_update_post(array('ID' => $this->album_id, 'post_date' => $d));
    }
  }


}
