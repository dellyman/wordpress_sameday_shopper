<?php include_once('css/style.php') ?>

<?php
    global $wpdb;
    $table_name = $wpdb->prefix . "woocommerce_dellyman_credentials"; 
    $user = $wpdb->get_row("SELECT * FROM $table_name WHERE id = 1");
    $ApiKey =  (!empty($user->API_KEY)) ? ($user->API_KEY) : ('');
    $Web_HookSecret =  (!empty($user->Web_HookSecret)) ? ($user->Web_HookSecret) : ('');
    $webhook_url =  (!empty($user->webhook_url)) ? ($user->webhook_url) : ('');      
?>
<h1><?php echo esc_html(get_admin_page_title()); ?></h1>
<?php if ( filter_input( INPUT_GET, 'status' ) === 'success' ) : ?>

    <p class="sucess"  > Successfully saved</p>

<?php endif ?>
<?php if ( filter_input( INPUT_GET, 'status' ) === 'error' ) : ?>

  <p class="error" >Invaild Credentials</p>  

<?php endif ?>
<div class="page-card">
    <p>Here you can place your API Credentials and set web hook url <a href="https://dellyman.com/settings">Find them here</a>. To be able send orders from your e-commerce website to our server.</p>
    <form action='admin-post.php' method="post">
        <div class="input">
            <label for="apiKey" class="label" >API Key</label>
            <input type="text" class="input-text" name="apiKey" id="apiKey" value = "<?php echo $ApiKey; ?>" >
        </div>
        <div class="input">
            <label for="webhook-seceret" class="label" >Web-Hook secret</label>
            <input type="text" class="input-text" name="webhookSecret" id="webhookSecret" value = "<?php echo $Web_HookSecret; ?>">
        </div>
        <div class="input">
            <label for="webhook-url" class="label" >Web-Hook Url</label>
            <input type="text" class="input-text" value="<?php echo get_site_url().'/wp-json/api/dellyman-webhook' ?>" disabled name="webhookUrl" id="webhookUrl">
        </div>
        <input name='action' type="hidden" value='login_credentials'>
        <button class="btn-same-day" >Save Credentials</button>
    </form>
    <p>Copy this link <strong><?php echo get_site_url().'/wp-json/api/dellyman-webhook' ?></strong> to webhook url in your dellyman dashbord </p>
</div>

