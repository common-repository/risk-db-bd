<?php
/**
 * @version 1.0.0
 */

/*
Plugin Name: Risk DB BD
Plugin URI: http://wordpress.org/plugins/risk-db-bd/
Description: RiskDB Integration for WooCommerce and Custom Order Status
Author: Shaiful Islam (Bluedot Technology Ltd)
Version: 1.0.0
Author URI: https://bluedot.ltd
License: GPL2
 */

/**
 * Add waiting shipment option
 * @return [type] [description]
 */
function rdbapi_register_awaiting_shipment_order_status()
{
    register_post_status('wc-awaiting-shipment', array(
        'label' => 'Awaiting Shipment',
        'public' => true,
        'exclude_from_search' => false,
        'show_in_admin_all_list' => true,
        'show_in_admin_status_list' => true,
        'label_count' => _n_noop('Awaiting shipment (%s)', 'Awaiting shipment (%s)'),
    ));
}
add_action('init', 'rdbapi_register_awaiting_shipment_order_status');

/**
 * Add waiting shipment in status
 * @param [type] $order_statuses [description]
 */
function rdbapi_add_awaiting_shipment_to_order_statuses($order_statuses)
{
    $new_order_statuses = array();
    foreach ($order_statuses as $key => $status) {
        $new_order_statuses[$key] = $status;
        if ('wc-processing' === $key) {
            $new_order_statuses['wc-awaiting-shipment'] = 'Awaiting Shipment';
        }
    }
    return $new_order_statuses;
}
add_filter('wc_order_statuses', 'rdbapi_add_awaiting_shipment_to_order_statuses');

/**
 * Add shipped option
 * @return [type] [description]
 */
function rdbapi_register_shipped_order_status()
{
    register_post_status('wc-shipped', array(
        'label' => 'Shipped',
        'public' => true,
        'exclude_from_search' => false,
        'show_in_admin_all_list' => true,
        'show_in_admin_status_list' => true,
        'label_count' => _n_noop('Shipped (%s)', 'Shipped (%s)'),
    ));
}
add_action('init', 'rdbapi_register_shipped_order_status');

/**
 * Add shipped in status
 * @param [type] $order_statuses [description]
 */
function rdbapi_add_shipped_to_order_statuses($order_statuses)
{
    $new_order_statuses = array();
    foreach ($order_statuses as $key => $status) {
        $new_order_statuses[$key] = $status;
        if ('wc-awaiting-shipment' === $key) {
            $new_order_statuses['wc-shipped'] = 'Shipped';
        }
    }
    return $new_order_statuses;
}
add_filter('wc_order_statuses', 'rdbapi_add_shipped_to_order_statuses');

/**
 * Add COD Failed option
 * @return [type] [description]
 */
function rdbapi_register_cod_failed_order_status()
{
    register_post_status('wc-cod-failed', array(
        'label' => 'COD Failed',
        'public' => true,
        'exclude_from_search' => false,
        'show_in_admin_all_list' => true,
        'show_in_admin_status_list' => true,
        'label_count' => _n_noop('COD Failed (%s)', 'COD Failed (%s)'),
    ));
}
add_action('init', 'rdbapi_register_cod_failed_order_status');

/**
 * Add COD Failed in status
 * @param [type] $order_statuses [description]
 */
function rdbapi_add_cod_failed_to_order_statuses($order_statuses)
{
    $new_order_statuses = array();
    foreach ($order_statuses as $key => $status) {
        $new_order_statuses[$key] = $status;
        if ('wc-shipped' === $key) {
            $new_order_statuses['wc-cod-failed'] = 'COD Failed';
        }
    }
    return $new_order_statuses;
}
add_filter('wc_order_statuses', 'rdbapi_add_cod_failed_to_order_statuses');

/**
 * Add COD Success option
 * @return [type] [description]
 */
