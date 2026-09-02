<?php
/** CLI-only view/permission regression checks. Never boots the app or connects to the configured network databases. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/../vendor/autoload.php';

use Illuminate\Config\Repository;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\ViewServiceProvider;
use LeadMax\TrackYourStats\System\Company;
use LeadMax\TrackYourStats\System\NavBar;
use LeadMax\TrackYourStats\User\Permissions;

$root = dirname(__DIR__);
$output = $argv[1] ?? sys_get_temp_dir() . '/risinglimitless-ui';
@mkdir($output . '/compiled', 0777, true);
$app = new Application($root);
$app->instance('config', new Repository(['app' => ['network_name' => 'Rising Limitless'], 'view' => ['paths' => [$root . '/resources/views'], 'compiled' => $output . '/compiled']]));
$app->instance('events', new Dispatcher($app));
$app->instance('files', new Filesystem());
$app->instance('router', new \Illuminate\Routing\Router($app['events'], $app));
$app->instance('session', new Store('ui-test', new ArraySessionHandler(120)));
$app['session']->start();
Facade::setFacadeApplication($app);
class_alias(\Illuminate\Support\Facades\Route::class, 'Route');
$app->register(ViewServiceProvider::class);
$view = $app['view'];
$app['router']->get('/announcements', fn () => null)->name('announcements.index');
$app['router']->get('/announcements/create', fn () => null)->name('announcements.create');
$app['router']->post('/announcements', fn () => null)->name('announcements.store');
$app['router']->get('/announcements/{announcement}/edit', fn () => null)->name('announcements.edit');
$app['router']->put('/announcements/{announcement}', fn () => null)->name('announcements.update');
$app['router']->delete('/announcements/{announcement}', fn () => null)->name('announcements.destroy');
$app['router']->get('/announcements/{announcement}/attachment', fn () => null)->name('announcements.attachment');
$app['router']->getRoutes()->refreshNameLookups();
$company = new Company();
$company->subDomain = 'risinglimitless';
$_SESSION['company'] = serialize($company);
$_SERVER['HTTP_HOST'] = 'risinglimitless.test';
$checks = 0;
function check($condition, $message) { global $checks; if (!$condition) throw new RuntimeException($message); $checks++; }
function context($role, $path, $granted = []) {
    global $app, $view;
    $_GET = [];
    $_SERVER['REQUEST_URI'] = $path;
    $request = Request::create('http://risinglimitless.test' . $path);
    $app->instance('request', $request);
    $permissions = (new ReflectionClass(Permissions::class))->newInstanceWithoutConstructor();
    $permissions->permissions = array_fill_keys($granted, 1);
    $_SESSION['permissions'] = serialize($permissions);
    $_SESSION['userType'] = $role;
    $_SESSION['repid'] = 42;
    $_SESSION['userData'] = serialize((object) ['idrep' => 42, 'first_name' => 'Jordan', 'last_name' => 'Parker', 'user_name' => 'jordan', 'email' => 'jordan@example.test', 'cell_phone' => '', 'skype' => '']);
    $view->share(['webroot' => '/', 'errors' => new ViewErrorBag(), 'navBar' => new NavBar($role, $permissions)]);
}
$offers = collect(range(1, 45))->map(fn($i) => (object) [
    'idoffer' => 1000 + $i, 'offer_name' => $i === 1 ? 'Partner&#039;s &amp; &lt;script&gt; offer' : ['DACH Premium', 'France Exclusive', 'Tier 1 Anglophone', 'Nordics Finance'][$i % 4] . ' ' . $i,
    'offer_type' => $i % 2, 'status' => 1, 'payout' => $i / 10, 'campaign_id' => 1, 'campaign_name' => 'Partner Network',
    'offer_timestamp' => '2026-08-' . str_pad(($i % 27) + 1, 2, '0', STR_PAD_LEFT) . ' 12:00:00', 'pivot' => ['payout' => 0.65],
]);
$offerCountries = [
    1001 => ['name' => "Partner's & <script> offer", 'countries' => ['US' => 'United States', 'CA' => 'Canada'], 'source' => 'rules', 'mode' => 'allowed', 'note' => ''],
    1002 => ['name' => 'France Exclusive 2', 'countries' => array_combine(['AU', 'AT', 'BE', 'CA', 'CH', 'DE', 'DK', 'ES', 'FI', 'FR', 'GB', 'IE', 'IT', 'LU', 'NL', 'NO', 'NZ', 'SE'], ['Australia', 'Austria', 'Belgium', 'Canada', 'Switzerland', 'Germany', 'Denmark', 'Spain', 'Finland', 'France', 'United Kingdom', 'Ireland', 'Italy', 'Luxembourg', 'Netherlands', 'Norway', 'New Zealand', 'Sweden']), 'source' => 'rules', 'mode' => 'allowed', 'note' => ''],
];
$offerData = ['offers' => $offers, 'urls' => ['tracking.example.test'], 'requestableOffers' => collect([(object) ['idoffer' => 999, 'offer_name' => 'Request access example', 'payout' => 2.5]]), 'offerCountries' => $offerCountries, 'availableGeoCount' => 3];
context(0, '/offer/manage', ['create_offers', 'edit_offer_rules', 'edit_affiliates', 'view_adv_reports']);
$html = $view->make('offer.manage', $offerData)->render();
check(str_contains($html, 'data-offer-row'), 'Offer rows were not rendered');
check(str_contains($html, 'Partner&#039;s &amp; &lt;script&gt; offer'), 'Offer name escaping failed');
check(!str_contains($html, "Partner's & <script>"), 'Unsafe offer name output');
check(str_contains($html, '/offer/1001/delete'), 'Admin delete action missing');
check(str_contains($html, 'network.css'), 'Shared stylesheet missing');
check(substr_count($html, 'data-offer-row') === 45, 'Expected 45 offers');
check(preg_match('/<a class="rl-brand has-logo"[^>]*>\s*<img class="rl-brand-logo"[^>]*>\s*<\/a>/', $html) === 1, 'Uploaded sidebar logo is not displayed without duplicate brand text');
check(preg_match('/<select id="offers-page-size"[^>]*>.*?<option selected>50<\/option>/s', $html) === 1, 'Manage Offers does not default to 50 rows');
check(preg_match('/<span class="rl-offer-id is-leading">#1001<\/span>\s*<span class="rl-offer-name">/', $html) === 1, 'God offer ID is not above the offer title');
check(preg_match('/<span class="rl-country-label">Available GEOs<\/span>\s*<span class="rl-offer-countries"/', $html) === 1, 'God offer rows are missing the Available GEOs label');
check(str_contains($html, '<span class="rl-advertiser-name">Partner Network</span>'), 'Advertiser values are missing shared blue text styling');
check(substr_count($html, 'data-geo-extra hidden') === 6 && str_contains($html, 'data-collapsed-label="Show all 18"'), 'Long GEO lists are not collapsed after 12 countries');
file_put_contents($output . '/offers.html', $html);
$missingLogoCompany = new Company();
$missingLogoCompany->subDomain = 'no-sidebar-logo';
$_SESSION['company'] = serialize($missingLogoCompany);
$fallbackBrandHtml = $view->make('offer.manage', $offerData)->render();
check(preg_match('/<a class="rl-brand"[^>]*>\s*<span class="rl-brand-mark">[^<]+<\/span><span>[^<]+<\/span>\s*<\/a>/', $fallbackBrandHtml) === 1, 'Sidebar brand fallback is missing its icon and text');
$_SESSION['company'] = serialize($company);
context(3, '/offer/manage');
$html = $view->make('offer.manage', $offerData)->render();
check(!str_contains($html, 'data-delete-offer'), 'Agent has delete control');
check(!str_contains($html, 'Create New Offer'), 'Agent has create control');
check(!str_contains($html, 'Payout') && !str_contains($html, '$0.65') && !str_contains($html, '$2.50'), 'Agent offer inventory exposes payout information');
check(str_contains($html, 'data-copy-text="https://tracking.example.test/'), 'Agent tracking link missing');
check(str_contains($html, 'data-request-offer'), 'Requestable offers missing');
check(str_contains($html, '<span class="rl-metric-label">Available GEOs</span><strong>3</strong>'), 'Agent unique GEO metric missing or incorrect');
check(!str_contains($html, '<th>Postback</th>') && !str_contains($html, 'offer_edit_pb.php'), 'Agent Postback column still visible');
check(!str_contains($html, '<summary>View link</summary>'), 'Agent View link disclosure still visible');
check(preg_match('/<span class="rl-offer-id is-leading">#1001<\/span>\s*<span class="rl-offer-name">/', $html) === 1, 'Agent offer ID is not above the offer title');
check(preg_match('/<span class="rl-country-label">Available GEOs<\/span>\s*<span class="rl-offer-countries"/', $html) === 1, 'Available GEOs label is not above the country badges');
$menu = $view->shared('navBar')->getVisibleMenu();
check(!in_array('Users', array_column($menu, 'label')), 'Agent can see Users navigation');
check(!in_array('Advertisers', array_column($menu, 'label')), 'Agent can see Advertisers navigation');
check(!collect($menu)->flatMap(fn($section) => $section['items'])->contains(fn($item) => $item['url'] === '/global_postback.php'), 'Agent can see Global Postback navigation');
check(!collect($menu)->flatMap(fn($section) => $section['items'])->contains(fn($item) => $item['url'] === '/notifications.php'), 'Agent can see Notifications navigation');
file_put_contents($output . '/agent-offers.html', $html);
context(2, '/offer/manage');
$html = $view->make('offer.manage', $offerData)->render();
check(!str_contains($html, 'data-offer-sort="payout"'), 'Manager payout column exposed');
check(!str_contains($html, 'Avg Payout'), 'Manager payout metric exposed');
check(!str_contains($html, 'data-payout='), 'Manager payout data exposed');
check(str_contains($html, '<span class="rl-metric-label">Advertisers</span>') && !str_contains($html, '<span class="rl-metric-label">Available GEOs</span>'), 'Manager summary metric changed with the agent layout');
check(preg_match('/<span class="rl-offer-id is-leading">#1001<\/span>\s*<span class="rl-offer-name">/', $html) === 1 && str_contains($html, '<span class="rl-country-label">Available GEOs</span>'), 'Manager offer cells do not use the shared layout');
file_put_contents($output . '/manager-offers.html', $html);
context(0, '/offer/manage');
$html = $view->make('offer.manage', ['offers' => collect(), 'urls' => []])->render();
check(str_contains($html, '$0.00'), 'Empty inventory handling failed');
file_put_contents($output . '/empty-offers.html', $html);
context(1, '/offer/manage');
$html = $view->make('offer.manage', $offerData)->render();
check(!str_contains($html, 'Avg Payout') && !str_contains($html, 'data-payout=') && !str_contains($html, '$0.10'), 'Admin without payout permission can see offer payouts');
context(1, '/offer/manage', [Permissions::VIEW_PAYOUTS]);
$html = $view->make('offer.manage', $offerData)->render();
check(str_contains($html, 'Avg Payout') && str_contains($html, 'data-payout='), 'Admin with payout permission cannot see offer payouts');
context(3, '/dashboard');
$agentMenu = $view->shared('navBar')->getVisibleMenu();
check(in_array('Dashboard', array_column($agentMenu, 'label')), 'Agent dashboard navigation missing');
check(collect($agentMenu)->firstWhere('label', 'Account')['items'][0]['url'] === '/account', 'My Account still points at the traffic dashboard');
context(1, '/announcements/create', [Permissions::CREATE_ANNOUNCEMENTS]);
$adminMenu = $view->shared('navBar')->getVisibleMenu();
check(in_array('Announcements', array_column($adminMenu, 'label')), 'Permitted Admin announcement navigation missing');
$html = $view->make('announcements.create', ['types' => \App\Announcement::TYPES, 'pageTitle' => 'New Announcement'])->render();
check(str_contains($html, 'name="attachment"') && str_contains($html, 'enctype="multipart/form-data"'), 'Announcement attachment form contract missing');
check(substr_count($html, 'name="type"') === 5, 'Announcement type options missing');
check(str_contains($html, 'name="is_pinned"') && str_contains($html, 'name="_token"'), 'Announcement options or CSRF missing');
file_put_contents($output . '/announcement-create.html', $html);
$adminAnnouncement = new \App\Announcement();
$adminAnnouncement->setDateFormat('Y-m-d H:i:s');
$adminAnnouncement->setRawAttributes(['id'=>8,'author_id'=>1,'title'=>'Admin & Update','type'=>'payments','body'=>'Payment <details> for everyone','attachment_disk'=>'local','attachment_path'=>'announcements/admin.pdf','attachment_name'=>'Payment Guide.pdf','is_pinned'=>1,'created_at'=>'2026-08-30 10:00:00','updated_at'=>'2026-08-30 10:00:00']);
$html = $view->make('announcements.index', ['announcements'=>collect([$adminAnnouncement]),'pageTitle'=>'Announcements'])->render();
check(str_contains($html, '<th>Title</th><th>Type</th><th>Text</th><th>Actions</th>'), 'Announcement management columns missing');
check(str_contains($html, '/announcements/8/edit') && str_contains($html, 'name="_method" value="DELETE"'), 'Announcement edit/delete actions missing');
check(str_contains($html, 'Payment &lt;details&gt; for everyone') && !str_contains($html, 'Payment <details>'), 'Announcement management text is not escaped');
file_put_contents($output . '/announcements.html', $html);
$html = $view->make('announcements.edit', ['announcement'=>$adminAnnouncement,'types'=>\App\Announcement::TYPES,'pageTitle'=>'Edit Announcement'])->render();
check(str_contains($html, 'value="PUT"') && str_contains($html, 'Admin &amp; Update') && str_contains($html, 'Remove the current attachment'), 'Edit Announcement form contract missing');
file_put_contents($output . '/announcement-edit.html', $html);
context(1, '/dashboard');
check(!in_array('Announcements', array_column($view->shared('navBar')->getVisibleMenu(), 'label')), 'Unpermitted Admin can see announcement navigation');
context(2, '/dashboard');
$announcement = new \App\Announcement();
$announcement->setDateFormat('Y-m-d H:i:s');
$announcement->setRawAttributes(['id'=>7,'author_id'=>1,'title'=>'<script>Unsafe</script>','type'=>'info','body'=>'Line one\nLine two','attachment_disk'=>'local','attachment_path'=>'announcements/test.pdf','attachment_name'=>'Guide <final>.pdf','is_pinned'=>1,'created_at'=>'2026-08-30 10:00:00','updated_at'=>'2026-08-30 10:00:00']);
$traffic = collect([(object)['offer_id'=>100,'offer_name'=>'Offer <sample>','unique_clicks'=>25,'total_sales'=>3],(object)['offer_id'=>200,'offer_name'=>'Second','unique_clicks'=>4,'total_sales'=>0]]);
$html = $view->make('dashboard', ['announcements'=>collect([$announcement]),'announcementCount'=>1,'traffic'=>$traffic,'profile'=>(object)['user_name'=>'jordan','first_name'=>'Jordan','last_name'=>'Parker'],'snapshotDate'=>\Illuminate\Support\Carbon::parse('2026-08-31 12:00:00'),'pageTitle'=>'Dashboard'])->render();
check(str_contains($html, '&lt;script&gt;Unsafe&lt;/script&gt;') && !str_contains($html, '<script>Unsafe</script>'), 'Announcement title is not escaped');
check(str_contains($html, 'Guide &lt;final&gt;.pdf'), 'Attachment name is not escaped');
check(str_contains($html, 'data-traffic-row') && str_contains($html, 'Totals — 2 offers'), 'Dashboard traffic table/totals missing');
check(str_contains($html, 'Offer &lt;sample&gt;') && !str_contains($html, 'Offer <sample>'), 'Traffic offer name is not escaped');
check(str_contains($html, 'class="rl-nav-direct is-active"') && !preg_match('/<summary[^>]*>.*?<span>Dashboard<\/span>/s', $html), 'Dashboard navigation still renders as a submenu');
file_put_contents($output . '/dashboard.html', $html);

$networkCss = file_get_contents($root . '/public/css/network.css');
check(str_contains($networkCss, 'button.rl-button.rl-primary{background:var(--rl-button,var(--rl-accent))'), 'Primary announcement button does not use the saved theme button color');
check(str_contains($networkCss, '.rl-type-options{border:0;border-bottom:1px solid var(--rl-border)'), 'Announcement Type section separator was not restored');
check(str_contains($networkCss, '.rl-type-options legend{width:auto;max-width:100%;padding:0;margin:0 0 9px;border:0;background:transparent;color:var(--rl-text)!important'), 'Announcement Type legend does not reset Bootstrap color and border');

context(3, '/dashboard');
$html = $view->make('home' , ['firstName' => 'Jordan', 'email' => 'jordan@example.test', 'userId' => 42, 'userType' => 3, 'canViewPostback' => false, 'postBackURL' => 'https://example.test/', 'domain' => 'https://example.test/signup.php?mid='])->render();
check(!str_contains($html, 'Postback URL'), 'Postback exposed without permission');
check(!str_contains($html, 'Two-Factor'), 'Unimplemented security feature shown');
check(str_contains($html, 'Not set'), 'Missing profile fields have no fallback');
file_put_contents($output . '/account.html', $html);
context(2, '/dashboard');
$html = $view->make('home', ['firstName' => 'Jordan', 'email' => 'jordan@example.test', 'userId' => 42, 'userType' => 2, 'canViewPostback' => true, 'postBackURL' => 'https://example.test/?uid=test&clickid=', 'domain' => 'https://example.test/signup.php?mid='])->render();
check(substr_count($html, 'data-copy-text=') === 2, 'Manager signup/postback links not distinct');
$accountDom = new DOMDocument();
@$accountDom->loadHTML($html);
$accountXPath = new DOMXPath($accountDom);
check($accountXPath->query('//*[contains(concat(" ",normalize-space(@class)," ")," rl-account-layout ")]/*[contains(@class,"rl-account-details")]')->length === 1, 'Account details missing from desktop layout');
check($accountXPath->query('//*[contains(@class,"rl-account-layout")]/*[contains(@class,"rl-account-support")]')->length === 1, 'Supporting cards missing from desktop layout');
check($accountXPath->query('//*[contains(@class,"rl-account-support")]//h2[contains(.,"Security") or contains(.,"Network Access")]')->length === 2, 'Security/access cards moved outside support column');
check($accountXPath->query('//*[contains(@class,"rl-account-support")]//*[@data-copy-text]')->length === 2, 'Manager links moved outside support column');
file_put_contents($output . '/manager-account.html', $html);
context(3, '/login');
$html = $view->make('auth.login', ['user' => (object) ['autoFillEmail' => ''], 'error' => null])->render();
check(str_contains($html, 'name="_token"'), 'Login CSRF field missing');
check(str_contains($html, 'name="txt_uname_email"'), 'Login field contract changed');
file_put_contents($output . '/login.html', $html);
context(0, '/user/manage', ['edit_affiliates', 'create_affiliates', 'create_managers']);
$accounts = collect(range(1, 8))->map(fn($i) => (object) ['idrep' => $i, 'user_name' => 'Partner ' . $i, 'email' => 'partner' . $i . '@example.test', 'referrer' => null, 'rep_timestamp' => '2026-08-12']);
$html = $view->make('user.manage', ['users' => $accounts])->render();
check(substr_count($html, 'data-user-row data-search=') === 8, 'User list rows missing');
check(str_contains($html, 'data-login-user="1"'), 'User login action missing');
file_put_contents($output . '/users.html', $html);
context(0, '/report/daily');
$report = collect(range(1, 8))->map(fn($i) => ['aggregate_date' => '2026-08-' . (10 + $i), 'clicks' => $i * 300, 'unique_clicks' => $i * 200, 'free_sign_ups' => $i * 20, 'pending_conversions' => $i, 'conversions' => $i * 10, 'revenue' => $i * 30, 'deductions' => 0]);
$html = $view->make('report.daily', ['report' => $report])->render();
check(str_contains($html, 'rl-report-controls'), 'Report controls missing');
check(str_contains($html, '2026-08-11'), 'Report data missing');
file_put_contents($output . '/report.html', $html);
// Representative affiliate report data verifies the new presentation without a database query.
$affiliateRows = [
    ['idrep' => 17, 'user_name' => 'Sample Rep', 'Clicks' => "<a href='/user/17/clicks'>1234</a>", 'UniqueClicks' => 800, 'FreeSignUps' => 2, 'PendingConversions' => 3, 'Conversions' => 12, 'Revenue' => '$240.00', 'Deductions' => '$0.00', 'EPC' => '$0.30', 'BonusRevenue' => '$0.00', 'ReferralRevenue' => '$0.00', 'TOTAL' => '$240.00'],
    ['idrep' => 'TOTAL', 'user_name' => '', 'Clicks' => 1234, 'UniqueClicks' => 800, 'FreeSignUps' => 2, 'PendingConversions' => 3, 'Conversions' => 12, 'Revenue' => '$240.00', 'Deductions' => '$0.00', 'EPC' => '$0.30', 'BonusRevenue' => '$0.00', 'ReferralRevenue' => '$0.00', 'TOTAL' => '$240.00'],
];
$fakeReporter = new class($affiliateRows) {
    public int $fetches = 0;
    public function __construct(private array $rows) {}
    public function fetchReport($from, $to): array { $this->fetches++; return $this->rows; }
    public function between($from, $to, $format): void { throw new RuntimeException('Report was queried twice'); }
};
$reportDates = ['startDate' => '2026-08-28 00:00:00', 'endDate' => '2026-08-28 23:59:59', 'originalStart' => '2026-08-28', 'originalEnd' => '2026-08-28'];
context(2, '/report/affiliate', ['create_managers']);
$html = $view->make('report.employee', ['reporter' => $fakeReporter, 'dates' => $reportDates, 'startDate' => '2026-08-28', 'endDate' => '2026-08-28', 'dateSelect' => 0])->render();
check($fakeReporter->fetches === 1, 'Affiliate summary triggered an extra report fetch');
check(str_contains($html, 'Total Raw Clicks') && str_contains($html, '1,234'), 'Report summary is not based on the filtered rows');
check(str_contains($html, 'Pending Conversions') && !str_contains($html, 'Total Revenue'), 'Manager report exposed a revenue summary');
check(!str_contains($html, '$240.00') && !str_contains($html, 'Sales Revenue'), 'Manager report exposed financial data');
check(str_contains($html, '/user/17/clicks'), 'Existing click drill-down was removed');
check(str_contains($html, '<tfoot>') && str_contains($html, 'TOTALS'), 'Report totals row is missing');
preg_match('#<tfoot>(.*?)</tfoot>#s', $html, $totalsMatch);
check(str_contains($totalsMatch[1], '1234') && !str_contains($totalsMatch[1], '@else'), 'Report total cells failed to render');
check(!str_contains($totalsMatch[1], '<a '), 'Totals must not link to nonexistent users or offers');
check(str_contains($html, 'data-performance-table'), 'Report table presentation hook missing');
file_put_contents($output . '/manager-report.html', $html);
context(0, '/report/affiliate');
$adminReporter = new class($affiliateRows) {
    public int $fetches = 0;
    public function __construct(private array $rows) {}
    public function fetchReport($from, $to): array { $this->fetches++; return $this->rows; }
};
$html = $view->make('report.employee', ['reporter' => $adminReporter, 'dates' => $reportDates, 'startDate' => '2026-08-28', 'endDate' => '2026-08-28', 'dateSelect' => 0])->render();
check($adminReporter->fetches === 1, 'Administrator report fetched more than once');
check(str_contains($html, 'Total Revenue') && str_contains($html, '$240.00'), 'Authorized revenue summary missing');
check(str_contains($html, 'role=3'), 'Affiliate export did not preserve its role filter');
file_put_contents($output . '/admin-report.html', $html);
$summary = \App\Support\ReportSummary::fromTotalledReport([], false);
check($summary['Clicks'] === 0.0 && $summary['uniqueRate'] === 0.0 && !isset($summary['Revenue']), 'Empty or restricted report summary failed');
$reportData = ['reporter' => $adminReporter, 'dates' => $reportDates, 'startDate' => '2026-08-28', 'endDate' => '2026-08-28', 'dateSelect' => 0];
context(1, '/report/affiliate');
$html = $view->make('report.employee', $reportData)->render();
check(!str_contains($html, '$240.00') && !str_contains($html, 'Total Revenue'), 'Admin without payout permission can see revenue');
context(1, '/report/affiliate?role=2', ['view_payouts']);
$html = $view->make('report.employee', $reportData)->render();
check(str_contains($html, 'role=2') && str_contains($html, 'Total Revenue'), 'Authorized admin role filter/revenue visibility lost');
$offerRows = array_map(function ($row) {
    $row['idoffer'] = $row['idrep']; $row['offer_name'] = $row['user_name'];
    unset($row['idrep'], $row['user_name']); return $row;
}, $affiliateRows);
$offerReporter = new class($offerRows) {
    public function __construct(private array $rows) {}
    public function fetchReport($from, $to): array { return $this->rows; }
};
$offerData = array_replace($reportData, ['reporter' => $offerReporter]);
context(2, '/report/offer');
$html = $view->make('report.offer.admin', $offerData)->render();
check(!str_contains($html, '$240.00') && !str_contains($html, 'Export Data'), 'Manager offer report exposed restricted controls/data');
check(str_contains($html, '/report/offer/17/user-conversions'), 'Offer conversion drill-down missing');
file_put_contents($output . '/manager-offer-report.html', $html);
context(1, '/report/offer', [Permissions::VIEW_PAYOUTS]);
$adminAdvertiserRows = array_replace($offerRows, [0 => array_replace($offerRows[0], ['Advertiser' => 'ADV-CODE'])]);
$adminAdvertiserReporter = new class($adminAdvertiserRows) {
    public function __construct(private array $rows) {}
    public function fetchReport($from, $to): array { return $this->rows; }
};
$html = $view->make('report.offer.admin', array_replace($offerData, ['reporter' => $adminAdvertiserReporter]))->render();
check(str_contains($html, 'data-report-field="Advertiser"') && str_contains($html, 'ADV-CODE'), 'Permitted Admin offer report is missing Advertiser code');
check(!str_contains($html, 'data-report-field="EPC"'), 'Permitted Admin offer report still shows EPC');
context(0, '/report/offer');
$html = $view->make('report.offer.admin', $offerData)->render();
check(str_contains($html, 'data-report-field="EPC"') && !str_contains($html, 'data-report-field="Advertiser"'), 'God offer report lost EPC');
context(3, '/report/offer');
$html = $view->make('report.offer.affiliate', array_replace($offerData, ['report' => (object) ['bonuses' => []], 'yesterdayConversions' => 7, 'yesterdayDate' => 'Aug 31, 2026']))->render();
check(!str_contains($html, '$240.00') && !str_contains($html, '>Revenue<') && !str_contains($html, '>EPC<') && !str_contains($html, '>Total<'), 'Agent report exposes payout columns or values');
check(str_contains($html, '/user/42/17/conversions-by-country'), 'Agent report lost country drill-down');
check(str_contains($html, "Yesterday's Conversions") && str_contains($html, '<strong>7</strong>') && str_contains($html, 'Aug 31, 2026'), 'Agent yesterday conversion metric missing');
check(!str_contains($html, 'Pending Conversions'), 'Agent offer report still shows Pending Conversions');
file_put_contents($output . '/agent-report.html', $html);

$linkRequest = Request::create('/report/offer?d_from=2025-08-25&d_to=2025-08-31&dateSelect=7&adminLogin=1');
$linkedRows = (new \LeadMax\TrackYourStats\Report\Filters\ClickLink($linkRequest, 'UniqueClicks', 'idoffer', '/offer/{id}/clicks', ['unique' => 1]))->filter([
    ['idoffer' => 17, 'UniqueClicks' => 800],
    ['idoffer' => 'TOTAL', 'UniqueClicks' => 800],
]);
check(str_contains($linkedRows[0]['UniqueClicks'], '/offer/17/clicks?') && str_contains($linkedRows[0]['UniqueClicks'], 'unique=1'), 'Offer Unique click link missing its filter');
check($linkedRows[1]['UniqueClicks'] === 800, 'Unique click totals were made clickable');
$agentLinkedRows = (new \LeadMax\TrackYourStats\Report\Filters\ClickLink($linkRequest, 'UniqueClicks', 'idrep', '/user/{id}/clicks-by-country', ['unique' => 1]))->filter([
    ['idrep' => 17, 'UniqueClicks' => 800],
    ['idrep' => 'TOTAL', 'UniqueClicks' => 800],
]);
check(str_contains($agentLinkedRows[0]['UniqueClicks'], '/user/17/clicks-by-country?') && str_contains($agentLinkedRows[0]['UniqueClicks'], 'unique=1'), 'Agent Unique click link missing its filter');

context(1, '/report/geo');
$geoRows = ['US' => ['total_clicks' => 20, 'unique_clicks' => 8, 'total_conversions' => 2]];
$html = $view->make('report.conversions.geo', ['reports' => $geoRows, 'startDate' => '2025-08-25', 'endDate' => '2025-08-31', 'dateSelect' => 7])->render();
check(str_contains($html, 'country=US') && str_contains($html, 'unique=1') && str_contains($html, '>8</a>'), 'GEO Unique click drill-down missing');
$offerClickSource = file_get_contents(base_path('resources/views/report/clicks/offer.blade.php'));
check(str_contains($offerClickSource, "appends((\$uniqueOnly ?? false) ? ['unique' => 1] : [])"), 'Offer Unique click pagination does not preserve its filter');

$restrictedRows = \App\Support\PayoutVisibility::withoutPayoutFields($offerRows);
foreach (['Revenue', 'Deductions', 'EPC', 'TOTAL', 'BonusRevenue', 'ReferralRevenue', 'paid', 'payout'] as $field) {
    check(!array_key_exists($field, $restrictedRows[0]), "Restricted JSON still includes {$field}");
}

context(1, '/report/daily');
$html = $view->make('report.daily', ['report' => $report])->render();
check(!str_contains($html, '>Revenue<') && !str_contains($html, '$30.00'), 'Admin without payout permission can see daily revenue');
context(1, '/report/daily', [Permissions::VIEW_PAYOUTS]);
$html = $view->make('report.daily', ['report' => $report])->render();
check(str_contains($html, '>Revenue<'), 'Admin with payout permission cannot see daily revenue');

$advertiserReporter = new class {
    public function between($from, $to, $format): void {}
};
context(1, '/report/advertiser', [Permissions::VIEW_ADV_REPORTS]);
$html = $view->make('report.advertiser', ['reporter' => $advertiserReporter, 'dates' => $reportDates])->render();
check(!str_contains($html, '>Revenue<') && !str_contains($html, '>EPC<') && !str_contains($html, '>TOTAL<'), 'Admin without payout permission can see advertiser payout columns');
context(1, '/report/advertiser', [Permissions::VIEW_ADV_REPORTS, Permissions::VIEW_PAYOUTS]);
$html = $view->make('report.advertiser', ['reporter' => $advertiserReporter, 'dates' => $reportDates])->render();
check(str_contains($html, '>Revenue<') && str_contains($html, '>EPC<') && str_contains($html, '>TOTAL<'), 'Admin with payout permission cannot see advertiser payout columns');

$clickRow = (object) ['idclicks' => 123, 'offer_name' => 'Sample Offer', 'conversion_timestamp' => '2026-08-28 12:00:00', 'paid' => '98765.43', 'sub1' => '', 'sub2' => '', 'sub3' => '', 'sub4' => '', 'sub5' => ''];
$clickPaginator = new class { public function links(): string { return ''; } public function withQueryString(): self { return $this; } };
$clickViewData = ['report' => [$clickRow], 'user' => (object) ['idrep' => 17, 'user_name' => 'Sample Rep'], 'reportCollection' => $clickPaginator, 'startDate' => '2026-08-28', 'endDate' => '2026-08-28', 'dateSelect' => 0, 'offerId' => 17];
context(2, '/user/17/conversions');
$html = $view->make('report.conversions.affiliate', $clickViewData)->render();
check(!str_contains($html, '>Paid<') && !str_contains($html, '98765.43'), 'Manager conversion details expose payout data');
context(1, '/user/17/conversions');
$html = $view->make('report.conversions.affiliate', $clickViewData)->render();
check(!str_contains($html, '>Paid<') && !str_contains($html, '98765.43'), 'Admin without payout permission can see conversion payout data');
context(1, '/user/17/conversions', [Permissions::VIEW_PAYOUTS]);
$html = $view->make('report.conversions.affiliate', $clickViewData)->render();
check(str_contains($html, '>Paid<') && str_contains($html, '98765.43'), 'Admin with payout permission cannot see conversion payout data');
$clickDetailRow = (object) array_merge((array) $clickRow, ['timestamp' => '2026-08-28 11:00:00', 'referer' => '', 'ip_address' => '127.0.0.1', 'isoCode' => 'US']);
$clickDetailData = array_replace($clickViewData, ['report' => [$clickDetailRow], 'uniqueOnly' => true]);
context(2, '/user/17/clicks?unique=1');
$html = $view->make('report.clicks.affiliate', $clickDetailData)->render();
check(str_contains($html, "Sample Rep's Unique Clicks") && !str_contains($html, '>Paid<') && !str_contains($html, '98765.43'), 'Manager Unique click details expose payout data');
context(1, '/user/17/clicks?unique=1');
$html = $view->make('report.clicks.affiliate', $clickDetailData)->render();
check(!str_contains($html, '>Paid<') && !str_contains($html, '98765.43'), 'Restricted Admin Unique click details expose payout data');
context(1, '/user/17/clicks?unique=1', [Permissions::VIEW_PAYOUTS]);
$html = $view->make('report.clicks.affiliate', $clickDetailData)->render();
check(str_contains($html, '>Paid<') && str_contains($html, '98765.43'), 'Permitted Admin Unique click details hide payout data');
context(2, '/report/affiliate');
$emptyReporter = new class { public function fetchReport($from, $to): array { return [[]]; } };
$html = $view->make('report.employee', array_replace($reportData, ['reporter' => $emptyReporter]))->render();
check(str_contains($html, 'No activity in this date range') && !str_contains($html, '<tfoot>'), 'Empty report renders misleading totals');
file_put_contents($output . '/empty-report.html', $html);
$manyRows = [$affiliateRows[0], array_replace($affiliateRows[0], ['idrep' => 18, 'user_name' => 'Second Rep', 'Clicks' => 80, 'UniqueClicks' => 30, 'Conversions' => 2]), array_replace($affiliateRows[0], ['idrep' => 19, 'user_name' => 'Third Rep', 'Clicks' => 9000, 'UniqueClicks' => 3000, 'Conversions' => 9]), array_replace($affiliateRows[1], ['Clicks' => 10314, 'UniqueClicks' => 3830, 'Conversions' => 23, 'FreeSignUps' => 6, 'PendingConversions' => 9])];
$reporterClass = get_class($fakeReporter);
$html = $view->make('report.employee', array_replace($reportData, ['reporter' => new $reporterClass($manyRows)]))->render();
check(str_contains($html, '3 reps') && str_contains($html, '10,314'), 'Report count or aggregate summary is incorrect');
file_put_contents($output . '/sortable-report.html', $html);
// Representative legacy form markup: verifies CSS compatibility, not legacy database actions.
context(0, '/campaign_create.php', ['create_offers']);
$fixture = <<<'BLADE'
@extends('layouts.master') @section('content') <div class="right_panel"><div class="white_box_outer"><div class="heading_holder"><span class="lft value_span9">Create Advertiser</span></div><div class="white_box value_span8"><form><div class="left_con01"><h3>Advertiser details</h3><p><label for="fixture-name">Name</label><input id="fixture-name" value="Example advertiser"></p><p><label for="fixture-email">Email</label><input id="fixture-email" type="email"></p></div><div class="right_con01"><h3>Account settings</h3><p><label for="fixture-status">Status</label><select id="fixture-status"><option>Active</option><option>Inactive</option></select></p><p><label for="fixture-notes">Notes</label><textarea id="fixture-notes"></textarea></p></div><div class="button_wrap"><button type="button" class="btn btn-primary">Save advertiser</button></div></form></div></div></div> @endsection
BLADE;
file_put_contents($output . '/legacy-form.blade.php', $fixture);
$view->addLocation($output);
file_put_contents($output . '/legacy-form.html', $view->make('legacy-form')->render());
$createUserSource = file_get_contents($root . '/legacy/aff_add.php');
$editUserSource = file_get_contents($root . '/legacy/aff_update.php');
$permissionSource = file_get_contents($root . '/src/User/Permissions.php');
$userSource = file_get_contents($root . '/src/User/User.php');
check(Permissions::$permissionsArray[Permissions::SMS_CHAT]['allowed_user_types'] === [\App\Privilege::ROLE_GOD], 'SMS Verification is still available to Agent accounts');
check(Permissions::$permissionsArray[Permissions::VIEW_SMS_STATS]['allowed_user_types'] === [\App\Privilege::ROLE_GOD], 'SMS Stats is still available to Office or Admin accounts');
check(Permissions::$permissionsArray[Permissions::EDIT_AFFILIATES]['description'] === 'Can Change Their Own Password', 'Agent password permission label was not updated');
$permissionDefaults = (new ReflectionClass(Permissions::class))->newInstanceWithoutConstructor();
check(!in_array(Permissions::SMS_CHAT, $permissionDefaults->affiliateOnlyPermissions, true), 'SMS Verification still bypasses Agent role filtering');
check(substr_count($permissionSource, '["aff_id", self::SMS_CHAT, self::VIEW_SMS_STATS, self::EDIT_AFFILIATES]') === 2, 'Office or Admin permission builders render Agent-only permissions');
check(str_contains($permissionSource, '["aff_id", self::SMS_CHAT]'), 'Agent permission builder still renders SMS Verification');
check(!preg_match('/name\s*=\s*["\']email["\']/', $createUserSource), 'Create User still renders an email field');
check(!str_contains($createUserSource, 'referralCheckBox') && !str_contains($createUserSource, 'printAffiliatesToSelectBox'), 'Create User still renders referrals');
check(str_contains($createUserSource, 'rl-name-grid') && str_contains($createUserSource, 'rl-user-form-actions'), 'Create User layout/action bar hooks missing');
foreach (['email', 'cell_phone', 'company_name', 'skype'] as $removedField) {
    check(!preg_match('/name\s*=\s*["\']'.preg_quote($removedField, '/').'["\']/', $editUserSource), "Edit User still renders {$removedField}");
}
check(!str_contains($editUserSource, 'Edit My Referrals') && !str_contains($editUserSource, 'printSelectBoxForEditAffiliate'), 'Edit User still renders referrals');
check(str_contains($editUserSource, 'rl-user-tabs-bar') && str_contains($editUserSource, 'rl-login-as-user'), 'Edit User tabs/login styling hooks missing');
check(str_contains($editUserSource, 'rl-name-grid') && str_contains($editUserSource, 'rl-user-form-actions'), 'Edit User layout/action bar hooks missing');
check(str_contains($permissionSource, 'rl-permission-option') && str_contains(file_get_contents($root . '/src/User/Create.php'), 'rl-role-option') && str_contains(file_get_contents($root . '/src/User/Update.php'), 'rl-role-option'), 'Restyled user role/permission controls missing');
check(str_contains($networkCss, 'label.rl-role-option input[type=radio]{') && str_contains($networkCss, 'appearance:none') && str_contains($networkCss, 'input[type=radio]:checked'), 'User role cards are missing visible aligned radio circles');
check(str_contains($userSource, "array_key_exists('email', \$_POST)") && str_contains($userSource, "currentUser->company_name"), 'Removed Edit User fields are not preserved server-side');
check(str_contains($networkCss, '.white_box form .button_wrap{display:flex;align-items:center;justify-content:flex-end') && str_contains($networkCss, 'background:var(--rl-soft)'), 'Shared form action bars are not separated and right aligned');
check(str_contains($networkCss, 'body.rl-app .modal-content{background:var(--rl-surface)') && str_contains($networkCss, 'body.rl-app .modal .table-striped>tbody>tr:nth-of-type(odd){background:var(--rl-soft)'), 'Legacy offer rule dialogs do not follow the active theme');
check(str_contains($networkCss, '.rl-country-label{display:block;margin-bottom:4px;color:var(--rl-accent)') && str_contains($networkCss, '.rl-advertiser-name{color:#3864a3;font-weight:700}') && str_contains($networkCss, ':root[data-theme=dark] .rl-advertiser-name{color:#9cbfff}'), 'Shared offer GEO label or advertiser colors are missing');
check(str_contains(file_get_contents($root . '/public/js/network-offers.js'), "closest('.rl-country-details').querySelectorAll('[data-geo-extra]')") && str_contains($networkCss, 'body.rl-app button.rl-geo-toggle{'), 'Long GEO list expansion control is missing');
$removedReportColumns = ['Free Sign Ups', 'Pending Conversions', 'Deductions', 'Codes', 'Bonus Revenue', 'Referral Revenue'];
foreach (['offer/admin.blade.php', 'offer/affiliate.blade.php', 'employee.blade.php', 'advertiser.blade.php', 'daily.blade.php'] as $reportView) {
    $reportSource = file_get_contents($root . '/resources/views/report/' . $reportView);
    foreach ($removedReportColumns as $removedColumn) {
        check(!str_contains($reportSource, $removedColumn), "{$reportView} still renders the {$removedColumn} column");
    }
}
$themeInit = file_get_contents($root . '/resources/views/layouts/partials/network-theme-init.blade.php');
$networkJs = file_get_contents($root . '/public/js/network.js');
check(str_contains($themeInit, "localStorage.getItem('rl-theme') === 'light' ? 'light' : 'dark'") && str_contains($networkJs, "localStorage.getItem('rl-theme') === 'light' ? 'light' : 'dark'"), 'Dark mode is not the default for users without a saved preference');
$sessionConfig = file_get_contents($root . '/config/session.php');
check(str_contains($sessionConfig, "env('SESSION_COOKIE', 'risinglimitless_session')"), 'Session cookie is not isolated from sibling Laravel applications');
check(str_contains($sessionConfig, "env('SESSION_SAME_SITE', 'lax')"), 'Session cookie SameSite policy is missing');
$advertiserCreateSource = file_get_contents($root . '/legacy/campaign_create.php');
$offerUrlCreateSource = file_get_contents($root . '/legacy/add_offer_url.php');
check(str_contains($advertiserCreateSource, 'rl-compact-form-card') && str_contains($advertiserCreateSource, 'rl-compact-form-actions'), 'Create Advertiser is missing shared form styling');
check(str_contains($offerUrlCreateSource, 'rl-compact-form-card') && str_contains($offerUrlCreateSource, 'rl-form-note') && str_contains($offerUrlCreateSource, 'rl-compact-form-actions'), 'Create Offer URL is missing shared form styling');
// Manager directory view: existing role permissions still control every action.
context(2, '/user/manage', ['edit_affiliates', 'create_affiliates', 'create_managers']);
$managerAccounts = collect(range(1, 8))->map(fn($i) => (object) [
    'idrep' => 1000 + $i, 'user_name' => $i === 1 ? 'Agent <sample>' : 'Partner' . $i,
    'email' => 'partner' . $i . '@example.test', 'status' => 1,
    'referrer' => (object) ['user_name' => 'jordan'],
    'rep_timestamp' => $i . ' months ago', 'directory_joined_at' => '2026-01-0' . $i . ' 12:00:00',
]);
$directorySummary = ['total' => 10, 'active' => 8, 'inactive' => 2, 'agents' => 8, 'managers' => 2, 'new_this_month' => 1];
$html = $view->make('user.manager-directory', ['users' => $managerAccounts, 'directorySummary' => $directorySummary])->render();
check(substr_count($html, 'data-directory-row ') === 8, 'Manager directory rows missing');
check(str_contains($html, 'Agent &lt;sample&gt;'), 'Manager usernames must be escaped');
check(str_contains($html, 'data-login-user="1001"'), 'Manager login control missing');
check(str_contains($html, '80.0% of your users'), 'Manager percentage calculation incorrect');
check(!str_contains($html, '<option value="1">'), 'Manager should not receive admin role selector');
check(str_contains($html, 'data-joined="2026-01-01'), 'Chronological sort value missing');
check(str_contains($html, 'rl-sidebar-details'), 'Manager profile details missing');
file_put_contents($output . '/manager-users.html', $html);
context(2, '/user/manage');
$html = $view->make('user.manager-directory', ['users' => $managerAccounts, 'directorySummary' => $directorySummary])->render();
check(!str_contains($html, 'data-login-user='), 'Unprivileged manager can impersonate');
check(!str_contains($html, 'rl-edit-user'), 'Unprivileged manager can edit users');
check(!str_contains($html, '> Add User'), 'Unprivileged manager can add users');
$html = $view->make('user.manager-directory', ['users' => collect(), 'directorySummary' => array_fill_keys(array_keys($directorySummary), 0)])->render();
check(str_contains($html, '0.0% of your users'), 'Empty manager directory divides by zero');
file_put_contents($output . '/empty-manager-users.html', $html);
context(3, '/');
$html = $view->make('landing-page')->render();
check(str_contains($html, 'action="/login"'), 'Homepage must use the existing login endpoint');
check(str_contains($html, 'name="_token"'), 'Homepage login CSRF token missing');
check(str_contains($html, 'name="txt_uname_email"') && str_contains($html, 'name="txt_password"'), 'Homepage login field contract changed');
check(!str_contains($html, 'SOC 2') && !str_contains($html, '$2.4M'), 'Unverified mockup claims were copied');
check(!str_contains($html, 'href="/signup.php"'), 'Homepage must not show signup links');
check(str_contains($html, 'id="support"'), 'Homepage support destination missing');
file_put_contents($output . '/landing.html', $html);
// Dispatch tests never invoke a real conversion or postback.
$index = new class extends \App\Http\Controllers\IndexController {
    public function clickRegistration(Request $request) { return 'click-handler'; }
    public function postBackRegistration(Request $request) { return 'postback-handler'; }
};
check($index->index(Request::create('/?repid=1&offerid=2')) === 'click-handler', 'Root click handler was bypassed');
check($index->index(Request::create('/?uid=test')) === 'postback-handler', 'Root postback handler was bypassed');
check($index->index(Request::create('/?repid=1&offerid=2&uid=test')) === 'click-handler', 'Root handler priority changed');
// Relational summary tests use a disposable in-memory SQLite connection only.
$capsule = new \Illuminate\Database\Capsule\Manager($app);
$capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
$capsule->bootEloquent();
$db = $capsule->getConnection();
$db->statement('CREATE TABLE rep (idrep INTEGER PRIMARY KEY, status INTEGER, lft INTEGER, rgt INTEGER, rep_timestamp TEXT)');
$db->statement('CREATE TABLE privileges (rep_idrep INTEGER, is_rep INTEGER, is_manager INTEGER)');
$db->table('rep')->insert([
    ['idrep' => 42, 'status' => 1, 'lft' => 1, 'rgt' => 10, 'rep_timestamp' => '2020-01-01 00:00:00'],
    ['idrep' => 1005, 'status' => 1, 'lft' => 2, 'rgt' => 3, 'rep_timestamp' => '2026-08-12 00:00:00'],
    ['idrep' => 1006, 'status' => 0, 'lft' => 4, 'rgt' => 7, 'rep_timestamp' => '2026-01-01 00:00:00'],
    ['idrep' => 1007, 'status' => 1, 'lft' => 5, 'rgt' => 6, 'rep_timestamp' => '2026-08-01 00:00:00'],
    ['idrep' => 9999, 'status' => 1, 'lft' => 11, 'rgt' => 12, 'rep_timestamp' => '2026-08-20 00:00:00'],
]);
$db->table('privileges')->insert([
    ['rep_idrep' => 1005, 'is_rep' => 1, 'is_manager' => 0],
    ['rep_idrep' => 1005, 'is_rep' => 1, 'is_manager' => 0],
    ['rep_idrep' => 1006, 'is_rep' => 0, 'is_manager' => 1],
    ['rep_idrep' => 1007, 'is_rep' => 1, 'is_manager' => 0],
    ['rep_idrep' => 9999, 'is_rep' => 1, 'is_manager' => 0],
]);
context(2, '/user/manage');
$scope = \App\User::myUsers();
$summary = \App\Support\UserDirectorySummary::forUsers($scope, '2026-08-01 00:00:00');
check($summary === ['total' => 3, 'active' => 2, 'inactive' => 1, 'agents' => 2, 'managers' => 1, 'new_this_month' => 2], 'Directory summary leaks outside the manager scope or double-counts users');
check($scope->getQuery()->joins === null, 'Summary mutated the caller query');
$emptySummary = \App\Support\UserDirectorySummary::forUsers(\App\User::where('idrep', -1), '2026-08-01 00:00:00');
check(array_sum($emptySummary) === 0, 'Empty summary must contain zeroes');
$db->table('rep')->insert(['idrep' => 1008, 'status' => 1, 'lft' => 8, 'rgt' => 9, 'rep_timestamp' => '2026-09-01 00:00:00']);
$futureSummary = \App\Support\UserDirectorySummary::forUsers(\App\User::myUsers(), '2026-08-01 00:00:00');
check($futureSummary['new_this_month'] === 2, 'Future-month records counted as joined this month');
$db->disconnect();
// Compile all Blade templates without running controllers or querying the database.
$compiler = $app['blade.compiler'];
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/resources/views')) as $file) {
    if (str_ends_with($file->getFilename(), '.blade.php')) $compiler->compile($file->getPathname());
}
echo "Passed {$checks} assertions. Templates compiled. Isolated fixtures: {$output}\n";
