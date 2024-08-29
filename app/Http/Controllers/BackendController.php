<?php

namespace App\Http\Controllers;

use App\Models\BO;
use App\Models\CLickAndImprs;
use App\Models\FE;
use App\Models\FTD;
use App\Models\Platform;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redirect;

class BackendController extends Controller
{
    protected $url = 'http://127.0.0.1:8081/api/bo/fetch'; //bo
    protected $url_fe = 'http://127.0.0.1:8081/api/fe/data'; //fe
    protected $url_cai = 'http://127.0.0.1:8081/api/cli/clicks'; //fe
    protected $url_sp = 'http://127.0.0.1:8081/api/cli/automate-spreedsheet'; //fe
    protected $credentials = [
        'email' => 'exousianavi',
        'password' => 'DataAnalys2024',
        'link' => 'https://www.1xoffer.com/page/manager/login.jsp',
    ];
    // representation of Baji currency on platform
    // protected $currencyType = [
    //     'all' => '-1',
    //     'BDT' => '8',
    //     'VND' => '2',
    //     'USD' => '15',
    //     'INR' => '7',
    //     'PKR' => '17',
    //     'PHP' => '16',
    //     'KRW' => '5',
    //     'IDR' => '6',
    //     'NPR' => '24',
    //     'THB' => '9',
    // ];


