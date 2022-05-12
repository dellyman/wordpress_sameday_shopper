<?php
/*
Plugin Name:    Dellyman Shipping
Plugin URI:		https://www.dellyman.com
Description:	Dellyman Plugin for E-commerce owners to tigger delivery and payment options for vendors
Version:		1.0.0
Author:			Dellyman
Author URI:		https://www.dellyman.com
*/

defined( 'ABSPATH' ) or die("You are not allowed to access this page");

class DellymanShipping
{
    // All methods
        function __construct( ) {
               add_action( 'admin_menu', array($this,'addMenu') );
        }

        function activatePlugin(){
            $this->addMenu();
            $this->createDB();
            flush_rewrite_rules(); 
        }
        function deactivatePlugin(){
            flush_rewrite_rules(); 
        }
        function addMenu(){
            add_menu_page('Dellyman Orders', 'Dellyman Orders', 'manage_options', 'dellyman-orders', 'index_page',plugins_url(basename(__DIR__).'/assets/svg/icon.svg'),60);
            add_submenu_page('dellyman-orders', 'Request Delivery', 'Request Delivery', 'manage_options', 'request-delivery', 'requestDelivery');
            add_submenu_page('dellyman-orders', 'Connect to Dellyman', 'Connect to Dellyman', 'manage_options', 'connect-to-dellyman', 'login_page');
        }

        function createDB(){
            //Creating table that store credentails
            global $wpdb;
            $table_name = $wpdb->prefix . "woocommerce_dellyman_credentials"; 
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id int(10) NOT NULL AUTO_INCREMENT,
            time datetime DEFAULT CURRENT_TIMESTAMP,
            API_KEY varchar(255)NOT NULL,
            Web_HookSecret varchar(255) DEFAULT '' NOT NULL,
            webhook_url varchar(255) DEFAULT '' NOT NULL,
            PRIMARY KEY  (id)
            ) $charset_collate;";
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );    
            dbDelta($sql);

            //Creating table that store tables after shipping
            global $wpdb;
            $table_name = $wpdb->prefix . "woocommerce_dellyman_products"; 
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id int(10) NOT NULL AUTO_INCREMENT,
            order_id varchar(255) DEFAULT '' NOT NULL,
            user_id int(10) NOT NULL,
            product_name varchar(255) DEFAULT '' NOT NULL,
            product_price varchar(255) DEFAULT '' NOT NULL,
            product_quantity varchar(255) DEFAULT '' NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
            ) $charset_collate";
            require_once( ABSPATH .'wp-admin/includes/upgrade.php' );
            dbDelta( $sql );
            
  
            //Creating table that order status  
            global $wpdb;
            $table_name = $wpdb->prefix . "woocommerce_dellyman_orders"; 
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id int(10) NOT NULL AUTO_INCREMENT,
            time datetime DEFAULT CURRENT_TIMESTAMP,
            order_id varchar(255) DEFAULT '' NOT NULL,
            user_id int(10) NOT NULL,
            dellyman_order_id int(10) NOT NULL,
            is_TrackBack boolean DEFAULT 0 NOT NULL,
            dellyman_status boolean DEFAULT 0 NOT NULL,
            reference_id varchar(255) DEFAULT '' NOT NULL,
            PRIMARY KEY  (id)
            ) $charset_collate;";
            require_once( ABSPATH .'wp-admin/includes/upgrade.php' );
            dbDelta( $sql );   



            //Creating table that store tables after shipping
            global $wpdb;
            $table_name = $wpdb->prefix . "woocommerce_dellyman_shipped_products"; 
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id int(10) NOT NULL AUTO_INCREMENT,
            order_id varchar(255) DEFAULT '' NOT NULL,
            dellyman_order_id int(10) NOT NULL,
            user_id int(10) NOT NULL,
            product_name varchar(255) DEFAULT '' NOT NULL,
            product_price varchar(255) DEFAULT '' NOT NULL,
            product_quantity varchar(255) DEFAULT '' NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
            ) $charset_collate";
            require_once( ABSPATH .'wp-admin/includes/upgrade.php' );
            dbDelta( $sql );
        }
}
if ( class_exists('DellymanShipping')  ) {
    $dellymanShipping =  new DellymanShipping();
}
//Wordpress Activation Hook
register_activation_hook(__FILE__, array(  $dellymanShipping , 'activatePlugin' ));
//Wordpress Deactivation Hook
register_deactivation_hook(__FILE__, array(  $dellymanShipping , 'deactivatePlugin' ));
function index_page(){
    include_once('includes/request.php');
}
function login_page() {
    include_once('includes/login.php');
}
function status_page(){
    include_once('includes/status.php');  
}

