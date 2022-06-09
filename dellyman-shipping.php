<?php
/*
Plugin Name:    Dokan Dellyman Shipping
Plugin URI:		https://www.dellyman.com
Description:	Dellyman Plugin for Multi Vendor E-commerce to tigger delivery and payment options for vendors
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
            product_id int(10) NOT NULL,
            user_id int(10) NOT NULL,
            product_name varchar(255) DEFAULT '' NOT NULL,
            sku varchar(255) DEFAULT NULL,
            price varchar(255) DEFAULT '' NOT NULL,
            quantity int(10) DEFAULT 0 NOT NULL,
            shipquantity int(10) DEFAULT 0 NOT NULL,
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
            dellyman_status varchar(255) DEFAULT 'PENDING' NOT NULL,
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
            sku varchar(255) DEFAULT NULL,
            product_id int(10) NOT NULL,
            product_name varchar(255) DEFAULT '' NOT NULL,
            price varchar(255) DEFAULT '' NOT NULL,
            quantity varchar(255) DEFAULT '' NOT NULL,
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


if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}
// Extending class
class DellymanOrders extends WP_List_Table
{
      private $orders;

      private function get_dellyman_orders($search = "")
      {
            global $wpdb;

            if (!empty($search)) {
                  return $wpdb->get_results(
                        "SELECT * from {$wpdb->prefix}woocommerce_dellyman_orders WHERE order_id Like '%{$search}%' OR reference_id Like '%{$search}%'",
                        ARRAY_A
                  );
            }else{
                  return $wpdb->get_results(
                        "SELECT * from {$wpdb->prefix}woocommerce_dellyman_orders ORDER BY time DESC",
                        ARRAY_A
                  );
            }
      }

      // Define table columns
      function get_columns()
      {
            $columns = array(
                  'cb'            => '<input type="checkbox" />',
                  'order_id' => 'Order id',
                  'reference_id'    => 'Dellyman order id',
                  'store_name'    => 'Store',
                  'item'      => 'Items',
                  'status' => 'Status',
                  'time' => 'Created'
            );
            return $columns;
      }

      // Bind table with columns, data and all
      function prepare_items()
      {
            if (isset($_POST['page']) && isset($_POST['s'])) {
                  $this->orders = $this->get_dellyman_orders($_POST['s']);
            } else {
                  $this->orders = $this->get_dellyman_orders();
            }

            $columns = $this->get_columns();
            $hidden = array();
            $sortable = $this->get_sortable_columns();
            $this->_column_headers = array($columns, $hidden, $sortable);

            /* pagination */
            $per_page = 20;
            $current_page = $this->get_pagenum();
            $total_items = count($this->orders);

            $this->orders = array_slice($this->orders, (($current_page - 1) * $per_page), $per_page);

            $this->set_pagination_args(array(
                  'total_items' => $total_items, // total number of items
                  'per_page'    => $per_page // items to show on a page
            ));

            usort($this->orders, array(&$this, 'usort_reorder'));

            $this->items = $this->orders;
      }

      // bind data with column
      function column_default($item, $column_name)
      {
            switch ($column_name) {
                  case 'order_id':
                        return '#'. $item['order_id'];
                  case 'reference_id':
                        return $item[$column_name];
                  case  'store_name';
                        $dokan_id = $item['order_id'];
                        global $wpdb;
                        $table_name = $wpdb->prefix . "woocommerce_dellyman_products"; 
                        $product = $wpdb->get_row("SELECT * FROM $table_name WHERE order_id = '$dokan_id'",OBJECT);
                        $store_info = dokan_get_store_info( $product->user_id);
                        return $store_info['store_name'];
                  case 'item':
                        $order = $item['dellyman_order_id'];
                        global $wpdb;
                        $table_name = $wpdb->prefix . "woocommerce_dellyman_shipped_products"; 
                        $products = $wpdb->get_results("SELECT * FROM $table_name WHERE dellyman_order_id = '$order'",OBJECT);
                        $allProductNames = "";
                        foreach ($products as $key => $shipProduct) {
                           if ($key == 0) {
                                 $allProductNames = $shipProduct->product_name."(". round($shipProduct->quantity)  .")";
                          }else{
                              $allProductNames = $allProductNames .",". $shipProduct->product_name."(". round($shipProduct->quantity) .")";
                          }
                        }
                       $productNames = "Total item(s)-". count($products) ." Products - " .$allProductNames;
                        return $productNames; 
                    case 'status':        
                        global $wpdb;
                        $table_name = $wpdb->prefix . "woocommerce_dellyman_credentials"; 
                        $user = $wpdb->get_row("SELECT * FROM $table_name WHERE id = 1");
                        $ApiKey =  (!empty($user->API_KEY)) ? $user->API_KEY : '';                                        
                        $response = wp_remote_post( 'https://dev.dellyman.com/api/v3.0/TrackOrder', array(
                            'body'    => json_encode([
                                'OrderID' => intval($item['dellyman_order_id'])
                            ]),
                            'headers' => [
                                'Authorization' => 'Bearer '. $ApiKey,
                                'Content-Type' =>  'application/json'
                            ]
                        ));
                        $status = json_decode(wp_remote_retrieve_body($response),true);
                        return $status['OrderStatus'];
                    case 'time':
                        return date("F j, Y, g:i a", strtotime($item['time'])); 
                  default:
                        return print_r($item, true); //Show the whole array for troubleshooting purposes
            }
      }

      // To show checkbox with each row
      function column_cb($item)
      {
            return sprintf(
                  '<input type="checkbox" name="user[]" value="%s" />',
                  $item['order_id']
            );
      }

      // Add sorting to columns
      protected function get_sortable_columns()
      {
            $sortable_columns = array(
                  'order_id'  => array('order_id', false),
                  'dellyman_order_id' => array('dellyman_order_id', false),
                  'reference_id'   => array('reference_id', true),
                  'time'   => array('time', true)
            );
            return $sortable_columns;
      }

      // Sorting function
      function usort_reorder($a, $b)
      {
            // If no sort, default to user_login
            $orderby = (!empty($_GET['orderby'])) ? $_GET['orderby'] : 'time';
            // If no order, default to asc
            $order = (!empty($_GET['order'])) ? $_GET['order'] : 'desc';
            // Determine sort order
            $result = strcmp($a[$orderby], $b[$orderby]);
            // Send final sort direction to usort
            return ($order != 'desc') ? $result : -$result;
      }
}

