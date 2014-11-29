<div class="wrap">


<form method="post" action="options.php">
<?php 
  settings_fields('autophoto-settings-group');

  do_settings_sections('autophoto-settings');

  submit_button();

  submit_button('Scan Now', 'secondary', 'btnAutophotoScanAjax');

  wp_enqueue_script("autophoto-scan-ajax", plugin_dir_url(__FILE__) . 'js/scan-ajax.js', array('jquery'));
  wp_localize_script('autophoto-scan-ajax', 'AutophotoAjax', array(
    'ajaxurl' => admin_url('admin-ajax.php'),
    'autophotoNonce' => wp_create_nonce('autophoto-ajax-nonce')
  ));
?>


</form>
</div>
