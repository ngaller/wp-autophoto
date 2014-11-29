<?php
namespace Autophoto;

class PhotoPostType {
  const POST_TYPE = "autophoto-photo";

  const THUMB_WIDTH = 200;

  private $_meta = array(
    "_autophoto_path"
  );

  /**
   * Initialize the post type
   */
  public function __construct(){
    add_action('init', array(&$this, 'init'));
    add_action('admin_init', array(&$this, 'admin_init'));
  }

  /**
   * Save a photo under the designated album 
   */
  public function create_photo_post($file, $parent_album) {
    $name = basename($file);
    
    $args = array(
      'post_name' => $this->get_photo_name($parent_album, $file),
      'post_title' => ucwords($name),
      'post_status' => 'publish',
      'post_type' => 'autophoto-photo',
      'post_parent' => $parent_album,
      'post_date' => $this->get_photo_date($file)
    );
    $thumb = $this->generate_thumb($file);
    $photo = wp_insert_post($args, true);
    if(is_wp_error($photo))
      throw new \Exception("Photo creation failed: " + $photo->get_message());
    add_post_meta($photo, "_autophoto_path", $file);
    add_post_meta($photo, "_autophoto_thumb", $thumb);

    return $photo;
  }

  /**
   * Create new post for the album under parent album
   *
   * @return int album id
   */
  public function create_album_post($name, $parent_album){
    $args = array(
      'post_name' => $name,
      'post_title' => ucwords($name),
      'post_status' => 'publish',
      'post_type' => 'autophoto-photo',
      'post_parent' => $parent_album
      // the post date will be updated based on the child posts during the scan
    );
    $album = wp_insert_post($args, true);
    if(is_wp_error($album))
      throw new \Exception("Album creation failed: " + $album->get_message());
    return $album;
  }

  /**
   * Locate existing album under the given parent album (or top level album, if null)
   *
   * @return int album id, or 0 if not found.
   */
  public function find_album($name, $parent_album){
    $args = array("post_parent" => $parent_album, "post_type" => "autophoto-album", "name" => $name);
    $matches = get_posts($args);
    if(count($matches) > 0){
      return $matches[0]->ID;
    }
    return 0;
  }

  /** 
   * Recalculate the album date as a function of all photos contained within the album
   */
  public function update_album_date($albumid){
    global $wpdb;
    $d = $wpdb->get_var($wpdb->prepare("select min(post_date) from $wpdb->posts where post_parent=%d and post_type like 'autophoto-%%'", $albumid));
    if($d){
      wp_update_post(array('ID' => $albumid, 'post_date' => $d));
    }
  }

  /**
   * Retrieve photos under the specified album, indexed by name.
   *
   * @return array Array of String -> id
   */
  public function get_album_photo_names($album){
    $args = array(
      "post_parent" => $album, 
      "post_type" => self::POST_TYPE, 
      "posts_per_page" => -1
    );
    $matches = get_posts($args);
    $result = array();
    foreach($matches as $post){
      $result[$post->post_name] = $post->ID;
    }
    return $result;
  }

  /**
   * Create a thumb file under the .thumbs directory.
   */
  private function generate_thumb($file) {
    $dir = dirname($file) . "/.thumbs";
    if(!is_dir($dir)){
      if(!mkdir($dir))
        throw new \Exception("Unable to create thumb directory $dir");
    }
    $thumb_path = "$dir/" . basename($file);
    if(file_exists($thumb_path))
      return $thumb_path;
    $img = imagecreatefromjpeg($file);
    $width = imagesx($img);
    $height = imagesy($img);
    $new_width = self::THUMB_WIDTH;
    $new_height = floor( $height * ( $new_width / $width ) );

    $tmp_img = imagecreatetruecolor($new_width, $new_height);
    if(!$tmp_img)
      throw new \Exception("Could not create image: " . error_get_last()["message"]);
    imagecopyresized($tmp_img, $img, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
    if(!imagejpeg($tmp_img, $thumb_path))
      throw new \Exception("Thumbnail generation failed: " . error_get_last()["message"]);
    return $thumb_path;
  }

  /**
   * Generate name (slug) for the given file / album photo
   * We want a name that is going to be unique per album since we'll use it to see if a photo already exists under an album,
   * and if it is not unique Wordpress will transparently change it to make it so.
   */
  public function get_photo_name($album, $file) {
    return sanitize_title("$album-" . strtolower(basename($file)));
  }

  /**
   * Attempt to extract the date taken from the specified file.
   */
  private function get_photo_date($file) {
    $exif = exif_read_data($file);
    if($exif && $exif["DateTime"]) {
      $time = strtotime($exif["DateTime"]);
    } else {
      $time = filectime($file);
    }
    return date("Y-m-d H:i:s", $time);
  }


  /**
   * Admin level handlers
   */
  public function admin_init() {
    add_action('add_meta_boxes', array(&$this, 'add_meta_boxes'));
  }

  /**
   * Create the post type if needed and register save handlers for this post type.
   */
  public function init() {
    $this->create_post_type();
    add_action('save_post', array(&$this, 'save_post'));
    add_filter('single_template', array(&$this, 'single_template_chooser'));
  }

  /**
   * Override default template with the one in the plugin's templates folder.
   */
  public function single_template_chooser($template) {
    global $post;


    if($post->post_type == self::POST_TYPE){
      $thumb = get_post_meta($post->ID, "_autophoto_thumb", true);
      if(@$_GET["display"] == "thumbnail")
        # special case where we just want to spit out the thumbnail data
        return __DIR__ . "/templates/photo-thumbnail.php";
      return __DIR__ . "/templates/single-photo.php";
    }
    return $template;
  }

  /**
   * Handler for posts saved from within the UI
   */
  public function save_post($post_id) {
    // verify if this is an auto save routine. 
    // If it is our form has not been submitted, so we dont want to do anything
    if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
    {
        return;
    }
    if(isset($_POST['post_type']) && $_POST['post_type'] == self::POST_TYPE && current_user_can('edit_post', $post_id))
    {
        foreach($this->_meta as $field_name)
        {
            // Update the post's meta field
            update_post_meta($post_id, $field_name, $_POST[$field_name]);
        }
    }
    else
    {
        return;
    } // if($_POST['post_type'] == self::POST_TYPE && current_user_can('edit_post', $post_id))

  }

  public function add_meta_boxes() {
  }

  private function create_post_type(){
    register_post_type(self::POST_TYPE,
      array(
        'labels' => array(
          'name' => __(sprintf('%ss', ucwords(str_replace("_", " ", self::POST_TYPE)))),
          'singular_name' => __(sprintf('%s', ucwords(str_replace("_", " ", self::POST_TYPE))))
        ),
        'public' => true,
        'has_archive' => false,
        'hierarchical' => true,
        'description' => 'Autophoto Photo',
        'supports' => array('title', 'editor'),
        'rewrite' => array('slug' => 'autophoto-photo')
      ));
  }
}
