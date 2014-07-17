<?php include '/usr/sausalito/ui/adm/includes/core/document_head.php'?>
	<div id="pjax">
		<div id="wrapper" data-adminica-nav-top="6" data-adminica-nav-inner="2">
			<?php include '/usr/sausalito/ui/adm/includes/components/topbar.php'?>
			<?php include '/usr/sausalito/ui/adm/includes/components/sidebar.php'?>
			<?php include '/usr/sausalito/ui/adm/includes/components/stackbar.php'?></div><!-- Closing Div for Stack Nav, you can boxes under the stack before this -->

			<div id="main_container" class="main_container container_16 clearfix">
				<?php include '/usr/sausalito/ui/adm/includes/components/navigation.php'?>
				<div class="flat_area grid_16">
					<h2>Tables
						<div class="holder">
							<?php include '/usr/sausalito/ui/adm/includes/components/dynamic_loading.php'?>
						</div>
					</h2>
				</div>
				<div class="box grid_16">
					<h2 class="box_head">Regular Table with Form Inputs</h2>
					<div class="controls">
						<a href="#" class="grabber"></a>
						<a href="#" class="toggle"></a>
					</div>
					<div class="block">
						<table class="static">
							<thead>
								<tr>
									<th>Column 1</th>
									<th>Column 2</th>
									<th>Column 3</th>
									<th>Column 4</th>
									<th></th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td><input type="text"/></td>
									<td><input type="text"/></td>
									<td><input type="text"/></td>
									<td><input type="text"/></td>
									<td>
										<button class="div_icon">
											<div class="ui-icon ui-icon-plusthick"></div>
										</button>
									</td>
								</tr>
								<tr>
									<td>Trident</td>
									<td>Internet
										 Explorer 4.0</td>
									<td>Win 95+</td>
									<td>4</td>
									<td></td>
								</tr>
								<tr>
									<td>Trident</td>
									<td>Internet
										 Explorer 5.0</td>
									<td>Win 95+</td>
									<td>5</td>
									<td></td>
								</tr>
								<tr>
									<td>Trident</td>
									<td>Internet
										 Explorer 5.5</td>
									<td>Win 95+</td>
									<td>5.5</td>
									<td></td>
								</tr>
								<tr>
									<td>Trident</td>
									<td>Internet
										 Explorer 6</td>
									<td>Win 98+</td>
									<td>6</td>
									<td></td>
								</tr>
								<tr>
									<td>Trident</td>
									<td>Internet Explorer 7</td>
									<td>Win XP SP2+</td>
									<td>7</td>
									<td></td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>

				<div class="box grid_16">
					<div class="block">
						<table class="static">
							<thead>
								<tr>
									<th>Rendering engine</th>
									<th>Browser</th>
									<th>Platform(s)</th>
									<th>Engine version</th>
									<th>CSS grade</th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td>Trident</td>
									<td>Internet
										 Explorer 4.0</td>
									<td>Win 95+</td>
									<td>4</td>
									<td>X</td>
								</tr>
								<tr>
									<td>Trident</td>
									<td>Internet
										 Explorer 5.0</td>
									<td>Win 95+</td>
									<td>5</td>
									<td>C</td>
								</tr>
								<tr>
									<td>Trident</td>
									<td>Internet
										 Explorer 5.5</td>
									<td>Win 95+</td>
									<td>5.5</td>
									<td>A</td>
								</tr>
								<tr>
									<td>Trident</td>
									<td>Internet
										 Explorer 6</td>
									<td>Win 98+</td>
									<td>6</td>
									<td>A</td>
								</tr>
								<tr>
									<td>Trident</td>
									<td>Internet Explorer 7</td>
									<td>Win XP SP2+</td>
									<td>7</td>
									<td>A</td>
								</tr>
								<tr>
									<td>Trident</td>
									<td>AOL browser (AOL desktop)</td>
									<td>Win XP</td>
									<td>6</td>
									<td>A</td>
								</tr>
								<tr>
									<td>Gecko</td>
									<td>Firefox 1.0</td>
									<td>Win 98+ / OSX.2+</td>
									<td>1.7</td>
									<td>A</td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
		<?php include '/usr/sausalito/ui/adm/includes/dialogs/dialog_welcome.php'?>
		<?php include '/usr/sausalito/ui/adm/includes/dialogs/dialog_logout.php'?>
<?php include '/usr/sausalito/ui/adm/includes/core/document_foot.php'?>