function requestDelivery(){
    include_once('includes/request.php');
}

add_action('admin_post_login_credentials','save_crendentials');

function save_crendentials(){;
    extract($_REQUEST);
    global $wpdb;
    $table_name = $wpdb->prefix . "woocommerce_dellyman_credentials"; 
    $details = $wpdb->get_row("SELECT * FROM $table_name WHERE id = 1 ",OBJECT);
    if (empty($details)) {
        //Insert in the database
        $wpdb->insert( 
            $table_name, 
            array( 
                'time' => current_time( 'mysql' ), 
                'API_KEY' => $apiKey, 
                'Web_HookSecret' => $webhookSecret, 
                'webhook_url' => get_site_url().'/wp-json/api/dellyman-webhook', 
            ) 
        );
    }else{
        //Update
        $dbData = array(
            'time' => current_time( 'mysql' ), 
            'API_KEY' => $apiKey, 
            'Web_HookSecret' => $webhookSecret, 
            'webhook_url' => get_site_url().'/wp-json/api/dellyman-webhook',
        );
        $wpdb->update($table_name, $dbData, array('id' => 1)); 
    }
    $redirect = add_query_arg( 'status', 'success', 'admin.php?page=connect-to-dellyman');
    wp_redirect( $redirect );
    exit;
}

function bookOrder($sameday,$carrier,$vendor_data,$shipping_address, $productNames,$pickupAddress,$vendorphone,$custPhone){
    $vendorName = $vendor_data["first_name"][0] .'  '.$vendor_data["last_name"][0];
    $phoneNumber = $vendor_data['billing_address_1'];
    $deliveredName = $shipping_address['first_name'] ." ". $shipping_address['last_name'];
    //booking order
    $date =  date("d/m/Y");
    $postdata = array( 
        'CustomerID' => 0,
        'PaymentMode' => 'pickup',
        'FixedDeliveryCharge' => 10,
        'Vehicle' => $carrier,
        'IsProductOrder' => 0,
        'BankCode' => "",
        'AccountNumber' => "",
        'IsProductInsurance' => 0,
        'InsuranceAmount' => 0,
        'PickUpContactName' =>$vendorName,
        'PickUpContactNumber' => $vendorphone,
        'PickUpGooglePlaceAddress' => $pickupAddress,
        'PickUpLandmark' => "Mobile",	
        'PickUpRequestedTime' => "06 AM to 09 PM",
        'PickUpRequestedDate' => $date,
        'DeliveryRequestedTime' => "06 AM to 09 PM",
        'Packages' => [
            array(
            'DeliveryContactName' =>$deliveredName ,
            'DeliveryContactNumber' => $custPhone ,
            'DeliveryGooglePlaceAddress' =>$shipping_address['address_1']." ,".$shipping_address['city'],
            'DeliveryLandmark' => "",
            'PackageDescription' => $productNames,
            'ProductAmount' => "2000"
            )
        ],
    );
    $jsonPostData = json_encode($postdata);
    global $wpdb;
    $table_name = $wpdb->prefix . "dellyman_credentials"; 
    $user = $wpdb->get_row("SELECT * FROM $table_name WHERE id = 1");
    $ApiKey =  (!empty($user->API_KEY)) ? ($user->API_KEY) : ('');

    $curl = curl_init();
    curl_setopt_array($curl, array(
    CURLOPT_URL => 'https://dev.dellyman.com/api/v3.0/BookOrder',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => $jsonPostData,
    CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json',
        'Authorization: Bearer ' . $ApiKey
    ),
    ));
    $responseJson = curl_exec($curl);
    curl_close($curl);
    $NoTags = strip_tags(preg_replace(array('~<br(.*?)</br>~Usi','~<b(.*?)</b>~Usi'), "", $responseJson));
    return json_decode($NoTags,true);
}
//Adding menu on vendor dashboard
add_filter( 'dokan_query_var_filter', 'dokan_load_document_menu' );
function dokan_load_document_menu( $query_vars ) {
    $query_vars['help'] = 'request-for-shipping';
    return $query_vars;
}
add_filter( 'dokan_get_dashboard_nav', 'dokan_add_help_menu' );
function dokan_add_help_menu( $urls ) {
    $urls['help'] = array(
        'title' => __( 'Request for shipping', 'dokan'),
        'icon'  => '<i class="fas fa-truck"></i>',
        'url'   => dokan_get_navigation_url( 'request-for-shipping' ),
        'pos'   => 51
    );
    return $urls;
}
add_action( 'dokan_load_custom_template', 'dokan_load_template' );
function dokan_load_template( $query_vars ) {
    if ( isset( $query_vars['request-for-shipping'] ) ) {
        require_once ( 'includes/request.php');
       }else {
     // require_once ( 'includes/request.php');
       }
}
add_filter( 'dokan_query_var_filter', 'dokan_load_delivery_status' );
function dokan_load_delivery_status($query_vars) {
    $query_vars['status'] = 'check-delivery-status';
    return $query_vars;
}
add_filter( 'dokan_get_dashboard_nav', 'dokan_add_status_menu' );
function dokan_add_status_menu( $urls ) {
    $urls['status'] = array(
        'title' => __( 'Delivery Status', 'dokan'),
        'icon'  => '<i class="fas fa-info-circle"></i>',
        'url'   => dokan_get_navigation_url( 'check-delivery-status' ),
        'pos'   => 52
    );
    return $urls;
}
add_action( 'dokan_load_custom_template', 'dokan_load_delivery_template' );
function dokan_load_delivery_template($query_vars) {
    if (isset( $query_vars['check-delivery-status'] ) ) {
        require_once ('includes/status.php');
       }else {
      //require_once ('includes/status.php');
       }
}

