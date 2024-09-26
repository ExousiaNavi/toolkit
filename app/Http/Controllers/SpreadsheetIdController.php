<?php

namespace App\Http\Controllers;

use App\Models\Platform;
use App\Models\SpreadsheetId;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class SpreadsheetIdController extends Controller
{
    //return the page
    public function spreedsheet_manage($brand)
    {
        // dd($brand);
        // $platforms = Platform::get();
        // dd($platforms);
        $spreedsheet_collection = SpreadsheetId::where('brand',$brand)->paginate(10);
        // dd($spreedsheet_collection);
        return view('admin.pages.spreedsheet', compact('spreedsheet_collection', 'brand'));
    }

    //insert 
    public function spreedsheet_add(Request $request)
    {
        // dd($request);
        // dd($this->extractSpreadsheetId($request->link));
        $existingKeyword = SpreadsheetId::where('sid',$request->keyword)->first();

        if(!$existingKeyword){
            $splitterType = explode('|', $request->currencyType);
            // dd($splitterType);
            SpreadsheetId::create([
                "sid" => $request->keyword,
                'spread_id' => $this->extractSpreadsheetId($request->link),
                'platform' => $request->platform,
                'brand' => $request->brand,
                'currencyType' => $splitterType[1],
                'index' => $splitterType[0],
                'is_active' => true
            ]);
            return Redirect::route('admin.manage.spreedsheet',$request->brand)->with(['status'=>'success']);
        }else{
            return Redirect::route('admin.manage.spreedsheet',$request->brand)->with(['status'=>'error']);
        }
        
    }

    //edit
    public function spreedsheet_edit(Request $request)
    {   
        // dd($request);
        $editRecord = SpreadsheetId::find($request->id);
        // dd($editRecord);
        if($editRecord){
            $splitterType = explode('|', $request->currencyType);
            $editRecord->update([
                'sid' => $request->keyword,
                'spread_id' => $this->extractSpreadsheetId($request->link),
                'platform' => $request->platform,
                'currencyType' => $splitterType[1],
                'index' => $splitterType[0],
                'is_active' => $request->is_active,
            ]);
        }
        // return Redirect::route('admin.manage.spreedsheet',$request->brand)->with(['status'=>'save','title'=>'Save Successfully!','text' => $request->currency_name.' has been successfully added to toolkit platform.', 'icon'=>'success']);
        return Redirect::route('admin.manage.spreedsheet',$editRecord->brand)->with(['status'=>'success']);
    }

    //archived
    public function spreedsheet_archived($id)
    {
        // dd($id);
        $archived = SpreadsheetId::find($id);
        if($archived){
            $archived->delete();
            return Redirect::route('admin.manage.spreedsheet',$archived->brand)->with(['status'=>'save']);
        }else{
            return Redirect::route('admin.manage.spreedsheet',$archived->brand)->with(['status'=>'error']);
        }
    }

    private function extractSpreadsheetId($url)
    {
        $matches = [];
        preg_match('/\/d\/([a-zA-Z0-9-_]+)\//', $url, $matches);
        
        if (isset($matches[1])) {
            $spreadsheetId = $matches[1];
        } else {
            $spreadsheetId = null;  // Handle error
        }
    
        // Extract the gid (sheet ID)
        $urlComponents = parse_url($url);
        parse_str($urlComponents['query'], $queryParams);
        $gid = $queryParams['gid'] ?? null;
    
        return $spreadsheetId;
    }
}