function rdbapi_register_cod_success_order_status()
{
    register_post_status('wc-cod-success', array(
        'label' => 'COD Success',
        'public' => true,
        'exclude_from_search' => false,
        'show_in_admin_all_list' => true,
        'show_in_admin_status_list' => true,
        'label_count' => _n_noop('COD Success (%s)', 'COD Success (%s)'),
    ));
}
add_action('init', 'rdbapi_register_cod_success_order_status');

/**
 * Add COD Success in status
 * @param [type] $order_statuses [description]
 */
function rdbapi_add_cod_success_to_order_statuses($order_statuses)
{
    $new_order_statuses = array();
    foreach ($order_statuses as $key => $status) {
        $new_order_statuses[$key] = $status;
        if ('wc-cod-failed' === $key) {
            $new_order_statuses['wc-cod-success'] = 'COD Success';
        }
    }
    return $new_order_statuses;
}
add_filter('wc_order_statuses', 'rdbapi_add_cod_success_to_order_statuses');

/**
 * Call RiskDB on Cod Failed
 * @param  [type] $order_id [description]
 * @return [type]           [description]
 */
function rdbapi_cod_failed($order_id)
{
    $order = wc_get_order($order_id);
    $shipping = $order->get_address('shipping');
    if (empty($shipping['address_1'])) {
        $shipping = $order->get_address('billing');
    }
    $param['name'] = implode(" ", array_filter([$shipping['first_name'], $shipping['last_name']]));
    $param['mobile'] = $shipping['phone'];
    $param['email'] = $shipping['email'];
    $param['type'] = 'failed';
    $param['api_token'] = get_option('riskdb_api_token');
    // Call API
    @wp_remote_retrieve_body(wp_remote_get("https://riskdbbd.com/api/event?" . http_build_query($param)));
    $order->update_status('cancelled', 'Order cancelled via COD Failed!');
}
add_action("woocommerce_order_status_cod-failed", 'rdbapi_cod_failed');

/**
 * Call RiskDB on Cod Success
 * @param  [type] $order_id [description]
 * @return [type]           [description]
 */
function rdbapi_cod_success($order_id)
{
    $order = wc_get_order($order_id);
    $shipping = $order->get_address('shipping');
    if (empty($shipping['address_1'])) {
        $shipping = $order->get_address('billing');
    }
    $param['name'] = implode(" ", array_filter([$shipping['first_name'], $shipping['last_name']]));
    $param['mobile'] = $shipping['phone'];
    $param['email'] = $shipping['email'];
    $param['type'] = 'success';
    $param['api_token'] = get_option('riskdb_api_token');
    // Call API
    @wp_remote_retrieve_body(wp_remote_get("https://riskdbbd.com/api/event?" . http_build_query($param)));
    $order->update_status('completed', 'Order completed via COD success!');
}
add_action("woocommerce_order_status_cod-success", 'rdbapi_cod_success');

function rdbapi_get_data($order_id)
{
    $order_notes = array();
    $args = array(
        'post_id' => $order_id,
        'orderby' => 'comment_ID',
        'order' => 'DESC',
        'approve' => 'approve',
        'search' => 'RiskDB:',
        'type' => 'order_note',
        'number' => 1,
    );
    remove_filter('comments_clauses', array(
        'WC_Comments',
        'exclude_order_comments',
    ), 10, 1);

    $notes = get_comments($args);
    if ($notes) {
        foreach ($notes as $note) {
            $order_notes = wpautop(wptexturize(wp_kses_post($note->comment_content)));
        }
    }
    $order_notes = html_entity_decode(strip_tags(str_replace(["\n", "\r"], '', $order_notes)));
    $order_notes = str_replace(['“', "”", '″'], '"', $order_notes);
    $order_notes = preg_replace("/(RiskDB:)(.*)/", '$2', $order_notes);
    return json_decode($order_notes);
}

/**
 * RiskDB Notice
 */