function get_products_ajax_request(){
    if(isset($_REQUEST['orderid'])){
        $orderid = $_REQUEST['orderid'];
        global $wpdb;
        $table_name = $wpdb->prefix . "dellyman_ship_products"; 
        $user = $wpdb->get_results("SELECT * FROM $table_name WHERE order_id = '$orderid' ",OBJECT);
        if(empty($user)){
            $order = wc_get_order( $orderid ); 
            //var_dump($order->get_items());
            $products = [];
            foreach ($order->get_items() as $key => $item ) {
                    $productdata  = $item->get_product();
                //   echo $item->get_name() .  $productdata->get_price() .$item->get_quantity();
                $product = [
                    'id'=>$item->get_product_id(),
                    'productName'=> preg_replace("/\'s+/", "", $item->get_name()),
                    'sku'=>$productdata->get_sku(),
                    'price'=> $productdata->get_price(),
                    'quantity' =>  $item->get_quantity(),
                    'shipquantity' => 0
                ];
                array_push($products,$product);
            }
            $seller = wp_get_current_user();
            $wpdb->insert( 
                $table_name, 
                array( 
                    'created_at' => current_time( 'mysql' ), 
                    'order_id' => $orderid, 
                    'products' => json_encode($products), 
                    'user_id' => $seller->ID, 
                ) 
            );   
        }
        global $wpdb;
        $table_name = $wpdb->prefix . "dellyman_ship_products"; 
        $user = $wpdb->get_results("SELECT * FROM $table_name WHERE order_id = '$orderid' ",OBJECT);
        $user = $user[0];
        $products = json_decode($user->products,true);
        //Customer
        $shippingOrder = new WC_Order($orderid);
        $shippingOrder->update_status("wc-ready-to-ship",'Ready to ship', TRUE); 
        $shipping_address = $shippingOrder->get_address('shipping'); 
        $customerName =  $shippingOrder->billing_first_name .' '. $shippingOrder->billing_last_name;
        $customerPhone = $shippingOrder ->billing_phone;
        $custData = [
            'address' => $shipping_address,
            'name' => $customerName,
            'customerPhone' => $customerPhone
        ];
        $data = ['products' => $products , 'customerdata' => $custData ];
        wp_send_json($data);
    }
    wp_die(); 
}
add_action( 'wp_ajax_get_products_ajax_request', 'get_products_ajax_request' );
add_action( 'wp_ajax_nopriv_get_products_ajax_request', 'get_products_ajax_request' ); 

