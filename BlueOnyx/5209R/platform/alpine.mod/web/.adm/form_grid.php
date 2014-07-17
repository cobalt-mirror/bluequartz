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
						<h2 class="section">Form Field Grid</h2>
						<div class="columns clearfix">
							<div class="col_25">
								<fieldset class="label_top top">
									<label for="text_field_inline">Field 1</label>
									<div>
										<input type="text">
									</div>
								</fieldset>
							</div>
							<div class="col_25">
								<fieldset class="label_top top">
									<label for="text_field_inline">Field 2</label>
									<div>
										<input type="text">
									</div>
								</fieldset>
							</div>
							<div class="col_25">
								<fieldset class="label_top top">
									<label for="text_field_inline">Field 3</label>
									<div>
										<input type="text">
									</div>
								</fieldset>
							</div>
							<div class="col_25">
								<fieldset class="label_top top right">
									<label for="text_field_inline">Field 4</label>
									<div>
										<input type="text">
									</div>
								</fieldset>
							</div>
						</div>
						<div class="columns clearfix compressed">
							<div class="col_20">
								<fieldset class="label_top">
									<label for="text_field_inline">Field 1</label>
									<div>
										<input type="text">
									</div>
								</fieldset>
							</div>
							<div class="col_20">
								<fieldset class="label_top">
									<label for="text_field_inline">Field 2</label>
									<div>
										<input type="text">
									</div>
								</fieldset>
							</div>
							<div class="col_20">
								<fieldset class="label_top">
									<label for="text_field_inline">Field 3</label>
									<div>
										<input type="text">
									</div>
								</fieldset>
							</div>
							<div class="col_20">
								<fieldset class="label_top">
									<label for="text_field_inline">Field 4</label>
									<div>
										<input type="text">
									</div>
								</fieldset>
							</div>
							<div class="col_20">
								<fieldset class="label_top">
									<label for="text_field_inline">Field 5</label>
									<div>
										<input type="text">
									</div>
								</fieldset>
							</div>
						</div>

						<fieldset class="label_side">
							<label>Permission</label>
							<div class="uniform inline clearfix">
								<label for="agree_1"><input type="checkbox" name="agree_1" value="yes" id="agree_1"/>I agree with the terms and conditions</label>
							</div>
						</fieldset>



						<div class="button_bar clearfix">
							<button class="dark blue no_margin_bottom link_button" data-link="index.php">
								<div class="ui-icon ui-icon-check"></div>
								<span>Submit</span>
							</button>
							<button class="light send_right close_dialog">
								<div class="ui-icon ui-icon-closethick"></div>
								<span>Cancel</span>
							</button>
						</div>
					</div>

				</div>
			</div>
		</div>
		<?php include '/usr/sausalito/ui/adm/includes/dialogs/dialog_welcome.php'?>
		<?php include '/usr/sausalito/ui/adm/includes/dialogs/dialog_logout.php'?>
<?php include '/usr/sausalito/ui/adm/includes/core/document_foot.php'?>