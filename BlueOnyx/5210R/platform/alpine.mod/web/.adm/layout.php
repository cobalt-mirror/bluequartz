<?php include '/usr/sausalito/ui/adm/includes/core/document_head.php'?>
	<div id="pjax">
		<div id="wrapper" data-adminica-nav-top="2" data-adminica-nav-inner="1">
			<?php include '/usr/sausalito/ui/adm/includes/components/topbar.php'?>
			<?php include '/usr/sausalito/ui/adm/includes/components/sidebar.php'?>
			<?php include '/usr/sausalito/ui/adm/includes/components/stackbar.php'?></div><!-- Closing Div for Stack Nav, you can boxes under the stack before this -->

			<div id="main_container" class="main_container container_16 clearfix">
				<?php include '/usr/sausalito/ui/adm/includes/components/navigation.php'?>
				<?php include("/usr/sausalito/ui/adm/includes/components/breadcrumb.php"); ?>
				<div class="flat_area grid_16">
					<h2>960.gs Grid System
						<div class="holder">
							<?php include '/usr/sausalito/ui/adm/includes/components/dynamic_loading.php'?>
						</div>
					</h2>
					<p><strong>Adminica</strong> is based on the <a target="_blank" href="http://www.960.gs">960.gs Grid System</a>. All widths are defined by styles eg. <strong>div class="grid_8"</strong> will give you a box half the width of the content area.</p>
					<p><strong>Adminica</strong> uses a 16 column grid but you can easily change this to 12 if you prefer.</p>
					<p>The layout can be set to be <a href="/.adm/css/theme/switcher1.php?style=layout_fixed.css">Fixed</a> or <a href="/.adm/css/theme/switcher1.php?style=switcher.css">Fluid</a> width. You can also choose whether a page has a <a href="/.adm/css/theme/switcher1.php?style=switcher.css">Side Nav</a> or <a href="/.adm/css/theme/switcher2.php?style=layout_top.css">Top Bar</a> and <strong>Adminica</strong> will <strong>rearrange and scale automatically</strong>. </p>
				</div>

				<div class="box grid_16">
					<h2 class="box_head">Grid_16</h2>
					<div class="controls">
						<a href="#" class="grabber"></a>
						<a href="#" class="toggle"></a>
					</div>
					<div class="toggle_container">
						<div class="block lines">
							<div class="columns">
								<div class="col_50 no_border_top">
									<div class="section">
										<p>50% content</p>
									</div>
								</div>
								<div class="col_50 no_border_top no_border_right">
									<div class="section">
										<p>50% content</p>
									</div>
								</div>
							</div>
							<div class="columns">
								<div class="col_50">
									<div class="section">
										<p>50% content</p>
									</div>
								</div>
								<div class="col_50 no_border_right">
									<div class="section">
										<p>50% content</p>
									</div>
								</div>
							</div>
							<div class="columns shade">
								<div class="col_66">
									<div class="col_50">
										<div class="section">
											<p>33% content</p>
										</div>
									</div>
									<div class="col_50 no_border_right">
										<div class="section">
											<p>33% content</p>
										</div>
									</div>
									<div class="col_100">
										<div class="section">
											<p>66% content</p>
										</div>
									</div>
									<div class="col_50">
										<div class="section">
											<p>33% content</p>
										</div>
									</div>
									<div class="col_50 no_border_right">
										<div class="section">
											<p>33% content</p>
										</div>
									</div>
								</div>
								<div class="col_33 no_border_right">
									<div class="section">
										<p>Lots of content in this column:</p>
										<p>33% content</p>
										<p>33% content</p>
									</div>
								</div>
							</div>
							<div class="columns">
								<div class="col_25">
									<div class="section">
										<p>25% content</p>
									</div>
								</div>
								<div class="col_25">
									<div class="section">
										<p>25% content</p>
									</div>
								</div>
								<div class="col_25">
									<div class="section">
										<p>25% content</p>
									</div>
								</div>
								<div class="col_25 no_border_right">
									<div class="section">
										<p>25% content</p>
									</div>
								</div>
							</div>
							<div class="columns even clearfix">
								<div class="col_50">
									<div class="section">
										<p>Lots of content in this column:</p>
										<p>50% content</p>
										<p>50% content</p>
										<p>50% content</p>
									</div>
								</div>
								<div class="col_50 no_border_right">
									<div class="section">
										<p>50% content</p>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="box grid_16">
					<h2 class="box_head">Box with Button Bar</h2>
					<div class="controls">
						<a href="#" class="grabber"></a>
						<a href="#" class="toggle"></a>
					</div>
					<div class="toggle_container">
						<div class="block">
							<div class="section">
								<p>This box has a button bar - a great place to put any of the <a href="/.adm/buttons.php">buttons</a> which come with Adminica.</p>
							</div>
							<div class="button_bar clearfix">
								<label>Buttons:</label>
								<button class="green">
									<span>Submit</span>
								</button>
								<button class="blue">
									<span>Save</span>
								</button>
								<button class="light send_right">
									<span>Clear</span>
								</button>
							</div>
						</div>
					</div>
				</div>

				<div class="flat_area grid_16">
					<h2>Standalone Button Bar</h2>
					<p>This should be used when you don't need a content box and just need some cool looking buttons!</p>
				</div>

				<div class="grid_16 box clearfix">
					<div class="indented_button_bar clearfix">
						<label>Buttons:</label>
						<button class="light on_dark" data-toggle-class="grid_16">
							<span>Toggle Grid_16</span>
						</button>
						<button class="blue on_dark" data-toggle-class="grid_8">
							<span>Toggle Grid_8</span>
						</button>
						<button class="red on_dark" data-toggle-class="grid_4">
							<span>Toggle Grid_4</span>
						</button>
						<button class="green send_right on_dark" data-toggle-class="box">
							<span>Toggle all Boxes</span>
						</button>
					</div>
				</div>
			</div>
			<div class="main_container container_16 clearfix full_size">
				<div class="box">
					<h2 class="box_head">Full Width Content box <span>No lines dividing sections</span></h2>
					<div class="controls">
						<a href="#" class="grabber"></a>
						<a href="#" class="toggle"></a>
					</div>
					<div class="toggle_container">
						<div class="block">
							<div class="columns">
								<div class="col_50">
									<div class="section">
										<p>50% content</p>
									</div>
								</div>
								<div class="col_50 no_border_right">
									<div class="section">
										<p>50% content</p>
									</div>
								</div>
							</div>
							<div class="columns">
								<div class="col_33">
									<div class="section">
										<p>33% content</p>
									</div>
								</div>
								<div class="col_33">
									<div class="section">
										<p>33% content</p>
									</div>
								</div>
								<div class="col_33 no_border_right">
									<div class="section">
										<p>33% content</p>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="main_container container_16 clearfix full_size">
				<div class="box">
					<div class="block lines">
						<div class="columns">
							<div class="col_50 no_border_top">
								<div class="section">
									<p>50% content</p>
								</div>
							</div>
							<div class="col_50 no_border_top no_border_right">
								<div class="section">
									<p>50% content</p>
								</div>
							</div>
						</div>
						<div class="columns">
							<div class="col_33">
								<div class="section">
									<p>33% content</p>
								</div>
							</div>
							<div class="col_33">
								<div class="section">
									<p>33% content</p>
								</div>
							</div>
							<div class="col_33 no_border_right">
								<div class="section">
									<p>33% content</p>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div id="main_container" class="main_container container_16 clearfix">
				<div class="box grid_8">
					<h2 class="box_head">Grid_8</h2>
					<div class="controls">
						<a href="#" class="grabber"></a>
						<a href="#" class="toggle"></a>
					</div>
					<div class="toggle_container">
						<div class="block">
							<div class="section">
								<p>Content goes here</p>
							</div>
						</div>
					</div>
				</div>
				<div class="box grid_4">
					<h2 class="box_head">Grid_4</h2>
					<div class="controls">
						<a href="#" class="grabber"></a>
						<a href="#" class="toggle"></a>
					</div>
					<div class="toggle_container">
						<div class="block">
							<div class="section">
								<p>Content goes here</p>
							</div>
						</div>
					</div>
				</div>
				<div class="box grid_4">
					<h2 class="box_head">Grid_4</h2>
					<div class="controls">
						<a href="#" class="grabber"></a>
						<a href="#" class="toggle"></a>
					</div>
					<div class="toggle_container">
						<div class="block">
							<div class="section">
								<p>Content goes here</p>
							</div>
						</div>
					</div>
				</div>
				<div class="box light grid_8">
					<h2 class="box_head">Grid_8</h2>
					<div class="controls">
						<a href="#" class="grabber"></a>
						<a href="#" class="toggle"></a>
					</div>
					<div class="toggle_container">
						<div class="block">
							<div class="section">
								<p>Content goes here</p>
							</div>
						</div>
					</div>
				</div>
				<div class="box grid_8">
					<h2 class="box_head grad_grey_dark">Grid_8</h2>
					<div class="controls">
						<a href="#" class="grabber"></a>
						<a href="#" class="toggle"></a>
					</div>
					<div class="toggle_container">
						<div class="block">
							<div class="section">
								<p>Content goes here</p>
							</div>
						</div>
					</div>
				</div>



				<div class="box grid_8">
					<h2 class="box_head grad_black">Grid_8</h2>
					<div class="controls">
						<a href="#" class="grabber"></a>
						<a href="#" class="toggle"></a>
					</div>
					<div class="toggle_container">
						<div class="block">
							<div class="section">
								<p>Content goes here</p>
							</div>
						</div>
					</div>
				</div>
				<div class="box grid_8">
					<h2 class="box_head grad_blue">Grid_8</h2>
					<div class="controls">
						<a href="#" class="grabber"></a>
						<a href="#" class="toggle"></a>
					</div>
					<div class="toggle_container">
						<div class="block">
							<div class="section">
								<p>Content goes here</p>
							</div>
						</div>
					</div>
				</div>
				<div class="box grid_8">
					<h2 class="box_head grad_green">Grid_8</h2>
					<div class="controls">
						<a href="#" class="grabber"></a>
						<a href="#" class="toggle"></a>
					</div>
					<div class="toggle_container">
						<div class="block">
							<div class="section">
								<p>Content goes here</p>
							</div>
						</div>
					</div>
				</div>
				<div class="box grid_8">
					<h2 class="box_head grad_magenta">Grid_8</h2>
					<div class="controls">
						<a href="#" class="grabber"></a>
						<a href="#" class="toggle"></a>
					</div>
					<div class="toggle_container">
						<div class="block">
							<div class="section">
								<p>Content goes here</p>
							</div>
						</div>
					</div>
				</div>
				<div class="box grid_8">
					<h2 class="box_head grad_navy">Grid_8</h2>
					<div class="controls">
						<a href="#" class="grabber"></a>
						<a href="#" class="toggle"></a>
					</div>
					<div class="toggle_container">
						<div class="block">
							<div class="section">
								<p>Content goes here</p>
							</div>
						</div>
					</div>
				</div>
				<div class="box grid_8">
					<h2 class="box_head grad_red">Grid_8</h2>
					<div class="controls">
						<a href="#" class="grabber"></a>
						<a href="#" class="toggle"></a>
					</div>
					<div class="toggle_container">
						<div class="block">
							<div class="section">
								<p>Content goes here</p>
							</div>
						</div>
					</div>
				</div>
				<div class="box grid_8">
					<h2 class="box_head grad_orange">Grid_8</h2>
					<div class="controls">
						<a href="#" class="grabber"></a>
						<a href="#" class="toggle"></a>
					</div>
					<div class="toggle_container">
						<div class="block">
							<div class="section">
								<p>Content goes here</p>
							</div>
						</div>
					</div>
				</div>
				<div class="box grid_8">
					<h2 class="box_head grad_brown">Grid_8</h2>
					<div class="controls">
						<a href="#" class="grabber"></a>
						<a href="#" class="toggle"></a>
					</div>
					<div class="toggle_container">
						<div class="block">
							<div class="section">
								<p>Content goes here</p>
							</div>
						</div>
					</div>
				</div>



				<div class="box grid_16">
					<h2 class="box_head round_all">Grid_16 <small>(Closed by default)</small></h2>
					<div class="controls">
						<a href="#" class="grabber"></a>
						<a href="#" class="toggle toggle_closed"></a>
					</div>
					<div class="toggle_container" style="display:none;">
						<div class="block">
							<div class="section">
								<p>Content goes here</p>
							</div>
						</div>
					</div>
				</div>

				<div class="box grid_8">
					<div class="block">
						<div class="section">
							<p>Grid_8</p>
						</div>
					</div>
				</div>
				<div class="box grid_8">
					<div class="block">
						<div class="section">
							<p>Grid_8</p>
						</div>
					</div>
				</div>
				<div class="box grid_4">
					<div class="block">
						<div class="section">
							<p>Grid_4</p>
						</div>
					</div>
				</div>
				<div class="box grid_4">
					<div class="block">
						<div class="section">
							<p>Grid_4</p>
						</div>
					</div>
				</div>
				<div class="box grid_8">
					<div class="block">
						<div class="section">
							<p>Grid_8</p>
						</div>
					</div>
				</div>
				<div class="box grid_2">
					<div class="block">
						<div class="section">
							<p>Grid_2</p>
						</div>
					</div>
				</div>
				<div class="box grid_2">
					<div class="block">
						<div class="section">
							<p>Grid_2</p>
						</div>
					</div>
				</div>
				<div class="box grid_2">
					<div class="block">
						<div class="section">
							<p>Grid_2</p>
						</div>
					</div>
				</div>
				<div class="box grid_2">
					<div class="block">
						<div class="section">
							<p>Grid_2</p>
						</div>
					</div>
				</div>
				<div class="box grid_4">
					<div class="block">
						<div class="section">
							<p>Grid_4</p>
						</div>
					</div>
				</div>
				<div class="box grid_4">
					<div class="block">
						<div class="section">
							<p>Grid_4</p>
						</div>
					</div>
				</div>
				<div class="flat_area grid_8">
					<h2>Column Grid_8</h2>
					<p><strong>Multicolumn text Layouts are straightforward</strong>.
					Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation <strong>ullamco</strong> laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat <strong>cupidatat</strong> non proident, sunt in culpa qui officia deserunt mollit anim id est laborum. </p>
				</div>
				<div class="flat_area grid_8">
					<h2>Column Grid_8</h2>
					<p>Multicolumn text Layouts are straightforward. Lorem ipsum dolor sit amet, <strong>consectetur</strong> adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut <strong>enim</strong> ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. <strong>Excepteur</strong> sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum. </p>
				</div>
			</div>
		</div>
		<?php include '/usr/sausalito/ui/adm/includes/dialogs/dialog_welcome.php'?>
		<?php include '/usr/sausalito/ui/adm/includes/dialogs/dialog_logout.php'?>
<?php include '/usr/sausalito/ui/adm/includes/core/document_foot.php'?>