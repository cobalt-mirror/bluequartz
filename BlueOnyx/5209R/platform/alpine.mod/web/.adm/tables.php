<?php include '/usr/sausalito/ui/adm/includes/core/document_head.php'?>
	<div id="pjax">
		<div id="wrapper" data-adminica-nav-top="6" data-adminica-nav-inner="1">
			<?php include '/usr/sausalito/ui/adm/includes/components/topbar.php'?>
			<?php include '/usr/sausalito/ui/adm/includes/components/sidebar.php'?>
			<?php include '/usr/sausalito/ui/adm/includes/components/stackbar.php'?></div><!-- Closing Div for Stack Nav, you can boxes under the stack before this -->

			<div id="main_container" class="main_container container_16 clearfix">
				<?php include '/usr/sausalito/ui/adm/includes/components/navigation.php'?>
				<div class="flat_area grid_16">
					<h2>Sortable Tables
						<div class="holder">
							<?php include '/usr/sausalito/ui/adm/includes/components/dynamic_loading.php'?>
						</div>
					</h2>
					<p>This <strong>jQuery powered Table</strong> takes a standard html table and turns it into a <strong>sortable</strong>, <strong>filterable</strong> and <strong>searchable</strong>  table. The<strong> search is LIVE</strong> so doesn't require you to reload the page! Also, the items are <strong>automatically paginated</strong> into sets of 10, 20 or 50. </p><p>Try it out and you'll see how <strong>powerful yet easy to use</strong> it is.</p>
				</div>
				<div class="box grid_16 single_datatable">
					<div id="dt1" class="no_margin"><?php include '/usr/sausalito/ui/adm/includes/content/datatables_data.php'?></div>
				</div>
				<div class="flat_area grid_16">
					<h2>Tabbed table</h2>
					<p>The table below can be placed in a tabbed box just like any other <strong>Adminica</strong> control. This is really good to supply extra info about table or even plot a graph of the data. </p>
				</div>
				<div class="box grid_16 tabs">
					<ul id="touch_sort" class="tab_header clearfix">
						<li><a href="#tabs-1">Table Data</a></li>
						<li><a href="#tabs-2">Table <span>(no pagination)</span></a></li>
						<li><a href="#tabs-3">Another Tab</a></li>
					</ul>
					<div class="controls">
						<a href="#" class="grabber"></a>
						<a href="#" class="toggle"></a>
					</div>
					<div class="toggle_container">
						<div id="tabs-1" class="block">
							<div id="dt2"><?php include '/usr/sausalito/ui/adm/includes/content/datatables_data.php'?></div>
						</div>
						<div id="tabs-2" class="block">
							<div id="dt3"><?php include '/usr/sausalito/ui/adm/includes/content/datatables_data.php'?></div>
						</div>
						<div id="tabs-3" class="block">
							<div class="section">
								<p>Information about the Table can go here, or another table could go here or pretty much anything could go here!</p>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<?php include '/usr/sausalito/ui/adm/includes/dialogs/dialog_welcome.php'?>
		<?php include '/usr/sausalito/ui/adm/includes/dialogs/dialog_logout.php'?>

<?php include '/usr/sausalito/ui/adm/includes/core/document_foot.php'?>