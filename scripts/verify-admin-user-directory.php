<?php
/** Permission and directory checks against isolated SQLite and rendered Blade only. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__.'/../vendor/autoload.php';

use App\Http\Controllers\UserController;
use App\Privilege;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use LeadMax\TrackYourStats\User\Permissions;

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.default'=>'sqlite', 'database.connections.sqlite'=>['driver'=>'sqlite','database'=>':memory:','prefix'=>'']]);
DB::purge('sqlite');
DB::statement('CREATE TABLE rep (idrep INTEGER PRIMARY KEY, user_name TEXT, email TEXT, status INTEGER, referrer_repid INTEGER, rep_timestamp TEXT, lft INTEGER, rgt INTEGER)');
DB::statement('CREATE TABLE privileges (rep_idrep INTEGER, is_admin INTEGER, is_manager INTEGER, is_rep INTEGER, is_god INTEGER)');
foreach ([[1,'Network Admin',0,1,1,12],[2,'Current Admin',1,1,2,5],[3,'Other Admin',1,1,6,9],[4,'Inactive Admin',1,0,10,11],[5,'Agent',3,1,3,4]] as [$id,$name,$role,$status,$left,$right]) {
    DB::table('rep')->insert(['idrep'=>$id,'user_name'=>$name,'email'=>'','status'=>$status,'referrer_repid'=>1,'rep_timestamp'=>null,'lft'=>$left,'rgt'=>$right]);
    DB::table('privileges')->insert(['rep_idrep'=>$id,'is_admin'=>(int)($role===1),'is_manager'=>(int)($role===2),'is_rep'=>(int)($role===3),'is_god'=>(int)($role===0)]);
}
class DirectoryPermissions {
    public function __construct(private array $allowed) {}
    public function can($permission) { return in_array($permission,$this->allowed,true); }
}
$checks=0;
function checkDirectory($ok,$message) { global $checks; if (!$ok) throw new RuntimeException($message); $checks++; }
function directorySession($role,$permissions) { $_SESSION=['repid'=>2,'userType'=>$role,'permissions'=>serialize(new DirectoryPermissions($permissions))]; }
function directoryRequest($query=[]) {
    global $app;
    $request=Request::create('/user/manage','GET',$query);
    $app->instance('request',$request);
    return $request;
}
function directoryHtml($users) {
    $source=file_get_contents(__DIR__.'/../resources/views/user/manage.blade.php');
    $source=str_replace(["@extends('layouts.master')",'@endsection'],['','@show'],$source);
    return Blade::render($source,['users'=>$users]);
}
$controller=new UserController();
directorySession(Privilege::ROLE_ADMIN,[Permissions::VIEW_ALL_USERS,Permissions::EDIT_AFFILIATES]);
directoryRequest(['role'=>1]);
$users=$controller->viewManageUsers()->getData()['users'];
checkDirectory($users->pluck('idrep')->sort()->values()->all()===[2,3],'Permitted Admin must see all active Admins, including outside their tree');
$html=directoryHtml($users);
checkDirectory((bool)preg_match('/<option\s+selected\s+value=\'1\'>Admins/',$html),'Admins filter missing or not selected');
checkDirectory(str_contains($html,'/aff_update.php?idrep=3'),'Admin management link missing');
checkDirectory(!str_contains($html,'data-login-user='),'View permission must not grant impersonation');
checkDirectory(!$users->contains('idrep',1),'Network Admin leaked into Admin results');
directoryRequest(['role'=>1,'showInactive'=>1]);
checkDirectory($controller->viewManageUsers()->getData()['users']->pluck('idrep')->all()===[4],'Inactive Admin filter failed');
directorySession(Privilege::ROLE_ADMIN,[Permissions::VIEW_ALL_USERS]);
directoryRequest(['role'=>1]);
checkDirectory(!str_contains(directoryHtml($users),'/aff_update.php'),'View permission bypassed existing edit permission');
directorySession(Privilege::ROLE_ADMIN,[Permissions::EDIT_AFFILIATES]);
checkDirectory(!str_contains(directoryHtml([]),"value='1'>Admins"),'Admin without View All Users received Admins option');
checkDirectory($controller->viewManageUsers()->getData()['users']->isEmpty(),'Admin without permission saw peers via direct role parameter');
directorySession(Privilege::ROLE_MANAGER,[Permissions::VIEW_ALL_USERS]);
checkDirectory(!str_contains(directoryHtml([]),"value='1'>Admins"),'Manager received Admins option');
directorySession(Privilege::ROLE_GOD,[]);
checkDirectory(str_contains(directoryHtml([]),"value='1'>Admins"),'Network Admin lost Admins option');
directorySession(Privilege::ROLE_ADMIN,[Permissions::VIEW_ALL_USERS]);
checkDirectory(!str_contains(view('report.options.user-type')->render(),"value='1'>Admins"),'Shared report selector changed unintentionally');
echo "Passed {$checks} Admin directory assertions (isolated SQLite and Blade).\n";
