<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\IP;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);
        // Get the local IP address of the server/computer
        $request_ip = getHostByName(getHostName());
        // dd($request_ip);
        $existedIP = IP::where('ip_address', $request_ip)->with('user')->first();
        // dd($existedIP);
        if(!$existedIP){
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);
    
            if($user){
                $ip = IP::create([
                    'user_id' => $user->id,
                    'ip_address' => $request_ip,
                ]);
            }
    
        }else{
            throw ValidationException::withMessages([
                'ip_status' => 'Your IP address is already in used, kindly ask the administrator!.',
            ]);
        }
        
        event(new Registered($user));

        // automatically login the user after registration
        // Auth::login($user);
        // throw ValidationException::withMessages([
        //     'ip_status' => 'Your registration is in process, kindly ask the administrator to grant access!.',
        // ]);

        return Redirect::route('login')->with(['ip_status'=>'Your registration is in process, kindly ask the administrator to grant access!.']);
        // return redirect(route('dashboard', absolute: false));
    }
}
