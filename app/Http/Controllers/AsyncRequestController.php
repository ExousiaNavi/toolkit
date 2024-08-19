<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class AsyncRequestController extends Controller
{
    //get the total of requesting account
    public function totalRequestAccount(){
        $usersGranted = User::where('role', '!=', 'admin')
        ->whereHas('ips', function ($query) {
            $query->where('status', 1); // Only include users with IPs that have status 0
        })
        ->with(['ips' => function ($query) {
            $query->where('status', 1); // Eager load only IPs with status 0
        }])->count();

        $usersRequested = User::where('role', '!=', 'admin')
        ->whereHas('ips', function ($query) {
            $query->where('status', 0); // Only include users with IPs that have status 0
        })
        ->with(['ips' => function ($query) {
            $query->where('status', 0); // Eager load only IPs with status 0
        }])->count();

        return response()->json(['usersRequested'=>$usersRequested, 'usersGranted'=>$usersGranted]);
    }
}
