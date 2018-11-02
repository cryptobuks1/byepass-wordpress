jQuery(document).ready(function($) {
	jQuery('input#wp-submit').click(function(){
		var email = jQuery('input#user_login').val();
		jQuery('input[name="identifier"]').val(email);
		jQuery('#bsubmit').submit();
		return false;
	});
	jQuery('input#wp-submit').val('Byepass Login');
	jQuery('label[for="user_login"]').html('Email<br><input type="text" name="log" id="user_login" class="input" value="" size="20">');
	jQuery('#login_error').prependTo(jQuery('#login'));
});