function post_products_dellyman_request(){
     if (isset($_POST['order']) AND isset($_POST['products']) AND isset($_POST['carrier']) ) { 
            extract($_POST);
            //Get Order Addresss 
            $order = new WC_Order($order); // Order id
            $shipping_address = $order->get_address('billing'); 
            $products =  $_POST['products'];
    
            //Cycle through products
            $shipProducts = array();
            foreach ($products as $key => $jsonproduct) {       
                $jsonproduct = stripslashes($jsonproduct);
                $arrayProduct = json_decode($jsonproduct);
                array_push($shipProducts, $arrayProduct );  
           }
         //Get product Names
         $allProductNames = "";
         foreach ($shipProducts as $key => $shipProduct) {
            if ($key == 0) {
                  $allProductNames = $shipProduct->productName."(". round($shipProduct->shipquantity)  .")";
           }else{
               $allProductNames = $allProductNames .",". $shipProduct->productName."(". round($shipProduct->shipquantity) .")";
           }
         }
        $productNames = "Total item(s)-". count($shipProducts) ." Products - " .$allProductNames;
        
        //Get email and password
        global $wpdb;
        $table_name = $wpdb->prefix . "dellyman_user"; 
        $user = $wpdb->get_results("SELECT * FROM $table_name ",OBJECT);
        $user = $user[0];

        //Get Authentciation
        $seller = wp_get_current_user();
        $vendor_data = get_user_meta($seller->ID);
        $store_address = get_option( 'woocommerce_store_address' );
        $store_city = get_option( 'woocommerce_store_city' );
        $pickupAddress = $store_address .', '. $store_city;
        $vendorphone = get_user_meta($seller->ID, 'billing_phone', true);
        $custPhone =  $order->get_billing_phone();
         $orderid = $_POST['order'] ;
         //send order
        $feedback = bookOrder($carrier,$vendor_data,$shipping_address, $productNames,$pickupAddress,$vendorphone,$custPhone);
        
         if ($feedback['ResponseCode'] == 100) {
            $dellyman_orderid = $feedback['OrderID'];
            $Reference = $feedback['Reference'];
            //Insert into delivery status in table
            global $wpdb;
            $table_name = $wpdb->prefix . "dellyman_ship_products_status";
            $wpdb->insert( 
                $table_name, 
                array( 
                    'time' => current_time( 'mysql' ), 
                    'order_id' => $orderid,
                    'reference_id' => $Reference,
                    'dellyman_order_id' =>$dellyman_orderid,
                    'products_shipped' => json_encode($shipProducts), 
                    'user_id' => $seller->ID, 
                ) 
            );
            //Update product
            global $wpdb;
            $table_name = $wpdb->prefix . "dellyman_ship_products"; 
            $user = $wpdb->get_results("SELECT * FROM $table_name WHERE order_id = '$orderid' ",OBJECT);
            $user = $user[0];
            $products = json_decode($user->products,true);
    
            foreach ($shipProducts as $key => $ShipProduct) {
                $mainkey = array_search($ShipProduct->id,array_column($products,'id'));
                $products[$mainkey]['quantity'] = $products[$mainkey]['quantity'] - $ShipProduct->shipquantity;
            }
            //Updating products
            $allQuantity = 0;
            foreach ($products as $key => $product) {
                $allQuantity = $allQuantity + $product['quantity'];
            }

            global $wpdb;
            $table_name = $wpdb->prefix . "dellyman_ship_products";
            $dbData = array(
                'products' =>json_encode($products)
            );
            $wpdb->update($table_name, $dbData, array('user_id' => $seller->ID, 'order_id' => $orderid)); 
            
            
            if ($allQuantity <= 0) {
                //Change Status
                $order = new WC_Order($orderid);
                $order->update_status("wc-fully-shipped", 'Fully Shipped', TRUE); 
            }else {
                //Change Status
                $order = new WC_Order($orderid);
                $order->update_status("wc-partially-shipped",'Partially shipped', TRUE); 
            }
         
        }
        $feedback['orderID'] = $_POST['order'];
    }else{
        $feedback = "No product was found";
     }
    wp_send_json($feedback);
    wp_die();

}
add_action( 'wp_ajax_post_products_dellyman_request', 'post_products_dellyman_request' );
add_action( 'wp_ajax_nopriv_post_products_dellyman_request', 'post_products_dellyman_request' ); 


