<?php include '/usr/sausalito/ui/adm/includes/core/document_head.php'?>
	<div id="pjax">
		<div id="wrapper" data-adminica-nav-top="9" data-adminica-nav-inner="2">
			<?php include '/usr/sausalito/ui/adm/includes/components/topbar.php'?>
			<?php include '/usr/sausalito/ui/adm/includes/components/sidebar.php'?>
			<?php include '/usr/sausalito/ui/adm/includes/components/stackbar.php'?></div><!-- Closing Div for Stack Nav, you can boxes under the stack before this -->

				<div id="main_container" class="main_container container_16 clearfix">
				<?php include '/usr/sausalito/ui/adm/includes/components/navigation.php'?>
				<div class="flat_area grid_16">
					<h2>Info Blocks
						<div class="holder">
							<?php include '/usr/sausalito/ui/adm/includes/components/dynamic_loading.php'?>
						</div>
					</h2>
					<p>These are really useful for displaying quick visual pieces of information that are important for the user. They can hold text, icons and most importantly charts.</p>
				</div>
				<div class="box grid_16">
					<div class="block lines">
						<div class="columns">
							<div class="col_20 no_border_top">
								<div class="info_box">
									<div class="value_tag"><span>+453 today</span></div>
									<div class="split one">
										<div class="chart">
											<span class="spark_bar large random_number_5">0,5,1,4,2,3</span>
										</div>
									</div>
									<label>Sales per hour<div class="comment icon_small chat_black"><a href="#"></a></div></label>
								</div>
							</div>
							<div class="col_20 no_border_top">
								<div class="info_box">
									<div class="split one">
										<div class="big_letter green">OK</div>
									</div>
									<label>System Status</label>
								</div>
							</div>
							<div class="col_20 no_border_top">
								<div class="info_box">
									<div class="split one">
										<div class="chart">
											<span class="spark_pie large random_number_3">
											26, 74, 105</span>
										</div>
									</div>
									<label>Resources</label>
								</div>
							</div>
							<div class="col_20 no_border_top">
								<div class="info_box">
									<div class="value_tag"><span>estimated</span></div>
									<div class="split one">
										<div class="chart">
											<span class="spark_line large random_number_5">
											26, 74, 102, 153, 25</span>
										</div>
									</div>
									<label>Daily Traffic</label>
								</div>
							</div>
							<div class="col_20 no_border_top no_border_right">
								<div class="info_box">
									<div class="split two">9,756 <small>ext</small></div>
									<div class="split two red">2,846 <small>int</small></div>
									<label>Connections</label>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="box grid_16">
					<div class="block lines">
						<div class="columns">
							<div class="col_20 no_border_top">
								<div class="info_box">
									<div class="split one">
										<a href="#"><img src="/.adm/images/icons/large/grey/alert.png" width="36" height="36" /></a>
									</div>
									<label>View Alerts</label>
								</div>
							</div>
							<div class="col_20 no_border_top">
								<div class="info_box">
									<div class="split three green">US</div>
									<div class="split three orange">Asia</div>
									<div class="split three red">Europe</div>
									<label>Global</label>
								</div>
							</div>
							<div class="col_20 no_border_top">
								<div class="info_box">
									<div class="split one"><span class="spark_bar large random_number_5">0,5,1,4,2,3</span></div>
									<label>Trends</label>
								</div>
							</div>
							<div class="col_20 no_border_top">
								<div class="info_box">
									<div class="split one">
										<div class="big_letter red">30%</div>
									</div>
									<label>Server Health</label>
								</div>
							</div>
							<div class="col_20 no_border_top no_border_right">
								<div class="info_box">
									<div class="split one">
										<div class="chart">
											<span class="spark_bar large random_number_5">0,5,1,4,2,3</span>
										</div>
									</div>
									<label>User Stats<div class="comment icon_small chat_black"><a href="#"></a></div></label>
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="box grid_16 tabs">
					<ul class="tab_header clearfix">
						<li><a href="#tabs-1">System Stats</a></li>
						<li><a href="#tabs-2">Server Stats</a></li>
					</ul>
					<div class="controls">
						<a href="#" class="grabber"></a>
						<a href="#" class="toggle"></a>
						<a href="#" class="show_all_tabs"></a>
					</div>
					<div class="toggle_container">
						<div id="tabs-1" class="block lines">
							<div class="columns">
								<div class="col_20 no_border_top">
									<div class="info_box">
										<div class="value_tag"><span>+453 today</span></div>
										<div class="split one">
											<div class="chart">
												<span class="spark_bar large random_number_5"></span>
											</div>
										</div>
										<label>Leads per hour<div class="comment icon_small chat_black"><a href="#"></a></div></label>
									</div>
								</div>
								<div class="col_20 no_border_top">
									<div class="info_box">
										<div class="value_tag"><span>estimated</span></div>
										<div class="split one">
											<div class="chart">
												<span class="spark_line large random_number_5"></span>
											</div>
										</div>
										<label>Daily Hits</label>
									</div>
								</div>
								<div class="col_20 no_border_top">
									<div class="info_box">
										<div class="split one">
											<div class="big_letter yellow">Fair</div>
										</div>
										<label>Remote Status</label>
									</div>
								</div>
								<div class="col_20 no_border_top">
									<div class="info_box">
										<div class="split one">
											<div class="chart">
												<span class="spark_pie large random_number_3">
												26, 74, 105</span>
											</div>
										</div>
										<label>Resources</label>
									</div>
								</div>
								<div class="col_20 no_border_top no_border_right">
									<div class="info_box">
										<div class="split two">3,122 <small>ext</small></div>
										<div class="split two red">253 <small>int</small></div>
										<label>Connections</label>
									</div>
								</div>
							</div>
						</div>
						<div id="tabs-2" class="block lines">
							<div class="columns">
								<div class="col_20 no_border_top">
									<div class="info_box">
										<div class="split one">
											<a href="#"><img src="/.adm/images/icons/large/grey/alert.png" width="36" height="36" /></a>
										</div>
										<label>View Alerts</label>
									</div>
								</div>
								<div class="col_20 no_border_top">
									<div class="info_box">
										<div class="split three green">US</div>
										<div class="split three orange">Asia</div>
										<div class="split three red">Europe</div>
										<label>Global</label>
									</div>
								</div>
								<div class="col_20 no_border_top">
									<div class="info_box">
										<div class="split one"><span class="spark_bar large random_number_5">0,5,1,4,2,3</span></div>
										<label>Trends</label>
									</div>
								</div>
								<div class="col_20 no_border_top">
									<div class="info_box">
										<div class="split one">
											<div class="big_letter red">30%</div>
										</div>
										<label>Server Health</label>
									</div>
								</div>
								<div class="col_20 no_border_top no_border_right">
									<div class="info_box">
										<div class="split one">
											<div class="chart">
												<span class="spark_bar large random_number_5">0,5,1,4,2,3</span>
											</div>
										</div>
										<label>User Stats<div class="comment icon_small chat_black"><a href="#"></a></div></label>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<?php include '/usr/sausalito/ui/adm/includes/dialogs/dialog_welcome.php'?>
		<?php include '/usr/sausalito/ui/adm/includes/dialogs/dialog_logout.php'?>

<?php include '/usr/sausalito/ui/adm/includes/core/document_foot.php'?>