<?php include '/usr/sausalito/ui/adm/includes/core/document_head.php'?>
	<div id="pjax">
		<div id="wrapper" data-adminica-nav-top="4" data-adminica-nav-inner="3">
			<?php include '/usr/sausalito/ui/adm/includes/components/topbar.php'?>
			<?php include '/usr/sausalito/ui/adm/includes/components/sidebar.php'?>
			<?php include '/usr/sausalito/ui/adm/includes/components/stackbar.php'?></div><!-- Closing Div for Stack Nav, you can boxes under the stack before this -->

				<div id="main_container" class="main_container container_16 clearfix">
				<?php include '/usr/sausalito/ui/adm/includes/components/navigation.php'?>

					<div class="box grid_16">
						<div class="block">
							<h2 class="section">Form Validation</h2>

							<form class="validate_form">
							<fieldset class="label_side top">
								<label for="required_field">Text Field<span>Regular field</span></label>
								<div>
									<input id="required_field" name="required_field" type="text" class="required">
									<div class="required_tag"></div>
								</div>
							</fieldset>

							<fieldset class="label_side">
								<label for="required_textarea">Textarea<span>Autogrow Textarea</span></label>
								<div class="clearfix">
									<textarea id="required_textarea" name="required_textarea" class="autogrow required"></textarea>
									<div class="required_tag"></div>
								</div>
							</fieldset>

							<fieldset class="label_side">
								<label for="required_email">Email Address</label>
								<div>
									<input type="text" id="required_email" name="required_email" class="required email">
									<div class="required_tag"></div>
								</div>
							</fieldset>


							<div class="button_bar clearfix">
								<button class="green" type="submit">
									<img height="24" width="24" alt="Bended Arrow Right" src="/.adm/images/icons/small/white/bended_arrow_right.png">
									<span>Submit</span>
								</button>
							</div>
							</form>
						</div>
					</div>
				</div>
			</div>
		</div>

		<?php include '/usr/sausalito/ui/adm/includes/dialogs/dialog_welcome.php'?>
		<?php include '/usr/sausalito/ui/adm/includes/dialogs/dialog_logout.php'?>

<?php include '/usr/sausalito/ui/adm/includes/core/document_foot.php'?>