
    jQuery(document).ready(function( ){
         jQuery("#order").change(function () {
             let orderID = jQuery("#order").val();
             if (!orderID || orderID == 'order') {
                jQuery(".section").addClass("mute");
                jQuery(".section").first().removeClass("mute");
                jQuery("#products").html("<span>Select an order first load in products in the order selected</span>");
                jQuery("#carrier").attr("disabled", true);
             }else{
                 jQuery(".section").removeClass("mute");
                 jQuery("#products").html("");
                 jQuery("#products").css("margin-bottom", "15px")
                 jQuery("#products").append("Loading products.....");
                 jQuery.ajax({
                     url: frontendajax.ajaxurl, // Since WP 2.8 ajaxurl is always defined and points to admin-ajax.php
                     data: {
                         'action': 'get_products_ajax_request',
                         'orderid': orderID
                     },
                     success: function (data) {
                         // This outputs the result of the ajax request (The Callback)
                         countTable = `<div class="d-flex justify-between ">
                           <p><strong> ${data.products.length}  ${ (data.products.length > 1) ? 'items' : 'item'} in the order</strong> ships to ${data.customerdata.address.address_1} ${ data.customerdata.address.city }</p>
                           <div>
                            <p class="m-0"><strong>Customer Name</strong> : ${data.customerdata.name}</p>
                            <p class="m-0"><strong>Customer Tel No</strong> : ${data.customerdata.customerPhone}</p>
                           </div>  
                         </div>`;
                         let table = ' ';
                         for (let i = 0;  i < data.products.length;  i++) {
                             const element = data.products[i];
                             stringOrder = JSON.stringify(element);
                             let toogle = `<div class='switch mt-3' >
                                                                <input  class='switch-input' id='switch-${i}' type='checkbox' name='products[]'  onclick='changeQty(${i},${element.quantity})'  value='${stringOrder}'/>
                                                                <label for="switch-${i}" class="switch-label"></label>
                                                        </div>`;
                             if (element.quantity <= 0 ) {
                                 toogle = `<div class="text-success mt-3">All items have been shipped</div>`
                             }
                                table += `<tr>
                                                        <td id="item-${i}">${ element.product_name }<br>${toogle}
                                                        </td>
                                                         <td>${ element.sku }</td>
                                                        <td>${element.quantity }</td>
                                                        <td>&#8358; ${ element.price}</td>
                                                        <td>&#8358; ${ element.price * element.quantity }</td>
                                                </tr>`
                             
                         }
                         let template = `
                         ${countTable}
                         <table>
                            <thead>
                            <tr> 
                            <td>Name</td>
                            <td>SKU</td>
                            <td>Quantity</td>
                            <td>Unit Price</td>
                            <td>Total Amount</td>
                            </tr>
                            </thead>
                            <tbody>${table}</tbody></table>`;
                         jQuery("#products").html(template);
                     },
                     error: function (errorThrown) {
                       console.log(errorThrown);
                     }
                 });

             }

         });
});

function changeQty(key, qty) {
        let checkbox = jQuery("#switch-" + key);
        if (checkbox.is(':checked')) {
            let input = `
            <div class="mt-3" id="div-${key}">
                <input type="number" class="form-input" min="1" max="${qty}" placeholder="Quantity" id="input-${key}"/>
                <button type="button" onclick="update(${key},${qty})" class="btn" id="update-${key}">Update</button>
                <p id=error-${key}></p>
            </div>`
            jQuery("#item-" + key).append(input);
        } else {
            jQuery('#div-' + key).remove();
            jQuery('#remQty-' + key).remove();
            let ProductInfo = JSON.parse(jQuery("#switch-" + key).val());
            ProductInfo.shipquantity = 0;
            let productString = JSON.stringify(ProductInfo)
            jQuery("#switch-" + key).val(productString);
        }
}