    //send request to python as BDT Currency
    public function BdtBOFetcher(Request $request)
    {
        ini_set('max_execution_time', 1200); // Increase to 10 minutes
        // $client = new \GuzzleHttp\Client(['timeout' => 1200]); // Set timeout to 20 minutes

        // Call the currencyCollection method to get the array for the requested currency
        $currencyData = $this->currencyCollection($request->currency);

        // Fetch data from the first platform
        $response = Http::timeout(1200)->post($this->url, [
            'email' => 'exousianavi',
            'password' => 'DataAnalys2024',
            'link' => 'https://www.1xoffer.com/page/manager/login.jsp',
            'fe_link' => 'https://bajipartners.com/page/affiliate/login.jsp',
            'currency' => $currencyData['index'],
            'keyword' => $currencyData['keywords']
        ]);

        if ($response->successful()) {
            $data = $response->json();

            foreach ($data as $item) {
                if (isset($item['bo']) && is_array($item['bo'])) {
                    foreach ($item['bo'] as $key => $value) {
                        $bo = BO::create([
                            'affiliate_username' => $value['Affiliate Username'],
                            'currency' => $value['Currency'],
                            'nsu' => $value['Registered Users'],
                            'ftd' => $value['Number of First Deposits'],
                            'active_player' => $value['Active Players'],
                            'total_deposit' => $value['Total Deposit'],
                            'total_withdrawal' => $value['Total Withdrawal'],
                            'total_turnover' => $value['Total Turnover'],
                            'profit_and_loss' => $value['Total Profit & Loss'],
                            'total_bonus' => $value['Total Bonus'],
                            'target_date' => Carbon::yesterday()->toDateString(),
                        ]);

                        
                        



                        // Fetch data from the second platform using the affiliate username
                        $accountData = $this->feAccountBaji($value['Affiliate Username']);
                        $fe_response = Http::timeout(1200)->post($this->url_fe, [
                            'username' => $value['Affiliate Username'],
                            'password' => $accountData,
                            'link' => 'https://bajipartners.com/page/affiliate/login.jsp',
                            'currency' => $value['Currency'],
                        ]);

                        if ($fe_response->successful()) {

                            // Fetch data clicks and impression
                        
                            // with recaptcha: richads, trafficnomads, Dao.ad, skipping
                            // skip adsterra, flatad, Exoclick

                            //no active ads: ClickAdu,ProfellerAds, hilltopads, trafficforce,admaven, Onclicka
                            //completed adcash,trafficstars,adxad

                            //Format data: [{'creative_id': '385568820', 'Impressions': '0', 'Clicks': '0', 'Spending': '0'}, {'creative_id': '390697020', 'Impressions': '59765', 'Clicks': '0', 'Spending': '29.28'}, {'creative_id': '390697620', 'Impressions': '0', 'Clicks': '0', 'Spending': '0'}]
                            $allowedUsernames = ['adcashpkr', 'trastarpkr', 'adxadbdt'];
                            if(in_array($value['Affiliate Username'], $allowedUsernames)){
                                $clicksAndImpressionData = $this->creativeId($value['Affiliate Username']);
                                $clicks_response = Http::timeout(1200)->post($this->url_cai, [
                                    'keywords' => $value['Affiliate Username'],
                                    'email' => $clicksAndImpressionData['email'],
                                    'password' => $clicksAndImpressionData['password'],
                                    'link' => $clicksAndImpressionData['link'],
                                    'dashboard' => $clicksAndImpressionData['dashboard'],
                                    'platform' => $clicksAndImpressionData['platform'],
                                    'creative_id' => $clicksAndImpressionData['creative_id'],
                                ]);

                                if($clicks_response->successful()){
                                    $clck_imprs = $clicks_response->json();
    
                                    if(isset($clck_imprs['data']['clicks_and_impr']) && is_array($clck_imprs['data']['clicks_and_impr'])){
                                        foreach ($clck_imprs['data']['clicks_and_impr'] as $clim) {
                                            CLickAndImprs::create([
                                                'b_o_s_id' => $bo->id,
                                                'creative_id' => $clim['creative_id'],
                                                'imprs' => $clim['Impressions'],
                                                'clicks' => $clim['Clicks'],
                                                'spending' => $clim['Spending'],
                                                
                                            ]);
                                        }
                                    }else{
                                        Log::warning('clicks_and_impr data is missing or not in expected format.', ['Clicks And Imprs' => $clck_imprs]);
                                    }
                                }else {
                                    return response()->json(['error' => 'Failed to fetch Clicks and Impression data'], 500);
                                }
                            }

                            


                            $fe_data = $fe_response->json();
                            // dd($fe_data);
                            if (isset($fe_data['data']['fe']) && is_array($fe_data['data']['fe'])) {
                                foreach ($fe_data['data']['fe'] as $fe_value) {
                                    FE::create([
                                        'b_o_s_id' => $bo->id,
                                        'keywords' => $fe_value['Keywords'],
                                        'currency' => $fe_value['Currency'],
                                        'registration_time' => $fe_value['Registration Time'],
                                        'first_deposit_time' => $fe_value['First Deposit Time'],
                                    ]);
                                }
                            } else {
                                // Log or handle the case where 'fe' data is missing or not an array
                                Log::warning('FE data is missing or not in expected format.', ['fe_data' => $fe_data]);
                            }
                            // ftd
                            if (isset($fe_data['data']['ftd']) && is_array($fe_data['data']['ftd'])) {
                                foreach ($fe_data['data']['ftd'] as $fe_value) {
                                    if($fe_value['First Deposit Time'] !== '0' && $fe_value['First Deposit Time'] !== ''){
                                        Log::info('First Deposit Time value.', ['first_deposit_time' => $fe_value['First Deposit Time']]);
                                        // Convert "First Deposit Time" to a Carbon instance
                                        $firstDepositTime = Carbon::createFromFormat('Y/m/d H:i:s', $fe_value['First Deposit Time']);
                                        
                                        // Check if the date is yesterday
                                        if ($firstDepositTime->isYesterday()) {
                                            FTD::create([
                                                'b_o_s_id' => $bo->id,
                                                'keywords' => $fe_value['Keyword'],
                                                'currency' => $fe_value['Currency'],
                                                'registration_time' => $fe_value['Registration Time'],
                                                'first_deposit_time' => $fe_value['First Deposit Time'],
                                            ]);
                                        } else {
                                            // Log or handle the case where the date is not yesterday
                                            Log::info('First Deposit Time is not yesterday.', ['first_deposit_time' => $fe_value['First Deposit Time']]);
                                        }
                                    }else{
                                        // Handle the case where "First Deposit Time" is 'First Deposit Time' or '0'
                                        Log::info('Invalid First Deposit Time value.', ['first_deposit_time' => $fe_value['First Deposit Time']]);
                                    }
                                }
                            } else {
                                // Log or handle the case where 'ftd' data is missing or not an array
                                Log::warning('FTD data is missing or not in expected format.', ['fe_data' => $fe_data]);
                            }
                        } else {
                            return response()->json(['error' => 'Failed to fetch FE data'], 500);
                        }
                    }
                } else {
                    // Log or handle the case where 'bo' data is missing or not an array
                    Log::warning('BO data is missing or not in expected format.', ['bo_data' => $item]);
                }
            }

            return response()->json(['result' => $data]);
        } else {
            return response()->json(['error' => 'Failed to fetch BO data'], 500);
        }
    }

