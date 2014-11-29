<?php
namespace Autophoto;

/**
 * Autophoto post type definition, used for photos as well as albums.
 */
class AutophotoPostType {
  const POST_TYPE = "autophoto";

  private $_meta = array(
  );

  /**
   * If initialize is specified, this registers the hooks necessary for the post type.
   * Otherwise, the constructor does nothing.
   */
  public function __construct($initialize=false){
    if($initialize){
      add_action('init', array(&$this, 'init'));
      add_action('admin_init', array(&$this, 'admin_init'));
    }
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
      # do we have a picture, or an album?
      # if there is a path associated with it, it should be a picture
      # note the get_metadata call is cached
      if(!empty($post->_autophoto_path)){
        if($theme_file = locate_template("single-autophoto-photo.php"))
          return $theme_file;
        return __DIR__ . "/templates/single-autophoto-photo.php";
      } else {
        if($theme_file = locate_template("single-autophoto-album.php"))
          return $theme_file;
        return __DIR__ . "/templates/single-autophoto-album.php";
      }
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
        'description' => 'Autophoto',
        'supports' => array('title', 'editor'),
        'rewrite' => array('slug' => 'autophoto')
      ));
  }
}
