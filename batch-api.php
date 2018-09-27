<?php
/**
 * Plugin Name: Batch API
 * Plugin URI: https://www.github.com/jchamill
 * Description: Exposes a batch api for plugin development.
 * Version: 0.0.1
 * Author: JC Hamill
 * Author URI: https://www.jchamill.com
 */

use \BatchAPI\BatchAPI;

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

require_once plugin_dir_path( __FILE__ ) . 'core/BatchApi.php';

BatchAPI::register();