add_action('admin_notices', 'rdbapi_my_order_edit_notice');
function rdbapi_my_order_edit_notice()
{
    global $woocommerce, $post;
    if (get_post_type() != 'shop_order') {return;}
    $order = new WC_Order($post->ID);
    $data = rdbapi_get_data($post->ID);
    if (!$data) {
        $shipping = $order->get_address('shipping');
        if (empty($shipping['address_1'])) {
            $shipping = $order->get_address('billing');
        }
        $param['mobile'] = $shipping['phone'];
        $param['email'] = $shipping['email'];
        $param['ip'] = $order->get_customer_ip_address();
        $param['api_token'] = get_option('riskdb_api_token');
        // Call API
        $url = "https://riskdbbd.com/api/check?" . http_build_query($param);
        $risk = @wp_remote_retrieve_body(wp_remote_get($url));
        $risk = json_decode($risk);
        if ($risk && $risk->status == 'available') {
            $order->add_order_note("RiskDB:" . json_encode($risk), 0, false);
        }
    } else {
        $risk = $data;
    }
    $msg = false;
    $type = 'info';
    if ($risk && $risk->status == 'available') {
        $msg = "Names: " . implode(", ", $risk->names) . " * ";
        $msg .= "Mobiles: " . implode(", ", $risk->mobiles) . " * ";
        $msg .= "Emails: " . implode(", ", $risk->emails);
        if ($risk->ip) {
            $msg .= "IP: " . implode(', ', array_filter([$risk->ip->country, $risk->ip->state, $risk->ip->postcode]));
        }
        $msg .= "<br>";
        $msg .= "<strong>COD Success: " . $risk->risk->cod_success . " | ";
        $msg .= "COD Success: " . $risk->risk->cod_fail . " | ";
        $msg .= "Payment Issue: " . $risk->risk->paymnet_issue . "</strong>";
        $type = ($risk->risk->cod_success > 50) ? 'success' : 'danger';
        $type .= ($risk->risk->paymnet_issue > 30) ? ' danger' : '';
    } else {
        $msg = 'Risk assessment data is not available.';
        $type = 'info';
    }
    ?>
    <div class="notice notice-<?php _e($type);?>">
        <p><?php _e($msg, 'text-domain');?></p>
    </div>
    <?php
}

/**
 * Setting Page
 * @return [type] [description]
 */
function rdbapi_setting()
{
    add_option('riskdb_api_token', '-');
    register_setting('riskdb_options_group', 'riskdb_api_token', '');
}
add_action('admin_init', 'rdbapi_setting');

function rdbapi_register_options_page()
{
    add_options_page('RiskDB Setting', 'RiskDB', 'manage_options', 'riskdb', 'rdbapi_options_page');
}
add_action('admin_menu', 'rdbapi_register_options_page');

function rdbapi_options_page()
{
    ?>
  <div>
  <?php screen_icon();?>
  <h2>RiskDB (COD risk assessment service)</h2>
  <form method="post" action="options.php">
  <?php settings_fields('riskdb_options_group');?>
  <p>Please contact us at <a href="mailto:riskdb@bluedot.ltd">riskdb@bluedot.ltd</a> to get your own API key.</p>
  <table>
  <tr valign="top">
  <th scope="row"><label for="riskdb_api_token">API Key</label></th>
  <td><input type="text" id="riskdb_api_token" name="riskdb_api_token" style="width: 300px;" value="<?php echo get_option('riskdb_api_token'); ?>" /></td>
  </tr>
  </table>
  <?php submit_button();?>
  </form>
  </div>
<?php
}
function rdbapi_settings_link($links)
{
    $settings_link = '<a href="options-general.php?page=riskdb">Settings</a>';
    array_unshift($links, $settings_link);
    return $links;
}
$plugin = plugin_basename(__FILE__);
add_filter("plugin_action_links_$plugin", 'rdbapi_settings_link');?>