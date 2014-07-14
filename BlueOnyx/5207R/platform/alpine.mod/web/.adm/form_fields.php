<?php include '/usr/sausalito/ui/adm/includes/core/document_head.php'?>
	<div id="pjax">
		<div id="wrapper" data-adminica-nav-top="4" data-adminica-nav-inner="1">
			<?php include '/usr/sausalito/ui/adm/includes/components/topbar.php'?>
			<?php include '/usr/sausalito/ui/adm/includes/components/sidebar.php'?>
			<?php include '/usr/sausalito/ui/adm/includes/components/stackbar.php'?></div><!-- Closing Div for Stack Nav, you can boxes under the stack before this -->

			<div id="main_container" class="main_container container_16 clearfix">
			<?php include '/usr/sausalito/ui/adm/includes/components/navigation.php'?>
				<div class="flat_area grid_16">
					<h2>Form elements and controls
						<div class="holder">
							<?php include '/usr/sausalito/ui/adm/includes/components/dynamic_loading.php'?>
						</div>
					</h2>
					<p>Check out the Application like <a href="#">navigation</a>. Resize to see the liquid layout in action. Expand/Collapse and sort boxes. Try out the WYSIWYGs.</p>
				</div>
				<div class="grid_16 box clearfix">
					<div class="indented_button_bar clearfix">
						<button class="light on_dark" id="toggle_lines">
							<span>Toggle Form Lines</span>
						</button>
						<p>If you prefer a simple style just add <strong>class = "no_lines"</strong>. You can also have combinations.</p>
					</div>
				</div>
				<div class="box grid_16">
					<h2 class="box_head">Form Elements</h2>
					<div class="controls">
						<a href="#" class="grabber"></a>
						<a href="#" class="toggle"></a>
					</div>
					<div class="toggle_container">
						<div class="block">
							<h2 class="section">Text Fields</h2>

							<fieldset class="label_side top">
								<label>Text Field<span>Label placed beside the Input</span></label>
								<div>
									<input type="text">
								</div>
							</fieldset>

							<fieldset class="label_side">
								<label>Textarea<span>Regular Textarea</span></label>
								<div class="clearfix">
									<textarea></textarea>
								</div>
							</fieldset>

							<fieldset>
								<label>Text Field<span>Label placed above the Input. Also has a required icon at the top right.</span></label>
								<div>
									<input title="Here's a tool tip for this input field." class="tooltip right" type="text">
									<div class="required_tag tooltip hover left" title="This field is required"></div>
								</div>
							</fieldset>

							<fieldset>
								<label>Autogrow Textarea<span>This textarea grows as you type</span></label>
								<div class="clearfix">
									<textarea title="This field keeps expanding, just like on Facebook" class="tooltip autogrow" placeholder="You keep typing, I keep growing…"></textarea>
									<div class="required_tag tooltip hover left" title="This field is required"></div>
								</div>
							</fieldset>
							<div class="columns">
								<div class="col_60">
									<fieldset>
										<label>60% Width Column</label>
										<div>
											<input type="text" placeholder="This input is 60% of page width">
										</div>
									</fieldset>
								</div>
								<div class="col_40">
									<fieldset class="right">
										<label>40% Width Column</label>
										<div class="clearfix">
											<select class="uniform full_width">
												<?php include '/usr/sausalito/ui/adm/includes/content/select_colours.php'?>
											</select>
										</div>
									</fieldset>
								</div>
							</div>

							<fieldset class="label_side">
								<label>Autocomplete<span>Programming</span></label>
								<div>
									<input type="text" class="autocomplete">
								</div>
							</fieldset>

							<div class="button_bar clearfix">
								<button class="green dark">
									<img src="/.adm/images/icons/small/white/bended_arrow_right.png">
									<span>Yes</span>
								</button>
								<button class="red dark">
									<img src="/.adm/images/icons/small/white/bended_arrow_right.png">
									<span>No</span>
								</button>
								<button class="grey dark send_right">
									<img src="/.adm/images/icons/small/white/bended_arrow_right.png">
									<span>Maybe</span>
								</button>
							</div>
						</div>
					</div>
				</div>
				<div class="box grid_16">
					<div class="toggle_container">
						<div class="block">
							<h2 class="section">Buttons</h2>
							<fieldset class="label_side top">
								<label>Buttons</label>
								<div>
									<button class="skin_colour">
										<img src="/.adm/images/icons/small/white/file_cabinet.png">
										<span>Button</span>
									</button>

									<button class="black icon_only">
										<img src="/.adm/images/icons/small/white/file_cabinet.png">
									</button>

									<button class="blue icon_only">
										<img src="/.adm/images/icons/small/white/film_strip.png">
									</button>

									<button class="navy icon_only">
										<img src="/.adm/images/icons/small/white/firefox.png">
									</button>

									<button class="red icon_only">
										<img src="/.adm/images/icons/small/white/flag.png">
									</button>

									<button class="green icon_only">
										<img src="/.adm/images/icons/small/white/folder.png">
									</button>

									<button class="orange icon_only">
										<img src="/.adm/images/icons/small/white/frames.png">
									</button>

									<div class="clearfix"></div>
									<p class="comment"><a href="/.adm/buttons.php">Click here</a> to see the full 500+ icon set including all the jQuery UI action icons.</p>

								</div>
							</fieldset>



							<div class="columns clearfix">
								<div class="col_33">
									<fieldset class="bottom">
										<label>Radio Buttons</label>
										<div>
											<div class="jqui_radios">
												<input type="radio" name="answer5" id="yes5"/><label for="yes5">Yes</label>
												<input type="radio" name="answer5" id="no5" checked="checked"/><label for="no5">No</label>																									</div>
										</div>
									</fieldset>
								</div>
								<div class="col_33">
									<fieldset class="bottom">
										<label>Checkbox Buttons</label>
										<div>
											<div class="jqui_checkbox">
												<input type="checkbox" name="answer6" id="yes6"/><label for="yes6">One</label>
												<input type="checkbox" name="answer6" id="no6"/><label for="no6">Two</label>
												<input type="checkbox" disabled="disabled" name="answer6" id="cant6"/><label for="cant6">Three</label>
											</div>
										</div>
									</fieldset>
								</div>
								<div class="col_33">
									<fieldset class="right bottom">
										<label for="file_upload">Uniform File Upload</label>

										<div class="clearfix">
											<input type="file" id="fileupload" class="uniform">
										</div>
									</fieldset>
								</div>
							</div>


						</div>
					</div>
				</div>
				<div class="box grid_16">
					<div class="toggle_container">
						<div class="block">
							<h2 class="section">Dialogs <small>All form fields can also be easily displayed in a dialog box.</small></h2>
							<fieldset class="label_side top bottom">
								<label>Dialog</label>
								<div class="clearfix">
									<button class="dialog_button" data-dialog="dialog_form">
										<img src="/.adm/images/icons/small/white/speech_bubble_2.png">
										<span>Open</span>
									</button>
								</div>
							</fieldset>
						</div>
					</div>
				</div>
				<div class="box grid_16 round_all">
					<div class="toggle_container">
						<div class="block">
							<h2 class="section">Select Boxes</h2>
							<div class="columns clearfix">
								<div class="col_50">
									<fieldset class="label_side top">

										<label>Uniform</label>
										<div class="clearfix">
											<select class="uniform full_width" multiple>
												<?php include '/usr/sausalito/ui/adm/includes/content/select_countries.php'?>
											</select>
										</div>
									</fieldset>
								</div>
								<div class="col_50">
									<fieldset class="label_side top right">
										<label><span class="alert badge alert_red">5</span>Browser</label>
										<div>
											<select class="full_width">
												<?php include '/usr/sausalito/ui/adm/includes/content/select_countries.php'?>
											</select>
										</div>
									</fieldset>
								</div>
								<div class="col_100">
									<fieldset class="bottom">
										<label>Filter & Search Draggable Multiselect</label>
										<div>
											<select class="multisorter indent" multiple="multiple" style="height:230px;">
												<?php include '/usr/sausalito/ui/adm/includes/content/select_colours.php'?>
											</select>
										</div>
									</fieldset>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="box grid_8">
					<div class="toggle_container">
						<div class="block">
							<h2 class="section">Radios & Checkboxes<small>Browser</small></h2>

							<fieldset class="label_side top">
								<label>Radios</label>
								<div>
									<label for="yes1"><input type="radio" name="answer1" id="yes1"/>Yes</label>
									<label for="no1"><input type="radio" name="answer1" id="no1"/>No</label>
									<label for="cant1"><input type="radio" disabled="disabled" name="answer1" id="cant1"/>Cant</label>
								</div>
							</fieldset>
							<fieldset class="label_side">
								<label>Checkboxes</label>
								<div>
									<label for="yes2"><input type="checkbox" name="answer2" id="yes2"/>Option 1</label>
									<label for="no2"><input type="checkbox" name="answer2" id="no2"/>Option 2</label>
									<label for="cant2"><input type="checkbox" disabled="disabled" name="answer2" id="cant2"/>Option 4</label>
								</div>
							</fieldset>

							<fieldset class="label_side">
								<label>Inline</label>
								<div class="inline clearfix">
									<label for="yes1b"><input type="radio" name="answer1b" id="yes"/>Yes</label>
									<label for="no1b"><input type="radio" name="answer1b" id="no"/>No</label>
								</div>
							</fieldset>
							<fieldset class="label_side bottom">
								<label>Inline</label>
								<div class="inline clearfix">
									<label for="yes2b"><input type="checkbox" name="answer2b" id="yes2b"/>One</label>
									<label for="no2b"><input type="checkbox" name="answer2b" id="no2b"/>Two</label>
								</div>
							</fieldset>
						</div>
					</div>
				</div>
				<div class="box grid_8">
					<div class="toggle_container">
						<div class="block">
							<h2 class="section">Radios & Checkboxes<small>Uniform.js</small></h2>
							<fieldset class="label_side top">
								<label>Radios</label>
								<div class="uniform">
									<label for="yes3"><input type="radio" name="answer3" id="yes3"/>Yes</label>
									<label for="no3"><input type="radio" name="answer3" id="no3"/>No</label>
									<label for="cant3"><input type="radio" disabled="disabled" name="answer3" id="cant3"/>Cant</label>
								</div>
							</fieldset>

							<fieldset class="label_side">
								<label>Checkboxes</label>
								<div class="uniform">
									<label for="yes4"><input type="checkbox" name="answer4" id="yes4"/>Option 1</label>
									<label for="no4"><input type="checkbox" name="answer4" id="no4"/>Option 2</label>
									<label for="cant4"><input type="checkbox" disabled="disabled" name="answer4" id="cant4"/>Option 3</label>
								</div>
							</fieldset>

							<fieldset class="label_side">
								<label>Inline</label>
								<div class="uniform inline clearfix">
									<label for="yes3b"><input type="radio" name="answer3b" id="yes3b"/>Yes</label>
									<label for="no3b"><input type="radio" name="answer3b" id="no3b"/>No</label>
								</div>
							</fieldset>

							<fieldset class="label_side bottom">
								<label>Inline</label>
								<div class="uniform inline clearfix">
									<label for="yes4b"><input type="checkbox" name="answer4b" id="yes4b"/>One</label>
									<label for="no4b"><input type="checkbox" name="answer4b" id="no4b"/>Two</label>
								</div>
							</fieldset>
						</div>
					</div>
				</div>

				<div id="date_picker_anchor" class="box grid_16">
					<div class="toggle_container">
						<div class="block">
							<h2 class="section">Time and Date</h2>
							<div class="columns clearfix">
								<div class="col_50">
									<fieldset class="label_side label_small top bottom">
										<label>Date Picker</label>
										<div class="clearfix">
											<div class="datepicker"></div>
										</div>
									</fieldset>
								</div>
								<div class="col_50">
									<fieldset class="label_side top right">
										<label>Date Picker</label>
										<div class="clearfix">
											<input type="text" class="datepicker" style="width:100px;">
										</div>
									</fieldset>
									<fieldset class="label_side right">
										<label>Time Picker</label>
										<div class="clearfix time_picker_holder">
											<div class="time_picker"></div>
										</div>
									</fieldset>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="main_container container_16 clearfix">
				<div class="box grid_16">
					<div class="toggle_container">
						<div class="block">
							<h2 class="section">Miscellaneous</h2>

							<fieldset class="label_side top">
								<label>Slider</label>
								<div>
									<div style="width:auto; margin-right: 45px;" class="slider has_labels">
										<ol class="slider_labels">
											<li>0</li>
											<li>10</li>
											<li>20</li>
											<li>30</li>
											<li>40</li>
											<li>50</li>
											<li>60</li>
											<li>70</li>
											<li>80</li>
											<li>90</li>
											<li class="last-child">100</li>
										</ol>
									</div>
									<strong id="slider_value" style="float: right; font-size: 16px; margin-top: -15px;">0</strong>
								</div>
							</fieldset>

							<fieldset class="label_side">
								<label>Vertical Sliders</label>
								<div class="clearfix">
									<div class="slider_vertical">
										<span>88</span>
										<span>77</span>
										<span>55</span>
										<span>33</span>
										<span>40</span>
										<span>45</span>
										<span>70</span>
										<span>60</span>
										<span>55</span>
										<span>53</span>
										<span>58</span>
										<span>70</span>
										<span>86</span>
										<span>75</span>
										<span>45</span>
										<span>53</span>
										<span>60</span>
										<span>55</span>
										<span>53</span>
										<span class="last-child">70</span>
									</div>
								</div>
							</fieldset>

							<fieldset class="label_side">
								<label>Slider Range</label>
								<div>
									<div class="slider_range"></div>
								</div>
							</fieldset>
							<fieldset class="label_side">
								<label>Slider Unlock</label>
								<div>
									<div class="slider_unlock" title="Slide all the way to activate"></div>
									<button type="submit" style="display:none" class="dialog_button" data-dialog="dialog_form"></button>
									<p class="comment">Here it is used to popup a dialog but it can be used for anything, for example: submit a form or follow a link.</p>
								</div>
							</fieldset>

							<fieldset class="label_side">
								<label>Progress Bar</label>
								<div>
									<div class="progressbar"></div>

								</div>
							</fieldset>
							<fieldset class="label_side">
								<label>Colour Picker</label>
								<div>
									<div id="colorpicker_inline"></div>
								</div>
							</fieldset>

							<fieldset class="label_side bottom">
								<label>Star Ratings</label>
								<div class="clearfix">
									<div id="star_rating">
										<select name="selrate">
											<option value="1">Very poor</option>
											<option value="2">Not that bad</option>
											<option value="3">Average</option>
											<option value="4" selected="selected">Good</option>
											<option value="5">Perfect</option>
										</select>
									</div>
								</div>
							</fieldset>
						</div>
					</div>
				</div>
				<div class="box grid_8">
					<div class="toggle_container">
						<div class="block">
							<div class="section">
								<div class="alert dismissible alert_blue">
								<img height="24" width="24" src="/.adm/images/icons/small/white/alert_2.png">
								This is a <strong>blue</strong> Alert!
								</div>
								<div class="alert dismissible alert_green">
								<img height="24" width="24" src="/.adm/images/icons/small/white/alert.png">
								This is a <strong>green</strong> Alert!
								</div>
								<div class="alert dismissible alert_red">
								<img height="24" width="24" src="/.adm/images/icons/small/white/alarm_bell.png">
								This is a <strong>red</strong> Alert!
								</div>
								<div class="alert alert_orange">
								<img height="24" width="24" src="/.adm/images/icons/small/white/alarm_clock.png">
								This is an <strong>orange</strong> Alert and cannot be dismissed.
								</div>
							</div>
						</div>
					</div>
				</div>
				<div id="alerts_anchor" class="box grid_8">
					<div class="toggle_container">
						<div class="block">
							<div class="section">
								<div class="alert dismissible alert_navy">
								<img height="24" width="24" src="/.adm/images/icons/small/white/cog_3.png">
								This is a <strong>navy</strong> Alert!
								</div>
								<div class="alert dismissible alert_black">
								<img height="24" width="24" src="/.adm/images/icons/small/white/locked_2.png">
								This is a <strong>black</strong> Alert!
								</div>
								<div class="alert dismissible alert_grey">
								<img height="24" width="24" src="/.adm/images/icons/small/white/speech_bubble_2.png">
								This is a <strong>grey</strong> Alert!
								</div>
								<div class="alert alert_light">
								<img height="24" width="24" src="/.adm/images/icons/small/grey/speech_bubble_2.png">
								This alert cannot be dismissed.
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php include '/usr/sausalito/ui/adm/includes/dialogs/dialog_welcome.php'?>
		<?php include '/usr/sausalito/ui/adm/includes/dialogs/dialog_logout.php'?>
		<?php include '/usr/sausalito/ui/adm/includes/dialogs/dialog_form.php'?>
<?php include '/usr/sausalito/ui/adm/includes/core/document_foot.php'?>