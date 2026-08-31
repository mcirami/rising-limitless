<?php

use App\Http\Controllers\UserController;
use LeadMax\TrackYourStats\System\Session;

$section = "affiliate-list";
require('header.php');


$assign = new \LeadMax\TrackYourStats\Table\Assignments(
	[
		"offerid"  => -1,
		"out"      => -1,
		"!idrep"   => Session::userID(),
		"clearAtt" => -1
	]
);

$assign->getAssignments();
$assign->setGlobals();

$idrep = (int)$idrep;

//checks to see if this User is a child of logged in User, if not redirect
if ( Session::userID() !== $idrep)
{
	if (!$user->hasRep($idrep) && Session::userType() == \App\Privilege::ROLE_MANAGER)
	{
		send_to("home.php");
	}
}

if ($idrep !== Session::userID())
{

	if (! Session::permissions()->can("edit_affiliates"))
	{
		send_to("home.php");
	}
}

$update = new \LeadMax\TrackYourStats\User\Update($assign);

//$update->updateAffiliatePayout();


$update->selectUser();


//run update
if ( Session::userType() == \App\Privilege::ROLE_GOD || Session::userType() == \App\Privilege::ROLE_ADMIN)
{
	$ezGawd = true;
}
else
{
	$ezGawd = false;
}


$insert = $user->Update("aff_update.php?idrep={$idrep}", $ezGawd);

$error = "";
if ($insert == "PWD_NO_MATCH")
{
	$error = "Passwords do not match.";
}


$update->dumpAssignablesToJavaScript();
$update->dumpPermissionsToJavascript();


?>
<script type = "text/javascript" src = "js/aff.js"></script>

<div id="error_message">
	<svg  style="color: red" width="34" height="34" viewBox="0 0 24 24" fill="red" xmlns="http://www.w3.org/2000/svg">
		<path d="M12 4a8 8 0 1 0 0 16 8 8 0 0 0 0-16zM2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12z" fill="red"/>
		<path d="M12 14a1 1 0 0 1-1-1V7a1 1 0 1 1 2 0v6a1 1 0 0 1-1 1zm-1.5 2.5a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0z" fill="red"/>
	</svg>
	<p></p>
