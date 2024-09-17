<?php

namespace App\Http\Controllers;

use App\Models\BO;
use App\Models\CidCollection;
use App\Models\CLickAndImprs;
use App\Models\FE;
use App\Models\FTD;
// use App\Models\Platform;
use Carbon\Carbon;
// use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
// use Illuminate\Support\Facades\Redirect;
// use Symfony\Component\Process\Exception\ProcessFailedException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\ConnectionException;
// use Symfony\Component\Process\Process;
class BackendController extends Controller
{
    protected $url = 'http://127.0.0.1:8082/api/bo/fetch'; //bo
    protected $url_fe = 'http://127.0.0.1:8082/api/fe/data'; //fe
    protected $url_cai = 'http://127.0.0.1:8082/api/cli/clicks'; //fe
    protected $url_sp = 'http://127.0.0.1:8082/api/cli/automate-spreedsheet'; //fe
    protected $credentials = [
        'email' => 'exousianavi',
        'password' => 'DataAnalys2024',
        'link' => 'https://www.1xoffer.com/page/manager/login.jsp',
    ];


    //send request to python as BDT Currency
    public function BdtBOFetcher(Request $request)
    {
        ini_set('max_execution_time', 3600); // Increase to 10 minutes
        // $client = new \GuzzleHttp\Client(['timeout' => 1200]); // Set timeout to 20 minutes
       
        // Call the currencyCollection method to get the array for the requested currency
        $currencyData = $this->currencyCollection($request->currency);

        try {
            // Fetch data from the first platform
            $response = Http::timeout(3600)->post($this->url, [
                'email' => 'exousianavi',
                'password' => 'DataAnalys2024',
                'link' => 'https://www.1xoffer.com/page/manager/login.jsp',
                'fe_link' => 'https://bajipartners.com/page/affiliate/login.jsp',
                'currency' => $currencyData['index'],
                'keyword' => $currencyData['keywords']
            ]);
    
            // Check if the response was successful (status code 200)

            if ($response->successful()) {
                $data = $response->json();
                $manual_affiliates = ['adsterra','propadsbdt','hilltopads'];
                foreach ($data as $item) {
                    if (isset($item['bo']) && is_array($item['bo'])) {
                        foreach ($item['bo'] as $key => $value) {

                            // Step 1: Check if the affiliate_username already exists and was created today
                            $existingRecord = BO::where('affiliate_username', $value['Affiliate Username'])->where('brand','baji')
                            ->whereDate('created_at', Carbon::today())
                            ->first();

                            // Step 2: If the record exists, delete it
                            if ($existingRecord) {
                                $existingRecord->delete();
                            }

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
                                'brand' => 'baji',
                                 // Check if the Affiliate Username is in the list and set is_manual accordingly
                                 'is_manual' => in_array($value['Affiliate Username'] ?? false, $manual_affiliates),
                            ]);

                            
                            



                            // Fetch data from the second platform using the affiliate username
                            $accountData = $this->feAccountBaji($value['Affiliate Username']);
                            $fe_response = Http::timeout(3600)->post($this->url_fe, [
                                'username' => $value['Affiliate Username'],
                                'password' => $accountData,
                                'link' => 'https://bajipartners.com/page/affiliate/login.jsp',
                                'currency' => $value['Currency'],
                            ]);

                            if ($fe_response->successful()) {

                                // Fetch data clicks and impression
                            
                                // with recaptcha: , Dao.ad, ClickAdu, ProfellerAds, skipping
                                // skip adsterra, flatad, 

                                //no active ads:, hilltopads, trafficforce,admaven, Onclicka

                                //completed adcash,trafficstars,adxad, trafficnomads, Exoclick, richads

                                //Format data: [{'creative_id': '385568820', 'Impressions': '0', 'Clicks': '0', 'Spending': '0'}, {'creative_id': '390697020', 'Impressions': '59765', 'Clicks': '0', 'Spending': '29.28'}, {'creative_id': '390697620', 'Impressions': '0', 'Clicks': '0', 'Spending': '0'}]
                                //Request for Imprssions and Clicks
                                //no cost and impression
                                //'richads','richadspush','richadspkr','richadspkpush', 
                                $pendingKeywords = [
                                    'adsterra','flatadbdt','propadsbdt','hilltopads','trafforcebdt',
                                    'admavenbdt','onclicbdtpush','tforcepushbdt'];
                                $allowedUsernames = ['adcashpkr', 'trastarpkr', 'adxadbdt','trafficnompkr', 'exoclick','daonppop',''];
                                if(!in_array($value['Affiliate Username'], $pendingKeywords)){
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
                                                Log::info('Creative ID:.', ['Clicks And Imprs' => $clck_imprs['data']['clicks_and_impr']]);
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
    
            // Handle non-200 responses
            return response()->json([
                "result" => [
                    'success' => false,
                    'error' => 'Failed to fetch data from the platform.',
                    'status_code' => $response->status()
                    ]
            ], $response->status());
    
        } catch (ConnectionException $e) {
            // Handle connection-related errors (e.g., timeout)
            Log::error('Connection error: ' . $e->getMessage());
            return response()->json([
                'result' => [
                    'success' => false,
                    'error' => 'Failed to connect to the server. Please try again later.',
                    'exception_message' => $e->getMessage(),
                    'hint' => 'Check your network connection or server status.',
                    'suggestions' => [
                        'Try again after a few minutes.',
                        'Contact support if the issue persists.',
                    ],
                ]
            ], 500);
    
        } catch (RequestException $e) {
            // Handle HTTP-related errors (e.g., 4xx or 5xx responses)
            Log::error('Request error: ' . $e->getMessage());
            return response()->json([
                'result' => [
                    'success' => false,
                    'error' => 'Request to the platform failed. Please try again later.',
                    'exception_message' => $e->getMessage(),
                    'suggestions' => [
                        'Ensure your API credentials are correct.',
                        'Check if the platform is down for maintenance.',
                    ],
                ]
            ], 500);
    
        } catch (\Exception $e) {
            // Handle any other errors
            Log::error('An unexpected error occurred: ' . $e->getMessage());
            return response()->json([
                'result' => [
                    'success' => false,
                    'error' => 'An unexpected error occurred.',
                    'exception_message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),  // Optional, for debugging purposes
                    'hint' => 'This error was not anticipated. Contact support if it persists.',
                ]
            ], 500);
        }

        
    }

    //automate spreedsheet report
    public function Spreedsheet(){
        ini_set('max_execution_time', 1200); // Increase to 10 minutes
        $dataset = [];
        // dd('recieved..');
        $bos = BO::with(['fe','ftds','clicks_impression:b_o_s_id,creative_id,imprs,clicks,spending'])
        ->select('id','affiliate_username', 'nsu', 'ftd', 'active_player','total_deposit','total_withdrawal','total_turnover','profit_and_loss','total_bonus') // Replace with the columns you want to retrieve
        ->where('brand','baji')
        ->where('is_merged',false)
        ->where('is_manual',false)
        ->whereDate('created_at', Carbon::today())
        ->latest()
        ->get();
        // dd($bos);

        // $keys = ["adxadbdt","adcash","trafficnombdt","exoclick",  'trafnomnpop'];
        $idToUsedKeywords = ['672477','673437','500658','500702','668180','668181','676083','500702', '760898',"500658","760898","382857420","402136020",'22210','852417','868539','1007305','1076509','6072336','6072337','6079867','55347','6394024','6705106','8126375','8391394','2819554','2822036','2582325','2383093','2803097','2803098','2826736','2488219','2383092','303343','3275182','3275412','21993820'];
        foreach ($bos as $bo) {
            // dd($bo->clicks_impression);
            $info = $this->spreedsheetId($bo->affiliate_username);
            // Initialize an array to store processed impression and click data
            $impressions_data = [];
            if (!empty($bo->clicks_impression)) {
                // Process each clicks_impression record and add keys
                foreach ($bo->clicks_impression as $impression) {
                    if(in_array($impression->creative_id, $idToUsedKeywords)){
                        // dd($this->cKeys($impression->creative_id));
                        $impressions_data[] = [
                            'b_o_s_id' => $impression->b_o_s_id,
                            'creative_id' => $this->cKeys($impression->creative_id),
                            'imprs' => $impression->imprs,
                            'clicks' => $impression->clicks,
                            'spending' => $impression->spending,
                            // Add any additional keys you need
                            'nsu' => $this->campaignNsuId($impression->creative_id), // Example of an additional key
                            'ftd' => $this->campaignFtdId($impression->creative_id), // Another additional key
                        ];
                    }else{
                        
                        $impressions_data[] = [
                            'b_o_s_id' => $impression->b_o_s_id,
                            'creative_id' => $impression->creative_id,
                            'imprs' => $impression->imprs,
                            'clicks' => $impression->clicks,
                            'spending' => $impression->spending,
                            // Add any additional keys you need
                            'nsu' => $this->campaignNsuId($impression->creative_id), // Example of an additional key
                            'ftd' => $this->campaignFtdId($impression->creative_id), // Another additional key
                        ];
                    }
                }
            } else {
                // Handle the case where $bo->clicks_impression is empty, if needed
                Log::warning('CLicks and Impression is empty array [].', ['clicks_impression' => $bo->clicks_impression]);
            }
            

            $dataset[] = [
                'spreadsheet' => $info,
                'keyword' => $bo->affiliate_username,
                'bo' => [$bo->nsu, $bo->ftd, $bo->active_player, $bo->total_deposit, $bo->total_withdrawal, $bo->total_turnover, $bo->profit_and_loss, $bo->total_bonus],
                'impression_and_clicks' => $impressions_data,
            ];

            Log::info('Inserting dataset : ', ["dataset" => $dataset]);
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
                    $bo = BO::where('affiliate_username', $fd['keyword'])->where('brand','baji')
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
                'email' => 'abiralmilan1014@gmail.com',
                'password' => 'B@j!qwe@4444',
                'link' => 'https://my.richads.com/login',
                'dashboard' => 'https://my.richads.com/campaigns/create',
                'platform' => 'richads'
            ],
            'trafficnompkr' => [
                'creative_id' => ['20948', '20947', '22698'],
                'email' => 'abiralmilan1014@gmail.com',
                'password' => 'B@j!qwe@4444',
                'link' => 'https://partners.trafficnomads.com/?login=adv',
                'dashboard' => 'https://partners.trafficnomads.com/stats/index',
                'platform' => 'trafficnomads'
            ],
            'daoadpkr' => [
                'creative_id' => ['286293', '286733', '284858','285126','287030','288497','315278'],
                'email' => 'abiralmilan1014@gmail.com',
                'password' => 'B@j!qwe@4444',
                'link' => 'https://dao.ad/login',
                'dashboard' => 'https://dao.ad/manage/dashboard',
                'platform' => 'daoad'
            ],
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
            'richads' => [
                'creative_id' => ['3215718', '2760629','3207015', '3207018', '3220300', '3231815', '3268132', '3318238'],
                'email' => 'bo.cc@chengyi-1.com',
                'password' => 'B@j!09876**1',
                'link' => 'https://my.richads.com/login',
                'dashboard' => 'https://my.richads.com/campaigns/create',
                'platform' => 'richads'
            ],
            'aff009a2' => [
                'creative_id' => ['672477','673437','500658','500702','668180','668181','676083','500702', '760898'],
                'email' => 'bo.cc@chengyi-1.com',
                'password' => 'B@j!09876**1',
                'link' => 'https://id.trafficstars.com/realms/trafficstars/protocol/openid-connect/auth?scope=openid&redirect_uri=http%3A%2F%2Fadmin.trafficstars.com%2Faccounts%2Fauth%2F%3Fnext%3Dhttps%3A%2F%2Fadmin.trafficstars.com%2F&response_type=code&client_id=web-app',
                'dashboard' => 'https://admin.trafficstars.com/advertisers/campaigns/',
                'platform' => 'trafficstars'
            ],
            'adcash' => [
                'creative_id' => ['382857420', '402136020'],
                'email' => 'bo.cc@chengyi-1.com',
                'password' => 'B@j!09876**1',
                'link' => 'https://auth.myadcash.com/',
                'dashboard' => 'https://adcash.myadcash.com/dashboard/main',
                'platform' => 'adcash'
            ],
            'trafficnombdt' => [
                'creative_id' => ['22210'],
                'email' => 'bo.cc@chengyi-1.com',
                'password' => 'B@j!09876**1',
                'link' => 'https://partners.trafficnomads.com/?login=adv',
                'dashboard' => 'https://partners.trafficnomads.com/stats/index',
                'platform' => 'trafficnomads'
            ],
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
            'exoclick' => [
                'creative_id' => ['6394024','6705106'],
                'email' => 'changchee',
                'password' => 'B@j!09876**1',
                'link' => 'https://admin.exoclick.com/login',
                'dashboard' => 'https://admin.exoclick.com/panel/advertiser/dashboard',
                'platform' => 'exoclick'
            ],
            'propadsbdt' => [
                'creative_id' => ['8126375','8391394'],
                'email' => 'babiebaraimagar@gmail.com',
                'password' => 'qwe@6666',
                'link' => 'https://partners.propellerads.com/#/auth',
                'dashboard' => 'https://my.richads.com/campaigns/create',
                'platform' => 'propellerads'
            ],
            'clickadu' => [
                'creative_id' => ['2383092','2488219','2826736','2803098','2803097','2383093','2582325','2822036','2819554'],
                'email' => 'bo.cc@chengyi-1.com',
                'password' => 'B@j!09876**1',
                'link' => 'https://www.clickadu.com/',
                'dashboard' => 'https://adv.clickadu.com/dashboard',
                'platform' => 'clickadu'
            ],
            'hilltopads' => [],
            'trafforcebdt' => [],
            'admavenbdt' => [],
            'richadspush' => [
                'creative_id' => ['3267951','3307619','3335637', '21993820'],
                'email' => 'bo.cc@chengyi-1.com',
                'password' => 'B@j!09876**1',
                'link' => 'https://my.richads.com/login',
                'dashboard' => 'https://my.richads.com/campaigns/create',
                'platform' => 'richads'
            ],
            'onclicbdtpush' => ['58230'],
            'tforcepushbdt' => [],
            'richadspkpush' => [
                'creative_id' => ['3335663', '21993821', '3307620', '3267962'],
                'email' => 'abiralmilan1014@gmail.com',
                'password' => 'B@j!qwe@4444',
                'link' => 'https://my.richads.com/login',
                'dashboard' => 'https://my.richads.com/campaigns/create',
                'platform' => 'richads'
            ],
            'daopkpush' => [
                'creative_id' => ['569978', '590501'],
                'email' => 'abiralmilan1014@gmail.com',
                'password' => 'B@j!qwe@4444',
                'link' => 'https://dao.ad/login',
                'dashboard' => 'https://dao.ad/manage/dashboard',
                'platform' => 'daoad'
            ],
            'daonppop' => [
              'creative_id' => ['319269'],
                'email' => 'aadikamal835@gmail.com',
                'password' => 'B@j!qwe@6666',
                'link' => 'https://dao.ad/login',
                'dashboard' => 'https://dao.ad/login',
                'platform' => 'daoad'  
            ],
            'trafnomnpop' => [
                'creative_id' => ['22510'],
                'email' => 'aadikamal835@gmail.com',
                'password' => 'B@j!qwe@6666',
                'link' => 'https://partners.trafficnomads.com/?login=adv',
                'dashboard' => 'https://partners.trafficnomads.com/stats/index',
                'platform' => 'trafficnomads'
            ],
            // 'trafnomnpop' => [],

        ];

        return $creative_id[$cid];
    }

    private function campaignNsuId($id){
        // dd($id);
        // $countNSU = FE::where()->count();
        // $cid = [
        //     "385568820" => "adchldMApkr",
        //     "21993820" => "riadFan12555wcbd",
        //     "390697020" => "adchpkrMain2_",
        //     "390697620" => "adchpkrAdult2_",
        //     "3268137" => "richpopSign2",
        //     "3352123" => "richlandrLSpk1",
        //     "20948" => "tnompkDirect1_1",
        //     "20947" => "tnomDirect2_2",
        //     "22698" => "tnomlandrpk1",
        //     "315278" => "daoadlandrLSpk1",
        //     "710956" => "trapkAdult2",
        //     "783520" => "trafRONpk",
        //     "3267962" => "riadbjrgfb199",
        //     "3307620" => "riadbjjt",
        //     "3335663" => "richICCT20pk1",
        //     "21993821" => "richCl12555wcbpk",
        //     "569978" => "daopkICCt20",
        //     "590501" => "richCl12555wcbpk",
        //     "6394024" => "exoAdultbdt",
        //     "6705106" => "exoLSBDAdult1",
        //     "3318238" => "riadldbjbd",
        //     "3215718" => "riadbdtMain",
        //     "760898" => "trsronbjbd-direct",
        //     "500658" => "trsronldbjbd",
        //     "402136020" => "adchbjbd_lksp",
        //     "382857420" => "adchbdtMainstr",
        //     "22210" => "tcnmlandLSbd1",
        //     "1076509" => "adstelaLSbd1",
        //     "1007305" => "adstrbdtMain2",
        //     "868539" => "adstrscbar",
        //     "852417" => "adstrbdtMain",
        //     "6079867" => "fltdCashback",
        //     "6072337" => "fltdsportrefund",
        //     "6072336" => "fltdpushAttrctv",
        //     "55347" => "adxldbjbd",
        //     "6705106" => "exoLSBDAdult1",
        //     "6394024" => "exoAdultbdt",
        //     "8391394" => "proplandLSbd1",
        //     "8126375" => "propadMainbdt",
        //     "2383092" => "cadupopAdult",
        //     "2488219" => "caduMainpopbd",
        //     "2826736" => "adupopamybd",
        //     "2803098" => "cadu1st",
        //     "2803097" => "cadu7mil",
        //     "2383093" => "caduwhlist",
        //     "2582325" => "caduRtbMainstr",
        //     "2822036" => "adupopCas",
        //     "2819554" => "adupopSign",
        //     "303343" => "htpdAdultbdt",
        //     "3275412" => "trafforceAdultsbdt",
        //     "3275182" => "trafforceAdultsbdt",
        //     "22510" => "tcnmFstDepoBnp",
        //     "8391394" => "proplandLSbd1",
        //     "8126375" => "propadMainbdt",
        //     '3267951' => "riadbjrgfb100",
        //     '3307619' => "riadbjjt",
        //     '3335637' => "riadICCT20bd",
        //     '3215718' => "riadbdtMain", 
        //     '2760629' => "riadpopcas",
        //     '3207015' => "riadbbl7m", 
        //     '3207018' => "riadbbl1st",
        //     '3220300' => "riadpopamybd",
        //     '3231815' => "riadAdultbdt", 
        //     '3268132' => "riadbdtMain2", 
        //     '3318238' => "riadldbjbd",
        //     '21993820' => "riadFan12555wcbd",//added 03
        //     '672477' => "trsSignup",
        //     '673437' => "trspopAttrv",
        //     '500658' => "trsronldbjbd",
        //     '500702' => "trspop6",
        //     '668180' => "trsbbl7m",
        //     '668181' => "trsbbl1st",
        //     '676083' => "trspopamybd",
        //     '500702' => "trsAdultbdt",
        //     '760898' => "trsronbjbd-direct",
        //     '286293' => "daopkSign",//added 04
        //     '286733' => "daopopAtrctv",
        //     '284858' => "daopkbbl",
        //     '285126' => "daopkbbl1st",
        //     '287030' => "daopkpopamybd",
        //     '288497' => "daopkbplxpsl127",
        //     '315278' => "daoadlandrLSpk1",
        //     '569978' => "daopkICCt20",//added 05
        //     '590501' => "richCl12555wcbpk",
        //     '319269' => "daoFstDptBnp",
        // ];
        $cid = CidCollection::where('cid',$id)->first();
        $countNSU = FE::where('keywords', $cid->keyword ?? '')->count();
        // dd($countNSU);
        return $countNSU;
    }

    private function cKeys($id){
        // dd($id);
        // $cid = [
        //     "385568820" => "adchldMApkr",
        //     "390697020" => "adchpkrMain2_",
        //     "390697620" => "adchpkrAdult2_",
        //     "3268137" => "richpopSign2",
        //     "3352123" => "richlandrLSpk1",
        //     "20948" => "tnompkDirect1_1",
        //     "20947" => "tnomDirect2_2",
        //     "22698" => "tnomlandrpk1",
        //     "315278" => "daoadlandrLSpk1",
        //     "710956" => "trapkAdult2",
        //     "783520" => "trafRONpk",
        //     "3267962" => "riadbjrgfb199",
        //     "3307620" => "riadbjjt",
        //     "3335663" => "richICCT20pk1",
        //     "21993821" => "richCl12555wcbpk",
        //     "569978" => "daopkICCt20",
        //     "590501" => "richCl12555wcbpk",
        //     "6394024" => "exoAdultbdt",
        //     "6705106" => "exoLSBDAdult1",
        //     "3318238" => "riadldbjbd",
        //     "3215718" => "riadbdtMain",
        //     "760898" => "trsronbjbd-direct",
        //     "500658" => "trsronldbjbd",
        //     "402136020" => "adchbjbd_lksp",
        //     "382857420" => "adchbdtMainstr",
        //     "22210" => "tcnmlandLSbd1",
        //     "22510" => "tcnmFstDepoBnp",
        //     "1076509" => "adstelaLSbd1",
        //     "1007305" => "adstrbdtMain2",
        //     "868539" => "adstrscbar",
        //     "852417" => "adstrbdtMain",
        //     "6079867" => "fltdCashback",
        //     "6072337" => "fltdsportrefund",
        //     "6072336" => "fltdpushAttrctv",
        //     "55347" => "adxldbjbd",
        //     "6705106" => "exoLSBDAdult1",
        //     "6394024" => "exoAdultbdt",
        //     "8391394" => "proplandLSbd1",
        //     "8126375" => "propadMainbdt",
        //     "2383092" => "cadupopAdult",
        //     "2488219" => "caduMainpopbd",
        //     "2826736" => "adupopamybd",
        //     "2803098" => "cadu1st",
        //     "2803097" => "cadu7mil",
        //     "2383093" => "caduwhlist",
        //     "2582325" => "caduRtbMainstr",
        //     "2822036" => "adupopCas",
        //     "2819554" => "adupopSign",
        //     "303343" => "htpdAdultbdt",
        //     "3275412" => "trafforceAdultsbdt",
        //     "3275182" => "trafforceAdultsbdt",
        //     "8391394" => "proplandLSbd1",
        //     "8126375" => "propadMainbdt",
        //     '21993820' => "riadFan12555wcbd",//added
        //     '672477' => "trsSignup",
        //     '673437' => "trspopAttrv",
        //     '500658' => "trsronldbjbd",
        //     '500702' => "trspop6",
        //     '668180' => "trsbbl7m",
        //     '668181' => "trsbbl1st",
        //     '676083' => "trspopamybd",
        //     '500702' => "trsAdultbdt",
        //     '760898' => "trsronbjbd-direct",
        //     '286293' => "daopkSign",//added 04
        //     '286733' => "daopopAtrctv",
        //     '284858' => "daopkbbl",
        //     '285126' => "daopkbbl1st",
        //     '287030' => "daopkpopamybd",
        //     '288497' => "daopkbplxpsl127",
        //     '315278' => "daoadlandrLSpk1",
        //     '569978' => "daopkICCt20",//added 05
        //     '590501' => "richCl12555wcbpk",
        // ];
        // dd($cid[$id]);
        $cid = CidCollection::where('cid',$id)->first();
        if($cid){
            return $cid->keyword;
        }else{
            return $id;
        }
        
    }
    private function campaignFtdId($id){
        // $countNSU = FE::where()->count();
        // $cid = [
        //     "385568820" => "adchldMApkr",
        //     "390697020" => "adchpkrMain2_",
        //     "390697620" => "adchpkrAdult2_",
        //     "3268137" => "richpopSign2",
        //     "3352123" => "richlandrLSpk1",
        //     "20948" => "tnompkDirect1_1",
        //     "20947" => "tnomDirect2_2",
        //     "22698" => "tnomlandrpk1",
        //     "315278" => "daoadlandrLSpk1",
        //     "710956" => "trapkAdult2",
        //     "783520" => "trafRONpk",
        //     "3267962" => "riadbjrgfb199",
        //     "3307620" => "riadbjjt",
        //     "3335663" => "richICCT20pk1",
        //     "21993821" => "richCl12555wcbpk",
        //     "569978" => "daopkICCt20",
        //     "590501" => "richCl12555wcbpk",
        //     "6394024" => "exoAdultbdt",
        //     "6705106" => "exoLSBDAdult1",
        //     "3318238" => "riadldbjbd",
        //     "3215718" => "riadbdtMain",
        //     "760898" => "trsronbjbd-direct",
        //     "500658" => "trsronldbjbd",
        //     "402136020" => "adchbjbd_lksp",
        //     "382857420" => "adchbdtMainstr",
        //     "22210" => "tcnmlandLSbd1",
        //     "22510" => "tcnmFstDepoBnp",
        //     "1076509" => "adstelaLSbd1",
        //     "1007305" => "adstrbdtMain2",
        //     "868539" => "adstrscbar",
        //     "852417" => "adstrbdtMain",
        //     "6079867" => "fltdCashback",
        //     "6072337" => "fltdsportrefund",
        //     "6072336" => "fltdpushAttrctv",
        //     "55347" => "adxldbjbd",
        //     "6705106" => "exoLSBDAdult1",
        //     "6394024" => "exoAdultbdt",
        //     "8391394" => "proplandLSbd1",
        //     "8126375" => "propadMainbdt",
        //     "2383092" => "cadupopAdult",
        //     "2488219" => "caduMainpopbd",
        //     "2826736" => "adupopamybd",
        //     "2803098" => "cadu1st",
        //     "2803097" => "cadu7mil",
        //     "2383093" => "caduwhlist",
        //     "2582325" => "caduRtbMainstr",
        //     "2822036" => "adupopCas",
        //     "2819554" => "adupopSign",
        //     "303343" => "htpdAdultbdt",
        //     "3275412" => "trafforceAdultsbdt",
        //     "3275182" => "trafforceAdultsbdt",
        //     "8391394" => "proplandLSbd1",
        //     "8126375" => "propadMainbdt",
        //     '3267951' => "riadbjrgfb100",
        //     '3307619' => "riadbjjt",
        //     '3335637' => "riadICCT20bd",
        //     '3215718' => "riadbdtMain", 
        //     '2760629' => "riadpopcas",
        //     '3207015' => "riadbbl7m", 
        //     '3207018' => "riadbbl1st",
        //     '3220300' => "riadpopamybd",
        //     '3231815' => "riadAdultbdt", 
        //     '3268132' => "riadbdtMain2", 
        //     '3318238' => "riadldbjbd",
        //     '21993820' => "riadFan12555wcbd",//added
        //     '672477' => "trsSignup",
        //     '673437' => "trspopAttrv",
        //     '500658' => "trsronldbjbd",
        //     '500702' => "trspop6",
        //     '668180' => "trsbbl7m",
        //     '668181' => "trsbbl1st",
        //     '676083' => "trspopamybd",
        //     '500702' => "trsAdultbdt",
        //     '760898' => "trsronbjbd-direct",
        //     '286293' => "daopkSign",//added 04
        //     '286733' => "daopopAtrctv",
        //     '284858' => "daopkbbl",
        //     '285126' => "daopkbbl1st",
        //     '287030' => "daopkpopamybd",
        //     '288497' => "daopkbplxpsl127",
        //     '315278' => "daoadlandrLSpk1",
        //     '569978' => "daopkICCt20",//added 05
        //     '590501' => "richCl12555wcbpk",
        //     '319269' => "daoFstDptBnp",
        // ];
        $cid = CidCollection::where('cid',$id)->first();
        $countNSU = FTD::where('keywords', $cid->keyword ?? '')->count();
        return $countNSU;
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
