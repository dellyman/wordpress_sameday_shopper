<?php
/*
Plugin Name:  Sameday Shopper Dellyman
Plugin URI:		https://www.dellyman.com
Description:	Dellyman Plugin for Sameday shopper to tigger delievry and payment options for vendors
Version:		1.0.0
Author:			Ajidagba Ayobami
Author URI:		https://renovservices.net/
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
             $this-> createDB();
               
        }
        function deactivatePlugin(){

        }
        function addMenu(){
                add_menu_page('Delivery Status', 'Sameday Shopper', 'manage_options', 'sameday-shopper-dellyman', 'index_page',plugins_url( 'dellyman-shipping/assets/svg/icon.svg' ),4);
                add_submenu_page( 'sameday-shopper-dellyman', 'Connect to Dellyman', 'Connect to Dellyman', 'manage_options', 'connect-to-dellyman', 'login_page');
            
            }
        function createDB(){
            global $wpdb;
             $table_name = $wpdb->prefix . "dellyman_user"; 
             $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id int(10) NOT NULL AUTO_INCREMENT,
            time datetime DEFAULT CURRENT_TIMESTAMP,
            user_id int(10) NOT NULL,
            email varchar(255) DEFAULT '' NOT NULL,
            password varchar(255) DEFAULT '' NOT NULL,
            PRIMARY KEY  (id)
            ) $charset_collate;";
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );    
            dbDelta( $sql );

  
            //Creating table that store status  
            global $wpdb;
            $table_name = $wpdb->prefix . "dellyman_ship_products_status"; 
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id int(10) NOT NULL AUTO_INCREMENT,
            time datetime DEFAULT CURRENT_TIMESTAMP,
            order_id varchar(255) DEFAULT '' NOT NULL,
            products_shipped longtext DEFAULT '' NOT NULL,
            user_id int(10) NOT NULL,
            dellyman_order_id int(10) NOT NULL,
            is_TrackBack boolean DEFAULT 0 NOT NULL,
            reference_id varchar(255) DEFAULT '' NOT NULL,
            PRIMARY KEY  (id)
            ) $charset_collate;";
            require_once( ABSPATH .'wp-admin/includes/upgrade.php' );
            dbDelta( $sql );   
            //Creating table that store tables
            global $wpdb;
            $table_name = $wpdb->prefix . "dellyman_ship_products"; 
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            order_id varchar(255) DEFAULT '' NOT NULL,
            user_id int(10) NOT NULL,
            products longtext DEFAULT '' NOT NULL,
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
        include_once('includes/login.php');
}
function login_page() {
        include_once('includes/login.php');
}

add_action('admin_post_login_crendentials','save_crendentials');

function save_crendentials(){;
    extract($_REQUEST);
    $auth = getAuth($email,$password);
   if ($auth == 'invaild') {
       $redirect = add_query_arg( 'status', 'error', 'admin.php?page=connect-to-dellyman');
        wp_redirect( $redirect );
        exit;
   }else{
       global $wpdb;
        $table_name = $wpdb->prefix . "dellyman_user"; 
        $user = $wpdb->get_results("SELECT * FROM $table_name WHERE user_id = '$user_id' ",OBJECT);
        if (empty($user)) {
            //Insert in the database
            $wpdb->insert( 
                $table_name, 
                array( 
                    'time' => current_time( 'mysql' ), 
                    'email' => $email, 
                    'password' => $password, 
                    'user_id' => $user_id, 
                ) 
            );
        }else{
            //Update
            $dbData = array(
                'email' =>$email,
                'password' => $password,
                'time' => current_time( 'mysql' )
            );
            $wpdb->update($table_name, $dbData, array('user_id' => $user_id)); 
        }
        $redirect = add_query_arg( 'status', 'success', 'admin.php?page=connect-to-dellyman');
        wp_redirect( $redirect );
        exit;
   }
}

function getAuth($email,$password){
    $ch = curl_init();
    $url = "http://206.189.199.89/api/v2.0/Login";
    curl_setopt($ch, CURLOPT_URL,  $url);
    curl_setopt($ch, CURLOPT_ENCODING, " ");
    curl_setopt($ch,  CURLOPT_TIMEOUT,  0);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
    // curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Content-Type: application/json"
    ));
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array(
         "Email" => $email,
        "Password"=> $password
    )));
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2TLS);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    $response = curl_exec($ch);

    $err = curl_error($ch);

    curl_close($ch);
    //echo $response;
    $data =  json_decode($response,true);

    if ($data['ResponseCode'] == 100) {
         return  ['auth'=>$data['CustomerAuth'], 'id'=>$data['CustomerID']];
    }else {
        return "invaild";
    }    
}
function bookOrder($sameday,$carrier,$vendor_data,$shipping_address, $productNames,$pickupAddress,$vendorphone,$custPhone){
        $vendorName = $vendor_data["first_name"][0] .'  '.$vendor_data["last_name"][0];
        $phoneNumber = $vendor_data['billing_address_1'];
        $deliveredName = $shipping_address['first_name'] ." ". $shipping_address['last_name'];
        //booking order
        $date =  date("d/m/Y");
        $postdata = array( 
            'CustomerID' => $sameday['id'],
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
            'CustomerAuth' => $sameday['auth']
        );
        $jsonPostData = json_encode($postdata);
        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL => 'http://206.189.199.89/api/v2.0/BookOrder',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $jsonPostData,
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json'
        ),
        ));
        $responseJson = curl_exec($curl);
        $response = json_decode($responseJson,true);
        return  $response;

}
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
                    'productName'=>$item->get_name(),
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
        $table_name = $wpdb->prefix . "dellyman_ship_products_status"; 
        $shipedorders = $wpdb->get_results("SELECT * FROM $table_name WHERE order_id = '$orderid' ",OBJECT);
        foreach ($shipedorders as $key => $shipedorder) {
            $dellyman_orderid = $shipProduct->dellyman_order_id;
            orderTrackBack($dellyman_orderid,$orderid);
        }
        global $wpdb;
        $table_name = $wpdb->prefix . "dellyman_ship_products"; 
        $user = $wpdb->get_results("SELECT * FROM $table_name WHERE order_id = '$orderid' ",OBJECT);
        $user = $user[0];
        $products = json_decode($user->products,true);
        wp_send_json($products);
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
            $shipping_address = $order->get_address('shipping'); 
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
        $sameday=getAuth($user->email,$user->password);
        $seller = wp_get_current_user();
         $vendor_data = get_user_meta($seller->ID);
         $store_info  = dokan_get_store_info($seller->ID);
         $pickupAddress = $store_info['address']['street_1'] .', '. $store_info['address']['city'] . ', '. $store_info['address']['state'] .', '. $store_info['address']['country'];
         $vendorphone = $store_info['phone'];
         $custPhone =    $order->get_billing_phone();
         $orderid = $_POST['order'] ;
         //send order
         $feedback = bookOrder($sameday,$carrier,$vendor_data,$shipping_address, $productNames,$pickupAddress,$vendorphone,$custPhone);
        
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
            global $wpdb;
            $table_name = $wpdb->prefix . "dellyman_ship_products";
            $dbData = array(
                'products' =>json_encode($products)
            );
            $wpdb->update($table_name, $dbData, array('user_id' => $seller->ID, 'order_id' => $orderid)); 
            
            //Change Status
            global $wpdb;
            $table_name = $wpdb->prefix . "dokan_orders"; 
            $dbData = array(
                'order_status' =>'wc-dellyman'
            );
            $wpdb->update($table_name, $dbData, array('seller_id' => $seller->ID, 'order_id' =>$orderid)); 
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

function orderTrackBack($dellyman_orderid,$orderid){
    //Get email and password
    global $wpdb;
    $table_name = $wpdb->prefix . "dellyman_user"; 
    $user = $wpdb->get_results("SELECT * FROM $table_name ",OBJECT);
    $user = $user[0];
    $sameday=getAuth($user->email,$user->password);
    $curl = curl_init();
    curl_setopt_array($curl, array(
    CURLOPT_URL => 'http://206.189.199.89/api/v2.0/TrackOrder',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => json_encode([
            "CustomerID" => $sameday['id'],
            "CustomerAuth" => $sameday['auth'],
            "OrderID" => intval($dellyman_orderid)
    ]),
    CURLOPT_HTTPHEADER => array(
    "Content-Type: application/json"
    )));
    $response = curl_exec($curl);
    $status = json_decode($response,true);
    curl_close($curl);
      //Getting Orders from Database with id 
        global $wpdb;
        $table_name = $wpdb->prefix . "dellyman_ship_products_status"; 
        $user = $wpdb->get_results("SELECT * FROM $table_name WHERE order_id = '$orderid' ",OBJECT);
        $user = $user[0];
    if ($status['OrderStatus'] == 'CANCELLED' AND $user->is_TrackBack == 0 ) {
         $products_shipped  = json_decode($user->products_shipped,true);
         //Get Orginal Products 
          global $wpdb;
        $table_name = $wpdb->prefix . "dellyman_ship_products"; 
        $orderUpdate = $wpdb->get_results("SELECT * FROM $table_name WHERE order_id = '$orderid' ",OBJECT);
        $orderUpdate  = $orderUpdate [0];
        $productUpdated = json_decode($orderUpdate->products,true);
        
        foreach ($products_shipped as $mainkey => $product_shipped) {
            $key = array_search($product_shipped['id'],array_column($productUpdated,'id') );
            $result = $productUpdated[$key]['quantity'] + $product_shipped['quantity'];
            $productUpdated[$key]['quantity'] = $result;
        }
        $productUpdated = json_encode($productUpdated);
        
        //Change Updated Products Quantites
        global $wpdb;
        $table_name = $wpdb->prefix . "dellyman_ship_products"; 
        $dbData = array(
            'products' =>$productUpdated
        );
        $wpdb->update($table_name, $dbData, array('order_id' =>$orderid)); 
        
        //Update is TrackBack to 1
        global $wpdb;
        $table_name = $wpdb->prefix . "dellyman_ship_products_status"; 
        $dbData = array(
          'is_TrackBack' => 1
        );
        $wpdb->update($table_name, $dbData, array('dellyman_order_id' => $dellyman_orderid, 'order_id' =>$orderid));      
    }
}
?>