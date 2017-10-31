<?php include '/usr/sausalito/ui/adm/includes/core/document_head.php'?>
	<div id="pjax">
		<div id="wrapper">
			<div class="isolate">
				<div class="center narrow">
					<div class="main_container full_size container_16 clearfix">
						<div class="box">
							<div class="block">
								<div class="section">
									<div class="alert dismissible alert_light">
										<img width="24" height="24" src="/.adm/images/icons/small/grey/locked.png">
										<strong>Welcome to Adminica.</strong> Please enter your details to login.
									</div>
								</div>
								<form action="index.php" class="validate_form">
								<fieldset class="label_side top">
									<label for="username_field">Username<span>or email address</span></label>
									<div>
										<input type="text" id="username_field" name="username_field" class="required">
									</div>
								</fieldset>
								<fieldset class="label_side">
									<label for="password_field">Password<span><a href="#">Do you remember?</a></span></label>
									<div>
										<input type="password" id="password_field" name="password_field" class="required">
									</div>
								</fieldset>
								<fieldset class="no_label bottom">
									<div style="">
										<div class="slider_unlock" title="Slide to Login"></div>
										<button type="submit" style="display:none"></button>
									</div>
								</fieldset>
								</form>
							</div>
						</div>
					</div>
					<a href="/.adm/index.php" id="login_logo"><span>Adminica</span></a>
					<button data-dialog="dialog_register" class="dialog_button send_right" style="margin-top:10px;">
						<img src="/.adm/images/icons/small/white/user.png">
						<span>Not Registered ?</span>
					</button>
				</div>
			</div>
		</div>
		<?php include '/usr/sausalito/ui/adm/includes/dialogs/dialog_register.php'?>
<?php include '/usr/sausalito/ui/adm/includes/core/document_foot.php'?>