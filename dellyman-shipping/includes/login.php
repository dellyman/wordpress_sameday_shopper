<?php include_once('css/style.php') ?>

<?php
      $current_user = wp_get_current_user();
       global $wpdb;
        $table_name = $wpdb->prefix . "dellyman_user"; 
        $user = $wpdb->get_results("SELECT * FROM $table_name WHERE user_id = '$current_user->ID' ");
        $user = $user[0];
        $email =  (!empty($user->email)) ? ($user->email) : ('');
        $password =  (!empty($user->password)) ? ($user->password) : ('');
?>
<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
<?php if ( filter_input( INPUT_GET, 'status' ) === 'success' ) : ?>

    <p class="sucess"  > Successful saved</p>

<?php endif ?>
<?php if ( filter_input( INPUT_GET, 'status' ) === 'error' ) : ?>

  <p class="error" >Invaild Credentials</p>  

<?php endif ?>
<div class="page-card">
    <p>Here you can place your dellyman login details. To be able send orders from your e-commerce website to our server.</p>
    <form action='admin-post.php' method="post">
    <div class="input">
        <label for="email" class="label" >E-mail</label>
        <input type="text" class="input-text" name="email" id="email" value = "<?php echo $email; ?>" >
    </div>
     <div class="input">
        <label for="password" class="label" >Password</label>
        <input type="password" class="input-text" name="password" id="password" value = "<?php echo $password; ?>">
    </div>
    <input type="hidden" name="user_id" value="<?php echo $current_user->ID?>" >
     <input name='action' type="hidden" value='login_crendentials'>
     <button class="btn-same-day" >Save Credentials</button>
  
</form>

</div>

