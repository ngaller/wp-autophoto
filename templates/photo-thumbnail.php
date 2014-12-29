<?php

ob_clean();
print "FOO";
die;

if(have_posts()){
  the_post();

  $thumb = get_post_meta($post->ID, "_autophoto_thumb", true);
  if($thumb && file_exists($thumb)) {
    header("Content-Type: image/jpeg");

    readfile($thumb);
  }

}