function custom_dellyman_post_order_status() {
  register_post_status( 'wc-ready-to-ship', array(
      'label'                     => 'Ready to ship',
      'public'                    => true,
      'show_in_admin_status_list' => true,
      'show_in_admin_all_list'    => true,
      'exclude_from_search'       => false,
      'label_count'               => _n_noop( 'Ready to ship <span class="count">(%s)</span>', 'Ready to ship <span class="count">(%s)</span>' )
  ) );
  register_post_status( 'wc-partially-shipped', array(
      'label'                     => 'Partially shipped',
      'public'                    => true,
      'show_in_admin_status_list' => true,
      'show_in_admin_all_list'    => true,
      'exclude_from_search'       => false,
      'label_count'               => _n_noop( 'Partially shipped <span class="count">(%s)</span>', 'Partially shipped <span class="count">(%s)</span>' )
  ) );
  register_post_status( 'wc-partially-delivered', array(
    'label'                     => 'Partially delivered',
    'public'                    => true,
    'show_in_admin_status_list' => true,
    'show_in_admin_all_list'    => true,
    'exclude_from_search'       => false,
    'label_count'               => _n_noop( 'Partially delivered <span class="count">(%s)</span>', 'Partially delivered <span class="count">(%s)</span>' )
) );
  register_post_status( 'wc-fully-shipped', array(
    'label'                     => 'Fully Shipped',
    'public'                    => true,
    'show_in_admin_status_list' => true,
    'show_in_admin_all_list'    => true,
    'exclude_from_search'       => false,
    'label_count'               => _n_noop( 'Fully shipped <span class="count">(%s)</span>', 'Fully shipped <span class="count">(%s)</span>')
) );
register_post_status( 'wc-fully-delivered', array(
    'label'                     => 'Fully Delivered',
    'public'                    => true,
    'show_in_admin_status_list' => true,
    'show_in_admin_all_list'    => true,
    'exclude_from_search'       => false,
    'label_count'               => _n_noop( 'Fully delivered <span class="count">(%s)</span>', 'Fully delivered <span class="count">(%s)</span>')
) );

}
add_action( 'init', 'custom_dellyman_post_order_status' );

function add_dellyman_custom_order_statuses($order_statuses) {

  $new_order_statuses = array();

  foreach ( $order_statuses as $key => $status ) {

      $new_order_statuses[ $key ] = $status;

      if ( 'wc-completed' === $key ) {
          $new_order_statuses['wc-ready-to-ship'] = 'Ready to ship';
          $new_order_statuses['wc-partially-shipped'] = 'Partially shipped';
      }
    
  }

  return $new_order_statuses;
}
add_filter( 'wc_order_statuses', 'add_dellyman_custom_order_statuses' );

//Adding Custom Delivery Status for dellyman
function dokan_add_new_custom_order_status( $order_statuses ) {
    $order_statuses[ 'wc-ready-to-ship' ] = _x( 'Ready to ship', 'Order status', 'text_domain' );
    $order_statuses[ 'wc-partially-shipped' ]   = _x( 'Partially shipped', 'Order status', 'text_domain' );
    $order_statuses[ 'wc-partially-delivered' ] = _x( 'Partially Delivered', 'Order status', 'text_domain' );
	$order_statuses[ 'wc-fully-shipped' ] = _x( 'Fully Shipped', 'Order status', 'text_domain' );
	$order_statuses[ 'wc-fully-delivered' ] = _x( 'Fully Delivered', 'Order status', 'text_domain' );
    return $order_statuses;
}
add_filter( 'wc_order_statuses', 'dokan_add_new_custom_order_status', 12, 1 ); 

