<?php

namespace App\Http\Controllers;

use App\Announcement;
use App\Privilege;
use App\Support\DashboardTrafficSnapshot;
use LeadMax\TrackYourStats\System\Company;
use LeadMax\TrackYourStats\System\Session;
use LeadMax\TrackYourStats\User\Permissions;

class DashboardController extends Controller
{
    public function home()
    {
        $role = (int) Session::userType();
        if (in_array($role, [Privilege::ROLE_MANAGER, Privilege::ROLE_AFFILIATE], true)) {
            return $this->trafficDashboard($role);
        }
        return $this->account();
    }

    public function account()
    {
        return view('home', [
            'canViewPostback' => Session::permissions()->can(Permissions::VIEW_POSTBACK),
            'postBackURL' => getWebRoot().'?uid='.Company::loadFromSession()->getUID().'&clickid=',
            'userId' => Session::userID(),
            'firstName' => Session::userData()->first_name,
            'email' => Session::userData()->email,
            'userType' => Session::userType(),
            'domain' => request()->getSchemeAndHttpHost().'/signup.php?mid=',
        ]);
    }

    private function trafficDashboard(int $role)
    {
        $announcements = Announcement::query()
            ->with('author:idrep,user_name,first_name,last_name')
            ->orderByDesc('is_pinned')
            ->orderByDesc('created_at')
            ->get();

        return view('dashboard', [
            'announcements' => $announcements,
            'announcementCount' => Announcement::query()->count(),
            'traffic' => DashboardTrafficSnapshot::forUser((int) Session::userID(), $role),
            'profile' => Session::userData(),
            'snapshotDate' => now(),
            'pageTitle' => 'Dashboard',
        ]);
    }
}