</div>
<!--right_panel-->
<div class = "right_panel">
	<div class = "white_box_outer">
		<div class="rounded mx-auto mt-10 columns-1 rl-user-edit-shell">
			<div class="top_section_user_edit border-b rl-user-tabs-bar">
				<!-- Tabs -->
				<ul id="tabs" class="inline-flex pt-2 px-1">
					<li class="px-10 py-4 font-semibold rounded-t-xl -mb-px value_span6-1 value_span4 is-active">
						<a class="value_span2" id="default-tab" href="#account">Account</a>
					</li>
					<?php if( Session::permissions()->can("edit_affiliates")) : ?>
						<li class="px-10 py-4 rounded-t-xl value_span4">
							<a href="#sub_ids">Sub ID's</a>
						</li>
					<?php endif; ?>
					<?php if ( Session::permissions()->can("edit_affiliates")) : ?>
						<li class="px-10 py-4 rounded-t-xl value_span4">
							<a href="#offers">Offers</a>
						</li>
					<?php endif; ?>
				</ul>
				<?php if( Session::userType() != \App\Privilege::ROLE_AFFILIATE) : ?>
					<a class="rl-button rl-login-as-user"
					   data-toggle='tooltip'
					   title="Login into this user"
					   href="#"
					   onclick="adminLogin('<?php echo $idrep; ?>')">
						Login
					</a>
				<?php endif; ?>
			</div>
			<!-- Tab Contents -->
			<div id="user_info" class="columns-1">
				<div id="account" class="p-4 columns-1 rl-user-account-tab">
					<div class="rl-page-heading"><div><h1>Edit User</h1><p>Update <?php echo htmlspecialchars(trim($update->selectedUser->first_name . " " . $update->selectedUser->last_name)); ?>'s account and access.</p></div></div>
					<div class = "white_box value_span8 rl-user-form-card">
						<span class = "small_txt value_span10"><?PHP echo $error; ?></span>

						<form action="<?php htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" id="form" class="rl-user-form" enctype="multipart/form-data">
							<input type="hidden" name="idrep" value="<?php echo $update->selectedUser->idrep; ?>">
							<div class="column_wrap rl-user-form-grid">
								<div class="left_con01">
									<h3>User Details</h3>
									<div class="rl-name-grid">
										<p><label class="value_span9">First Name</label><input type="text" class="form-control" name="first_name" maxlength="155" value="<?php echo htmlspecialchars($update->selectedUser->first_name); ?>" id="first_name"></p>
										<p><label class="value_span9">Last Name</label><input type="text" class="form-control" name="last_name" maxlength="155" value="<?php echo htmlspecialchars($update->selectedUser->last_name); ?>" id="last_name"></p>
									</div>
									<p><label class="value_span9">Username</label><input type="text" class="form-control" name="user_name" maxlength="155" value="<?php echo htmlspecialchars($update->selectedUser->user_name); ?>" id="user_name" <?php echo Session::userType() == \App\Privilege::ROLE_GOD ? '' : 'readonly'; ?>></p>
									<p><label class="value_span9">Password</label><input type="password" class="form-control" name="password" minlength="5" maxlength="255" value="" id="password" autocomplete="new-password"></p>
									<p><label class="value_span9">Re-Enter Password</label><input type="password" class="form-control" name="confirmpassword" minlength="5" maxlength="255" value="" id="confirmpassword" autocomplete="new-password"></p>
								</div>
								<div class="right_con01">
									<h3>Account Details</h3>
									<p><label class="value_span9">Status</label><select class="form-control input-sm" id="status" name="status"><option value="1" <?php echo $update->selectedUser->status == 1 ? 'selected' : ''; ?>>Active</option><option value="0" <?php echo $update->selectedUser->status == 0 ? 'selected' : ''; ?>>Disabled</option></select></p>
									<?php if ($ezGawd): ?>
										<div class="rl-user-field"><span class="rl-field-label">Account Type</span><?php $update->printRadios(); ?></div>
										<?php $update->notifyIfCanChangePriviliges(); ?>
									<?php endif; ?>
									<div class="rl-user-field"><span class="rl-field-label">Permissions</span><div class="value_span10 rl-permissions-list" id="permissionsP"></div></div>
									<?php if (Session::userType() == \App\Privilege::ROLE_GOD || Session::userType() == \App\Privilege::ROLE_ADMIN) { $update->printReferrer(); } ?>
								</div>
							</div>
							<div class="button_wrap rl-user-form-actions">
								<span class="btn_yellow"><a onclick="history.go(-1);" class="value_span6-2 value_span2 value_span1-2">Cancel</a></span>
								<span class="btn_yellow"><input type="submit" name="button" class="value_span6-2 value_span2 value_span1-2" value="Save Changes"></span>
							</div>
						</form>


					</div>
				</div>
				<?php
				if( Session::permissions()->can("edit_affiliates")) :

					$userClass = new UserController();
					$subIds = $userClass->getUserSubIds();

					?>
					<div id="sub_ids" class="hidden p-4 columns-1">
						<div class = "heading_holder value_span9">
							<span class = "lft"><?php echo $update->selectedUser->first_name . " " . $update->selectedUser->last_name; ?>'s Sub Id's</span>
						</div>
						<div class="form-group searchDiv">
							<input id="searchBox" class="form-control" type="search" placeholder="Search Sub Id's..." />
						</div>
						<div class="white_box value_span8">
							<table class="table_01 large_table sub_ids" id="mainTable">
								<thead>
								<tr>
									<th class="value_span9">Sub ID</th>
									<th class="value_span9">Action</th>
								</tr>
								</thead>
								<tbody id="subid_content"></tbody>
							</table>
						</div>
					</div>
				<?php endif; ?>


				<?php

				if ( Session::permissions()->can("edit_affiliates"))
				{

					echo " <div id=\"offers\" class=\"hidden p-4 columns-1\"><div class=\"heading_holder value_span9\"><span
                    class=\"lft\">{$update->selectedUser->first_name} 's offers.</span></div>

				        <div class=\"white_box_outer\">
				
				            <div class=\"white_box manage_aff value_span8\">
				                <p>
				                <table class=\"table_01   large_table\" id=\"mainTable\">
				                    <thead>
				
				                    <tr>
				
				                        <th class=\"value_span9\">Offer ID</th>
				                        <th class=\"value_span9\">Offer Name</th>";

					if(Session::userType() == \App\Privilege::ROLE_GOD) {
						echo "<th class=\"value_span9\">Offer Payout</th>";
					}

					if ( Session::userType() == \App\Privilege::ROLE_GOD)
					{
						echo "<th class=\"value_span9\">Change Aff Payout</th>";
					}

					if ( Session::userType() == \App\Privilege::ROLE_GOD)
					{
						echo "<th class=\"value_span9\">Offer Access</th>";
					}

					if (Session::userType() == \App\Privilege::ROLE_GOD) {
						echo "<th class=\"value_span9\">Offer Cap</th>
								<th class=\"value_span9\">Daily Max Conversions</th>";
					}

					echo "
				
				                    </tr>
				                    </thead>
				                    <tbody>";


					$update->getAffiliateOfferInfo();


					echo "</tbody>
				                </table>
				
				
				            </div>
				
				
				            </p>
				
				        </div></div>";
				}

				?>

			</div>
		</div>

	</div>