function dokan_add_custom_order_status_button_class( $text, $status ) {
    switch ( $status ) {
        case 'wc-ready-to-ship':
        case 'Ready to ship':
            $text = 'info';
            break;
        case 'wc-partially-shipped':
        case 'Partially shipped':
            $text = 'info';
            break;
        case 'wc-partially-delivered':
        case 'Partially Delivered':
            $text = 'success';
            break;     
		case 'wc-fully-shipped':
        case 'Fully Shipped':
            $text = 'info';
            break;  
	    case 'wc-fully-delivered':
        case 'Fully Delivered':
            $text = 'info';
            break; 
    }       
    return $text;
}
add_filter( 'dokan_get_order_status_class', 'dokan_add_custom_order_status_button_class', 10, 2 );

// Webhook
function change_status_order(WP_REST_Request $request) {
    // In practice this function would fetch the desired data. Here we are just making stuff up.
    $key  = $request->get_header('X-Dellyman-Signature');
    global $wpdb;
    $table_name = $wpdb->prefix . "dellyman_credentials"; 
    $user = $wpdb->get_row("SELECT * FROM $table_name WHERE id = 1");
    $ApiKey =  (!empty($user->API_KEY)) ? $user->API_KEY : '';
    $Web_HookSecret =  (!empty($user->Web_HookSecret)) ? $user->Web_HookSecret : '';
    $webhook_url =  (!empty($user->webhook_url)) ? $user->webhook_url : ''; 

    $myKey = hash_hmac('sha256', $webhook_url, $Web_HookSecret);
     
    if($key == $myKey){
        //Move order to deliver
        global $wpdb;
        $table_name = $wpdb->prefix . "dellyman_ship_products_status"; 
        $body  = json_decode($request->get_body());
        $order = $wpdb->get_row("SELECT * FROM $table_name WHERE dellyman_order_id =". $body['order']['OrderCode'] ." AND reference_id =". $body['order']['OrderID']);
        if($body['order']['OrderStatus'] == "COMPLETED"){
            //Get order to 
            $table_name = $wpdb->prefix . "wc_order_stats"; 
            $order = $wpdb->get_row("SELECT * FROM $table_name WHERE order_id =". $order->order_id);
            if($order->status == "wc-partially-shipped"){
                $newStatus = "wc-partially-delivered";
            }elseif($order->status == "wc-fully-shipped"){
                $newStatus = "wc-fully-delivered";
            }
            $dbData = array(
                'order_status' => $newStatus 
            );
            $wpdb->update($table_name, $dbData, array('order_id' =>$order->order_id)); 
        }else{
            //Track Back
            $products_shipped  = json_decode($order->products_shipped,true);
            //Get Orginal Products 
            global $wpdb;
            $table_name = $wpdb->prefix . "dellyman_ship_products"; 
            $orderUpdate = $wpdb->get_row("SELECT * FROM $table_name WHERE order_id = '$order->order_id' ",OBJECT);
            $productUpdated = json_decode($orderUpdate->products,true);
                
            foreach ($products_shipped as $mainkey => $product_shipped) {
                $key = array_search($product_shipped['id'],array_column($productUpdated,'id') );
                $result = $productUpdated[$key]['quantity'] + $product_shipped['shipquantity'];
                $productUpdated[$key]['quantity'] = $result;
            }
            $productUpdated = json_encode($productUpdated);
                
            //Change Updated Products Quantites
            global $wpdb;
            $table_name = $wpdb->prefix . "dellyman_ship_products"; 
            $dbData = array(
                'products' => $productUpdated
            );
            $wpdb->update($table_name, $dbData, array('order_id' => $order->order_id)); 
                
            //Update is TrackBack to 1
            global $wpdb;
            $table_name = $wpdb->prefix . "dellyman_ship_products_status"; 
            $dbData = array(
                'is_TrackBack' => 1
            );
            $wpdb->update($table_name, $dbData, array('dellyman_order_id' => $body['order']['OrderCode'], 'order_id' => $order->order_id));   
        }

    }

}

// * This function is where we register our routes for our example endpoint.
// */
function prefix_register_product_routes() {
   //Here we are registering our route for a collection of products and creation of products.
   register_rest_route( 'api', '/dellyman-webhook', array(
           'methods'  => WP_REST_Server::CREATABLE,
           'callback' => 'change_status_order',
    ));
}
add_action( 'rest_api_init', 'prefix_register_product_routes' );
?>