<?php

$section = "create-affiliate";
require('header.php');


if (\LeadMax\TrackYourStats\System\Session::userType() == \App\Privilege::ROLE_AFFILIATE || \LeadMax\TrackYourStats\System\Session::userType() == \App\Privilege::ROLE_UNKNOWN)
{
	send_to("home.php");
}

if (!\LeadMax\TrackYourStats\System\Session::permissions()->can("create_affiliates"))
{
	send_to("home.php");
}



$assign = new \LeadMax\TrackYourStats\Table\Assignments(
	[
	]
);

$create = new \LeadMax\TrackYourStats\User\Create();
$assign->getAssignments();
$assign->setGlobals();


$na     = new \LeadMax\TrackYourStats\User\User();
$result = $na->RegisterAndSetPriviliges('/aff_update.php?idrep=');


$create->dumpAssignablesToJavaScript();

$create->dumpPermissionsToJavascript();


switch ($result)
{
	case "USR_OR_EMAIL":
		$error = "The username you entered already exists in the system.";
		break;

	case "PWD":
		$error = "Passwords do no match.";
		break;

	case "EMPTY_USR_NAME" :
		$error = "Please enter a username.";
		break;

	case "EMPTY_PWD" :
		$error = "Please enter a password.";
		break;

	default:
		$error = "";
		break;
}


?>

<script type = "text/javascript" src = "js/aff.js"></script>


<!--right_panel-->
<div class = "right_panel">
	<div class = "white_box_outer">
		<div class="rl-page-heading"><div><h1>Create New User</h1><p>Add a user and configure their account access.</p></div></div>

		<div class = "white_box value_span8 rl-user-form-card">
			<span class = "small_txt value_span10"><?PHP echo $error; ?></span>

			<form action = "<?php htmlspecialchars($_SERVER['PHP_SELF']); ?>" method = "post" id = "form"
			      class = "form-horizontal rl-user-form" enctype = "multipart/form-data">

				<div class="column_wrap rl-user-form-grid">
					<div class = "left_con01 value_span7">
						<h3 class="value_span10">User Details</h3>
						<div class="rl-name-grid">
						<p>
							<label class = "value_span9">First Name</label>

							<input type = "text" class = "form-control" name = "first_name" maxlength = "155"
							       id = "first_name"/>
						</p>
						<p>
							<label class = "value_span9">Last Name</label>

							<input type = "text" class = "form-control" name = "last_name" maxlength = "155"
							       id = "last_name"/>
						</p>
						</div>
						<p>
							<label class = "value_span9">Username</label>

							<input type = "text" class = "form-control" name = "user_name" maxlength = "155"
							       id = "user_name"/>
						</p>
						<p>
							<label class = "value_span9">Password</label>

							<input type = "password" class = "form-control" name = "password" minlength = "5" maxlength = "255"
							       value = ""
							       id = "password"/>
						</p>
						<p>
							<label class = "value_span9">Re-Enter Password</label>

							<input type = "password" class = "form-control" name = "confirmpassword" minlength = "5" maxlength = "255"
							       value = "" id = "confirmpassword"/>
						</p>
						</div><!-- left_con01 -->
					<div class = "right_con01 value_span7">
						<h3 class="value_span10">Account Details</h3>
						<p>
							<label class = "value_span9">Status</label>
							<select class = "form-control input-sm " id = "status" name = "status">
								<option value = "1" selected>Active</option>
								;
								<option value = "0">Disabled</option>
								;
							</select>
						</p>
						<div class="rl-user-field">
							<span class="rl-field-label">Account Type</span>
							<?php $create->printRadios(); ?>
						</div>

						<div class="rl-user-field"><span class="rl-field-label">Permissions</span><div class="rl-permissions-list" id="permissionsP"></div></div>
						<p><label class = "value_span9">Manager</label><select required class = "form-control input-sm " id = "referrer_repid" name = "referrer_repid"></select></p>

					</div><!-- right_con01 -->

				</div><!-- column_wrap -->
				<div class="button_wrap rl-user-form-actions">
					<span class = "btn_yellow"> <a onclick = "history.go(-1);"
					                                                         class = "value_span6-2 value_span2 value_span1-2"
						>Cancel</a></span>
	            <span class = "btn_yellow"> <input type = "submit" name = "button"
	                                               class = "value_span6-2 value_span2 value_span1-2"
	                                               value = "Create User"/></span>
				</div>

			</form>


		</div>


	</div>


	<!--right_panel-->

	<?php include "footer.php"; ?>


	<script type = "text/javascript">


		// A $( document ).ready() block.
			$(document).ready(function () {
				var roleOptions = $(".rl-role-options input[type=radio]");
				if (roleOptions.length && !roleOptions.is(":checked")) {
					roleOptions.first().prop("checked", true).trigger("click");
				}
				/* console.log("ready!");
			jQuery(function ($) {
				$("#cell_phone").mask("(999) 999-9999");
			}); */
		});


	</script>