</div>

<?php $update->checkBox() ?>

<!--right_panel-->
<script src="https://cdn.jsdelivr.net/npm/axios@1.8.4/dist/axios.min.js"></script>

<script type = "text/javascript">

	$("#salaryCheckBox").click(function () {
		if ($("#salaryCheckBox").is(":checked"))
			$("#salaryPaid").prop("disabled", false);
		else
			$("#salaryPaid").prop("disabled", true);
	});

	// A $( document ).ready() block.

	const subIds = JSON.parse('<?php echo $subIds; ?>');
	const idrep = '<?php echo $idrep; ?>';
	displayContent(subIds);

	document.getElementById('searchBox').addEventListener('input', (e) => {
		const userInput = e.target.value.trim().toLowerCase();
		let filteredSubIds = subIds.filter((subId) => {
			return subId.subId.toLowerCase().includes(userInput);
		})

		displayContent(filteredSubIds);

	});

	function displayContent(subIds) {

		let html = "";
		subIds.forEach((subId) => {
			html += "<tr>" +
				"<td>" + subId['subId'] + "</td>" +
				"<td class='button_wrap'>";
			if (subId["blocked"]) {
				html += "<button class='block_sub_id' disabled='disabled'" +
					" data-subid='" + subId["subId"] + "'" +
					" data-rep='" + idrep + "'" +
					">Blocked</button>" +
					"<button class='unblock_button value_span6-2 value_span2 value_span1-2'" +
					" data-subid='" + subId["subId"] + "'" +
					" data-rep='" + idrep + "'>UnBlock</button>";
			} else {
				html += "<button class='block_sub_id value_span6-2 value_span2 value_span1-2'" +
					" data-subid='" + subId["subId"] + "'" +
					" data-rep='" + idrep + "'>Block ID</button>" +
					"<button style='display: none;'" +
					" disabled='disabled'" +
					" class='unblock_button value_span6-2 value_span2 value_span1-2'" +
					" data-subid='" + subId["subId"] + "'" +
					" data-rep='" + idrep + "'" +
					">UnBlock</button>";
			}

			html += "</td></tr>";
		})

		document.getElementById('subid_content').innerHTML = html;
		setBlockButtons();
		setUnblockButtons();
	}

	function setTwoNumberDecimal(event) {
		this.value = parseFloat(this.value).toFixed(2);
	}

	function setBlockButtons() {
		const blockButtons = document.querySelectorAll('.block_sub_id');
		if (blockButtons) {
			blockButtons.forEach((button) => {
				button.addEventListener('click', (e) => {
					e.preventDefault();
					const button = e.target;
					const userID = button.dataset.rep;
					const subID = button.dataset.subid;

					const packets = {
						user_id: userID,
						sub_id: subID
					}

					axios.post('user/block-sub-id', packets).then((response) => {
						if (response.data.success) {
							button.innerHTML = "Blocked"
							button.disabled = true;
							button.classList.remove("value_span6-2", "value_span2", "value_span1-2");
							const unblockButton = button.nextElementSibling;
							unblockButton.disabled = false;
							unblockButton.style.display = "block";
						} else {
							console.log(response);
						}
					})

				})
			});
		}
	}

	function setUnblockButtons() {
		const unblock_buttons = document.querySelectorAll('.unblock_button');
		if(unblock_buttons) {
			unblock_buttons.forEach((button) => {
				button.addEventListener('click', (e) => {
					e.preventDefault();
					const button = e.target;
					const userID = button.dataset.rep;
					const subID = button.dataset.subid;

					const packets = {
						user_id: userID,
						sub_id: subID
					}

					axios.post('user/unblock-sub-id', packets).then((response) => {
						if (response.data.success) {
							button.disabled = true;
							button.style.display = "none";
							const blockButton = button.previousElementSibling;
							blockButton.innerHTML = "Block ID";
							blockButton.disabled = false;
							blockButton.classList.add("value_span6-2", "value_span2", "value_span1-2");
						} else {
							console.log(response);
						}
					})

				})
			});
		}
	}

	$(document).ready(function () {

		$("#mainTable").tablesorter(
			{
				sortList: [[5, 1]],
				widgets: ['staticRow']
			});

		if ($('#affRadio').is(':checked')) {
			$("#referralP").show();
		}

	});


</script>
<?php include 'footer.php'; ?>
