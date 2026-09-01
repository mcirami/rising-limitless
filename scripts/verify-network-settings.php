<?php
/** CLI-only regression tests. All company writes and uploads use isolated fixtures. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
ini_set('zend.exception_ignore_args', '1');
require __DIR__.'/../vendor/autoload.php';
use App\Support\NetworkTheme;
use App\Http\Controllers\NetworkSettingsController;
use App\Services\DBWhiteLabelService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use LeadMax\TrackYourStats\System\Company as LegacyCompany;

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
// Override both connections BEFORE any database operation.
config(['database.connections.master'=>['driver'=>'sqlite','database'=>':memory:','prefix'=>''], 'database.connections.mysql.database'=>'risinglimitless', 'database.default'=>'master','session.driver'=>'array']);
DB::purge('master');
$db = DB::connection('master');
$reflection = new ReflectionProperty(LeadMax\TrackYourStats\Database\DatabaseConnection::class, 'instanceMaster');
$reflection->setValue(null, $db->getPdo());
LeadMax\TrackYourStats\Database\DatabaseConnection::changeConnection($db->getPdo());
$_SESSION = ['COMPANY_SUBDOMAIN'=>'settings_fixture'];
$_SERVER['REQUEST_URI'] = '/settings.php';
$db->statement('CREATE TABLE company (id INTEGER PRIMARY KEY, subDomain TEXT UNIQUE, shortHand TEXT, colors TEXT, email TEXT, skype TEXT, login_url TEXT, landing_page TEXT)');
$db->statement('CREATE TABLE offer_urls (id INTEGER PRIMARY KEY, url TEXT, company_id INTEGER)');
$db->table('company')->insert([
    ['id'=>1,'subDomain'=>'settings_fixture','shortHand'=>'Fixture','colors'=>'','email'=>'','skype'=>'','login_url'=>'','landing_page'=>''],
    ['id'=>2,'subDomain'=>'other_fixture','shortHand'=>'Other','colors'=>'','email'=>'','skype'=>'','login_url'=>'','landing_page'=>''],
]);
$checks = 0;
function checkSettings($condition, $message) { global $checks; if (!$condition) throw new RuntimeException($message); $checks++; }
checkSettings(DBWhiteLabelService::getSubDomain('www.risinglimitless.com') === 'risinglimitless', 'WWW host was treated as a tenant database');
checkSettings(DBWhiteLabelService::getSubDomain('127.0.0.1:8000') === 'risinglimitless', 'IP host did not use the configured database');
checkSettings(DBWhiteLabelService::getSubDomain('settings_fixture.example.com') === 'settings_fixture', 'Valid tenant hostname was not preserved');
$unknownHost = new DBWhiteLabelService('unknown.example.com');
$unknownHost->findCompanySubDomain();
checkSettings($unknownHost->subDomain === 'risinglimitless', 'Unknown hostname selected an unconfigured database');
$knownHost = new DBWhiteLabelService('settings_fixture.example.com');
$knownHost->findCompanySubDomain();
checkSettings($knownHost->subDomain === 'settings_fixture', 'Known tenant hostname did not select its database');
checkSettings(NetworkTheme::colors('') === NetworkTheme::DEFAULTS, 'Empty palette must have eleven defaults');
checkSettings(NetworkTheme::colors(false) === NetworkTheme::DEFAULTS, 'False palette must be safe');
checkSettings(NetworkTheme::colors('#abc123;invalid')[0] === 'ABC123', 'Color normalization failed');
checkSettings(NetworkTheme::colors('#abc123;invalid')[1] === 'FFFFFF', 'Bad color fallback failed');
checkSettings(!str_contains(NetworkTheme::css('};body{display:none}'), 'display:none'), 'CSS injection allowed');
checkSettings(str_contains(NetworkTheme::css(''), '[data-theme=dark]{--rl-bg:#10141d'), 'Dark backgrounds must be preserved');
$stale = new LegacyCompany(); $stale->colors=['']; $stale->loaded=true;
checkSettings(count($stale->getColors()) === 11, 'Old serialized palettes must be safe');
$company = new LegacyCompany(); $company->reloadSettings();
checkSettings($company->getID() === 1, 'Company lookup failed');
$data = ['shortHand'=>'Saved fixture','email'=>'test@example.test','skype'=>'contact','login_url'=>'http://example.test/login','landing_page'=>'https://example.test/'];
foreach (NetworkTheme::DEFAULTS as $i=>$color) $data['valueSpan'.($i+1)] = '#'.strtolower($color);
checkSettings(Validator::make($data, NetworkSettingsController::rules())->passes(), 'Valid settings rejected');
foreach (['valueSpan2'=>'nope','shortHand'=>'','email'=>'bad','login_url'=>'javascript:alert(1)','landing_page'=>'https://user:pass@example.test'] as $key=>$value) {
    checkSettings(Validator::make(array_replace($data,[$key=>$value]), NetworkSettingsController::rules())->fails(), 'Invalid '.$key.' accepted');
}
$session = app('session')->driver(); $session->start();
app('redirect')->setSession($session);
$request = Request::create('http://settings.test/settings.php', 'POST', $data);
$request->setLaravelSession($session); $app->instance('request', $request);
$controller = new NetworkSettingsController();
$response = $controller->save($request);
checkSettings($response->getStatusCode() === 302, 'Save should redirect');
$row = $db->table('company')->where('id',1)->first();
checkSettings($row->shortHand === 'Saved fixture' && $row->email === 'test@example.test', 'Company details were not saved');
checkSettings($row->colors === implode(';',NetworkTheme::DEFAULTS), 'Colors did not round-trip');
checkSettings(LegacyCompany::loadFromSession()->getShortHand() === 'Saved fixture', 'Session not refreshed after save');
checkSettings($db->table('company')->where('id',2)->value('shortHand') === 'Other', 'Save touched another company');
checkSettings($controller->save($request)->getStatusCode() === 302, 'Unchanged save must succeed');
$invalid = Request::create('/settings.php','POST',array_replace($data,['valueSpan1'=>'invalid']));
try { $controller->save($invalid); checkSettings(false,'Invalid save succeeded'); } catch (Illuminate\Validation\ValidationException $e) { checkSettings(true,'Invalid save blocked'); }
checkSettings($db->table('company')->where('id',1)->value('colors') === implode(';',NetworkTheme::DEFAULTS), 'Invalid save changed palette');
// Isolate the upload directory away from real public assets.
$temp = sys_get_temp_dir().'/network-settings-'.bin2hex(random_bytes(6)); mkdir($temp,0755,true);
$app->usePublicPath($temp);
$png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aWQAAAABJRU5ErkJggg==');
file_put_contents($temp.'/test.png', $png);
$upload = Request::create('/upload_logo.php','POST',[],[],['file1'=>new UploadedFile($temp.'/test.png','test.png','image/png',null,true)]);
$upload->setLaravelSession($session); $app->instance('request',$upload);
checkSettings($controller->upload($upload,'logo')->getStatusCode() === 302, 'PNG upload failed');
checkSettings(file_get_contents($temp.'/images/settings_fixture/logo.png') === $png, 'Uploaded PNG not stored');
file_put_contents($temp.'/bad.png','not an image');
$bad = Request::create('/upload_logo.php','POST',[],[],['file1'=>new UploadedFile($temp.'/bad.png','bad.png','image/png',null,true)]);
$bad->setLaravelSession($session); $app->instance('request',$bad);
$controller->upload($bad,'logo');
checkSettings(file_get_contents($temp.'/images/settings_fixture/logo.png') === $png, 'Invalid upload replaced existing logo');
checkSettings($session->get('errors')->has('upload'), 'Invalid upload did not show error');
$missingUpload = Request::create('/upload_logo.php','POST');
try { $controller->upload($missingUpload,'logo'); checkSettings(false,'Missing file accepted'); } catch (Illuminate\Validation\ValidationException $e) { checkSettings(true,'Missing file blocked'); }
$ico = file_get_contents(__DIR__.'/../public/images/trackyourstats/favicon.ico');
file_put_contents($temp.'/test.ico',$ico);
$icon = Request::create('/upload_favicon.php','POST',[],[],['file2'=>new UploadedFile($temp.'/test.ico','test.ico','image/x-icon',null,true)]);
$icon->setLaravelSession($session); $app->instance('request',$icon);
$controller->upload($icon,'favicon');
checkSettings(file_get_contents($temp.'/images/settings_fixture/favicon.ico') === $ico, 'Favicon did not round-trip');
file_put_contents($temp.'/bad.ico',substr($ico,0,22));
$badIcon = Request::create('/upload_favicon.php','POST',[],[],['file2'=>new UploadedFile($temp.'/bad.ico','bad.ico','image/x-icon',null,true)]);
$badIcon->setLaravelSession($session); $app->instance('request',$badIcon);
$controller->upload($badIcon,'favicon');
checkSettings(file_get_contents($temp.'/images/settings_fixture/favicon.ico') === $ico, 'Truncated favicon replaced existing icon');
$csrf = new App\Http\Middleware\VerifyCsrfToken($app, app('encrypter'));
$csrfRequest = Request::create('/settings.php','POST'); $csrfRequest->setLaravelSession($session);
try { $csrf->handle($csrfRequest,fn()=>response('ok')); checkSettings(false,'Missing CSRF token accepted'); }
catch (Illuminate\Session\TokenMismatchException $e) { checkSettings(true,'Missing CSRF token blocked'); }
$csrfRequest->merge(['_token'=>$session->token()]);
checkSettings($csrf->handle($csrfRequest,fn()=>response('ok'))->getStatusCode() === 200,'Valid CSRF token rejected');
$db->statement('CREATE TABLE logins (session_id TEXT, repid INTEGER)');
checkSettings((new App\Http\Middleware\LegacyUserAuth())->handle($request, fn()=>response('bad'))->isRedirect(), 'Signed-out settings access allowed');
$_SESSION['COMPANY_SUBDOMAIN']='missing_fixture';
$missing = new LegacyCompany(); $missing->reloadSettings();
checkSettings(!$missing->isLoaded(), 'Missing company marked loaded');
checkSettings(count($missing->getColors()) === 11, 'Missing company colors unsafe');
try { $controller->save($request); checkSettings(false,'Missing company save accepted'); } catch (Illuminate\Database\Eloquent\ModelNotFoundException $e) { checkSettings(true,'Missing company blocked'); }
foreach ([['GET','/settings.php'],['POST','/settings.php'],['POST','/upload_logo.php'],['POST','/upload_favicon.php']] as [$method,$path]) {
    $route = app('router')->getRoutes()->match(Request::create($path,$method));
    $middleware = $route->gatherMiddleware();
    checkSettings(in_array('web',$middleware) && in_array('legacy.auth',$middleware) && in_array('role:0',$middleware), 'Settings route missing protection: '.$path);
}
foreach ([1,2,3] as $role) {
    $_SESSION['userType']=$role;
    try { (new App\Http\Middleware\LegacyAccountTypeMiddleware())->handle($request,fn()=>true,'0'); checkSettings(false,'Non-admin allowed'); }
    catch (Symfony\Component\HttpKernel\Exception\HttpException $e) { checkSettings($e->getStatusCode() === 403,'Non-admin not denied'); }
}
// Only remove files created above, never application assets.
unlink($temp.'/images/settings_fixture/logo.png'); unlink($temp.'/images/settings_fixture/favicon.ico'); unlink($temp.'/bad.png'); unlink($temp.'/bad.ico');
rmdir($temp.'/images/settings_fixture'); rmdir($temp.'/images'); rmdir($temp);
echo "Passed $checks settings assertions (isolated SQLite and temporary uploads).\n";
