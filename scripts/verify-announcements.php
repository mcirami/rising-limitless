<?php
/** Announcement and traffic-dashboard regression tests use isolated SQLite and temporary storage only. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
ini_set('zend.exception_ignore_args', '1');
require __DIR__.'/../vendor/autoload.php';

use App\Announcement;
use App\Http\Controllers\AnnouncementController;
use App\Privilege;
use App\Support\DashboardTrafficSnapshot;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use LeadMax\TrackYourStats\User\Permissions;

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['session.driver'=>'array','database.default'=>'sqlite','database.connections.sqlite'=>['driver'=>'sqlite','database'=>':memory:','prefix'=>'']]);
DB::purge('sqlite');
$schema = DB::connection()->getSchemaBuilder();
$schema->create('permissions', function($t){$t->integer('aff_id')->primary();$t->tinyInteger('create_affiliates')->default(0);});
$schema->create('privileges', function($t){$t->increments('idprivileges');$t->integer('rep_idrep');$t->tinyInteger('is_god')->default(0);$t->tinyInteger('is_admin')->default(0);$t->tinyInteger('is_manager')->default(0);$t->tinyInteger('is_rep')->default(0);});
DB::table('permissions')->insert([['aff_id'=>1],['aff_id'=>2]]);
DB::table('privileges')->insert([['rep_idrep'=>1,'is_god'=>1,'is_admin'=>0,'is_manager'=>0,'is_rep'=>0],['rep_idrep'=>2,'is_god'=>0,'is_admin'=>1,'is_manager'=>0,'is_rep'=>0]]);
require __DIR__.'/../database/migrations/2026_08_31_000001_create_announcements_table_and_permission.php';
$migration = new CreateAnnouncementsTableAndPermission(); $migration->up();
$checks=0;
function verifyAnnouncement($ok,$message){global $checks;if(!$ok)throw new RuntimeException($message);$checks++;}
verifyAnnouncement($schema->hasTable('announcements'),'Announcements migration missing table');
verifyAnnouncement($schema->hasColumn('permissions',Permissions::CREATE_ANNOUNCEMENTS),'Permission column missing');
verifyAnnouncement((int)DB::table('permissions')->where('aff_id',1)->value(Permissions::CREATE_ANNOUNCEMENTS)===1,'Network Admin permission not enabled');
verifyAnnouncement((int)DB::table('permissions')->where('aff_id',2)->value(Permissions::CREATE_ANNOUNCEMENTS)===0,'Regular Admin permission enabled by default');
verifyAnnouncement(Permissions::$permissionsArray[Permissions::CREATE_ANNOUNCEMENTS]['allowed_user_types']===[Privilege::ROLE_GOD,Privilege::ROLE_ADMIN],'Permission role metadata incorrect');

$schema->create('rep',function($t){$t->increments('idrep');$t->string('user_name')->nullable();$t->string('first_name')->nullable();$t->string('last_name')->nullable();$t->integer('lft');$t->integer('rgt');});
$schema->create('offer',function($t){$t->increments('idoffer');$t->string('offer_name');});
$schema->create('clicks',function($t){$t->increments('idclicks');$t->dateTime('first_timestamp');$t->integer('rep_idrep');$t->integer('offer_idoffer');$t->integer('click_type');});
$schema->create('conversions',function($t){$t->increments('id');$t->integer('user_id');$t->integer('click_id');$t->dateTime('timestamp');});
DB::table('rep')->insert([
 ['idrep'=>10,'user_name'=>'manager','lft'=>1,'rgt'=>8],['idrep'=>11,'user_name'=>'agent-a','lft'=>2,'rgt'=>3],['idrep'=>12,'user_name'=>'agent-b','lft'=>4,'rgt'=>5],['idrep'=>20,'user_name'=>'outside','lft'=>9,'rgt'=>10],
]);
DB::table('offer')->insert([['idoffer'=>100,'offer_name'=>'Alpha'],['idoffer'=>200,'offer_name'=>'Beta']]);
$today=Carbon::parse('2026-08-31 12:00:00');
DB::table('clicks')->insert([
 ['idclicks'=>1,'first_timestamp'=>'2026-08-31 09:00:00','rep_idrep'=>11,'offer_idoffer'=>100,'click_type'=>0],
 ['idclicks'=>2,'first_timestamp'=>'2026-08-31 09:01:00','rep_idrep'=>11,'offer_idoffer'=>100,'click_type'=>1],
 ['idclicks'=>3,'first_timestamp'=>'2026-08-31 10:00:00','rep_idrep'=>12,'offer_idoffer'=>200,'click_type'=>0],
 ['idclicks'=>4,'first_timestamp'=>'2026-08-31 10:01:00','rep_idrep'=>20,'offer_idoffer'=>100,'click_type'=>0],
 ['idclicks'=>5,'first_timestamp'=>'2026-08-30 10:01:00','rep_idrep'=>11,'offer_idoffer'=>100,'click_type'=>0],
 ['idclicks'=>6,'first_timestamp'=>'2026-08-31 11:01:00','rep_idrep'=>11,'offer_idoffer'=>100,'click_type'=>2],
]);
DB::table('conversions')->insert([['id'=>1,'user_id'=>11,'click_id'=>1,'timestamp'=>'2026-08-31 10:00:00'],['id'=>2,'user_id'=>20,'click_id'=>4,'timestamp'=>'2026-08-31 10:00:00']]);
$agent=DashboardTrafficSnapshot::forUser(11,Privilege::ROLE_AFFILIATE,$today);
verifyAnnouncement($agent->count()===1 && (int)$agent[0]->unique_clicks===1 && (int)$agent[0]->total_sales===1,'Agent traffic scope/totals incorrect');
$manager=DashboardTrafficSnapshot::forUser(10,Privilege::ROLE_MANAGER,$today);
verifyAnnouncement($manager->count()===2 && $manager->sum('unique_clicks')==2 && $manager->sum('total_sales')==1,'Manager team traffic scope/totals incorrect');
verifyAnnouncement(!$manager->contains('offer_name','outside'),'Outside traffic leaked');
verifyAnnouncement(DashboardTrafficSnapshot::forUser(1,Privilege::ROLE_ADMIN,$today)->isEmpty(),'Admin received manager/agent snapshot');

$temp=sys_get_temp_dir().'/announcement-test-'.bin2hex(random_bytes(6));mkdir($temp,0755,true);
config(['filesystems.disks.local'=>['driver'=>'local','root'=>$temp]]); Storage::forgetDisk('local');
$session=app('session')->driver();$session->start();app('redirect')->setSession($session);
$_SESSION=['repid'=>1];
file_put_contents($temp.'/source.pdf','safe fixture');
$request=Request::create('/announcements','POST',['title'=>'Launch','type'=>'info','body'=>'Details','is_pinned'=>'1'],[],['attachment'=>new UploadedFile($temp.'/source.pdf','Launch Notes.pdf','application/pdf',null,true)]);
$request->setLaravelSession($session);$app->instance('request',$request);
$controller=new AnnouncementController();$response=$controller->store($request);
verifyAnnouncement($response->getStatusCode()===302 && str_ends_with($response->getTargetUrl(),'/announcements'),'Save did not redirect to announcement management');
$announcement=Announcement::query()->first();
verifyAnnouncement($announcement && $announcement->title==='Launch' && $announcement->is_pinned,'Announcement fields not saved');
verifyAnnouncement($announcement->attachment_name==='Launch Notes.pdf' && Storage::disk('local')->exists($announcement->attachment_path),'Private attachment not saved');
verifyAnnouncement(!str_contains($announcement->attachment_path,'Launch Notes'),'Original filename exposed in storage path');
$originalPath=$announcement->attachment_path;
$download=$controller->download($announcement);
verifyAnnouncement($download->getStatusCode()===200 && str_contains((string)$download->headers->get('content-disposition'),'Launch Notes.pdf'),'Protected download response incorrect');
$editRequest=Request::create('/announcements/'.$announcement->id,'PUT',['title'=>'Updated Launch','type'=>'bonus','body'=>'Revised details']);
$editRequest->setLaravelSession($session);$app->instance('request',$editRequest);$editResponse=$controller->update($editRequest,$announcement);
$announcement->refresh();
verifyAnnouncement($editResponse->isRedirect()&&$announcement->title==='Updated Launch'&&$announcement->type==='bonus','Announcement text update failed');
verifyAnnouncement($announcement->attachment_path===$originalPath&&Storage::disk('local')->exists($originalPath),'Normal edit removed the existing attachment');
file_put_contents($temp.'/replacement.txt','replacement fixture');
$replaceRequest=Request::create('/announcements/'.$announcement->id,'PUT',['title'=>'Updated Launch','type'=>'info','body'=>'With replacement'],[],['attachment'=>new UploadedFile($temp.'/replacement.txt','Replacement.txt','text/plain',null,true)]);
$replaceRequest->setLaravelSession($session);$app->instance('request',$replaceRequest);$controller->update($replaceRequest,$announcement);$announcement->refresh();
verifyAnnouncement($announcement->attachment_name==='Replacement.txt'&&Storage::disk('local')->exists($announcement->attachment_path),'Replacement attachment was not saved');
verifyAnnouncement(!Storage::disk('local')->exists($originalPath),'Replaced attachment was not cleaned up');
$replacementPath=$announcement->attachment_path;
$invalid=Request::create('/announcements','POST',['title'=>'','type'=>'script','body'=>'']);
try{$controller->store($invalid);verifyAnnouncement(false,'Invalid announcement accepted');}catch(Illuminate\Validation\ValidationException $e){verifyAnnouncement(true,'Invalid announcement blocked');}
verifyAnnouncement(Announcement::query()->count()===1,'Invalid request created a row');
foreach ([['GET','announcements'],['GET','announcements/create'],['POST','announcements'],['GET','announcements/1/edit'],['PUT','announcements/1'],['DELETE','announcements/1']] as [$method,$path]) {$route=app('router')->getRoutes()->match(Request::create('/'.$path,$method));$mw=$route->gatherMiddleware();verifyAnnouncement(in_array('legacy.auth',$mw)&&in_array('role:0,1',$mw)&&in_array('permissions:'.Permissions::CREATE_ANNOUNCEMENTS,$mw),'Announcement management route missing access controls');}
$route=app('router')->getRoutes()->match(Request::create('/announcements/1/attachment','GET'));verifyAnnouncement(in_array('legacy.auth',$route->gatherMiddleware()),'Download route is public');
class AnnouncementPermissionStub { public function __construct(private bool $allowed) {} public function can($permission) { return $this->allowed && $permission === Permissions::CREATE_ANNOUNCEMENTS; } }
$_SESSION['userType']=Privilege::ROLE_ADMIN; $_SESSION['permissions']=serialize(new AnnouncementPermissionStub(false));
$denied=(new App\Http\Middleware\LegacyPermissionMiddleware())->handle($request,fn()=>response('allowed'),Permissions::CREATE_ANNOUNCEMENTS);
verifyAnnouncement($denied->isRedirect() && str_ends_with($denied->getTargetUrl(),'/dashboard'),'Admin without permission was allowed');
$_SESSION['permissions']=serialize(new AnnouncementPermissionStub(true));
$allowed=(new App\Http\Middleware\LegacyPermissionMiddleware())->handle($request,fn()=>response('allowed'),Permissions::CREATE_ANNOUNCEMENTS);
verifyAnnouncement($allowed->getContent()==='allowed','Permitted Admin was denied');
$_SESSION['userType']=Privilege::ROLE_MANAGER;
try{(new App\Http\Middleware\LegacyAccountTypeMiddleware())->handle($request,fn()=>response('allowed'),'0','1');verifyAnnouncement(false,'Manager reached Admin create route');}catch(Symfony\Component\HttpKernel\Exception\HttpException $e){verifyAnnouncement($e->getStatusCode()===403,'Manager role denial was incorrect');}
$deleteResponse=$controller->destroy($announcement);
verifyAnnouncement($deleteResponse->isRedirect()&&Announcement::query()->count()===0,'Announcement deletion failed');
verifyAnnouncement(!Storage::disk('local')->exists($replacementPath),'Deleted announcement attachment was not cleaned up');
Storage::disk('local')->deleteDirectory('announcements');unlink($temp.'/source.pdf');unlink($temp.'/replacement.txt');rmdir($temp);
$migration->down();verifyAnnouncement(!$schema->hasTable('announcements')&&!$schema->hasColumn('permissions',Permissions::CREATE_ANNOUNCEMENTS),'Migration rollback incomplete');
echo "Passed {$checks} announcement/dashboard assertions (isolated SQLite and private storage).\n";
