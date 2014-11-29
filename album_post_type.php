<?php
namespace Autophoto;

class AlbumPostType {
  const POST_TYPE = "autophoto-album";

  private $_meta = array(
  );

  public function __construct(){
    add_action('init', array(&$this, 'init'));
    add_action('admin_init', array(&$this, 'admin_init'));
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
   * Create new post for the album under parent album
   *
   * @return int album id
   */
  public function create_album_post($name, $parent_album){
    $args = array(
      'post_name' => $name,
      'post_title' => ucwords($name),
      'post_status' => 'publish',
      'post_type' => 'autophoto-album',
      'post_parent' => $parent_album
    );
    $album = wp_insert_post($args, true);
    if(is_wp_error($album))
      throw new \Exception("Album creation failed: " + $album->get_message());
    return $album;
  }

  public function admin_init() {
    add_action('add_meta_boxes', array(&$this, 'add_meta_boxes'));
  }

  public function init() {
    $this->create_post_type();
    add_action('save_post', array(&$this, 'save_post'));
    add_filter('single_template', array(&$this, 'single_template_chooser'));
  }

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

  /**
   * Override default template with the one in the plugin's templates folder.
   */
  public function single_template_chooser($template) {
    global $post;


    if($post->post_type == self::POST_TYPE){
      if(@$_GET["display"] == "thumbnail")
        # special case where we just want to spit out the thumbnail data
        return __DIR__ . "/templates/album-thumbnail.php";
      if($theme_file = locate_template("single-autophoto-album.php"))
        return $theme_file;
      return __DIR__ . "/templates/single-autophoto-album.php";
    }
    return $template;
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
        'has_archive' => true,
        'hierarchical' => true,
        'description' => 'Autophoto Album',
        'supports' => array('title', 'editor'),
        'rewrite' => array('slug' => 'autophoto')
      ));
  }
}
