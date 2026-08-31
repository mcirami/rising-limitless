<?php
/**
 * Author: Dean
 * Email: dwm348@gmail.com
 * Date: 10/30/2017
 * Time: 11:16 AM
 */

use App\Privilege;
use App\User;

$section = "offer-urls";

require('header.php');


if (!\LeadMax\TrackYourStats\System\Session::permissions()->can("edit_offer_urls")) {
    send_to("home.php");
}

$managers = User::withRole(Privilege::ROLE_MANAGER)->select(['rep.idrep', 'rep.user_name'])->orderBy('rep.user_name')->get();

if (isset($_POST['submit'])) {
    $URLs = new \LeadMax\TrackYourStats\Offer\URLs(\LeadMax\TrackYourStats\System\Company::loadFromSession());
	$assignedManagerId = isset($_POST['assigned_manager_id']) && $_POST['assigned_manager_id'] !== ''
			? (int)$_POST['assigned_manager_id']
			: null;
	if ($URLs->createOfferURL($_POST["url"], $_POST["status"], $assignedManagerId)) {
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
                    <p class="rl-user-field">
                        <label class="rl-field-label" for="manager-search">Find Assigned Manager</label>
                        <input type="search" id="manager-search" placeholder="Search by ID or username" class="form-control">
                    </p>
                    <p class="rl-user-field">
                        <label class="rl-field-label" for="assigned_manager_id">Assigned To</label>
                        <select id="assigned_manager_id" name="assigned_manager_id" class="form-control">
                            <option value="">All</option>
                            <?php foreach ($managers as $manager):
                                $searchValue = strtolower($manager->idrep . ' ' . $manager->user_name);
                                $label = $manager->user_name . ' (ID: ' . $manager->idrep . ')';
                            ?>
                                <option value="<?= (int)$manager->idrep ?>" data-search="<?= htmlspecialchars($searchValue, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
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

	<script type="text/javascript">
		(function () {
			const select = document.getElementById('assigned_manager_id');
			const searchInput = document.getElementById('manager-search');
			if (!select || !searchInput) {
				return;
			}

			const optionsData = Array.from(select.options).map(option => ({
				value: option.value,
				text: option.text,
				search: (option.getAttribute('data-search') || option.text).toLowerCase(),
			}));

			const renderOptions = (filterTerm) => {
				const currentValue = select.value;
				select.innerHTML = '';
				let selectionApplied = false;

				optionsData.forEach(option => {
					if (!filterTerm || option.search.indexOf(filterTerm) !== -1) {
						const newOption = new Option(option.text, option.value, false, option.value === currentValue);
						newOption.setAttribute('data-search', option.search);
						select.add(newOption);
						if (option.value === currentValue) {
							selectionApplied = true;
						}
					}
				});

				if (!selectionApplied && select.options.length) {
					select.selectedIndex = 0;
				}
			};

			searchInput.addEventListener('input', function () {
				renderOptions(this.value.toLowerCase());
			});
		})();
	</script>
