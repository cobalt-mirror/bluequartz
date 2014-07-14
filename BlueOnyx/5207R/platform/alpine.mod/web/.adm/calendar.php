<?php include '/usr/sausalito/ui/adm/includes/core/document_head.php'?>
	<div id="pjax">
		<div id="wrapper" data-adminica-nav-top="8">
			<?php include '/usr/sausalito/ui/adm/includes/components/topbar.php'?>
			<?php include '/usr/sausalito/ui/adm/includes/components/sidebar.php'?>
			<?php include '/usr/sausalito/ui/adm/includes/components/stackbar.php'?></div><!-- Closing Div for Stack Nav, you can boxes under the stack before this -->

			<div id="main_container" class="main_container container_16 clearfix">
				<?php include '/usr/sausalito/ui/adm/includes/components/navigation.php'?>
				<div class="flat_area grid_16">
					<h2>Fullcalendar
						<div class="holder">
							<?php include '/usr/sausalito/ui/adm/includes/components/dynamic_loading.php'?>
						</div>
					</h2>
					<p>Here is a really powerful Events calendar which can display events from multiple sources. The user can drag and drop events to rearrange events and also drop new events onto the calendar from the list. Try it out.</p>
				</div>
				<div class="box grid_13">
					<h2 class="box_head">Calendar</h2>
					<div class="controls">
						<a href="#" class="grabber"></a>
						<a href="#" class="toggle"></a>
					</div>
					<div class="toggle_container">
						<div class="block">
							<div class="section">
								<div id="calendar"></div>
							</div>
						</div>
					</div>
				</div>
				<div class="box grid_3">
					<h2 class="box_head">Events <small>(Drag/Drop)</small></h2>
					<div class="block" style="overflow:visible">
					<ul id="calendar_drag_list" class="flat large">
						<li><a class="button full_width blue" data-colour="calendar_blue" href="#">Meeting</a></li>
						<li><a class="button full_width green" data-colour="calendar_green" href="#">Lunch</a></li>
						<li><a class="button full_width red" data-colour="calendar_red" href="#">Holidays</a></li>
						<li><a class="button full_width navy" data-colour="calendar_navy" href="#">Social</a></li>
					</ul>
					</div>

				</div>
			</div>
		</div>
		<?php include '/usr/sausalito/ui/adm/includes/dialogs/dialog_welcome.php'?>
		<?php include '/usr/sausalito/ui/adm/includes/dialogs/dialog_logout.php'?>
<?php include '/usr/sausalito/ui/adm/includes/core/document_foot.php'?>