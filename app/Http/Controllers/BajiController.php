<?php

namespace App\Http\Controllers;

use App\Models\BO;
use App\Models\Brand;
use App\Models\CLickAndImprs;
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
        $currencies = Currency::where('brand_id', 1)->get();
        // $bo = BO::with(['fe','ftds', 'clicks_impression'])->whereDate('created_at', Carbon::today())->latest()->paginate(10);
        $bo = BO::with(['fe','ftds', 'clicks_impression'])->where('brand','baji')->latest()->paginate(10);
        // dd($bo);
        $completedTask = BO::whereDate('created_at', Carbon::today())->where('brand','baji')->distinct()->pluck('currency')->toArray();
        $platforms = Platform::with('platformKeys')->get()->toArray();
        // dd($platforms);
        // dd($affiliates);
        $collectionKeys = $this->manualKeys();
        $m_count = BO::where('is_manual',true)->where('brand','baji')->count();
        return view('admin.pages.baji', compact("m_count","collectionKeys","currencies", 'bo', 'username', 'completedTask', 'platforms'));
    }

    //manual key collections
    private function manualKeys(){
        // Step 1: Get BOs where is_manual is true
       $bos = BO::where('is_manual',true)->get();
       // Step 2: Extract affiliate_username values from the BOs
       $boUsernames = $bos->pluck('affiliate_username')->toArray();
       // Step 3: Get matching PlatformKey records based on affiliate_usernames
       // $platformKeys = PlatformKey::with('platform')->whereIn('key', $boUsernames)->get();
       $keysCollection = [
           [
               'platform' => 'Adsterra',
               'currency' => 'NPR',
               'aff_username' => 'adsterra',
               'campaign_id' => ['852417','868539','1007305','1076509'],
           ],
           [
               'platform' => 'PropellerAds',
               'currency' => 'BDT',
               'aff_username' => 'propadsbdt',
               'campaign_id' => ['8391394','8126375'],
           ],
           [
               'platform' => 'HilltopAds',
               'currency' => 'BDT',
               'aff_username' => 'hilltopads',
               'campaign_id' => ['303343'],
           ],
           
           
           
       ];

       // Step 4: Filter the keysCollection based on the boUsernames
       $filteredKeysCollection = array_filter($keysCollection, function ($item) use ($boUsernames) {
           return in_array($item['aff_username'], $boUsernames);
       });

       // Step 5: Return the filtered collection
       return array_values($filteredKeysCollection); // Optional: Re-index the array
   }

    //insert clicks and impressions and cost
    public function insert(Request $request){
        // dd($request);
        // Validation
        $validatedData = $request->validate([
            'aff_username'      => 'required|array',
            'aff_username.*'    => 'required|string',
            'camp_id_input'     => 'required|array',
            'camp_id_input.*'   => 'required|string', // Adjust type if necessary
            'cost_input'        => 'required|array',
            'cost_input.*'      => 'required|numeric',
            'impression_input'  => 'required|array',
            'impression_input.*'=> 'required|integer',
            'click_input'       => 'required|array',
            'click_input.*'     => 'required|integer',
        ]);

        // Now you can access the values as arrays
        $affUsernames = $validatedData['aff_username'];
        $campaignIds = $validatedData['camp_id_input'];
        $costs = $validatedData['cost_input'];
        $impressions = $validatedData['impression_input'];
        $clicks = $validatedData['click_input'];

        // You can now loop over the arrays or manipulate the data as needed
        foreach ($affUsernames as $key => $username) {
            // Access related data with the same index
            $bosId = $this->bosID($affUsernames[$key]);
            $campaignId = $campaignIds[$key];
            $cost = $costs[$key];
            $impression = $impressions[$key];
            $click = $clicks[$key];

            // dd($bosId,$campaignId, $cost, $impression, $click);
            // Example: Store or process this data
            CLickAndImprs::create([
                'b_o_s_id' => $bosId,
                'creative_id' => $campaignId,
                'imprs' => $impression,
                'clicks' => $click,
                'spending' => $cost
            ]);
        }

        return Redirect::route('admin.'.$request->backto)->with(['status'=>'success', 'resend'=>true]);
    }

    //get the bo id based on affiliates username
    private function bosID($username){
        // Step 1: Find the record by affiliate_username
        $boRecord = BO::where('affiliate_username', $username)->first();

        if ($boRecord) {
            // Step 2: Update the record with the new data
            $boRecord->update(['is_manual'=>false]);

            // Step 3: Retrieve the updated record
            $updatedRecord = BO::where('affiliate_username', $username)->first();

            // Optionally return the updated record
            return $updatedRecord->id;
        } else {
            // If the record doesn't exist, handle it accordingly
            return response()->json(['message' => 'Record not found.'], 404);
        }
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
