<?php
/** Regression checks use an isolated in-memory database; never the configured application database. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
ini_set('zend.exception_ignore_args', '1');
require __DIR__.'/../vendor/autoload.php';

use App\Http\Controllers\OfferRoutingFlowController;
use App\OfferRoutingFlow;
use App\Support\OfferFlowRouter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use LeadMax\TrackYourStats\User\Permissions;

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['session.driver'=>'array', 'cache.default'=>'array', 'database.default'=>'sqlite', 'database.connections.sqlite'=>['driver'=>'sqlite','database'=>':memory:','prefix'=>'']]);
DB::purge('sqlite');
$checks = 0;
function checkFlow($ok, $message) { global $checks; if (!$ok) throw new RuntimeException($message); $checks++; }
checkFlow(OfferFlowRouter::forEntry(1) === null, 'Pre-migration clicks must retain legacy behavior');
$migration = require __DIR__.'/../database/migrations/2026_10_07_000001_create_offer_routing_flows_table.php';
$migration->up();
DB::statement('CREATE TABLE offer (idoffer INTEGER PRIMARY KEY, offer_name TEXT, status INTEGER)');
foreach ([1=>'Dates Cozy - AT',2=>'Dates Cozy - AT BE CH DE NL',3=>'CV - AU ES FR',4=>'CheekyCrush - AU GB HU IE RO US',5=>'Global fallback',6=>'Another entry',7=>'Inactive'] as $id=>$name) {
    DB::table('offer')->insert(['idoffer'=>$id,'offer_name'=>$name,'status'=>$id===7?0:1]);
}
$steps = [
    ['offer_id'=>1,'countries'=>['AT']],
    ['offer_id'=>2,'countries'=>['AT','BE','CH','DE','NL']],
    ['offer_id'=>3,'countries'=>['AU','ES','FR']],
    ['offer_id'=>4,'countries'=>['AU','GB','HU','IE','RO','US']],
];
foreach (['AT'=>1,'BE'=>2,'CH'=>2,'AU'=>3,'FR'=>3,'US'=>4,'GB'=>4,'CA'=>5,'UNKNOWN'=>5,''=>5,' us '=>4] as $country=>$expected) {
    $result = OfferFlowRouter::resolve($steps,5,$country);
    checkFlow($result['offer_id']===$expected, 'Wrong routing for '.$country);
}
checkFlow(OfferFlowRouter::resolve(array_reverse($steps),5,'AU')['offer_id']===4,'Priority reorder ignored');
checkFlow(OfferFlowRouter::resolve($steps,5,'US')['skipped']===[1,2,3], 'Skipped steps incorrect');
checkFlow(OfferFlowRouter::resolve($steps,5,null)['fallback'], 'Unknown country must use fallback');
$session=app('session')->driver();$session->start();app('redirect')->setSession($session);
function flowRequest($data, $method='POST') {
    global $session, $app;
    $r=Request::create('/offer/routing-flows',$method,$data);$r->setLaravelSession($session);$app->instance('request',$r);return $r;
}
$controller=new OfferRoutingFlowController();
$payload=['name'=>'Dates flow','entry_offer_id'=>1,'fallback_offer_id'=>5,'steps'=>$steps,'is_active'=>1];
$response=$controller->store(flowRequest($payload));
$flow=OfferRoutingFlow::first();
checkFlow($response->isRedirect() && $flow->steps===$steps,'Flow did not persist ordered steps');
checkFlow(OfferFlowRouter::forEntry(1)?->id===$flow->id,'Active entry not found');
checkFlow(OfferFlowRouter::forEntry(2)===null,'Destination membership enrolled unrelated traffic');
$preview=$controller->preview(flowRequest(['steps'=>$steps,'fallback_offer_id'=>5,'country'=>'AU']))->getData(true);
checkFlow($preview['offer_id']===3 && $preview['position']===3,'Preview differs from runtime');
foreach ([['entry_offer_id'=>1], ['entry_offer_id'=>6,'fallback_offer_id'=>7], ['entry_offer_id'=>6,'fallback_offer_id'=>999], ['entry_offer_id'=>null], ['entry_offer_id'=>6,'steps'=>[]], ['entry_offer_id'=>6,'steps'=>[['offer_id'=>2,'countries'=>['ZZ']]]], ['entry_offer_id'=>6,'steps'=>[['offer_id'=>2,'countries'=>['AT']],['offer_id'=>2,'countries'=>['BE']]]]] as $change) {
    try {$controller->store(flowRequest(array_replace($payload,$change)));checkFlow(false,'Invalid flow accepted');}
    catch (Illuminate\Validation\ValidationException $e) {checkFlow(true,'Rejected invalid flow');}
}
checkFlow(OfferRoutingFlow::count()===1,'Invalid saves changed storage');
$controller->duplicate($flow);
$copy=OfferRoutingFlow::orderByDesc('id')->first();
checkFlow(!$copy->is_active && $copy->entry_offer_id===null && $copy->steps===$steps,'Duplicate must be independent, unassigned and disabled');
$controller->update(flowRequest(array_replace($payload,['entry_offer_id'=>6,'steps'=>array_reverse($steps)]),'PUT'),$copy);
checkFlow($flow->fresh()->steps===$steps,'Editing copy modified original');
$controller->update(flowRequest(array_replace($payload,['is_active'=>0]),'PUT'),$flow);
checkFlow(OfferFlowRouter::forEntry(1)===null,'Paused flow still routes');
$controller->update(flowRequest($payload,'PUT'),$flow);
foreach ([['GET','offer/routing-flows'],['GET','offer/routing-flows/create'],['POST','offer/routing-flows'],['POST','offer/routing-flows/preview'],['GET','offer/routing-flows/1/edit'],['PUT','offer/routing-flows/1'],['POST','offer/routing-flows/1/duplicate'],['DELETE','offer/routing-flows/1']] as [$method,$path]) {
    $route=app('router')->getRoutes()->match(Request::create('/'.$path,$method));$mw=$route->gatherMiddleware();
    checkFlow(in_array('legacy.auth',$mw)&&in_array('role:0',$mw)&&in_array('permissions:'.Permissions::EDIT_OFFER_RULES,$mw),'Flow route lacks access control');
}
// Exercise real click resolution against legacy PDO access checks and cached GeoIP.
LeadMax\TrackYourStats\Database\DatabaseConnection::changeConnection(DB::connection()->getPdo());
DB::statement('CREATE TABLE rep (idrep INTEGER PRIMARY KEY, status INTEGER)');
DB::statement('CREATE TABLE permissions (aff_id INTEGER)');
DB::statement('CREATE TABLE rep_has_offer (rep_idrep INTEGER, offer_idoffer INTEGER)');
DB::table('rep')->insert(['idrep'=>10,'status'=>1]);
foreach ([1,2,3,4,5,6] as $id) DB::table('rep_has_offer')->insert(['rep_idrep'=>10,'offer_idoffer'=>$id]);
Illuminate\Support\Facades\Cache::put('geoip_192.0.2.1',['isoCode'=>'AU'],60);
$apply=new ReflectionMethod(LeadMax\TrackYourStats\Clicks\URLEvents\ClickRegistrationEvent::class,'applyCountryFlow');
function eventFor($offer) { return new LeadMax\TrackYourStats\Clicks\URLEvents\ClickRegistrationEvent(10,$offer,['repid'=>10,'offerid'=>$offer,'sub1'=>'original-sub'],'192.0.2.1'); }
$event=eventFor(1);checkFlow($apply->invoke($event) && $event->offerId===3 && $event->subVarArray['sub1']==='original-sub','Live resolution or sub-ID preservation failed');
$event=eventFor(2);checkFlow($apply->invoke($event) && $event->offerId===2,'Direct destination traffic changed');
// A destination also assigned to another active flow must not chain.
$copy->update(['entry_offer_id'=>3]);
$event=eventFor(1);checkFlow($apply->invoke($event) && $event->offerId===3,'Destination flow was followed');
DB::table('rep_has_offer')->where('offer_idoffer',3)->delete();
$event=eventFor(1);checkFlow(!$apply->invoke($event),'Destination access was bypassed');
DB::table('rep_has_offer')->insert(['rep_idrep'=>10,'offer_idoffer'=>3]);
DB::table('rep_has_offer')->where('offer_idoffer',1)->delete();
$event=eventFor(1);checkFlow(!$apply->invoke($event),'Entry access was bypassed');
DB::table('rep_has_offer')->insert(['rep_idrep'=>10,'offer_idoffer'=>1]);
DB::table('offer')->where('idoffer',3)->update(['status'=>0]);
$event=eventFor(1);checkFlow(!$apply->invoke($event),'Inactive destination was accepted');
DB::table('offer')->where('idoffer',3)->update(['status'=>1]);
DB::table('rep')->where('idrep',10)->update(['status'=>0]);
$event=eventFor(1);checkFlow(!$apply->invoke($event),'Inactive affiliate was accepted');
// Verify only GEO checks are replaced, leaving device/repeat-click checks installed.
DB::statement('CREATE TABLE rule (idrule INTEGER, offer_idoffer INTEGER)');
DB::statement('CREATE TABLE geo_rule (idgeo_rule INTEGER, rule_idrule INTEGER)');
DB::statement('CREATE TABLE country_list (geo_rule_idgeo_rule INTEGER)');
DB::statement('CREATE TABLE device_rule (iddevice_rule INTEGER, rule_idrule INTEGER)');
DB::statement('CREATE TABLE device_list (device_rule_iddevice_rule INTEGER)');
$property=new ReflectionProperty(LeadMax\TrackYourStats\Offer\Rules::class,'ruleObjs');
$_SERVER['REMOTE_ADDR']='192.0.2.1';
$normal=$property->getValue(new LeadMax\TrackYourStats\Offer\Rules(3));
$routed=$property->getValue(new LeadMax\TrackYourStats\Offer\Rules(3,null,true));
checkFlow(count($normal)===3 && count($routed)===2,'Wrong rules replaced');
checkFlow($routed[0] instanceof LeadMax\TrackYourStats\Offer\Rules\NoneUnique && $routed[1] instanceof LeadMax\TrackYourStats\Offer\Rules\Device,'Device or repeat-click protections removed');
// Render the real editor with an isolated shell for optional browser QA.
if (isset($argv[1])) {
    $fixtureDir=$argv[1];
    if (!is_dir($fixtureDir)) mkdir($fixtureDir,0755,true);
    file_put_contents($fixtureDir.'/flow-test-layout.blade.php', <<<'LAYOUT'
<!doctype html><html><head><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="/css/bootstrap.min.css"><link rel="stylesheet" href="/css/network.css"><style>.flow-editor{margin:24px!important;width:auto!important}.panels_wrap{margin:0!important;padding:0!important}</style></head><body class="rl-app"><main class="panels_wrap">@yield('content')</main>@yield('footer')</body></html>
LAYOUT
    );
    app('view')->addLocation($fixtureDir);
    $app->instance('request',Request::create('http://127.0.0.1:8974/offer/routing-flows/1/edit'));
    app('url')->forceRootUrl('http://127.0.0.1:8974');
    $source=str_replace("@extends('layouts.master')","@extends('flow-test-layout')",file_get_contents(__DIR__.'/../resources/views/offer-flows/editor.blade.php'));
    $html=Illuminate\Support\Facades\Blade::render($source, ['flow'=>$flow->fresh(),'offers'=>App\Offer::orderBy('idoffer')->get(),'countries'=>LeadMax\TrackYourStats\Offer\Rules\Geo::$countries,'errors'=>new Illuminate\Support\ViewErrorBag()]);
    file_put_contents($fixtureDir.'/editor.html',$html);
    checkFlow(str_contains($html,'Dates flow') && str_contains($html,'flow-step-template'),'Editor failed to render');
}
$controller->destroy($flow);checkFlow(OfferFlowRouter::forEntry(1)===null && OfferRoutingFlow::count()===1,'Deletion affected wrong flow');
$migration->down();checkFlow(OfferFlowRouter::forEntry(3)===null,'Rollback broke legacy clicks');
echo "Passed {$checks} offer routing flow assertions (isolated SQLite).\n";
