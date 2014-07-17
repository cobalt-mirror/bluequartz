<?php include '/usr/sausalito/ui/adm/includes/core/document_head.php'?>
	<div id="pjax">
		<div id="wrapper" data-adminica-nav-top="4" data-adminica-nav-inner="4">
			<?php include '/usr/sausalito/ui/adm/includes/components/topbar.php'?>
			<?php include '/usr/sausalito/ui/adm/includes/components/sidebar.php'?>
			<?php include '/usr/sausalito/ui/adm/includes/components/stackbar.php'?></div><!-- Closing Div for Stack Nav, you can boxes under the stack before this -->
			<div id="main_container" class="main_container container_16 clearfix">
				<?php include '/usr/sausalito/ui/adm/includes/components/navigation.php'?>

				<div class="box grid_16">
					<div class="block">
					<form class="validate_form">
						<h2 class="section">Contact Form</h2>
						<div class="columns clearfix">
							<div class="col_50">
								<fieldset class="label_side label_small top">
									<label for="text_field_inline">Name</label>
									<div>
										<input type="text" name="required_name" id="required_name" class="required">
										<div class="required_tag"></div>
									</div>
								</fieldset>
							</div>
							<div class="col_50">
								<fieldset class="label_side label_small top right">
									<label for="text_field_inline">Email</label>
									<div>
										<input type="text" name="required_email" id="required_email" class="required email">
										<div class="required_tag"></div>
									</div>
								</fieldset>
							</div>
						</div>
						<fieldset class="label_side label_small">
							<label for="text_field_inline">Subject</label>
							<div>
								<input type="text">
							</div>
						</fieldset>
						<fieldset class="label_top">
							<label for="text_field_inline">Message</label>
							<div>
								<textarea></textarea>
							</div>
						</fieldset>
						<div class="columns">
							<div class="col_50">
								<fieldset class="label_side label_small">
									<label>Priority</label>
									<div>
										<div class="jqui_radios">
											<input type="radio" name="answer5" id="yes5"/><label for="yes5">Regular</label>
											<input type="radio" name="answer5" id="no5"/><label for="no5">Urgent</label>																										</div>
									</div>
								</fieldset>
							</div>
							<div class="col_50">
								<fieldset class="label_side label_small right">
									<label for="file_upload">Attachment</label>

									<div class="clearfix">
										<input type="file" id="fileupload" class="uniform">
									</div>
								</fieldset>
							</div>
						</div>
						<fieldset class="no_label">
							<div class="uniform inline clearfix">
								<label for="agree_1"><input type="checkbox" name="agree_1" value="yes" id="agree_1"/>Send a copy to my own email address.</label>
							</div>
						</fieldset>

						<div class="button_bar clearfix">
							<button class="dark" type="submit">
								<img src="/.adm/images/icons/small/white/mail.png">
								<span>Send</span>
							</button>
							<button class="light send_right" type="reset">
								<span>Reset</span>
							</button>
						</div>
						</form>
					</div>

				</div>
			</div>
		</div>
		<?php include '/usr/sausalito/ui/adm/includes/dialogs/dialog_welcome.php'?>
		<?php include '/usr/sausalito/ui/adm/includes/dialogs/dialog_logout.php'?>
<?php include '/usr/sausalito/ui/adm/includes/core/document_foot.php'?>