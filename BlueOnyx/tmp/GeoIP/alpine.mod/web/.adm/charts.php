<?php include '/usr/sausalito/ui/adm/includes/core/document_head.php'?>
	<div id="pjax">
		<div id="wrapper" data-adminica-nav-top="9" data-adminica-nav-inner="1">
			<?php include '/usr/sausalito/ui/adm/includes/components/topbar.php'?>
			<?php include '/usr/sausalito/ui/adm/includes/components/sidebar.php'?>
			<?php include '/usr/sausalito/ui/adm/includes/components/stackbar.php'?></div><!-- Closing Div for Stack Nav, you can boxes under the stack before this -->
				<div id="main_container" class="main_container container_16 clearfix">
				<?php include '/usr/sausalito/ui/adm/includes/components/navigation.php'?>
				<div class="flat_area grid_16">
					<h2>Graphs and Charts
						<div class="holder">
							<?php include '/usr/sausalito/ui/adm/includes/components/dynamic_loading.php'?>
						</div>
					</h2>
				</div>
				<div class="box grid_16">
					<h2 class="box_head">Line Graph</h2>
					<div class="controls">
						<a href="#" class="grabber"></a>
						<a href="#" class="toggle"></a>
					</div>
					<div class="toggle_container">
						<div class="block">
							<div class="section">
								<div id="flot_line" class="flot"></div>
							</div>
				 		</div>
					</div>
				 </div>

				<div class="box grid_16">
					<h2 class="box_head">Point Graph with Pie chart</h2>
					<div class="controls">
							<a href="#" class="grabber"></a>
							<a href="#" class="toggle"></a>
						</div>
					<div class="toggle_container">
						<div class="block">
							<div class="columns">
								<div class="col_66">
									<div class="section">
										<div id="flot_points" class="flot"></div>
									</div>
								</div>
								<div class="col_33">
									<div class="section">
										<div id="flot_pie_1" class="flot"></div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="box grid_16">
					<h2 class="box_head">Bar Graph</h2>
					<div class="controls">
						<a href="#" class="grabber"></a>
						<a href="#" class="toggle"></a>
					</div>
					<div class="toggle_container">
						<div class="block">
							<div class="section">
								<div id="flot_bar" class="flot"></div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php include '/usr/sausalito/ui/adm/includes/dialogs/dialog_welcome.php'?>
		<?php include '/usr/sausalito/ui/adm/includes/dialogs/dialog_logout.php'?>
<?php include '/usr/sausalito/ui/adm/includes/core/document_foot.php'?>