    //automate spreedsheet report
    public function Spreedsheet(){
        ini_set('max_execution_time', 1200); // Increase to 10 minutes
        $dataset = [];
        // dd('recieved..');
        $bos = BO::with(['clicks_impression:b_o_s_id,creative_id,imprs,clicks,spending'])
        ->select('id','affiliate_username', 'nsu', 'ftd', 'active_player','total_deposit','total_withdrawal','total_turnover','profit_and_loss','total_bonus') // Replace with the columns you want to retrieve
        ->where('is_merged',false)
        ->whereDate('created_at', Carbon::today())
        ->latest()
        ->get();
        // dd($bos);
        foreach ($bos as $bo) {
            // dd($bo->clicks_impression);
            $info = $this->spreedsheetId($bo->affiliate_username);
            $dataset[] = [
                'spreadsheet' => $info,
                'keyword' => $bo->affiliate_username,
                'bo' => [$bo->nsu, $bo->ftd, $bo->active_player, $bo->total_deposit, $bo->total_withdrawal, $bo->total_turnover, $bo->profit_and_loss, $bo->total_bonus],
                'impression_and_clicks' => $bo->clicks_impression,
            ];
        }
        // dd($dataset);
        $sp = Http::withOptions(['timeout'=>1200,'connect_timeout' => 1200,])->post($this->url_sp, [
            'request_data' => $dataset,
        ]);

        if ($sp->successful()) {
            $sdata = $sp->json();
            $filteredData = array_slice($sdata['data'], 1);
            // dd($filteredData);
            foreach ($filteredData as $fd) {
                if($fd['status'] === 200){
                    $bo = BO::where('affiliate_username', $fd['keyword'])
                                ->whereDate('created_at', Carbon::today())  // Use whereDate to match only the date part of created_at
                                ->latest()  // Get the most recent record
                                ->first();  // Fetch the first record

                    if($bo) {
                        $bo->update(['is_merged' => true]);  // Update the is_merged column
                        Log::info('BO successfully updated the is_merged column.', ['BO' => $bo]);
                    } else {
                        Log::warning('Not found, BO failed to update the is_merged column.', ['keyword' => $fd['keyword']]);
                    }
                }
            }
            return response()->json(['result' => $sdata]);
        }else{
            return response()->json(['error' => 'Failed to fetch FE data'], 500);
        }
        
    }

    // private function for creative_id
    private function creativeId($cid){
        $creative_id = [
            'richadspkr' => [
                'creative_id' => ['3268137', '3352123'],
                'username' => '',
                'password' => ''
            ],
            'trafficnompkr' => [
                'creative_id' => ['20948', '20947', '22698'],
                'email' => 'abiralmilan1014@gmail.com',
                'password' => 'B@j!qwe@4444'
            ],
            'daoadpkr' => [],
            'adcashpkr' => [
                'creative_id' => ['385568820', '390697020', '390697620'],
                'email' => 'abiralmilan1014@gmail.com',
                'password' => 'B@j!qwe@4444',
                'link' => 'https://auth.myadcash.com/',
                'dashboard' => 'https://adcash.myadcash.com/dashboard/main',
                'platform' => 'adcash'
            ],
            'trastarpkr' => [
                'creative_id' => ['710956', '783520'],
                'email' => 'abiralmilan1014@gmail.com',
                'password' => 'B@j!qwe@4444',
                'link' => 'https://id.trafficstars.com/realms/trafficstars/protocol/openid-connect/auth?scope=openid&redirect_uri=http%3A%2F%2Fadmin.trafficstars.com%2Faccounts%2Fauth%2F%3Fnext%3Dhttps%3A%2F%2Fadmin.trafficstars.com%2F&response_type=code&client_id=web-app',
                'dashboard' => 'https://admin.trafficstars.com/advertisers/campaigns/',
                'platform' => 'trafficstars'
            ],
            'richads' => ['3215718','3318238'],
            'aff009a2' => ['500658','760898'],
            'adcash' => ['382857420','402136020'],
            'trafficnombdt' => ['22210'],
            'adsterra' => [],
            'flatadbdt' => [],
            'adxadbdt' => [
                'creative_id' => ['55347'],
                'email' => 'bo.cc@chengyi-1.com',
                'password' => 'B@j!09876**1',
                'link' => 'https://td.adxad.com/auth/login?lang=en',
                'dashboard' => 'https://td.adxad.com/auth/login?lang=en',
                'platform' => 'adxad'
            ],
            'exoclick' => ['6352776','5962530','6338274','6338296','6365518','6394024','6705106'],
            'propadsbdt' => ['8126375','8391394'],
            'clickadu' => [],
            'hilltopads' => [],
            'trafforcebdt' => [],
            'admavenbdt' => [],
            'richadspush' => ['3335637'],
            'onclicbdtpush' => ['58230'],
            'tforcepushbdt' => [],
            'richadspkpush' => ['3335663'],
            'daopkpush' => [],
            'daonppop' => [],
            'trafnomnpop' => [],
            'trafnomnpop' => [],

        ];

        return $creative_id[$cid];
    }

