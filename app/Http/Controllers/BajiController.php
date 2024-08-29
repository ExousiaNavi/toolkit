<?php

namespace App\Http\Controllers;

use App\Models\BO;
use App\Models\Brand;
use App\Models\Currency;
use App\Models\Platform;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;

class BajiController extends Controller
{
    //main page of baji
    public function index(){
        $username = BO::whereDate('created_at', Carbon::today())->pluck('affiliate_username')->toArray();
        // dd($username);
        $currencies = Currency::get();
        $bo = BO::with(['fe','ftds', 'clicks_impression'])->whereDate('created_at', Carbon::today())->latest()->paginate(10);
        // dd($bo);
        $completedTask = BO::whereDate('created_at', Carbon::today())->distinct()->pluck('currency')->toArray();
        $platforms = Platform::with('platformKeys')->get()->toArray();
        // dd($platforms);
        // dd($affiliates);
        return view('admin.pages.baji', compact("currencies", 'bo', 'username', 'completedTask', 'platforms'));
    }

    public function createCurrency(Request $request){
        // dd($request);
        $brand = Brand::create(['brand'=>$request->brand]);
        if($brand){
            Currency::create([
                'brand_id'=>$brand->id, 
                'currency'=>$request->currency_name,
                'url'=>$request->spreadsheet_link,
                'email'=>$request->email,
                'password'=>$request->password,
            ]);

            return Redirect::route(Auth::user()->role.'.baji')->with(['status'=>'save','title'=>'Save Successfully!','text' => $request->currency_name.' has been successfully added to toolkit platform.', 'icon'=>'success']);
        }
    }
}
