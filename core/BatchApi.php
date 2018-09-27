<?php

namespace BatchAPI;

/**
 * Batch API for Wordpress.
 */
class BatchAPI {

  public static function register() {
    $plugin = new self();

    add_action( 'admin_menu', array( $plugin, 'adminMenu' ), 999 );
    add_action( 'admin_head', array( $plugin, 'removeMenus' ) );
    add_action( 'admin_enqueue_scripts', array( $plugin, 'adminScripts' ), 11 );
    add_action( 'wp_ajax_batch_process', array( $plugin, 'process' ) );
  }

  public function adminMenu() {
    add_submenu_page(
      'tools.php',
      'Batch Process',
      'Batch Process',
      'manage_options',
      'batchapi-process',
      array( $this, 'start' )
    );
  }

  public function removeMenus() {
    remove_submenu_page( 'tools.php', 'batchapi-process' );
  }

  public function adminScripts() {
    wp_enqueue_script( 'batch-api-scripts', plugin_dir_url(__FILE__) . 'batch.js', array( 'jquery' ), '1.0.0', true );
    wp_localize_script( 'batch-api-scripts', 'batchapi', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
  }

  public static function generateUniqueId() {
    return substr( md5( uniqid( rand(), true ) ), 16 );
  }

  public static function execute($id, $operations, $redirect_url = false) {
    $batch = array(
      'id' => $id,
      'operations' => $operations,
      'total' => count($operations),
      'redirect_url' => $redirect_url,
    );

    self::addBatch($batch);

    // Redirect to page that handles the batch process.
    wp_redirect( admin_url('tools.php?page=batchapi-process&batch_id=' . $id ) );
  }

  public function start() {
    $batch = $this->getBatch( $_GET['batch_id'] );
    print '<div class="wrap">';
    if ( $batch ) {
      print '<div id="batchapi-status" class="batchapi-ready" data-batch="' . esc_attr( $_GET['batch_id'] ) . '">';
      print '<h2>Working...</h2>';
      print '<div style="width:0;height:50px;background-color:#00A8EF;"></div>';
      print '</div>';
    } else {
      print '<h2>Batch API</h2>';
      print '<p>This batch has already been processed.';
    }
    print '</div>';
  }

  public function process() {
    $step = $_POST['step'];
    $batch_id = $_POST['batch_id'];
    $batch = $this->getBatch( $batch_id );
    $operations = $this->getOperations( $batch_id );
    $operations_remaining = count( $operations );
    $total = $batch['total'];
    $operation_ids = array();
    $start_time = time();
    $num_executed = 0;

    if ( !empty( $operations ) ) {
      foreach ( $operations as $operation ) {
        $function = unserialize( $operation->option_value );
        if ( is_callable( $function['function'] ) ) {
          call_user_func_array( $function['function'], $function['args'] );
        }
        $operation_ids[] = $operation->option_id;
        $step++;
        $num_executed++;
        // Break out if executing for more than 1 second.
        if (time() - $start_time > 1) {
          break;
        }
      }
    }

    // Remove operations that were implemented in this pass.
    $this->deleteOperations( $operation_ids );

    // Need a way to know if we are finished.
    if ( $num_executed >= $operations_remaining ) {
      $step = 'done';
      $this->deleteBatch( $batch_id );
    }

    print json_encode( array(
      'step' => $step,
      'percentage' => floor( 100 / $total * $step ),
      'url' => $batch['redirect_url']
    ));

    exit();
  }

  public function addBatch($batch) {
    $operations = $batch['operations'];
    unset($batch['operations']);

    add_option('batchapi_' . $batch['id'], $batch, '', 'no');

    foreach ($operations as $i => $operation) {
      add_option('batchapi_ops_' . $batch['id'] . '_' . $i, $operation, '', 'no');
    }
  }

  public function getBatch($batch_id) {
    return get_option( 'batchapi_' . $batch_id );
  }

  public function getOperations($batch_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'options';

    $operations = $wpdb->get_results( $wpdb->prepare(
      "SELECT option_id, option_value FROM $table_name
       WHERE option_name LIKE %s",
      'batchapi_ops_' . $batch_id . '%'
    ) );

    return $operations;
  }

  public function deleteBatch($batch_id) {
    delete_option( 'batchapi_' . $batch_id );
  }

  public function deleteOperations($operation_ids) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'options';
    $option_ids = implode(',', $operation_ids);
    $wpdb->query(
      "DELETE FROM $table_name
       WHERE option_id IN($option_ids)"
    );
  }
}