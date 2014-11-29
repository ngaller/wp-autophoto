<?php

if(have_posts()){
  the_post();

  $album_id = $post->ID;
  $query = new WP_Query("post_parent=$album_id&post_type=autophoto-photo&posts_per_page=1");
  if($query->have_posts()){
    $query->the_post();

    $thumb = get_post_meta($post->ID, "_autophoto_thumb", true);
    if($thumb && file_exists($thumb)) {
      ob_clean();

      header("Content-Type: image/jpeg");

      readfile($thumb);
    }
  }


}
