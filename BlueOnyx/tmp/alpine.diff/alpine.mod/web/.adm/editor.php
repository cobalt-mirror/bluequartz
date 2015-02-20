<?php include '/usr/sausalito/ui/adm/includes/core/document_head.php'?>
	<div id="pjax">
		<div id="wrapper" data-adminica-nav-top="3" data-adminica-nav-inner="5">
			<?php include '/usr/sausalito/ui/adm/includes/components/topbar.php'?>
			<?php include '/usr/sausalito/ui/adm/includes/components/sidebar.php'?>
			<?php include '/usr/sausalito/ui/adm/includes/components/stackbar.php'?></div><!-- Closing Div for Stack Nav, you can boxes under the stack before this -->
			<div id="main_container" class="main_container container_16 clearfix">
				<?php include '/usr/sausalito/ui/adm/includes/components/navigation.php'?>
				<div class="flat_area grid_16">
					<h2>Rich Text Editor <small>(WYSIWYG)</small>
						<div class="holder">
							<?php include '/usr/sausalito/ui/adm/includes/components/dynamic_loading.php'?>
						</div>
					</h2>
					<p><strong>Adminica</strong> utilizes <strong>Tiny Editor</strong>, one of the most popular online rich text editors.
This editor has all the usual formatting options you expect. It can be toggled and sorted like any other <strong>Adminica</strong> box.</p>
				</div>
				<div class="box grid_16">
					<h2 class="box_head">Tiny Editor</h2>
					<div class="controls">
						<a href="#" class="grabber"></a>
						<a href="#" class="toggle"></a>
					</div>
					<div class="block">
						<form>
						<textarea id="tiny_input" class="tinyeditor"></textarea>

						<div class="button_bar clearfix">
							<button type="submit" class="dark" onsubmit="editor1.post();">
								<img src="/.adm/images/icons/small/white/bended_arrow_right.png">
								<span>Submit</span>
							</button>
						</div>
					</div>
				</div>
				<div class="box grid_16">
					<div class="block">
						<form>
						<textarea id="tiny_input2" class="tinyeditor"></textarea>

						<div class="button_bar clearfix">
							<button type="submit" class="dark" onsubmit="editor2.post()">
								<img src="/.adm/images/icons/small/white/bended_arrow_right.png">
								<span>Submit</span>
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