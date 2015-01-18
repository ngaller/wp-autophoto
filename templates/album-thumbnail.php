<?php
ob_clean();

if(have_posts()){
  the_post();

  $thumb = $post->_autophoto_thumb;
  if(!empty($thumb)) {
    // photo
    header("Content-Type: image/jpeg");

    echo base64_decode($thumb);
  } else {
    // album
    $album_id = $post->ID;
    $query = new WP_Query("post_parent=$album_id&post_type=autophoto&posts_per_page=1");
    if($query->have_posts()){
      $query->the_post();

      $thumb = get_post_meta($post->ID, "_autophoto_thumb", true);
      if($thumb) {
        header("Content-Type: image/jpeg");

        echo base64_decode($thumb);
      }
    }
  }

}