function index_page(){
 
      // Creating an instance
      $orderTable = new DellymanOrders();

      echo '<div class="wrap"><h2>Dellyman orders</h2>';
      // Prepare table
      $orderTable->prepare_items();
      ?>
            <form method="post">
                  <input type="hidden" name="page" value="employees_list_table" />
                  <?php $orderTable->search_box('search', 'search_id'); ?>
            </form>
      <?php
      // Display table
      $orderTable->display();
      echo '</div>';
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
    wp_redirect($redirect);
    exit;
}

function bookOrder($carrier,$vendor_data,$shipping_address, $productNames,$pickupAddress,$vendorphone,$custPhone, $store_city,$customer_city){
    $vendorName = $vendor_data["first_name"][0] .'  '.$vendor_data["last_name"][0];
    $phoneNumber = $vendor_data['billing_address_1'];
    $deliveredName = $shipping_address['first_name'] ." ". $shipping_address['last_name'];
    //booking order
    $date =  date("m/d/Y");
    $postdata = array( 
        'CustomerID' => 0,
        'PaymentMode' => 'online',
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
            'ProductAmount' => "2000",
            "PickUpCity" =>  $store_city,
            "DeliveryCity" => $customer_city,
            "PickUpState" => "Lagos",
            "DeliveryState" => "Lagos"
            )
        ],
    );
    $jsonPostData = json_encode($postdata);
    global $wpdb;
    $table_name = $wpdb->prefix . "woocommerce_dellyman_credentials"; 
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


