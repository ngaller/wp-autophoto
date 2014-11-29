<?php

if(!defined('ABSPATH') && !defined('WP_UNINSTALL_PLUGIN'))
  exit();  // if not called from Wordpress, exit

delete_option('autophoto_options');
