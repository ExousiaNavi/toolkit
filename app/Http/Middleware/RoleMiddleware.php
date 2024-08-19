<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $role): Response
    {
        $userRole = $request->user()->role;

        // dd($userRole);
        // Define the valid roles and their respective dashboard routes using model constants
        $roles = [
            'admin' => User::ADMIN,
            'user' => User::USER,
        ];

        // Check if the user's role exists in the roles array and redirect accordingly
        if (array_key_exists($userRole, $roles)) {
            // dd('true');
            $routeName = $roles[$userRole];
            // dd($routeName);
            // Prevent redirection loop by checking if the current route matches the target route
            // if (!$request->routeIs($routeName)) {
            //     dd('true');
            //     return Redirect::route($routeName)->with([
            //         'status' => '',
            //         'message' => '',
            //     ]);
            // }
        }

        // Proceed to the next middleware if roles match
        return $next($request);
    }
}
