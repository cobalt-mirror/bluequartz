<?php include '/usr/sausalito/ui/adm/includes/core/document_head.php'?>
	<div id="pjax">
		<div id="wrapper" data-adminica-nav-top="3" data-adminica-nav-inner="1">
			<?php include '/usr/sausalito/ui/adm/includes/components/topbar.php'?>
			<?php include '/usr/sausalito/ui/adm/includes/components/sidebar.php'?>
			<?php include '/usr/sausalito/ui/adm/includes/components/stackbar.php'?></div><!-- Closing Div for Stack Nav, you can boxes under the stack before this -->

			<div id="main_container" class="main_container container_16 clearfix">
				<?php include '/usr/sausalito/ui/adm/includes/components/navigation.php'?>
				<div class="flat_area grid_16">
					<h2>Tab Controls
						<div class="holder">
							<?php include '/usr/sausalito/ui/adm/includes/components/dynamic_loading.php'?>
						</div>
					</h2>
					<p>Like accordions, tabs are a great way to<strong> present alot of content/data without overwhelming the user</strong>. <strong>Adminica</strong> has two types: a regular <strong>horizontal tab</strong> layout and a <strong>vertical tab</strong> layout. </p>
					<p><strong>Note: </strong>Like nearly all Adminica layout objects, the tab boxes width can be controlled using the <a target="_blank" href="http://www.960.gs">960.gs Grid System</a>. In this example, both tab boxes have a <strong>class="grid_8".</strong></p>
				</div>

				<div class="box grid_8 tabs">
					<ul class="tab_header clearfix">
						<li><a href="#tabs-1">Quicklinks</a></li>
						<li><a href="#tabs-2">Content</a></li>
					</ul>
					<div class="controls">
						<a href="#" class="grabber"></a>
						<a href="#" class="toggle"></a>
						<a href="#" class="show_all_tabs"></a>
					</div>
					<div class="toggle_container">
						<div id="tabs-1" class="block">
							<ul class="flat medium">
								<li><span class="spark_bar small random_number_5 spark_inline"></span> Aenean tempor ullamcorper</li>
								<li><span class="spark_line small random_number_5 spark_inline"></span>Rutrum commodo, vehicula tempus</li>
								<li><span class="spark_bar small random_number_5 spark_inline"></span><a href="#">Curabitur nec arcu</a></li>
							</ul>
						</div>
						<div id="tabs-2" class="block">
							<div class="section">
								<h1>Primary Heading</h1>
								<p>Lorem Ipsum is simply dummy text of the <a href="#" title="This is a tooltip">printing industry</a>. Lorem Ipsum has been the industry’s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book.</p>
								<h2>Secondary Heading</h2>
								<p>Lorem Ipsum is simply dummy text of the printing industry. Lorem Ipsum has been the industry’s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book.</p> 					</div>
						</div>
					</div>
				</div>
				<div class="box grid_8 side_tabs tabs">
					<div class="side_holder">
						<ul class="tab_sider clearfix">
							<li><a href="#tabs-a">Daily Stats</a></li>
							<li><a href="#tabs-b">Content</a></li>
							<li><a href="#tabs-c">Tab 3</a></li>
							<li><a href="#tabs-d">Tab 4</a></li>
							<li><a href="#tabs-e">Tab 5</a></li>
						</ul>
					</div>
					<div id="tabs-a" class="block">
						<ul class="flat">
							<li><span class="spark_line small random_number_5 spark_inline"></span><strong>341 </strong>Items</li>
							<li><span class="spark_line small random_number_5 spark_inline"></span><strong>892 </strong>Posts</li>
							<li><span class="spark_line small random_number_5 spark_inline"></span><strong>12,062 </strong>Comments</li>
							<li><span class="spark_line small random_number_5 spark_inline"></span><strong>90,568 </strong>Members</li>
							<li><span class="spark_line small random_number_5 spark_inline"></span><strong>103,451 </strong>Unique Visitors</li>
							<li><span class="spark_line small random_number_5 spark_inline"></span><strong>983,876 </strong>Hits</li>
							<li><span class="spark_line small random_number_5 spark_inline"></span><strong>7,543,948 </strong>Monthly Hits</li>
						</ul>
					</div>
					<div id="tabs-b" class="block">
						<div class="section">
							<h1>Primary Heading</h1>
							<p>Lorem Ipsum is simply dummy text of the <a href="#" title="This is a tooltip">printing industry</a>. Lorem Ipsum has been the industry’s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book.</p>

							<h2>Secondary Heading</h2>
							<p>Lorem Ipsum is simply dummy text of the printing industry. Lorem Ipsum has been the industry’s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book.</p>
						</div>
					</div>
					<div id="tabs-c" class="block">
						<div class="section">
							<p>Content goes here.</p>
						</div>
					</div>
					<div id="tabs-d" class="block">
						<div class="section">
							<p>Content goes here.</p>
						</div>
					</div>
					<div id="tabs-e" class="block">
						<div class="section">
							<p>Content goes here.</p>
						</div>
					</div>
				</div>
			</div>
		</div>

		<?php include '/usr/sausalito/ui/adm/includes/dialogs/dialog_welcome.php'?>
		<?php include '/usr/sausalito/ui/adm/includes/dialogs/dialog_logout.php'?>
<?php include '/usr/sausalito/ui/adm/includes/core/document_foot.php'?>