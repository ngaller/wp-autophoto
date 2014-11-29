<?php
if(!class_exists('PostTypeTemplate')){
  // 
  class PostTypeTemplate {
    // Post Type to be defined in sub class
    protected $post_type;

    protected $meta = array();


    /**
     * Initialize the post type
     */
    public function __construct(){
      add_action('init', array(&$this, 'init'));
      add_action('admin_init', array(&$this, 'admin_init'));
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
      if(isset($_POST['post_type']) && $_POST['post_type'] == $this->post_type && current_user_can('edit_post', $post_id))
      {
          foreach($this->meta as $field_name)
          {
              // Update the post's meta field
              update_post_meta($post_id, $field_name, $_POST[$field_name]);
          }
      }
      else
      {
          return;
      } // if($_POST['post_type'] == $this->post_type && current_user_can('edit_post', $post_id))

    }

    public function add_meta_boxes() {
    }

    private function create_post_type(){
      register_post_type($this->post_type,
        array(
          'labels' => array(
            'name' => __(sprintf('%ss', ucwords(str_replace("_", " ", $this->post_type)))),
            'singular_name' => __(sprintf('%s', ucwords(str_replace("_", " ", $this->post_type))))
          ),
          'public' => true,
          'has_archive' => false,
          'hierarchical' => true,
          'description' => __(sprintf('%s', ucwords(str_replace("_", " ", $this->post_type)))),
          'supports' => array('title', 'editor')
        ));
    }
  }
}

new AutophotoPhotoPostType();
