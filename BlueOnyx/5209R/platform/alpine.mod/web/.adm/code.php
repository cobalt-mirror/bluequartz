<?php include '/usr/sausalito/ui/adm/includes/core/document_head.php'?>
	<div id="pjax">
		<div id="wrapper" data-adminica-nav-top="3" data-adminica-nav-inner="4">
			<?php include '/usr/sausalito/ui/adm/includes/components/topbar.php'?>
			<?php include '/usr/sausalito/ui/adm/includes/components/sidebar.php'?>
			<?php include '/usr/sausalito/ui/adm/includes/components/stackbar.php'?></div><!-- Closing Div for Stack Nav, you can boxes under the stack before this -->
				<div id="main_container" class="main_container container_16 clearfix">
				<?php include '/usr/sausalito/ui/adm/includes/components/navigation.php'?>
				<div class="box grid_16">
					<h2 class="box_head">Expanded Code View</h2>
					<div class="controls">
						<a href="#" class="grabber"></a>
						<a href="#" class="toggle"></a>
					</div>
					<div class="toggle_container">
						<div class="block">
							<script type="syntaxhighlighter" class="brush:xml; toolbar:false;"><![CDATA[
							<div class="box grid_16">
								<h2 class="box_head grad_colour">Expanded Code View</h2>
								<div class="controls">
										<a href="#" class="grabber"></a>
										<a href="#" class="toggle"></a>
									</div>
								</div>
								<div class="toggle_container">
									<div class="block no_padding">
										Markup for a headered box.
									</div>
								</div>
							</div>		]]></script>
						</div>
					</div>
				</div>
				<div class="box grid_16">
					<div class="block">
						<script type="syntaxhighlighter" class="brush:xml;collapse:true;"><![CDATA[
						<div class="box grid_16">
							<div class="block no_padding">
								<p>This is a regular box without header.</p>
							</div>
						</div>				]]></script>
						<script type="text/javascript">
							SyntaxHighlighter.all();
						</script>
					</div>
				</div>
			</div>


		</div>
		<?php include '/usr/sausalito/ui/adm/includes/dialogs/dialog_welcome.php'?>
		<?php include '/usr/sausalito/ui/adm/includes/dialogs/dialog_logout.php'?>
<?php include '/usr/sausalito/ui/adm/includes/core/document_foot.php'?>