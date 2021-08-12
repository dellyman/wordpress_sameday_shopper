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
            $table_name = $wpdb->prefix . "dellyman_ship_products_status"; 
            $orders = $wpdb->get_results("SELECT * FROM $table_name WHERE user_id = '$seller->ID' ",OBJECT);
             global $wpdb;
            $table_name = $wpdb->prefix . "dellyman_user"; 
            $user = $wpdb->get_results("SELECT * FROM $table_name ",OBJECT);
            $user = $user[0];

            //Get Authentciation
            $sameday=getAuth($user->email,$user->password);
            $CustomerID = $sameday['id'];
            $CustomerAuth = $sameday['auth'];
            $statusOrders = [];
            foreach ($orders as $key => $order) {
                $dellyman_order = intval($order->dellyman_order_id);
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
                        "CustomerID" => $CustomerID,
                        "CustomerAuth" => $CustomerAuth,
                        "OrderID" => $dellyman_order
                ]),
                CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json"
                )));
                $response = curl_exec($curl);
                $status = json_decode($response,true);
                curl_close($curl);
                //print_r($status);
                // echo $status['OrderStatus'];
                $statusOrder = [
                    'order_id' => $order->order_id,
                    'products_shipped' => $order->products_shipped,
                    'reference_id' =>$order->reference_id,
                    'created_at' =>$order->time,
                    'status' => $status['OrderStatus']
                ];
                orderTrackBack($order->dellyman_order_id,$order->order_id,);
                array_push($statusOrders,$statusOrder);

            }?>
     <?php require_once('css/style.php') ?>
        <article class="status-content-area">
        	<h1>Delivery status</h1>
        <p>Check the status of the products you've shipped </p>
        <div style="overflow-x:auto;">
            <table>
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
                    <?php if (empty($statusOrders)) {?>
                        <tr>
                            <td colspan="5" style="text-align:center;padding: 20px;">No deliveries sent </td>
                        </tr>   
                    <?php }else{ ?>
                        <?php foreach ($statusOrders as $key => $statusOrder) {?>
                            <tr>
                                <td><?php echo $statusOrder['order_id']; ?></td>
                                <td class="text-small"><?php
                                $products = json_decode($statusOrder['products_shipped'],true);
                                $productlist = "";
                                foreach ($products as $key => $product) {
                                    if ($key == 0) {
                                        $productlist = $product['productName'].'-'.$product['sku'].' - x '.round($product['shipquantity']).' Qty <br>';
                                    }else{
                                        $productlist = $productlist .  $product['productName'].'-'.$product['sku'].' - x '.round($product['shipquantity']).' Qty <br>';
                                    }
                                }
                                echo $productlist;
                                ?></td>
                                <td><?php echo $statusOrder['reference_id']; ?></td>
                                <td><?php echo date("F j, Y, g:i a",strtotime($statusOrder['created_at'])); ?></td>
                                <td><?php echo $statusOrder['status']; ?></td>
                            </tr>    
                        <?php } ?>   
                    <?php } ?>                      
                </tbody>           
            </table>
        </div>
   
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