function get_products_ajax_request(){
    if(isset($_REQUEST['orderid'])){
        $orderid = $_REQUEST['orderid'];
        global $wpdb;
        $table_name = $wpdb->prefix . "woocommerce_dellyman_products"; 
        $products = $wpdb->get_results("SELECT * FROM $table_name WHERE order_id = '$orderid' ",OBJECT);
        if(empty($products)){
            $order = wc_get_order($orderid); 
            foreach ($order->get_items() as $key => $item ) {
                $productdata  = $item->get_product();
                $seller = wp_get_current_user();
                $wpdb->insert( 
                    $table_name, 
                    array( 
                        'created_at' => current_time('mysql'), 
                        'product_id'=>$item->get_product_id(),
                        'order_id' => $orderid, 
                        'product_name' => preg_replace("/\'s+/", "", $item->get_name()), 
                        'sku' => $productdata->get_sku(),
                        'quantity' =>  $item->get_quantity(),
                        'user_id' => $seller->ID, 
                        'shipquantity' => 0,
                        'price'=> $productdata->get_price()
                    ) 
                ); 
            }
          
        }
        $products = $wpdb->get_results("SELECT * FROM $table_name WHERE order_id = '$orderid' ",OBJECT);

        //Customer
        $shippingOrder = new WC_Order($orderid);
        $shippingOrder->update_status("wc-ready-to-ship",'Ready to ship', TRUE); 
        $shipping_address = $shippingOrder->get_address('billing'); 
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
                  $allProductNames = $shipProduct->product_name."(". round($shipProduct->shipquantity)  .")";
           }else{
               $allProductNames = $allProductNames .",". $shipProduct->product_name."(". round($shipProduct->shipquantity) .")";
           }
         }
        $productNames = "Total item(s)-". count($shipProducts) ." Products - " .$allProductNames;
        
  

        //Get Authentciation
        $seller = wp_get_current_user();
        $vendor_data = get_user_meta($seller->ID);
        $store_info = dokan_get_store_info( $seller->ID );
        $store_address = $store_info['address']['street_1'];
        $store_city = $store_info['address']['city'];
        $pickupAddress = $store_address .', '. $store_city;
        $vendorphone = $store_info['phone'];
        $custPhone =  $order->get_billing_phone();
         $orderid = $_POST['order'] ;
         $customer_city = $order->get_billing_city();
         //send order
        $feedback = bookOrder($carrier,$vendor_data,$shipping_address, $productNames,$pickupAddress,$vendorphone,$custPhone, $store_city,$customer_city );
        
         if ($feedback['ResponseCode'] == 100) {
            $dellyman_orderid = $feedback['OrderID'];
            $Reference = $feedback['Reference'];
            //Insert into delivery orders in table
            global $wpdb;
            $table_name = $wpdb->prefix . "woocommerce_dellyman_orders";
            $wpdb->insert( 
                $table_name, 
                array( 
                    'time' => current_time('mysql'), 
                    'order_id' => $orderid,
                    'reference_id' => $Reference,
                    'dellyman_order_id' =>$dellyman_orderid,
                    'user_id' => $seller->ID, 
                ) 
            );
            //insert into shipped products
            foreach ($shipProducts as $key => $item) {
                global $wpdb;
                $table_name = $wpdb->prefix . "woocommerce_dellyman_shipped_products";
                $wpdb->insert( 
                    $table_name, 
                    array( 
                        'created_at' => current_time('mysql'), 
                        'product_id'=>$item->product_id,
                        'order_id' => $orderid, 
                        'product_name' => $item->product_name, 
                        'dellyman_order_id' =>$dellyman_orderid,
                        'sku' => $item->sku,
                        'quantity' =>  $item->shipquantity,
                        'price'=> $item->price
                    ) 
                ); 
            }

            //Update product
            global $wpdb;
            $table_name = $wpdb->prefix . "woocommerce_dellyman_products"; 
            $products = $wpdb->get_results("SELECT * FROM $table_name WHERE order_id = '$orderid'",ARRAY_A);
    
            foreach ($shipProducts as $key => $ShipProduct) {
                $mainkey = array_search($ShipProduct->id,array_column($products,'id'));
                $updatedQty = $products[$mainkey]['quantity'] - $ShipProduct->shipquantity;
                global $wpdb;
                $table_name = $wpdb->prefix . "woocommerce_dellyman_products"; 
                //Update
                $dbData = array(
                    'quantity' => $updatedQty, 
                    'shipquantity' => $ShipProduct->shipquantity
                );
                $wpdb->update($table_name, $dbData, array('id' => $shipProduct->id)); 
            }

            //Updating products
            //Update product
            global $wpdb;
            $table_name = $wpdb->prefix . "woocommerce_dellyman_products"; 
            $products = $wpdb->get_results("SELECT * FROM $table_name WHERE order_id = '$orderid'",ARRAY_A);
            $allQuantity = 0;
            foreach ($products as $key => $product) {
                $allQuantity = $allQuantity + $product['quantity'];
            }
            
            if ($allQuantity < 1) {
                //Change Status
                $order = new WC_Order($orderid);
                $order->update_status("wc-fully-shipped", 'Order moved to fully shipped', FALSE); 
            }else {
                //Change Status
                $order = new WC_Order($orderid);
                $order->update_status("wc-partially-shipped",'Order moved to partially shipped', FALSE); 
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
  register_post_status( 'wc-partially-deliver', array(
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

      if ('wc-completed' === $key ) {
          $new_order_statuses['wc-ready-to-ship'] = 'Ready to ship';
          $new_order_statuses['wc-partially-shipped'] = 'Partially shipped';
          $new_order_statuses['wc-partially-deliver'] = 'Partially Delivered';
          $new_order_statuses['wc-fully-shipped'] = 'Fully Shipped';
          $new_order_statuses['wc-fully-delivered'] = 'Fully Delivered';
      }
    
  }

  return $new_order_statuses;
}
add_filter( 'wc_order_statuses', 'add_dellyman_custom_order_statuses' );


// Webhook
function change_status_order(WP_REST_Request $request) {
    // In practice this function would fetch the desired data. Here we are just making stuff up.
    $key  = $request->get_header('X-Dellyman-Signature');
    error_log($key);
    global $wpdb;
    $table_name = $wpdb->prefix . "woocommerce_dellyman_credentials"; 
    $user = $wpdb->get_row("SELECT * FROM $table_name WHERE id = 1");
    $Web_HookSecret =  (!empty($user->Web_HookSecret)) ? $user->Web_HookSecret : ''; 
    $myKey = hash_hmac('sha256', urldecode($request->get_body()), $Web_HookSecret);
  
     
    if($key == $myKey){
        //Move order to deliver
        global $wpdb;
        $table_name = $wpdb->prefix . "woocommerce_dellyman_orders"; 
        $body = json_decode(urldecode($request->get_body()),true);
        error_log(urldecode($request->get_body()));
        $orderID = $body['order']['OrderID'];
        $order = $wpdb->get_row("SELECT * FROM $table_name WHERE dellyman_order_id = ". $orderID);

        if($body['order']['OrderStatus'] == "COMPLETED"){
            //Get order to 
            $table_name = $wpdb->prefix . "wc_order_stats"; 
            $order = $wpdb->get_row("SELECT * FROM $table_name WHERE order_id = ". $order->order_id);
            if($order->status == "wc-partially-shipped"){
                $order = new WC_Order($order->order_id);
                $order->update_status("wc-partially-deliver", 'Order moved to partially delivered', FALSE); 
            }elseif($order->status == "wc-fully-shipped"){
                $order = new WC_Order($order->order_id);
                $order->update_status("wc-fully-delivered", 'Order moveed to fully delivered', FALSE); 
            }
        }elseif($body['order']['OrderStatus'] == "CANCELLED"){
            //Track Back
            //Get Products 
            global $wpdb;
            $table_name = $wpdb->prefix . "woocommerce_dellyman_products"; 
            $products = $wpdb->get_results("SELECT * FROM $table_name WHERE order_id = '$order->order_id'",OBJECT);
            global $wpdb;
            $table_name = $wpdb->prefix . "woocommerce_dellyman_shipped_products"; 
            $products_shipped = $wpdb->get_results("SELECT * FROM $table_name WHERE order_id = '$order->order_id'",ARRAY_A);

                
            foreach ($products_shipped as $mainkey => $item) {

                $key = array_search($item ->id,array_column($products,'id') );
                $quantity = $products[$key]['quantity'] + $item->quantity;
                $ship_quantity = $products[$key]['shipquantity'] - $item->quantity;

                global $wpdb;
                $table_name = $wpdb->prefix . "woocommerce_dellyman_products"; 
                //Update
                $dbData = array(
                    'quantity' => $quantity, 
                    'shipquantity' => $ship_quantity
                );
                $wpdb->update($table_name, $dbData, array('id' => $products[$key]['id'])); 
            }
 
                
            //Update is TrackBack to 1
            global $wpdb;
            $table_name = $wpdb->prefix . "woocommerce_dellyman_orders"; 
            $dbData = array(
                'is_TrackBack' => 1
            );
            $wpdb->update($table_name, $dbData, array('dellyman_order_id' => $body['order']['OrderCode'], 'order_id' => $order->order_id));   
        }
    }else{
        //echo "Not from dellyman";
    }

}
// * This function is where we register our routes for our example endpoint.
// */
function prefix_register_product_routes() {
   //Here we are registering our route for a collection of products and creation of products.
   register_rest_route( 'api', '/dellyman-webhook', array(
           'methods'  => WP_REST_Server::CREATABLE,
           'callback' => 'change_status_order',
           'permission_callback' => '__return_true'
    ));
}
add_action( 'rest_api_init', 'prefix_register_product_routes' );


    add_filter( 'dokan_query_var_filter', 'dokan_load_document_menu' );
    function dokan_load_document_menu( $query_vars ) {
        $query_vars['help'] = 'request-for-shipping';
        return $query_vars;
    }
    add_filter( 'dokan_get_dashboard_nav', 'dokan_add_help_menu' );
    function dokan_add_help_menu( $urls ) {
        $urls['help'] = array(
            'title' => __('Request for shipping', 'dokan'),
            'icon'  => '<i class="fas fa-truck"></i>',
            'url'   => dokan_get_navigation_url( 'request-for-shipping' ),
            'pos'   => 51
        );
        return $urls;
    }
    add_action( 'dokan_load_custom_template', 'dokan_load_template' );
    function dokan_load_template( $query_vars ) {
        if ( isset($query_vars['request-for-shipping'] ) ) {
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
            require_once ( 'includes/delivery_details.php');
        }
    }

        /**
     * Add new custom status for WC order statuses
     *
     * @param array $order_statuses
     *
     * @return array $order_statuses
     */
    function dokan_add_new_custom_order_status( $order_statuses ) {

        $order_statuses[ 'wc-ready-to-ship' ]           = _x( 'Ready to ship', 'Order status', 'text_domain' );
        $order_statuses[ 'wc-partially-shipped' ]   = _x( 'Partially shipped', 'Order status', 'text_domain' );
        $order_statuses[ 'wc-partially-deliver' ] = _x( 'Partially Delivered', 'Order status', 'text_domain' );
        $order_statuses[ 'wc-fully-shipped' ] = _x( 'Fully Shipped', 'Order status', 'text_domain' );
        $order_statuses[ 'wc-fully-delivered' ] = _x( 'Fully Delivered', 'Order status', 'text_domain' );
        
        return $order_statuses;
    }

    add_filter( 'wc_order_statuses', 'dokan_add_new_custom_order_status', 12, 1 );

    /**
     * Add new custom status button class on order status
     *
     * @param string $text
     * @param string $status
     *
     * @return string $text
     */
    function dokan_add_custom_order_status_button_class( $text, $status ) {
        switch ( $status ) {
            case 'wc-ready-to-ship':
            case 'ready-to-ship':
            case 'wc-partially-shipped':
            case 'partially-shipped':
            case 'wc-partially-deliver':
            case 'partially-deliver':
            case 'wc-fully-shipped':
            case 'fully-shipped':
            case 'wc-fully-delivered':
            case 'fully-delivered':
                $text = 'success';
            break;        
        }    
        return $text;
    }
    add_filter( 'dokan_get_order_status_class', 'dokan_add_custom_order_status_button_class', 10, 2 );


    /**
     * Custom order status translated
     *
     * @param string $text
     * @param string $status
     *
     * @return string $text
     */
    function dokan_add_custom_order_status_translated( $text, $status ) {
        switch ( $status ) {
            case 'wc-ready-to-ship':
            case 'ready-to-ship':
                $text = __( 'Ready to Ship', 'text_domain' );
                break;
            case 'wc-partially-shipped':
            case 'partially-shipped':
                $text = __( 'Partially shipped', 'text_domain' );
                break;
            case 'wc-partially-deliver':
            case 'partially-deliver':
                $text = __( 'Partially delivered', 'text_domain' );
                break; 
            case 'wc-fully-shipped':
            case 'fully-shipped':
                $text = __( 'Fully Shipped', 'text_domain' );
                break; 
            case 'wc-fully-delivered':
            case 'fully-delivered':
                $text = __( 'Fully Delivered', 'text_domain' );
                break;         
        }    
        return $text;
    }
    add_filter( 'dokan_get_order_status_translated', 'dokan_add_custom_order_status_translated', 10, 2 );
    
?>