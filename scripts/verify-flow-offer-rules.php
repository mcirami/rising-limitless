<?php
/** Isolated tests for flow-to-offer rule generation; never touches the configured database. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__.'/../vendor/autoload.php';

use App\Http\Controllers\OfferRoutingFlowController;
use App\OfferRoutingFlow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

$app=require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['session.driver'=>'array','database.default'=>'sqlite','database.connections.sqlite'=>['driver'=>'sqlite','database'=>':memory:','prefix'=>'']]);
DB::purge('sqlite');
DB::statement('CREATE TABLE offer (idoffer INTEGER PRIMARY KEY, offer_name TEXT, status INTEGER)');
DB::statement('CREATE TABLE rule (idrule INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, offer_idoffer INTEGER, type TEXT, redirect_offer INTEGER, deny INTEGER, is_active INTEGER)');
DB::statement('CREATE TABLE geo_rule (idgeo_rule INTEGER PRIMARY KEY AUTOINCREMENT, rule_idrule INTEGER)');
DB::statement('CREATE TABLE country_list (idcountry_list INTEGER PRIMARY KEY AUTOINCREMENT, geo_rule_idgeo_rule INTEGER, country_code TEXT, country_name TEXT, cap_status INTEGER, cap INTEGER)');
$flowsMigration=require __DIR__.'/../database/migrations/2026_10_07_000001_create_offer_routing_flows_table.php';$flowsMigration->up();
$rulesMigration=require __DIR__.'/../database/migrations/2026_10_07_000002_add_routing_flow_id_to_rules.php';$rulesMigration->up();
foreach ([926,927,928,929,930] as $id) DB::table('offer')->insert(['idoffer'=>$id,'offer_name'=>'Offer '.$id,'status'=>1]);
$session=app('session')->driver();$session->start();app('redirect')->setSession($session);
$checks=0;
function checkPublish($ok,$message){global $checks;if(!$ok)throw new RuntimeException($message);$checks++;}
function publishRequest($data){global $app,$session;$r=Request::create('/offer/routing-flows','POST',$data);$r->setLaravelSession($session);$app->instance('request',$r);return $r;}
function rejectPublish($call,$fragment){try{$call();checkPublish(false,'Expected conflict: '.$fragment);}catch(ValidationException $e){checkPublish(str_contains(json_encode($e->errors()),$fragment),'Wrong validation failure');}}
$controller=new OfferRoutingFlowController();
$data=['name'=>'CC flow','entry_offer_id'=>926,'is_active'=>1,'fallback_offer_id'=>929,'steps'=>[
 ['offer_id'=>926,'countries'=>['AU','ES','FR']],['offer_id'=>927,'countries'=>['BE','DE','NL']],['offer_id'=>928,'countries'=>['US']],
]];
$controller->store(publishRequest($data));$flow=OfferRoutingFlow::first();
checkPublish(DB::table('rule')->count()===0,'Ordinary save created rules');
$publish=$data+['create_offer_rules'=>1];
$controller->update(publishRequest($publish),$flow);
$rules=DB::table('rule')->orderBy('offer_idoffer')->get();
checkPublish($rules->count()===3,'Expected a rule for every offer');
checkPublish($rules->pluck('redirect_offer')->all()===[927,928,929],'Incorrect next-offer/final fallback chain');
checkPublish($rules->every(fn($r)=>$r->is_active==1 && $r->deny==0 && $r->routing_flow_id==$flow->id),'Rules not active allow-lists or untracked');
$geo=DB::table('geo_rule')->where('rule_idrule',$rules[0]->idrule)->value('idgeo_rule');
checkPublish(DB::table('country_list')->where('geo_rule_idgeo_rule',$geo)->pluck('country_code')->all()===['AU','ES','FR'],'Accepted countries were not copied');
$ids=$rules->pluck('idrule')->all();
$controller->update(publishRequest($publish),$flow);
checkPublish(DB::table('rule')->orderBy('offer_idoffer')->pluck('idrule')->all()===$ids,'Repeated generation duplicated rules');
checkPublish(DB::table('geo_rule')->count()===3 && DB::table('country_list')->count()===7,'Repeated generation duplicated children');
$reordered=$publish;$reordered['steps']=[$data['steps'][1],$data['steps'][0]];
$reordered['steps'][1]['countries']=['CA'];
$controller->update(publishRequest($reordered),$flow);
checkPublish(DB::table('rule')->where('offer_idoffer',927)->value('redirect_offer')===926,'Reorder did not update redirect');
checkPublish(DB::table('rule')->where('offer_idoffer',926)->value('redirect_offer')===929,'New last step did not redirect to fallback');
checkPublish(DB::table('rule')->count()===2 && DB::table('geo_rule')->count()===2 && DB::table('country_list')->count()===4,'Removed-step rules or child records leaked');
$controller->update(publishRequest($publish),$flow);
$paused=$publish;$paused['is_active']=0;
$controller->update(publishRequest($paused),$flow);
checkPublish(DB::table('rule')->where('is_active',1)->count()===3,'Explicit generation from paused flow should create active independent rules');
$all=$publish;$all['steps'][2]=['offer_id'=>928,'allow_all_countries'=>1];
$controller->update(publishRequest($all),$flow);
checkPublish(DB::table('country_list')->where('country_code','ALL')->count()===1,'Allow-all sentinel missing');
// Test the real legacy GEO checker without opening a GeoIP database.
$geoReflection=new ReflectionClass(LeadMax\TrackYourStats\Offer\Rules\Geo::class);
$checker=$geoReflection->newInstanceWithoutConstructor();
$geoReflection->getProperty('filteredRules')->setValue($checker,[['deny'=>0,'country_list'=>['ALL'],'redirect_offer'=>929]]);
foreach (['UNKNOWN','US',null] as $country) {
 $geoReflection->getProperty('countryISO')->setValue($checker,$country);
 checkPublish($checker->checkRules(),'Allow-all failed in legacy GEO engine');
}
$geoReflection->getProperty('filteredRules')->setValue($checker,[['deny'=>0,'country_list'=>['AU','FR','ES'],'redirect_offer'=>927]]);
$geoReflection->getProperty('countryISO')->setValue($checker,'AU');checkPublish($checker->checkRules(),'Allowed country rejected');
$geoReflection->getProperty('countryISO')->setValue($checker,'BE');checkPublish(!$checker->checkRules() && $checker->redirectOffer===927,'Country mismatch did not redirect');
$manual=DB::table('rule')->insertGetId(['name'=>'Existing manual rule','offer_idoffer'=>927,'type'=>'geo','redirect_offer'=>929,'deny'=>0,'is_active'=>1],'idrule');
$before=$flow->fresh()->toArray();
$conflicting=$publish;$conflicting['name']='Must roll back';
rejectPublish(fn()=>$controller->update(publishRequest($conflicting),$flow),'already has an active GEO rule');
checkPublish($flow->fresh()->toArray()===$before && DB::table('rule')->where('idrule',$manual)->value('is_active')===1,'Conflict partially saved flow or changed manual rule');
$device=DB::table('rule')->insertGetId(['name'=>'Device','offer_idoffer'=>927,'type'=>'device','redirect_offer'=>929,'deny'=>0,'is_active'=>1],'idrule');
$controller->update(publishRequest($publish+['replace_geo_rules'=>1]),$flow);
checkPublish(DB::table('rule')->where('idrule',$manual)->value('is_active')===0,'Explicit replacement did not deactivate manual GEO rule');
checkPublish(DB::table('rule')->where('idrule',$device)->value('is_active')===1,'Device rule changed');
$other=$publish;$other['entry_offer_id']=930;
rejectPublish(fn()=>$controller->store(publishRequest($other)),'generated by flow');
checkPublish(OfferRoutingFlow::count()===1,'Cross-flow conflict left partial new flow');
$cycle=$publish;$cycle['fallback_offer_id']=926;
rejectPublish(fn()=>$controller->update(publishRequest($cycle),$flow),'redirect loop');
$fallbackRule=DB::table('rule')->insertGetId(['name'=>'Fallback back edge','offer_idoffer'=>929,'type'=>'geo','redirect_offer'=>926,'deny'=>0,'is_active'=>1],'idrule');
rejectPublish(fn()=>$controller->update(publishRequest($publish),$flow),'redirect loop');
DB::table('rule')->where('idrule',$fallbackRule)->delete();
$controller->duplicate($flow);$copy=OfferRoutingFlow::orderByDesc('id')->first();
checkPublish(DB::table('rule')->where('routing_flow_id',$copy->id)->count()===0,'Duplicate took ownership of rules');
$normal=$data;$normal['steps'][0]['countries']=['CA'];
$controller->update(publishRequest($normal),$flow);
checkPublish(DB::table('country_list')->where('country_code','CA')->count()===0,'Ordinary save changed published rules');
$count=DB::table('rule')->count();$controller->destroy($flow);
checkPublish(DB::table('rule')->count()===$count && DB::table('rule')->whereNotNull('routing_flow_id')->count()===0,'Deleting flow deleted independent rules or left stale ownership');
$rulesMigration->down();checkPublish(!DB::getSchemaBuilder()->hasColumn('rule','routing_flow_id'),'Migration rollback failed');
echo "Passed {$checks} flow-to-offer rule assertions (isolated SQLite).\n";
