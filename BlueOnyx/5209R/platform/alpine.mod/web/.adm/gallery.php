<?php include '/usr/sausalito/ui/adm/includes/core/gui_head.php'?>
	<div id="pjax">
		<div id="wrapper" data-adminica-nav-top="5" >
			<?php include '/usr/sausalito/ui/adm/includes/components/topbar.php'?>
			<?php include '/usr/sausalito/ui/adm/includes/components/sidebar.php'?>
			<?php include '/usr/sausalito/ui/adm/includes/components/stackbar.php'?></div><!-- Closing Div for Stack Nav, you can boxes under the stack before this -->

			<div id="main_container" class="main_container clearfix container_16">
				<?php include '/usr/sausalito/ui/adm/includes/components/navigation.php'?>
				<div class="flat_area grid_16">
					<h2>Sortable Gallery
						<div class="holder">
							<?php include '/usr/sausalito/ui/adm/includes/components/dynamic_loading.php'?>
						</div>
					</h2>
					<p>This is one of <strong>Adminica</strong>'s coolest features, a dynamically sortable gallery. Change the radio buttons and watch them slide! </p>
					<p><strong>You can sort anything with this not just image tiles</strong> eg. it could rearrange a contact or feature list.</p>
				</div>
					<div class="box grid_16">
						<div class="block">
							<div class="columns clearfix">
								<div class="col_60">
									<fieldset class="label_side label_small bottom">
										<label>Filter by:<span>Colour</span></label>
										<div>
											<div class="jqui_radios">
												<input type="radio" name="filter" class="isotope_filter" id="f_all" data-isotope-filter="*" checked="checked"/><label for="f_all">All</label>
												<input type="radio" name="filter" class="isotope_filter" id="f_blue" data-isotope-filter=".blue"/><label for="f_blue">Blue</label>
												<input type="radio" name="filter" class="isotope_filter" id="f_sepia" data-isotope-filter=".sepia"/><label for="f_sepia">Sepia</label>
												<input type="radio" name="filter" class="isotope_filter" id="f_bw" data-isotope-filter=".bw"/><label for="f_bw">Grey</label>
											</div>
										</div>
									</fieldset>
								</div>
								<div class="col_40">
									<fieldset class="label_side label_small bottom right">
										<label>Sort by:<span>Order</span></label>
										<div>
											<div class="jqui_radios">
												<input type="radio" name="sort" class="isotope_sort" id="s_name" data-isotope-sort="sort_1" checked="checked"/><label for="s_name">Name</label>
												<input type="radio" name="sort" class="isotope_sort" id="s_size" data-isotope-sort="sort_2"/><label for="s_size">Size</label>
											</div>
										</div>
									</fieldset>
								</div>
							</div>
						</div>
					</div>
					<div class="grid_16">
						<div class="isotope_holder indented_area">
							<?php include '/usr/sausalito/ui/adm/includes/content/gallery_images.php'?>
						</div>
					</div>
					<div class="flat_area grid_16">
						<p><strong>Note:</strong> You will need moderate programming ability if you want to change how the sorter works, eg. If you wanted to reverse sort or sort by date. However, if you just want to add more categories then it's a piece of cake.</p>
					</div>


				</div>
			</div>
		<?php include '/usr/sausalito/ui/adm/includes/dialogs/dialog_welcome.php'?>
		<?php include '/usr/sausalito/ui/adm/includes/dialogs/dialog_logout.php'?>
		<?php include '/usr/sausalito/ui/adm/includes/dialogs/dialog_delete.php'?>
<?php include '/usr/sausalito/ui/adm/includes/core/gui_foot.php'?>
