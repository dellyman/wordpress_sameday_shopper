<?php
/**
 *  Dokan Dashboard Template
 *
 *  Dokan Main Dahsboard template for Fron-end
 *
 *  @since 2.4
 *
 *  @package dokan
 */
?>
<div class="dokan-dashboard-wrap">
    <?php
        /**
         *  dokan_dashboard_content_before hook
         *
         *  @hooked get_dashboard_side_navigation
         *
         *  @since 2.4
         */
        do_action( 'dokan_dashboard_content_before' );
    ?>

    <div class="dokan-dashboard-content">

        <?php
            /**
             *  dokan_dashboard_content_before hook
             *
             *  @hooked show_seller_dashboard_notice
             *
             *  @since 2.4
             */
            do_action( 'dokan_help_content_inside_before' );
        ?>
        <?php
           $seller = wp_get_current_user(); 
            global $wpdb;
            $table_name = $wpdb->prefix . "woocommerce_dellyman_orders"; 
            $orders = $wpdb->get_results("SELECT * FROM $table_name WHERE user_id = '$seller->ID' ORDER BY time DESC",OBJECT);
        ?>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.0/css/jquery.dataTables.min.css">
  
    <?php require_once('css/style.php') ?>
        <article class="status-content-area">
        	<h1>Delivery status</h1>
        <p>Check the status of the products you've shipped </p>
        <div style="overflow-x:auto;">
            <table id="status">
                <thead>
                    <tr>
                        <th>Order Id</th>
                        <th>Products</th>
                        <th>Reference ID</th>
                        <th>Shipped Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)) {?>
                        <tr>
                            <td colspan="5" style="text-align:center;padding: 20px;">No deliveries sent </td>
                        </tr>   
                    <?php }else{ ?>
                        <?php foreach ($orders as $key => $order) {?>
                            <tr>
                                <td>#<?php echo $order->order_id ?></td>
                                <td class="text-small"><?php
                                    $orderid = $order->dellyman_order_id;
                                    global $wpdb;
                                    $table_name = $wpdb->prefix . "woocommerce_dellyman_shipped_products"; 
                                    $products = $wpdb->get_results("SELECT * FROM $table_name WHERE dellyman_order_id = '$orderid'",OBJECT);
                                    $allProductNames = "";
                                    foreach ($products as $key => $shipProduct) {
                                        if ($key == 0) {
                                                $allProductNames = $shipProduct->product_name."(". round($shipProduct->quantity)  .")";
                                        }else{
                                            $allProductNames = $allProductNames .",". $shipProduct->product_name."(". round($shipProduct->quantity) .")";
                                        }
                                    }
                                    $productNames = "Total item(s)-". count($products) ." Products - " .$allProductNames;
                                    echo $productNames; 
                                ?></td>
                                <td><?php echo $order->reference_id; ?></td>
                                <td><?php echo date("F j, Y, g:i a",strtotime($order->time)); ?></td>
                                <td><?php 
                                        global $wpdb;
                                        $table_name = $wpdb->prefix . "woocommerce_dellyman_credentials"; 
                                        $user = $wpdb->get_row("SELECT * FROM $table_name WHERE id = 1");
                                        $ApiKey =  (!empty($user->API_KEY)) ? $user->API_KEY : '';                                        
                                        $response = wp_remote_post( 'https://dev.dellyman.com/api/v3.0/TrackOrder', array(
                                            'body'    => json_encode([
                                                'OrderID' => intval($order->dellyman_order_id)
                                            ]),
                                            'headers' => [
                                                'Authorization' => 'Bearer '. $ApiKey,
                                                'Content-Type' =>  'application/json'
                                            ]
                                        ));
                                        $status = json_decode(wp_remote_retrieve_body($response),true);
                                        echo $status['OrderStatus'];
                                ?></td>
                            </tr>    
                        <?php } ?>   
                    <?php } ?>                      
                </tbody>           
            </table>
        </div>
     <script src="https://cdn.datatables.net/1.11.0/js/jquery.dataTables.min.js"></script>
     <script>
          jQuery(document).ready( function () {
               jQuery('#status').DataTable({
                columnDefs: [ {bSortable: false, targets: [1]} ],
                 "order": [[ 1, "desc" ]]              
                });
            } );
     </script>
        </article><!-- .dashboard-content-area -->

         <?php
            /**
             *  dokan_dashboard_content_inside_after hook
             *
             *  @since 2.4
             */
            do_action( 'dokan_dashboard_content_inside_after' );
        ?>


    </div><!-- .dokan-dashboard-content -->

    <?php
        /**
         *  dokan_dashboard_content_after hook
         *
         *  @since 2.4
         */
        do_action( 'dokan_dashboard_content_after' );
    ?>

</div><!-- .dokan-dashboard-wrap -->