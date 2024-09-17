<?php

namespace App\Http\Controllers;

use App\Models\BO;
use App\Models\CidCollection;
use App\Models\CLickAndImprs;
use App\Models\Currency;
use App\Models\FE;
use App\Models\FTD;
use App\Models\Platform;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Bj88Controller extends Controller
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

    //index
    public function index(){
        $username = BO::whereDate('created_at', Carbon::today())->pluck('affiliate_username')->toArray();
        // dd($username);
        $currencies = Currency::where('brand_id', 2)->get();
        // $bo = BO::with(['fe','ftds', 'clicks_impression'])->whereDate('created_at', Carbon::today())->latest()->paginate(10);
        $bo = BO::with(['fe','ftds', 'clicks_impression'])->where('brand','bj88')->latest()->paginate(10);
        // dd($bo);
        $completedTask = BO::whereDate('created_at', Carbon::today())->where('brand','bj88')->distinct()->pluck('currency')->toArray();
        // Replace 'USD' with 'KHR'
        $completedTask = array_map(function($currency) {
            return $currency === 'USD' ? 'KHR' : $currency;
        }, $completedTask);

        $platforms = Platform::with('platformKeys')->get()->toArray();
        $collectionKeys = $this->manualKeys();
        $m_count = BO::where('is_manual',true)->where('brand','bj88')->count();
        return view('admin.pages.bj88', compact("m_count","collectionKeys","currencies", 'bo', 'username', 'completedTask', 'platforms'));
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
               'platform' => 'Hilltopads',
               'currency' => 'KRW',
               'aff_username' => '88krhtopads',
               'campaign_id' => ['314347','307282','307283'],
           ],
           [
               'platform' => 'Adsterra',
               'currency' => 'KRW',
               'aff_username' => '88krpadsterra',
               'campaign_id' => ['1110835','1108887','1017102'],
           ],
           [
               'platform' => 'Adsterra',
               'currency' => 'PHP',
               'aff_username' => '88phpadsterra',
               'campaign_id' => ['1085890','1071850','1111631','1008090','994165','994161','976448','964582'],
           ],
           [
               'platform' => 'HilltopAds',
               'currency' => 'VND',
               'aff_username' => '88vnhtopads',
               'campaign_id' => ['317552','307638','303432','305459','301644','301647'],
           ],
       ];

       // Step 4: Filter the keysCollection based on the boUsernames
       $filteredKeysCollection = array_filter($keysCollection, function ($item) use ($boUsernames) {
           return in_array($item['aff_username'], $boUsernames);
       });

       // Step 5: Return the filtered collection
       return array_values($filteredKeysCollection); // Optional: Re-index the array
   }
    
    //fetch the bo for bj88
    public function bj88BO(Request $request){
        ini_set('max_execution_time', 3600); // Increase to 10 minutes

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
                $manual_affiliates = ['88krhtopads','88krpadsterra','88phpadsterra','88vnhtopads'];
                foreach ($data as $item) {
                    if (isset($item['bo']) && is_array($item['bo'])) {
                        foreach ($item['bo'] as $key => $value) {

                            // Step 1: Check if the affiliate_username already exists and was created today
                            $existingRecord = BO::where('affiliate_username', $value['Affiliate Username'])
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
                                'brand' => 'bj88',
                                 // Check if the Affiliate Username is in the list and set is_manual accordingly
                                 'is_manual' => in_array($value['Affiliate Username'] ?? false, $manual_affiliates),
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
                            
                                // with recaptcha: , Dao.ad, ClickAdu, ProfellerAds, skipping
                                // skip adsterra, flatad, 

                                //no active ads:, hilltopads, trafficforce,admaven, Onclicka

                                //completed adcash,trafficstars,adxad, trafficnomads, Exoclick, richads

                                //Format data: [{'creative_id': '385568820', 'Impressions': '0', 'Clicks': '0', 'Spending': '0'}, {'creative_id': '390697020', 'Impressions': '59765', 'Clicks': '0', 'Spending': '29.28'}, {'creative_id': '390697620', 'Impressions': '0', 'Clicks': '0', 'Spending': '0'}]
                                //Request for Imprssions and Clicks
                                //no cost and impression
                                //'richads','richadspush','richadspkr','richadspkpush', 

                                
                                $pendingKeywords = [
                                    'adsterra','flatadbdt','propadsbdt','clickadu','hilltopads',
                                    'trafforcebdt','admavenbdt','onclicbdtpush','tforcepushbdt',
                                    '88idflatad','88phpadsterra','88phflatad','88krhtopads',
                                    '88krpadsterra','88vnhtopads','88vnflatad'
                                ];
                                $allowedUsernames = ['adcashpkr', 'trastarpkr', 'adxadbdt','trafficnompkr', 'exoclick'];
                                if(!in_array($value['Affiliate Username'], $pendingKeywords)){
                                    $clicksAndImpressionData = $this->creativeId($value['Affiliate Username']);
                                    $clicks_response = Http::timeout(3600)->post($this->url_cai, [
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

    // private function for creative_id
    private function creativeId($cid){
        $creative_id = [
            '88idriads' => [
                'creative_id' => ['3323728', '3311676'],
                'email' => 'apakhabar8888@gmail.com',
                'password' => 'Khabarbaik8888.!@#$%^',
                'link' => 'https://my.richads.com/login',
                'dashboard' => 'https://my.richads.com/campaigns/create',
                'platform' => 'richads'
            ],
            '88idflatad' => [],
            '88idcadu' => [
                'creative_id' => ['2976321','2947550'],
                'email' => 'apakhabar8888@gmail.com',
                'password' => 'Khabarbaik8888.!@#$%^',
                'link' => 'https://www.clickadu.com/',
                'dashboard' => 'https://adv.clickadu.com/dashboard',
                'platform' => 'clickadu'
            ],//clickadu
            '88idriadspush' => [
                'creative_id' => ['3316498', '3316496', '3316494','3316493','3316491','3316490','3316483'],
                'email' => 'apakhabar8888@gmail.com',
                'password' => 'Khabarbaik8888.!@#$%^',
                'link' => 'https://my.richads.com/login',
                'dashboard' => 'https://my.richads.com/campaigns/create',
                'platform' => 'richads'
            ],
            '88phpadsterra' => [],//adsterra
            '88phtfnomads' => [
                'creative_id' => ['22407', '20979', '20980','22126','20376','20326','20234'],
                'email' => 'seri1212yoon@gmail.com',
                'password' => 'yoon1212seri!!*!@',
                'link' => 'https://partners.trafficnomads.com/?login=adv',
                'dashboard' => 'https://partners.trafficnomads.com/stats/index',
                'platform' => 'trafficnomads'
            ],
            '88phtfstars' => [
                'creative_id' => ['748046'],
                'email' => 'seri1212yoon@gmail.com',
                'password' => 'yoon1212seri!!*!@',
                'link' => 'https://id.trafficstars.com/realms/trafficstars/protocol/openid-connect/auth?scope=openid&redirect_uri=http%3A%2F%2Fadmin.trafficstars.com%2Faccounts%2Fauth%2F%3Fnext%3Dhttps%3A%2F%2Fadmin.trafficstars.com%2F&response_type=code&client_id=web-app',
                'dashboard' => 'https://admin.trafficstars.com/advertisers/campaigns/',
                'platform' => 'trafficstars'
            ],
            '88phclickadu' => [
                'creative_id' => ['3049539','2885938','2836898','2826858'],
                'email' => 'seri1212yoon@gmail.com',
                'password' => 'yoon1212seri!!*!@',
                'link' => 'https://www.clickadu.com/',
                'dashboard' => 'https://adv.clickadu.com/dashboard',
                'platform' => 'clickadu'
            ],//clickadu
            '88phflatad' => [],
            '88phadxad' => [
                'creative_id' => ['59694','59248','55756'],
                'email' => 'seri1212yoon@gmail.com',
                'password' => 'yoon1212seri!!*!@',
                'link' => 'https://td.adxad.com/auth/login?lang=en',
                'dashboard' => 'https://td.adxad.com/auth/login?lang=en',
                'platform' => 'adxad'
            ],
            '88krhtopads' => [],
            '88krtfnomads' => [
                'creative_id' => ['20987'],
                'email' => 'bj88krw.sm@gmail.com',
                'password' => 'bj88Krw888!!@',
                'link' => 'https://partners.trafficnomads.com/?login=adv',
                'dashboard' => 'https://partners.trafficnomads.com/stats/index',
                'platform' => 'trafficnomads'
            ],
            '88krclickadu' => [
                'creative_id' => ['2978611','2882275'],
                'email' => 'bj88krw.sm@gmail.com',
                'password' => 'bj88Krw888!!@',
                'link' => 'https://www.clickadu.com/',
                'dashboard' => 'https://adv.clickadu.com/dashboard',
                'platform' => 'clickadu'
            ],//clickadu
            '88krpadsterra' => [],//adsterra
            '88vnrichads' => [
                'creative_id' => ['3268845','3233017','3216750','3216781'],
                'email' => 'bj88vnd.sm@gmail.com',
                'password' => 'bj88Vn126!!*1',
                'link' => 'https://my.richads.com/login',
                'dashboard' => 'https://my.richads.com/campaigns/create',
                'platform' => 'richads'
            ],
            '88vnhtopads' => [],
            '88vntfnmads' => [
                'creative_id' => ['15736','20373','22122','15737','20179','20180'],
                'email' => 'bj88vnd.sm@gmail.com',
                'password' => 'bj88Vn126!!*1',
                'link' => 'https://partners.trafficnomads.com/?login=adv',
                'dashboard' => 'https://partners.trafficnomads.com/stats/index',
                'platform' => 'trafficnomads'
            ],
            '88vnflatad' => [],
            '88vnclickadu' => [
                'creative_id' => ['2885928','2555207','2555190','2821990','2821993'],
                'email' => 'bj88vnd.sm@gmail.com',
                'password' => 'bj88Vn126!!*1',
                'link' => 'https://www.clickadu.com/',
                'dashboard' => 'https://adv.clickadu.com/dashboard',
                'platform' => 'clickadu'
            ],
            '88khdaopush' => [
                'creative_id' => ['322372','301903'],
                'email' => 'bj88khr.sm1@gmail.com',
                'password' => 'Khr881314!!*',
                'link' => 'https://dao.ad/login',
                'dashboard' => 'https://dao.ad/manage/dashboard',
                'platform' => 'daoad'
            ],
            '88phadxadpush' => [
                'creative_id' => ['60126','57986','57594','55775'],
                'email' => 'seri1212yoon@gmail.com',
                'password' => 'yoon1212seri!!*!@',
                'link' => 'https://td.adxad.com/auth/login?lang=en',
                'dashboard' => 'https://td.adxad.com/auth/login?lang=en',
                'platform' => 'adxad'
            ],
            '88vnrichadpush' => [
                'creative_id' => ['3400206','3380074','3380073','3380072','3380071','3379987'],
                'email' => 'bj88vnd.sm@gmail.com',
                'password' => 'bj88Vn126!!*1',
                'link' => 'https://my.richads.com/login',
                'dashboard' => 'https://my.richads.com/campaigns/create',
                'platform' => 'richads'
            ],

        ];

        return $creative_id[$cid];
    }

    //automate spreedsheet report
    public function Spreedsheet(){
        ini_set('max_execution_time', 1200); // Increase to 10 minutes
        $dataset = [];
        // dd('recieved..');
        $bos = BO::with(['fe','ftds','clicks_impression:b_o_s_id,creative_id,imprs,clicks,spending'])
        ->select('id','affiliate_username', 'nsu', 'ftd', 'active_player','total_deposit','total_withdrawal','total_turnover','profit_and_loss','total_bonus') // Replace with the columns you want to retrieve
        ->where('brand','bj88')
        ->where('is_merged',false)
        ->where('is_manual',false)
        ->whereDate('created_at', Carbon::today())
        ->latest()
        ->get();
        // dd($bos);

        // $keys = ["adxadbdt","adcash","trafficnombdt","exoclick",  'trafnomnpop'];
        $idToUsedKeywords = [
            '672477','673437','500658','500702','668180','668181','676083',
            '500702', '760898',"500658","760898","382857420","402136020",
            '22210','852417','868539','1007305','1076509','6072336','6072337',
            '6079867','55347','6394024','6705106','8126375','8391394','2819554',
            '2822036','2582325','2383093','2803097','2803098','2826736','2488219',
            '2383092','303343','3275182','3275412','21993820'];
            
        foreach ($bos as $bo) {
            // dd($bo->clicks_impression);
            // dd($bo);
            $info = $this->spreedsheetId($bo->affiliate_username);
            // Initialize an array to store processed impression and click data
            $impressions_data = [];

            if (!empty($bo->clicks_impression)) {
                // dd($bo->clicks_impression);
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
            // dd($sdata);
            $filteredData = array_slice($sdata['data'], 1);
            // dd($filteredData);
            // Filter out null values
            $filteredData = array_filter($filteredData, function ($item) {
                return !is_null($item);
            });


            foreach ($filteredData as $fd) {
                if(isset($fd['status']) && $fd['status'] === 200){
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

    private function cKeys($id){
        $cid = CidCollection::where('cid',$id)->first();
        if($cid){
            return $cid->keyword;
        }else{
            return $id;
        }
        
    }

    private function campaignNsuId($id){
        // dd($id);
        // $countNSU = FE::where()->count();
        $cid = CidCollection::where('cid',$id)->first();
        // if($cid){
        //     dd($cid->keyword);
        // }
        $countNSU = FE::where('keywords', $cid->keyword ?? '')->count();
        // dd($countNSU);
        // Log::warning('keyword.', ['keyword' => $cid->keyword]);
        return $countNSU;
    }
    
    private function campaignFtdId($id){
        $cid = CidCollection::where('cid',$id)->first();
        $countNSU = FTD::where('keywords', $cid->keyword ?? '')->count();
        return $countNSU;
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
                'keywords' => ['88vnrichads', '88vnhtopads','88vntfnmads','88vnflatad','88vnclickadu','88vnrichadpush']
            ],
            'USD' => [
                'index' => '15',
                'keywords' => ['88khdaopush']
            ],
            'KHR' => [
                'index' => '15',
                'keywords' => ['88khdaopush']
            ],
            'INR' => [
                'index' => '7',
                'keywords' => ['keyword5', 'keyword6']
            ],
            'PKR' => [
                'index' => '17',
                'keywords' => ['richadspkr', 'richadspkpush', 'daopkpush', 'trafficnompkr', 'adcashpkr', 'daoadpkr', 'trastarpkr'],
            ],
            'PHP' => [
                'index' => '16',
                'keywords' => ['88phpadsterra', '88phtfnomads','88phtfstars','88phclickadu','88phflatad','88phadxad','88phadxadpush']
            ],
            'KRW' => [
                'index' => '5',
                'keywords' => ['88krhtopads', '88krclickadu','88krtfnomads','88krpadsterra']
            ],
            'IDR' => [
                'index' => '6',
                'keywords' => ['88idriads', '88idflatad','88idcadu','88idriadspush']
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

    // private function for spreedsheet id
    private function spreedsheetId($sid){
        $sheet_id = [
            '88vnrichadpush' => [
                'spreed_id' => '1KGBA7C1m_R3o9csspSrkhUh9xVWAQROIRr5zNClBw64',
                'platform' => 'Richads'
            ],
            '88phadxadpush' => [
                'spreed_id' => '15gz7xwNiFcmvCH_V9vJUVsxocJYe6GarrFtmITfxZE8',
                'platform' => 'ADxAD'
            ],
            '88khdaopush' => [
                'spreed_id' => '1AdAsdOcUW2K6GE35VFrwXgKnht3jkDy4tQj7TAoXSKM',
                'platform' => 'DaoAd'
            ],
            '88vnrichads' => [
                'spreed_id' => '1mbXn37u8-y4GsD6CbKjz-rdJilTgt1cHJ-TGJNSyFBU',
                'platform' => 'Richads'
            ],
            '88vnhtopads' => [
                'spreed_id' => '1mbXn37u8-y4GsD6CbKjz-rdJilTgt1cHJ-TGJNSyFBU',
                'platform' => 'HilltopAds'
            ],
            '88vntfnmads' => [
                'spreed_id' => '1mbXn37u8-y4GsD6CbKjz-rdJilTgt1cHJ-TGJNSyFBU',
                'platform' => 'TrafficNomads'
            ],
            '88vnflatad' => [
                'spreed_id' => '1mbXn37u8-y4GsD6CbKjz-rdJilTgt1cHJ-TGJNSyFBU',
                'platform' => 'Flatad'
            ],
            '88vnclickadu' => [
                'spreed_id' => '1mbXn37u8-y4GsD6CbKjz-rdJilTgt1cHJ-TGJNSyFBU',
                'platform' => 'ClickAdu'
            ],
            '88krhtopads' => [
                'spreed_id' => '1Q54aQxtXQYk8JVmtJH3KE-HW_bIDFjMCgJuT2FI0n5I',
                'platform' => 'Hilltopads'
            ],
            '88krclickadu' => [
                'spreed_id' => '1Q54aQxtXQYk8JVmtJH3KE-HW_bIDFjMCgJuT2FI0n5I',
                'platform' => 'ClickAdu'
            ],
            '88krtfnomads' => [
                'spreed_id' => '1Q54aQxtXQYk8JVmtJH3KE-HW_bIDFjMCgJuT2FI0n5I',
                'platform' => 'Traffic Nomads'
            ],
            '88krpadsterra' => [
                'spreed_id' => '1Q54aQxtXQYk8JVmtJH3KE-HW_bIDFjMCgJuT2FI0n5I',
                'platform' => 'Adsterra'
            ],
            '88idriadspush' => [
                'spreed_id' => '1xOZZVuB9-a6DFsTwIjoW7va7znZX2POvShfNdVt4BmA',
                'platform' => 'Richads'
            ],
            '88idriads' => [
                'spreed_id' => '19NocXpXiiTXvpGtqyQx_yrOX9weC664kLc5OIwyCyvk',
                'platform' => 'Richads'
            ],
            '88idflatad' => [
                'spreed_id' => '19NocXpXiiTXvpGtqyQx_yrOX9weC664kLc5OIwyCyvk',
                'platform' => 'FlatAd'
            ],
            '88idcadu' => [
                'spreed_id' => '19NocXpXiiTXvpGtqyQx_yrOX9weC664kLc5OIwyCyvk',
                'platform' => 'ClickAdu'
            ],
            '88phpadsterra' => [
                'spreed_id' => '1ZyXSc_q0lo8cMS7GxlGfLO4jUeHqWQnZQF0O8vFc09E',
                'platform' => 'Adsterra'
            ],
            '88phtfnomads' => [
                'spreed_id' => '1ZyXSc_q0lo8cMS7GxlGfLO4jUeHqWQnZQF0O8vFc09E',
                'platform' => 'Traffic Nomads'
            ],
            '88phtfstars' => [
                'spreed_id' => '1ZyXSc_q0lo8cMS7GxlGfLO4jUeHqWQnZQF0O8vFc09E',
                'platform' => 'TrafficStars'
            ],
            '88phclickadu' => [
                'spreed_id' => '1ZyXSc_q0lo8cMS7GxlGfLO4jUeHqWQnZQF0O8vFc09E',
                'platform' => 'ClickAdu'
            ],
            '88phflatad' => [
                'spreed_id' => '1ZyXSc_q0lo8cMS7GxlGfLO4jUeHqWQnZQF0O8vFc09E',
                'platform' => 'FlatAd'
            ],
            '88phadxad' => [
                'spreed_id' => '1ZyXSc_q0lo8cMS7GxlGfLO4jUeHqWQnZQF0O8vFc09E',
                'platform' => 'ADxAD'
            ],
        ];

        return $sheet_id[$sid];
    }

    //fe accounts for baji
    private function feAccountBaji($key)
    {
        $accounts = [
            '88idriads' => 'A1b2c3',
            '88idflatad' => 'A1b2c3',
            '88idcadu' => 'A1b2c3',
            '88idriadspush' => 'A1b2c3',
            '88phpadsterra' => 'A1b2c3',
            '88phtfnomads' => 'A1b2c3',
            '88phtfstars' => 'A1b2c3',
            '88phclickadu' => 'A1b2c3',
            '88phflatad' => 'A1b2c3',
            '88phadxad' => 'A1b2c3',
            '88krhtopads' => 'A1b2c3',
            '88krclickadu' => 'A1b2c3',
            '88krtfnomads' => 'A1b2c3',
            '88krpadsterra' => 'A1b2c3',
            '88vnrichads' => 'affSystem0701',
            '88vnhtopads' => 'affSystem0701',
            '88vntfnmads' => 'affSystem0701',
            '88vnflatad' => 'A1b2c3',
            '88vnclickadu' => 'affSystem0701',
            '88khdaopush' => 'A1b2c3',
            '88phadxadpush' => 'A1b2c3',
            '88vnrichadpush' => 'A1b2c3',
            
        ];
        return $accounts[$key];
    }
}
