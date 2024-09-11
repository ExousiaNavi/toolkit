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

class Six6SController extends Controller
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
        $currencies = Currency::where('brand_id', 3)->get();
        // $bo = BO::with(['fe','ftds', 'clicks_impression'])->whereDate('created_at', Carbon::today())->latest()->paginate(10);
        $bo = BO::with(['fe','ftds', 'clicks_impression'])->where('brand','six6s')->latest()->paginate(10);
        // dd($bo);
        $completedTask = BO::whereDate('created_at', Carbon::today())->where('brand','six6s')->distinct()->pluck('currency')->toArray();
        $platforms = Platform::with('platformKeys')->get()->toArray();
        
        return view('admin.pages.six6s', compact("currencies", 'bo', 'username', 'completedTask', 'platforms'));
    }

    //fetch the bo for bj88
    public function six6sBO(Request $request){
        ini_set('max_execution_time', 1200); // Increase to 10 minutes

        // Call the currencyCollection method to get the array for the requested currency
        $currencyData = $this->currencyCollection($request->currency);
        try {
            // Fetch data from the first platform
            $response = Http::timeout(1200)->post($this->url, [
                'email' => 'exousianavi',
                'password' => 'DataAnalyst2024',
                'link' => 'https://six6scps.com/page/manager/login.jsp',
                'fe_link' => 'https://6saffiliates.com/page/affiliate/login.jsp',
                'currency' => $currencyData['index'],
                'keyword' => $currencyData['keywords']
            ]);
    
            // Check if the response was successful (status code 200)

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
                                'brand' => 'six6s'
                            ]);

                            
                            



                            // Fetch data from the second platform using the affiliate username
                            $accountData = $this->feAccountBaji($value['Affiliate Username']);
                            $fe_response = Http::timeout(1200)->post($this->url_fe, [
                                'username' => $value['Affiliate Username'],
                                'password' => $accountData,
                                'link' => 'https://6saffiliates.com/page/affiliate/login.jsp',
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
                                    'adsterra','flatadbdt','propadsbdt','clickadu','hilltopads','trafforcebdt',
                                    'admavenbdt','onclicbdtpush','tforcepushbdt','s6adsterrabdt','s6shilltopads',
                                    's6clickadubdt','s6clickadubdt'
                                ];
                                // $allowedUsernames = ['adcashpkr', 'trastarpkr', 'adxadbdt','trafficnompkr', 'exoclick'];
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
        ->where('brand','six6s')
        ->where('is_merged',false)
        ->whereDate('created_at', Carbon::today())
        ->latest()
        ->get();
        // dd($bos);

        // $keys = ["adxadbdt","adcash","trafficnombdt","exoclick",  'trafnomnpop'];
        $idToUsedKeywords = ['672477','673437','500658','500702','668180','668181','676083','500702', '760898',"500658","760898","382857420","402136020",'22210','852417','868539','1007305','1076509','6072336','6072337','6079867','55347','6394024','6705106','8126375','8391394','2819554','2822036','2582325','2383093','2803097','2803098','2826736','2488219','2383092','303343','3275182','3275412','21993820'];
        foreach ($bos as $bo) {
            // dd($bo->clicks_impression);
            // dd($bo);
            $info = $this->spreedsheetId($bo->affiliate_username);
            // Initialize an array to store processed impression and click data
            $impressions_data = [];

            // commented just for now to make a BO functional
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

    // private function for creative_id
    private function creativeId($cid){
        $creative_id = [
            's6srichpush' => [
                'creative_id' => ['3335556', '3308090','21806290'],
                'email' => 'ameliachoo1214@gmail.com',
                'password' => 'Ameli@1214choo!!',
                'link' => 'https://my.richads.com/login',
                'dashboard' => 'https://my.richads.com/campaigns/create',
                'platform' => 'richads'
            ],
            's6srichads' => [
                'creative_id' => ['3244702', '3244704','3323687','3231925','3209364','2834504','3215766'],
                'email' => 'ameliachoo1214@gmail.com',
                'password' => 'Ameli@1214choo!!',
                'link' => 'https://my.richads.com/login',
                'dashboard' => 'https://my.richads.com/campaigns/create',
                'platform' => 'richads'
            ],
            's6strafficnomads' => [
                'creative_id' => ['22215','20976','20370','20051','14416','14336','20193','20152'],
                'email' => 'ameliachoo1214@gmail.com',
                'password' => 'Ameli@1214choo!!',
                'link' => 'https://partners.trafficnomads.com/?login=adv',
                'dashboard' => 'https://partners.trafficnomads.com/stats/index',
                'platform' => 'trafficnomads'
            ],
            's6sadcash' => [
                'creative_id' => ['385618020','381998820','325091220','383460420','382878420'],
                'email' => 'ameliachoo1214@gmail.com',
                'password' => 'Ameli@1214choo!!',
                'link' => 'https://auth.myadcash.com/',
                'dashboard' => 'https://adcash.myadcash.com/dashboard/main',
                'platform' => 'adcash'
            ],
            's6adsterrabdt' => [], //adsterra,
            's6strafficstars' => [
                'creative_id' => ['783524','766488','669549','518457','674463','672634'],
                'email' => 'ameliachoo1214@gmail.com',
                'password' => 'Ameli@1214choo!!',
                'link' => 'https://id.trafficstars.com/realms/trafficstars/protocol/openid-connect/auth?scope=openid&redirect_uri=http%3A%2F%2Fadmin.trafficstars.com%2Faccounts%2Fauth%2F%3Fnext%3Dhttps%3A%2F%2Fadmin.trafficstars.com%2F&response_type=code&client_id=web-app',
                'dashboard' => 'https://admin.trafficstars.com/advertisers/campaigns/',
                'platform' => 'trafficstars'
            ],
            's6shilltopads' => [],
            's6clickadubdt' => [],
            's6clickadubdt' => [],
            's6daoadbdt' => [
                'creative_id' => ['288535','285382','288362','286303'],
                'email' => 'ameliachoo1214@gmail.com',
                'password' => 'Ameli@1214choo!!',
                'link' => 'https://dao.ad/login',
                'dashboard' => 'https://dao.ad/manage/dashboard',
                'platform' => 'daoad'
            ],
            's6srichpkrpush' => [
                'creative_id' => ['3335557', '3308531','3268064'],
                'email' => 'priya124bi@gmail.com',
                'password' => 'Aeem&H9)@!!!',
                'link' => 'https://my.richads.com/login',
                'dashboard' => 'https://my.richads.com/campaigns/create',
                'platform' => 'richads'
            ],
        ];

        return $creative_id[$cid];
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
        $countNSU = FE::where('keywords', $cid->keyword)->count();
        // dd($countNSU);
        Log::warning('keyword.', ['keyword' => $cid->keyword]);
        return $countNSU;
    }
    
    private function campaignFtdId($id){
        $cid = CidCollection::where('cid',$id)->first();
        $countNSU = FTD::where('keywords', $cid->keyword)->count();
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
                'keywords' => ['s6srichads', 's6strafficnomads','s6sadcash','s6adsterrabdt','s6strafficstars','s6shilltopads','s6clickadubdt','s6daoadbdt','s6srichpush']
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
                'keywords' => ['s6srichpkrpush'],
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
            's6srichpush' => [
                'spreed_id' => '1t1ynyYEgG9wmFyugVYLWjdoVbUg6rVsNYKD9_rg8ZFk',
                'platform' => 'Richads'
            ],
            's6srichads' => [
                'spreed_id' => '12PafRcKWqBSM8-AwOSnkV2mnVmRtb25TGjcPZwfHD2I',
                'platform' => 'Richads'
            ],
            's6strafficnomads' => [
                'spreed_id' => '12PafRcKWqBSM8-AwOSnkV2mnVmRtb25TGjcPZwfHD2I',
                'platform' => 'TrafficNomads'
            ],
            's6sadcash' => [
                'spreed_id' => '12PafRcKWqBSM8-AwOSnkV2mnVmRtb25TGjcPZwfHD2I',
                'platform' => 'Adcash'
            ],
            's6adsterrabdt' => [
                'spreed_id' => '12PafRcKWqBSM8-AwOSnkV2mnVmRtb25TGjcPZwfHD2I',
                'platform' => 'Adsterra'
            ],
            's6strafficstars' => [
                'spreed_id' => '12PafRcKWqBSM8-AwOSnkV2mnVmRtb25TGjcPZwfHD2I',
                'platform' => 'TrafficStars'
            ],
            's6shilltopads' => [
                'spreed_id' => '12PafRcKWqBSM8-AwOSnkV2mnVmRtb25TGjcPZwfHD2I',
                'platform' => 'HilltopAds'
            ],
            's6clickadubdt' => [
                'spreed_id' => '12PafRcKWqBSM8-AwOSnkV2mnVmRtb25TGjcPZwfHD2I',
                'platform' => 'ClickAdu'
            ],
            's6daoadbdt' => [
                'spreed_id' => '12PafRcKWqBSM8-AwOSnkV2mnVmRtb25TGjcPZwfHD2I',
                'platform' => 'DaoAd'
            ],
            's6srichpkrpush' => [
                'spreed_id' => '1k35ikKSvC5hzc9qr5a3xWsG9Uma2K1fnKpTrdHUe-ks',
                'platform' => 'Richads'
            ],
            
        ];

        return $sheet_id[$sid];
    }

    //fe accounts for baji
    private function feAccountBaji($key)
    {
        $accounts = [
            's6srichads' => 'abc45666',
            's6strafficnomads' => 'abc45666',
            's6sadcash' => 'abc45666',
            's6adsterrabdt' => 'abc45666',
            's6strafficstars' => 'abc45666',
            's6shilltopads' => 'abc45666',
            's6clickadubdt' => 'abc45666',
            's6daoadbdt' => 'abc45666',
            's6srichpush' => 'abc45666',
            's6srichpkrpush' => 'qwe45666',
        ];
        return $accounts[$key];
    }

    
}
