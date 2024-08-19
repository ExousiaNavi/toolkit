<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redirect;

class BackendController extends Controller
{
    //send request to pthon
    public function test(){
        $response = Http::post('http://127.0.0.1:8081/api/test/fetch', [
            'email' => 'test@email.com',
            'password' => 'abc123',
        ]);

        // Handle the response from FastAPI
        if ($response->successful()) {
            // dd($response->json());
            $data = $response->json();
            // return Redirect::route('admin.baji')->with(['result'=>$data]);
            return response()->json(['result'=>$data]);
        } else {
            return response()->json(['error' => 'Failed to fetch data'], 500);
        }
    }
}
