
<!-- Start: footer_view.php -->
                    <table width="100%" align="center" cellspacing="0" cellpadding="0" border="0">
                        <tbody>
                            <tr>
                                <td><?php echo $wiki; ?></td>
                                <td><p class="footer" align="right"><?php echo $page_render_part_one; ?>&nbsp;<strong>{elapsed_time}</strong>&nbsp;<?php echo $page_render_part_two; ?></p></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
<!-- closes body div -->
<!-- Start: Static footer -->

<!-- Start: dialog_form -->
<!-- End: dialog_form -->
<!-- Start: dialog_delete -->
<!-- End: dialog_delete -->
<!-- Start: dialog_welcome -->
<!-- Not used at the moment -->
<!-- End: dialog_welcome -->
<!-- Start: dialog_logout -->
<div class="display_none">
        <div id="dialog_logout" class="dialog_content narrow" title="<?php echo $logout_text ?>">
                <div class="block">
                        <div class="section">
                                <h1><?php echo $page_title ?></h1>
                                <div class="dashed_line"></div>
                                <p><?php echo $logoutConfirm ?></p>
                        </div>
                        <div class="button_bar clearfix">
                                <button class="dark no_margin_bottom link_button" data-link="/logout/true">
                                        <div class="ui-icon ui-icon-check"></div>
                                        <span><?php echo $logout_text ?></span>
                                </button>
                                <button class="light send_right close_dialog">
                                        <div class="ui-icon ui-icon-closethick"></div>
                                        <span><?php echo $cancel_text ?></span>
                                </button>
                        </div>
                </div>
        </div>
</div> 
<!-- End: dialog_logout -->
<!-- Start: loading_overlay -->
                <div id="loading_overlay">
                        <div class="loading_message round_bottom">
                                <img src="/.adm/images/interface/loading.gif" alt="loading" />
                        </div>
                </div>
<!-- End: loading_overlay -->
        </body>
</html>
