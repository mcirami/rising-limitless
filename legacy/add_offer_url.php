<?php
/**
 * Author: Dean
 * Email: dwm348@gmail.com
 * Date: 10/30/2017
 * Time: 11:16 AM
 */

$section = "offer-urls";

require('header.php');


if (!\LeadMax\TrackYourStats\System\Session::permissions()->can("edit_offer_urls")) {
    send_to("home.php");
}

if (isset($_POST['submit'])) {
    $URLs = new \LeadMax\TrackYourStats\Offer\URLs(\LeadMax\TrackYourStats\System\Company::loadFromSession());
    if ($URLs->createOfferURL($_POST["url"], $_POST["status"])) {
        send_to("offer_urls.php");
    }
}


?>

<!--right_panel-->
<div class="right_panel">
    <div class="white_box_outer large_table ">
        <div class="heading_holder">
            <span class="lft value_span9">Create Offer URL</span>

        </div>

        <div class="white_box value_span8 rl-compact-form-card">
            <form action="add_offer_url.php" method="post" class="rl-compact-form">
                <div class="rl-compact-form-body">
                    <div class="rl-form-note"><i class="fas fa-info-circle" aria-hidden="true"></i><span>Point the offer URL to this server IP: <strong><?= htmlspecialchars($_SERVER["SERVER_ADDR"] ?? '', ENT_QUOTES, 'UTF-8') ?></strong></span></div>
                    <p class="rl-user-field">
                        <label class="rl-field-label" for="url">Offer URL</label>
                        <input id="url" type="text" name="url" value="" placeholder="https://example.com" inputmode="url" required>
                    </p>
                    <p class="rl-user-field">
                        <label class="rl-field-label" for="status">Status</label>
                        <select id="status" name="status">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </p>
                </div>
                <div class="button_wrap rl-compact-form-actions">
                    <span class="btn_yellow"><input type="submit" value="Create Offer URL" name="submit"></span>
                </div>
            </form>
        </div>
    </div>
    <!--right_panel-->


    <?php include 'footer.php'; ?>