    // private function for spreedsheet id
    private function spreedsheetId($sid){
        $sheet_id = [
            'richadspkr' => [
                'spreed_id' => '1dkVVwuyLmvfpzYmhYVyCBK0XglN7SIc-jRMObmnt9w0',
                'platform' => 'Richads'
            ],
            'trafficnompkr' => [
                'spreed_id' => '1dkVVwuyLmvfpzYmhYVyCBK0XglN7SIc-jRMObmnt9w0',
                'platform' => 'TrafficNomads'
            ],
            'daoadpkr' => [
                'spreed_id' => '1dkVVwuyLmvfpzYmhYVyCBK0XglN7SIc-jRMObmnt9w0',
                'platform' => 'DaoAd'
            ],
            'adcashpkr' => [
                'spreed_id' => '1dkVVwuyLmvfpzYmhYVyCBK0XglN7SIc-jRMObmnt9w0',
                'platform' => 'Adcash'
            ],
            'trastarpkr' => [
                'spreed_id' => '1dkVVwuyLmvfpzYmhYVyCBK0XglN7SIc-jRMObmnt9w0',
                'platform' => 'TrafficStars'
            ],
            'richads' => [
                'spreed_id' => '1ymX-gWD0H-fy986-Mv7cbSUycAGs74jiX2-7gCk6Fg0',
                'platform' => 'Richads'
            ],
            'aff009a2' => [
                'spreed_id' => '1ymX-gWD0H-fy986-Mv7cbSUycAGs74jiX2-7gCk6Fg0',
                'platform' => 'TrafficStars'
            ],
            'adcash' => [
                'spreed_id' => '1ymX-gWD0H-fy986-Mv7cbSUycAGs74jiX2-7gCk6Fg0',
                'platform' => 'Adcash'
            ],
            'trafficnombdt' => [
                'spreed_id' => '1ymX-gWD0H-fy986-Mv7cbSUycAGs74jiX2-7gCk6Fg0',
                'platform' => 'TrafficNomads'
            ],
            'adsterra' => [
                'spreed_id' => '1ymX-gWD0H-fy986-Mv7cbSUycAGs74jiX2-7gCk6Fg0',
                'platform' => 'Adsterra'
            ],
            'flatadbdt' => [
                'spreed_id' => '1ymX-gWD0H-fy986-Mv7cbSUycAGs74jiX2-7gCk6Fg0',
                'platform' => 'FlatAd'
            ],
            'adxadbdt' => [
                'spreed_id' => '1ymX-gWD0H-fy986-Mv7cbSUycAGs74jiX2-7gCk6Fg0',
                'platform' => 'ADxAD'
            ],
            'exoclick' => [
                'spreed_id' => '1ymX-gWD0H-fy986-Mv7cbSUycAGs74jiX2-7gCk6Fg0',
                'platform' => 'Exoclick'
            ],
            'propadsbdt' => [
                'spreed_id' => '1ymX-gWD0H-fy986-Mv7cbSUycAGs74jiX2-7gCk6Fg0',
                'platform' => 'PropellerAds'
            ],
            'clickadu' => [
                'spreed_id' => '1ymX-gWD0H-fy986-Mv7cbSUycAGs74jiX2-7gCk6Fg0',
                'platform' => 'ClickAdu'
            ],
            'hilltopads' => [
                'spreed_id' => '1ymX-gWD0H-fy986-Mv7cbSUycAGs74jiX2-7gCk6Fg0',
                'platform' => 'HilltopAds'
            ],
            'trafforcebdt' => [
                'spreed_id' => '1ymX-gWD0H-fy986-Mv7cbSUycAGs74jiX2-7gCk6Fg0',
                'platform' => 'Trafficforce'
            ],
            'admavenbdt' => [
                'spreed_id' => '1ymX-gWD0H-fy986-Mv7cbSUycAGs74jiX2-7gCk6Fg0',
                'platform' => 'AdMaven'
            ],
            'richadspush' => [
                'spreed_id' => '1OzdmWMWmwVGgz0c0Q2NS3lSc38Jr9IJ_H3ibVz_gE0I',
                'platform' => 'Richads' 
            ],
            'onclicbdtpush' => [
                'spreed_id' => '1OzdmWMWmwVGgz0c0Q2NS3lSc38Jr9IJ_H3ibVz_gE0I',
                'platform' => 'Onclicka' 
            ],
            'tforcepushbdt' => [
                'spreed_id' => '1OzdmWMWmwVGgz0c0Q2NS3lSc38Jr9IJ_H3ibVz_gE0I',
                'platform' => 'TrafficForce' 
            ],
            'richadspkpush' => [
                'spreed_id' => '1_9wbZy6wxL5RM4sUU6ehxSwMVQNwtaAN57qz0BMQnyc',
                'platform' => 'Richads' 
            ],
            'daopkpush' => [
                'spreed_id' => '1_9wbZy6wxL5RM4sUU6ehxSwMVQNwtaAN57qz0BMQnyc',
                'platform' => 'DaoAd' 
            ],
            'daonppop' => [
                'spreed_id' => '1TBUUmJVzGoKpTxVoV5OH_jggRz_RqtHLEyg3uKyWoxU',
                'platform' => 'Dao.Ad' 
            ],
            'trafnomnpop' => [
                'spreed_id' => '1TBUUmJVzGoKpTxVoV5OH_jggRz_RqtHLEyg3uKyWoxU',
                'platform' => 'TrafficNomads' 
            ],
            

        ];

        return $sheet_id[$sid];
    }
    // private function for currency and associated keywords
    private function currencyCollection($curr)
    {
        // Mapping of currency codes to their respective values and keywords
        $currencyType = [
            'all' => [
                'index' => '-1',
                'keywords' => []
            ],
            'BDT' => [
                'index' => '8',
                'keywords' => ['richads', 'richadspush', 'onclicbdtpush', 'tforcepushbdt', 'aff009a2', 'adcash', 'trafficnombdt', 'adsterra', 'flatadbdt', 'adxadbdt', 'exoclick', 'propadsbdt', 'clickadu', 'hilltopads', 'trafforcebdt', 'admavenbdt']
            ],
            'VND' => [
                'index' => '2',
                'keywords' => ['keyword1', 'keyword2']
            ],
            'USD' => [
                'index' => '15',
                'keywords' => ['keyword3', 'keyword4']
            ],
            'INR' => [
                'index' => '7',
                'keywords' => ['keyword5', 'keyword6']
            ],
            'PKR' => [
                'index' => '17',
                'keywords' => ['richadspkr', 'richadspkpush', 'daopkpush', 'trafficnompkr', 'adcashpkr', 'daoadpkr', 'trastarpkr'],
                // 'creative_id' => ['3268137', '3352123', '']
            ],
            'PHP' => [
                'index' => '16',
                'keywords' => ['keyword7', 'keyword8']
            ],
            'KRW' => [
                'index' => '5',
                'keywords' => ['keyword7', 'keyword8']
            ],
            'IDR' => [
                'index' => '6',
                'keywords' => ['keyword7', 'keyword8']
            ],
            'NPR' => [
                'index' => '24',
                'keywords' => ['daonppop', 'trafnomnpop']
            ],
            'THB' => [
                'index' => '9',
                'keywords' => ['keyword7', 'keyword8']
            ],

        ];

        return $currencyType[$curr];
    }

