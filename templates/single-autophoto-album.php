<?php 
wp_enqueue_style('autophoto-album', plugins_url('album.css', __FILE__));
get_header(); 
?>

<div id="primary" class="content-area">
<div id="content" class="site-content" role="main">

<?php

if(have_posts()):
  the_post();
  $parent_id = $post->ID;
  $query = new WP_Query(array(
    "post_parent" => $parent_id,
    "post_type" => array("autophoto-photo", "autophoto-album")
  ));
  while($query->have_posts()):
    $query->the_post();

    if($post->post_type == "autophoto-album"){
      echo "<figure class='album-link'>";
    } else {
      echo "<figure class='photo-link'>";
    }
?>
  <a href='<?php the_permalink() ?>'>
    <img src='<?php the_permalink() ?>&display=thumbnail' alt='<?php the_title() ?>' />
    <figcaption><?php the_title() ?></figcaption>
  </a>
</figure>
<?php
  endwhile;

  wp_reset_postdata();

endif;
?>

  </div> <!-- #content -->
</div> <!-- #primary -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>
