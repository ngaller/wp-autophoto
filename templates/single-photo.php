<?php

if(have_posts()){
  the_post();

  $image = get_post_meta($post->ID, "_autophoto_path", true);
  if($image && file_exists($image)) {
    ob_clean();

    header("Content-Type: image/jpeg");

    readfile($image);
  }

}
