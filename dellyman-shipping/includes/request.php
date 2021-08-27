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
            $table_name = $wpdb->prefix . "dokan_orders"; 
            $orders = $wpdb->get_results("SELECT * FROM $table_name WHERE seller_id = '$seller->ID' AND (order_status = 'wc-processing' OR order_status = 'wc-dellyman') ");
            $orders = json_decode(json_encode($orders),true);
            $curl = curl_init();
            curl_setopt_array($curl, array(
            CURLOPT_URL => 'http://206.189.199.89/api/v2.0/Vehicles',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
            ));
            $response = curl_exec($curl);
            curl_close($curl);
            $carriers = json_decode($response,true);
            if (empty($carriers)) {
            $carriers = [];
            }
         ?>
        <?php require_once('css/style.php') ?>
        <article class="help-content-area">
   <form action="" id="send-request" method="post">
        	<h1>Request for pick-up</h1>
              <div ></div>
          	<div class="section mt-3">
                  <h5 class="m-0" >Step 1: Select order</h5>
                    Only orders with the payment status of PAID and fulfulment status of AWAITING PROCESSING or PROCESSING will be listed below.
                    <div class="mt-3">
                        <label for="orders">Select Order</label>
                        <select name="order" id="order" placeholder="Select Orders">
                                <option value="order" >Select Order</option>
                                    <?php foreach ($orders as $key => $order) {?>
                                          <option value="<?php echo $order['order_id'] ?>" ><?php echo $order['order_id'] ?></option>
                                    <?php } ?>
                        </select>
                    </div>
            </div>

            <div class="section mt-3 mute" >
                <h5  class="m-0"> Step 2: Pick product(s) from the order to ship</h5>
                <div id='products'>Select an order above, to enable you pick product(s) to ship.</div>
            </div>
            <div class="section mt-3  mute " >
               <h5 class="m-0"> Step:3 Select a carrier</h5>
               <div class="mt-3">
                <label for="vechicle"> Select carrier</label>
                 <select  name="carrier" id="carrier" placeholder="Select Orders" disabled>
                        <option value="carrier">Select Carrier</option>      
                        <?php foreach ($carriers as $key => $carrier) {?>
                            <option value="<?php echo $carrier['Name'] ?>"><?php echo $carrier['Name'] ?></option> 
                            <?php } ?> 
              </select>
            </div>
            </div>
            
            <div class="mt-3">
                <button  type="button" class="btn-same-day"  id="submit" onclick="confirm('confirm')"  disabled> Send for pick-up</button>
             </div>
          </form>
             <div class="backdrop" id="overlay">
                <div class="loader">
                    <div class="d-block">
                        <div class="loader-text">
                            <div class="lds-roller"><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div>
                            </div>
                        </div>
                        <div class="loader-text"> Requesting for pick-up</div>		
                    </div>															
                </div>
                <div class="modal" id="modal" >
                    <div class="modal-info text-center" id="modal-info">
                        <h1>Are you sure?</h1>
                        <p>You want to ship these items</p>
                        <button class="btn btn-primary btn-medium" name="send" value="send" form="send-request">Yes</button>
                        <button class="btn btn-danger btn-medium"onclick="confirm()">No</button>
                        <div class="actions">
                        </div>														
                    </div>
                     <div class="modal-info text-center" id="modal-message">
                        <div class="big-text" id="message"></div>
                        <button class="btn btn-danger btn-medium"onclick="confirm()">OK</button>
                        <div class="actions">
                        </div>														
                    </div>																	
                </div>
            </div> 
         <?php
                wp_register_script( 'request_script', plugins_url('/js/index.js', __FILE__), array('jquery'));
                wp_enqueue_script( 'request_script' );
                wp_localize_script( 'request_script', 'frontendajax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' )));
          //require_once('js/index.php') 
          ?> 
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