function update(key, qty) {
        let shippedQty = parseInt(jQuery("#input-" + key).val());
        if (shippedQty == 0 || shippedQty == "") {
            jQuery("#error-" + key).html('<div class="text-error">Quantity cannot be empty</div>');
        }
        else if (shippedQty < 0) {
            jQuery("#error-" + key).html('<div class="text-error">Quantity cannot be negative</div>');
        }
        else if (isNaN(shippedQty)) {
            jQuery("#error-" + key).html('<div class="text-error">Only integers are accepted </div>');
        }
        else if (shippedQty > qty) {
            jQuery("#error-" + key).html('<div class="text-error">Shipping quantity is more than order quantity</div>');
        } else {
            remainingQty = qty - shippedQty;
            let feedback = '<p  id="remQty-' + key + '"  class="text-success" >' + shippedQty + ' quantity(es) will be shipped</p>';
            let ProductInfo = JSON.parse(jQuery("#switch-" + key).val());
            ProductInfo.shipquantity = parseInt(shippedQty);
            let productString = JSON.stringify(ProductInfo);
            jQuery("#switch-" + key).val(productString);
            jQuery('#div-' + key).remove();
            jQuery("#item-" + key).append(feedback);
        }
}

jQuery(document).on("mousemove touchstart touchend", function () {
    let order = jQuery("#order").val();
    let checkboxes = new Array();
    let allShippingQty = new Array();
    jQuery("input:checkbox[name='products[]']:checked").each(function () {
        checkboxes.push(jQuery(this).val())
        let shipqty = JSON.parse(jQuery(this).val());
        if (shipqty.shipquantity > 0) {
            allShippingQty.push(shipqty.shipquantity);
        } else { }
    });
    if (checkboxes.length != 0 && allShippingQty.length != 0 && checkboxes.length == allShippingQty.length) {
        jQuery("#carrier").removeAttr("disabled");
    } else {
        jQuery("#carrier").attr("disabled", true);
    }
    let carrier = jQuery("#carrier").val();
    if (order != "order" && checkboxes.length != 0 && allShippingQty.length != 0 && checkboxes.length == allShippingQty.length && carrier != "carrier") {
        jQuery("#submit").removeAttr("disabled");
    } else {
        jQuery("#submit").attr("disabled", true);
    }
});

function confirm(data) {
        if (jQuery('#overlay').css('display') == "none") {
            jQuery("#overlay").css("display", "flex");
            if (data == 'confirm') {
                jQuery('#modal-message').css("display", "none");
                jQuery('#modal-info').css("display", "block");
           }else{
                jQuery('#modal-message').css("display", "block");
                jQuery('#modal-info').css("display", "none");
            }
        } else {
            jQuery('#modal').css("display", "none");
            jQuery("#overlay").css("display", "none");
        }
}

jQuery("#send-request").submit(function (e) {
    e.preventDefault();
    jQuery("#modal").css("display", "none");
    jQuery(".loader").addClass("d-flex");
    //Assignnig variable
    let order = jQuery("#order").val();
    let carrier = jQuery("#carrier").val();
    let checkboxes = new Array();
    jQuery("input:checkbox[name='products[]']:checked").each(function () {
        checkboxes.push(jQuery(this).val())
    });
    let message = jQuery("#message");
    jQuery.ajax({
        url: frontendajax.ajaxurl, // Since WP 2.8 ajaxurl is always defined and points to admin-ajax.php
        type: 'POST',
        data: {
            'action': 'post_products_dellyman_request',
            'order' : order,
            'products':checkboxes,
            'carrier': carrier
        }, 
        success: function(data) { 
            RemoveLoader()
            if (data) {
                confirm()
                if(data.ResponseCode == 100){
                    let successMsg = `Sucessfully sent #${data.orderID} to dellyman, we will be coming for the pickup later in the day. The Delivery ID is ${data.Reference}`;
                    message.html(successMsg);

                }else{
                    message.html(data.ResponseMessage);
                }
            }else{
               message.html("Please try again");
               message.addClass('text-error');
            }   
            // animated top scrolling
            jQuery('body, html').animate({ scrollTop: 0 });
            setTimeout(() => {
                message.empty();
            }, 100000);
        },
        error: function (errorThrown) {
            //RemoveLoader();
        }
    });
});
function RemoveLoader() {
    jQuery(".loader").removeClass("d-flex");
    jQuery("#modal").css("display", "block");
    jQuery("#overlay").css("display", "none");
    jQuery(".section").addClass("mute");
    jQuery("#products").html("<span class='spacing--mr1'>Select an order first load in products in the order selected</span>");
    jQuery("#carrier").attr("disabled", true);
    jQuery("#send-request")[0].reset();
    jQuery(".section").first().removeClass("mute");
}