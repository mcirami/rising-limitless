<?php
/**
 * Created by PhpStorm.
 * User: professional slacker
 * Date: 11/6/2017
 * Time: 4:00 PM
 */


require('header.php');

if(isset($_POST["offer_name"]))
{
    $campaignID = \LeadMax\TrackYourStats\Offer\Campaigns::createCampaign($_POST["offer_name"]);

    if($campaignID)
    {
            \LeadMax\TrackYourStats\System\Notify::info("Successfully created advertiser", "!");
    }

}



?>

<!--right_panel-->
<div class="right_panel">
    <div class="white_box_outer">
        <div class="heading_holder value_span9"><span class="lft">Create Advertiser </span></div>
        <div class="white_box value_span8 rl-compact-form-card">
            <form action="campaign_create.php" method="post" id="form" class="rl-compact-form">
                <div class="rl-compact-form-body">
                    <p class="rl-user-field">
                        <label class="rl-field-label" for="offer_name">Advertiser Name</label>
                        <input id="offer_name" name="offer_name" type="text" value="" placeholder="Enter advertiser name" autocomplete="organization" required>
                    </p>
                </div>
                <div class="button_wrap rl-compact-form-actions">
                    <span class="btn_yellow"><input type="submit" name="button" value="Create Advertiser"></span>
                </div>
            </form>
        </div>
    </div>
</div>
<?php include 'footer.php'; ?>
