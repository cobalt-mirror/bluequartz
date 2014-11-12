<?php include '/usr/sausalito/ui/adm/includes/core/document_head.php'?>
	<div id="pjax">
		<div id="wrapper">
			<div class="isolate">
				<div class="center">
					<div class="main_container full_size container_16 clearfix">
						<div class="box light grid_16">
							<h2 class="box_head">Isolated Wizard</h2>
							<div class="controls">
								<div class="wizard_progressbar"></div>
							</div>
							<div class="toggle_container">
								<div class="wizard">

									<div class="wizard_steps">
										<ul class="clearfix">
											<li class="current">
												<a href="#step_1" class="clearfix">
													<span>1. <strong>Basic Information</strong></span>
													<small>Name, email, address, etc.</small>
												</a>
											</li>
											<li>
												<a href="#step_2" class="clearfix">
													<span>2. <strong>Personal Information</strong></span>
													<small>A few more details...</small>
												</a>
											</li>
											<li>
												<a href="#step_3" class="clearfix">
													<span>3. <strong>Unimportant Information</strong></span>
													<small>Were nearly there!</small>
												</a>
											</li>
											<li>
												<a href="#step_4" class="clearfix">
													<span>4. <strong>Finish</strong></span>
													<small>Confirm and complete</small>
												</a>
											</li>
										</ul>
									</div>


									<div class="wizard_content">

		                            	<form action="#" method="post" class="validate_form">
										<div id="step_1" class="step block" style="display:block;">
											<div class="section">
												<h2>1. Account Information</h2>
												<p>Welcome to <a href="/.adm/index.php">Adminica</a>, please enter your information to register on the system.</p>
											</div>
											<div class="columns clearfix">
												<div class="col_50">
													<fieldset class="label_side top">
														<label>Name</label>
														<div>
															<input type="text" name="required_1a" id="required_1a" class="required">
															<div class="required_tag"></div>
				                                        </div>
													</fieldset>
												</div>
												<div class="col_50">
													<fieldset class="label_side top">
														<label>Surname</label>
														<div>
															<input type="text" name="required_1b" id="required_1b" class="required">
															<div class="required_tag"></div>
				                                        </div>
													</fieldset>
												</div>
											</div>
											<div class="columns clearfix">
												<div class="col_50">
													<fieldset class="label_side bottom">
														<label>Email</label>
														<div>
															<input type="email" name="required_1c" id="required_1c" class="required">
															<div class="required_tag"></div>
				                                        </div>
													</fieldset>
												</div>
												<div class="col_50 clearfix">
													<fieldset class="label_side clearfix bottom">
														<label class="clearfix">Phone<span>with country code</span></label>
														<div>
															<input type="number" name="required_1d" id="required_1d" class="required">
															<div class="required_tag"></div>
				                                        </div>
													</fieldset>
												</div>
											</div>

											<div class="button_bar clearfix">
												<button class="next_step forward send_right" data-goto="step_2" type="button">
													<img height="24" width="24" alt="Bended Arrow Right" src="/.adm/images/icons/small/white/bended_arrow_right.png">
													<span>Next Step</span>
												</button>
											</div>
										</div>

										<div id="step_2" class="step block">
											<h2 class="section">2. Personal Information</h2>

											<div class="columns clearfix">
												<div class="col_50">
													<fieldset class="label_side top">
														<label>Date of Birth</label>
														<div class="clearfix">
															<input type="text" name="required_2a" id="required_2a" class="datepicker required" style="width:100px;">
															<div class="required_tag"></div>
														</div>
													</fieldset>
												</div>
												<div class="col_50">
													<fieldset class="label_side top">
														<label>Gender</label>
														<div>
															<div class="jqui_radios">
																<input type="radio" name="required_2b" id="required_2b1"  class="required" /><label for="required_2b1">Male</label>
																<input type="radio" name="required_2b" id="required_2b2" /><label for="required_2b2">Female</label>																						</div>
															<div class="required_tag"></div>
														</div>
													</fieldset>
												</div>
											</div>
											<fieldset>
												<label>Biography<span>What an annoying question in a registration wizard! Luckily it is not required.</span></label>
												<div class="clearfix">
													<textarea class="autogrow" placeholder="Once upon a time..."></textarea>
												</div>
											</fieldset>

											<div class="button_bar clearfix">
												<button class="next_step back light" data-goto="step_1" type="button">
													<img height="24" width="24" alt="Bended Arrow Right" src="/.adm/images/icons/small/grey/bended_arrow_left.png">
													<span>Prev Step</span>
												</button>
												<button class="next_step forward send_right" data-goto="step_3" type="button">
													<img height="24" width="24" alt="Bended Arrow Right" src="/.adm/images/icons/small/white/bended_arrow_right.png">
													<span>Next Step</span>
												</button>
											</div>
										</div>

										<div id="step_3" class="step block">
											<div class="section">
												<h2>3. Unimportant Information</h2>
												<p>These fields aren't so important and so can be skipped as there aren't any required fields.</p>
											</div>

											<fieldset class="label_side top">
												<label>Text Field<span>Just a regular field</span></label>
												<div>
													<input type="text" name="required_3a" id="required_3a">
		                                        </div>
											</fieldset>

											<fieldset class="label_side">
												<label>Text Field<span>Another regular field</span></label>
												<div>
													<input type="text" name="required_3b" id="required_3b">
		                                        </div>
											</fieldset>

											<div class="button_bar clearfix">
												<button class="next_step back light" data-goto="step_2" type="button">
													<img height="24" width="24" alt="Bended Arrow Right" src="/.adm/images/icons/small/grey/bended_arrow_left.png">
													<span>Prev Step</span>
												</button>
												<button class="next_step forward send_right" data-goto="step_4" type="button">
													<img height="24" width="24" alt="Bended Arrow Right" src="/.adm/images/icons/small/white/bended_arrow_right.png">
													<span>Next Step</span>
												</button>
											</div>
										</div>

										<div id="step_4" class="step block">
											<div class="section">
												<h2>4. Finish</h2>
												<p>If all your fields are valid this form is going to submit, EXCITING.</p>
											</div>

											<fieldset class="label_side top">
												<label>Last field<span>Just one more...</span></label>
												<div>
												<input type="text" name="required_4a" id="required_4a" class="required">
												<div class="required_tag"></div>
		                                        </div>
											</fieldset>

											<fieldset class="label_side">
												<label>Permission</label>
												<div class="uniform inline clearfix">
													<input type="checkbox" name="required_4b" id="required_4b" class="required"/><label>I agree with the terms and conditions</label>
													<label for="required_4b" generated="true" class="error">This field is required.</label>
												</div>
											</fieldset>

											<div class="button_bar clearfix">
												<button class="next_step back light" data-goto="step_3" type="button">
													<img height="24" width="24" alt="Bended Arrow Right" src="/.adm/images/icons/small/grey/bended_arrow_left.png">
													<span>Prev Step</span>
												</button>
												<button class="next_step green send_right submit_button" type="button">
													<img height="24" width="24" alt="Bended Arrow Right" src="/.adm/images/icons/small/white/bended_arrow_right.png">
													<span>Complete</span>
												</button>
											</div>
										</div>
		                           	</form>
									</div>

								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
<?php include '/usr/sausalito/ui/adm/includes/core/document_foot.php'?>