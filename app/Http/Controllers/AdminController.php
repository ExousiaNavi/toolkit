<?php

namespace App\Http\Controllers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function index(){
        // dd('admin');
        return view('admin.dashboard');
    }

    // granted
    public function grantedAccount(){
        // dd('dwawadwad');
        $users = User::where('role', '!=', 'admin')
        ->whereHas('ips', function ($query) {
            $query->where('status', 1); // Only include users with IPs that have status 0
        })
        ->with(['ips' => function ($query) {
            $query->where('status', 1); // Eager load only IPs with status 0
        }])
        ->latest()
        ->paginate(10); // Adjust the number of items per page as needed

        // Add initials and formatted date to each user
        $users->getCollection()->transform(function ($user) {
            $user->initials = $this->getInitials($user->name);
            $user->formatted_date = $this->formatDate($user->created_at);
            return $user;
        });

        return view('admin.pages.granted', compact('users'));
    }

    // requesting
    public function requestAccount(){
        // dd('dwawadwad');
        $users = User::where('role', '!=', 'admin')
        ->whereHas('ips', function ($query) {
            $query->where('status', 0); // Only include users with IPs that have status 0
        })
        ->with(['ips' => function ($query) {
            $query->where('status', 0); // Eager load only IPs with status 0
        }])
        ->latest()
        ->paginate(10); // Adjust the number of items per page as needed

        // Add initials and formatted date to each user
        $users->getCollection()->transform(function ($user) {
            $user->initials = $this->getInitials($user->name);
            $user->formatted_date = $this->formatDate($user->created_at);
            return $user;
        });

        return view('admin.pages.requesting', compact('users'));
    }

    //granted permission
    public function setAccountAccess(Request $request){
        // dd($request->id);
        $existID = User::with('ips')->find($request->id);
        // Debug the validated ID
        // dd($existID);
        // Example of updating the ips
        if ($existID) {
            // Update the ips related to the user
            $existID->ips->status = 1; // Or any other logic
            $existID->ips->save();
        }

        // Optionally return a response or redirect
        return Redirect::route('admin.request.account')->with(['status'=>'granted','title'=>'Granted Successfully!','text' => $existID->name.' has been granted access to toolkit platform.', 'icon'=>'success']);
        // return response()->json(['status'=>'success','message' => $existID->name.' has now access to toolkit platform.']);
    }

    //granted permission
    public function setAccountRemoveAccess(Request $request){
        // dd($request->id);
        $existID = User::with('ips')->find($request->id);
        // Debug the validated ID
        // dd($existID);
        // Example of updating the ips
        if ($existID) {
            // Update the ips related to the user
            $existID->ips->status = 2; // 2 mean declined
            $existID->ips->save();
        }

        // Optionally return a response or redirect
        return Redirect::route('admin.request.account')->with(['status'=>'declined','title'=>'Request Declined!','text' => $existID->name.' has been declined access to the toolkit platform.', 'icon'=>'error']);
        // return response()->json(['status'=>'success','message' => $existID->name.' has now access to toolkit platform.']);
    }

    // generate profile based on name
    private function getInitials($name){
        $words = explode(' ', $name);

        // Get the first character of the first word
        $initials = strtoupper($words[0][0]);

        if (count($words) > 1) {
            // Check if the first letters of both words are the same
            if ($words[0][0] == $words[1][0]) {
                // If the letters are the same, take the second character of the second word
                $initials .= strtoupper($words[1][1]);
            } else {
                // Otherwise, take the first character of the second word
                $initials .= strtoupper($words[1][0]);
            }
        }

        return $initials;
    }

    // format date
    private function formatDate($date){
        return Carbon::parse($date)->format('d M, h:i A'); // 12-hour format with AM/PM
    }
}