    //fe accounts for baji
    private function feAccountBaji($key)
    {
        $accounts = [
            'richads' => 'affSystem0701',
            'richadspush' => 'affSystem0701',
            'richadspkr' => 'qwert12345',
            'richadspkpush' => 'qwert12345',
            'aff009a2' => 'affSystem0701',
            'trastarpkr' => 'qwert12345',
            'adcash' => 'affSystem0701',
            'adcashpkr' => 'qwert12345',
            'trafficnombdt' => 'affSystem0701',
            'trafficnompkr' => 'qwert12345',
            'trafnomnpop' => 'qwert12345',
            'adsterra' => 'affSystem0701',
            'flatadbdt' => 'affSystem0701',
            'adxadbdt' => 'affSystem0701',
            'exoclick' => 'affSystem0701',
            'propadsbdt' => 'affSystem0701',
            'clickadu' => 'affSystem0701',
            'hilltopads' => 'affSystem0701',
            'trafforcebdt' => 'affSystem0701',
            'tforcepushbdt' => 'affSystem0701',
            'admavenbdt' => 'affSystem0701',
            'daopkpush' => 'welcome123',
            'daoadpkr' => 'qwert12345',
            'daonppop' => 'qwert12345',
            'onclicbdtpush' => 'affSystem0701',
        ];
        return $accounts[$key];
    }
}
