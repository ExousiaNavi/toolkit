<?php

namespace App\Http\Controllers;

use App\Models\BO;
use App\Models\BoAccount;
use App\Models\CidCollection;
use App\Models\CLickAndImprs;
use App\Models\Currency;
use App\Models\FE;
use App\Models\FTD;
use App\Models\Platform;
use App\Models\SpreadsheetId;
use App\Services\CurrencyService;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Ic88Controller extends Controller
{
    protected $url = 'http://127.0.0.1:8082/api/bo/fetch'; //bo
    protected $url_fe = 'http://127.0.0.1:8082/api/fe/data'; //fe
    protected $url_cai = 'http://127.0.0.1:8082/api/cli/clicks'; //fe
    protected $url_sp = 'http://127.0.0.1:8082/api/cli/automate-spreedsheet'; //fe


    protected $currencyService;

    // Inject the CurrencyService
    public function __construct(CurrencyService $currencyService)
    {
        $this->currencyService = $currencyService;
    }

    //index
    public function index()
    {
        $username = BO::whereDate('created_at', Carbon::today())->pluck('affiliate_username')->toArray();
        // dd($username);
        $currencies = Currency::where('brand_id', 5)->get();
        // $bo = BO::with(['fe','ftds', 'clicks_impression'])->whereDate('created_at', Carbon::today())->latest()->paginate(10);
        $bo = BO::with(['fe', 'ftds', 'clicks_impression'])->where('brand', 'ic88')->latest()->paginate(10);
        // dd($bo);
        $completedTask = BO::whereDate('created_at', Carbon::today())->where('brand', 'ic88')->distinct()->pluck('currency')->toArray();
        $platforms = Platform::with('platformKeys')->get()->toArray();
        $collectionKeys = $this->manualKeys();
        $m_count = BO::where('is_manual', true)->where('brand', 'ic88')->count();
        $presentCurType = SpreadsheetId::where('brand', 'bj88')->distinct()->pluck('currencyType')->toArray();
        return view('admin.pages.ic88', compact("presentCurType","m_count", "collectionKeys", "currencies", 'bo', 'username', 'completedTask', 'platforms'));
    }

    //manual key collections
    private function manualKeys()
    {
        // Step 1: Get BOs where is_manual is true
        $bos = BO::where('is_manual', true)->get();
        // Step 2: Extract affiliate_username values from the BOs
        $boUsernames = $bos->pluck('affiliate_username')->toArray();
        // Step 3: Get matching PlatformKey records based on affiliate_usernames
        // $platformKeys = PlatformKey::with('platform')->whereIn('key', $boUsernames)->get();
        $keysCollection = [
            [
                'platform' => 'PropellerAds',
                'currency' => 'CAD',
                'aff_username' => 'iccapropads',
                'campaign_id' => ['8369634', '8215035'],
            ],



        ];

        // Step 4: Filter the keysCollection based on the boUsernames
        $filteredKeysCollection = array_filter($keysCollection, function ($item) use ($boUsernames) {
            return in_array($item['aff_username'], $boUsernames);
        });

        // Step 5: Return the filtered collection
        return array_values($filteredKeysCollection); // Optional: Re-index the array
    }

    //fetch the bo for ic88
    public function ic88BO(Request $request)
    {
        ini_set('max_execution_time', 3600); // Increase to 10 minutes

        // Call the currencyCollection method to get the array for the requested currency
        $currencyData = $this->currencyService->currencyCollection($request->currency, 'ic88');
        $target_dates = $this->getPreviousDays();
        $allResults = [];
        if (!is_null($currencyData['index']) || !empty($currencyData['keywords'])) {
           
            foreach ($target_dates as $d) {
                try {
                    // Fetch data from the first platform
                    $boaccount = BoAccount::where('brand', 'ic88')->first();
                    // dd($boaccount);
                    $response = Http::timeout(3600)->post($this->url, [
                        'email' => $boaccount->email,
                        'password' => $boaccount->password,
                        'link' => $boaccount->link,
                        'fe_link' => $boaccount->fe_link,
                        'currency' => $currencyData['index'],
                        'keyword' => $currencyData['keywords'],
                        'targetdate' => $d //# "2024/09/05",
                    ]);

                    // Check if the response was successful (status code 200)

                    if ($response->successful()) {
                        $data = $response->json();
                        $manual_affiliates = ['iccapropads'];
                        foreach ($data as $item) {
                            if (isset($item['bo']) && is_array($item['bo'])) {
                                foreach ($item['bo'] as $key => $value) {

                                    // Step 1: Check if the affiliate_username already exists and was created today
                                    $existingRecord = BO::where('affiliate_username', $value['Affiliate Username'])->where('brand', 'ic88')
                                        ->whereDate('target_date', $d)
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
                                        'target_date' => str_replace('/', '-', $d),
                                        // 'target_date' => Carbon::yesterday()->toDateString(),
                                        'brand' => 'ic88',
                                        // Check if the Affiliate Username is in the list and set is_manual accordingly
                                        'is_manual' => in_array($value['Affiliate Username'] ?? false, $manual_affiliates),
                                    ]);



                                    // // Fetch data from the second platform using the affiliate username
                                    // $accountData = $this->feAccountBaji($value['Affiliate Username']);
                                    // $fe_response = Http::timeout(1200)->post($this->url_fe, [
                                    //     'username' => $value['Affiliate Username'],
                                    //     'password' => $accountData,
                                    //     'link' => '',//ic88 no affiliate
                                    //     'currency' => $value['Currency'],
                                    // ]);

                                    // if ($fe_response->successful()) {

                                    //     // Fetch data clicks and impression

                                    //     // with recaptcha: , Dao.ad, ClickAdu, ProfellerAds, skipping
                                    //     // skip adsterra, flatad, 

                                    //     //no active ads:, hilltopads, trafficforce,admaven, Onclicka

                                    //     //completed adcash,trafficstars,adxad, trafficnomads, Exoclick, richads

                                    //     //Format data: [{'creative_id': '385568820', 'Impressions': '0', 'Clicks': '0', 'Spending': '0'}, {'creative_id': '390697020', 'Impressions': '59765', 'Clicks': '0', 'Spending': '29.28'}, {'creative_id': '390697620', 'Impressions': '0', 'Clicks': '0', 'Spending': '0'}]
                                    //     //Request for Imprssions and Clicks
                                    //     //no cost and impression
                                    //     //'richads','richadspush','richadspkr','richadspkpush', 


                                    //     // $pendingKeywords = ['adsterra','flatadbdt','propadsbdt','clickadu','hilltopads','trafforcebdt','admavenbdt','onclicbdtpush','tforcepushbdt'];
                                    //     // $allowedUsernames = ['adcashpkr', 'trastarpkr', 'adxadbdt','trafficnompkr', 'exoclick'];
                                    //     // if(!in_array($value['Affiliate Username'], $pendingKeywords)){
                                    //     //     $clicksAndImpressionData = $this->creativeId($value['Affiliate Username']);
                                    //     //     $clicks_response = Http::timeout(1200)->post($this->url_cai, [
                                    //     //         'keywords' => $value['Affiliate Username'],
                                    //     //         'email' => $clicksAndImpressionData['email'],
                                    //     //         'password' => $clicksAndImpressionData['password'],
                                    //     //         'link' => $clicksAndImpressionData['link'],
                                    //     //         'dashboard' => $clicksAndImpressionData['dashboard'],
                                    //     //         'platform' => $clicksAndImpressionData['platform'],
                                    //     //         'creative_id' => $clicksAndImpressionData['creative_id'],
                                    //     //     ]);

                                    //     //     if($clicks_response->successful()){
                                    //     //         $clck_imprs = $clicks_response->json();

                                    //     //         if(isset($clck_imprs['data']['clicks_and_impr']) && is_array($clck_imprs['data']['clicks_and_impr'])){
                                    //     //             foreach ($clck_imprs['data']['clicks_and_impr'] as $clim) {
                                    //     //                 Log::info('Creative ID:.', ['Clicks And Imprs' => $clck_imprs['data']['clicks_and_impr']]);
                                    //     //                 CLickAndImprs::create([
                                    //     //                     'b_o_s_id' => $bo->id,
                                    //     //                     'creative_id' => $clim['creative_id'],
                                    //     //                     'imprs' => $clim['Impressions'],
                                    //     //                     'clicks' => $clim['Clicks'],
                                    //     //                     'spending' => $clim['Spending'],

                                    //     //                 ]);
                                    //     //             }
                                    //     //         }else{
                                    //     //             Log::warning('clicks_and_impr data is missing or not in expected format.', ['Clicks And Imprs' => $clck_imprs]);
                                    //     //         }
                                    //     //     }else {
                                    //     //         return response()->json(['error' => 'Failed to fetch Clicks and Impression data'], 500);
                                    //     //     }
                                    //     // }




                                    //     $fe_data = $fe_response->json();
                                    //     // dd($fe_data);
                                    //     if (isset($fe_data['data']['fe']) && is_array($fe_data['data']['fe'])) {
                                    //         foreach ($fe_data['data']['fe'] as $fe_value) {
                                    //             FE::create([
                                    //                 'b_o_s_id' => $bo->id,
                                    //                 'keywords' => $fe_value['Keywords'],
                                    //                 'currency' => $fe_value['Currency'],
                                    //                 'registration_time' => $fe_value['Registration Time'],
                                    //                 'first_deposit_time' => $fe_value['First Deposit Time'],
                                    //             ]);
                                    //         }
                                    //     } else {
                                    //         // Log or handle the case where 'fe' data is missing or not an array
                                    //         Log::warning('FE data is missing or not in expected format.', ['fe_data' => $fe_data]);
                                    //     }
                                    //     // ftd
                                    //     if (isset($fe_data['data']['ftd']) && is_array($fe_data['data']['ftd'])) {
                                    //         foreach ($fe_data['data']['ftd'] as $fe_value) {
                                    //             if($fe_value['First Deposit Time'] !== '0' && $fe_value['First Deposit Time'] !== ''){
                                    //                 Log::info('First Deposit Time value.', ['first_deposit_time' => $fe_value['First Deposit Time']]);
                                    //                 // Convert "First Deposit Time" to a Carbon instance
                                    //                 $firstDepositTime = Carbon::createFromFormat('Y/m/d H:i:s', $fe_value['First Deposit Time']);

                                    //                 // Check if the date is yesterday
                                    //                 if ($firstDepositTime->isYesterday()) {
                                    //                     FTD::create([
                                    //                         'b_o_s_id' => $bo->id,
                                    //                         'keywords' => $fe_value['Keyword'],
                                    //                         'currency' => $fe_value['Currency'],
                                    //                         'registration_time' => $fe_value['Registration Time'],
                                    //                         'first_deposit_time' => $fe_value['First Deposit Time'],
                                    //                     ]);
                                    //                 } else {
                                    //                     // Log or handle the case where the date is not yesterday
                                    //                     Log::info('First Deposit Time is not yesterday.', ['first_deposit_time' => $fe_value['First Deposit Time']]);
                                    //                 }
                                    //             }else{
                                    //                 // Handle the case where "First Deposit Time" is 'First Deposit Time' or '0'
                                    //                 Log::info('Invalid First Deposit Time value.', ['first_deposit_time' => $fe_value['First Deposit Time']]);
                                    //             }
                                    //         }
                                    //     } else {
                                    //         // Log or handle the case where 'ftd' data is missing or not an array
                                    //         Log::warning('FTD data is missing or not in expected format.', ['fe_data' => $fe_data]);
                                    //     }
                                    // } else {
                                    //     return response()->json(['error' => 'Failed to fetch FE data'], 500);
                                    // }

                                    //there is no FE
                                    $pendingKeywords = [
                                        'adsterra',
                                        'flatadbdt',
                                        'propadsbdt',
                                        'clickadu',
                                        'hilltopads',
                                        'trafforcebdt',
                                        'admavenbdt',
                                        'onclicbdtpush',
                                        'tforcepushbdt',
                                        's6adsterrabdt',
                                        's6shilltopads',
                                        's6clickadubdt',
                                        's6clickadubdt',
                                        'jbpktfshop',
                                        'jbpkflatad',
                                        'jbtrafficshop',
                                        'jbhilltopads',
                                        'jbclickadubdt',
                                        'jbflatadbdt',
                                        'jbadsterrabdt',
                                        'iccapropads',
                                        'iccadaoad' //skip for now because of 2fa
                                    ];
                                    $allowedUsernames = ['adcashpkr', 'trastarpkr', 'adxadbdt', 'trafficnompkr', 'exoclick'];
                                    if (!in_array($value['Affiliate Username'], $pendingKeywords)) {
                                        $clicksAndImpressionData = $this->creativeId($value['Affiliate Username']);
                                        $clicks_response = Http::timeout(3600)->post($this->url_cai, [
                                            'keywords' => $value['Affiliate Username'],
                                            'email' => $clicksAndImpressionData['email'],
                                            'password' => $clicksAndImpressionData['password'],
                                            'link' => $clicksAndImpressionData['link'],
                                            'dashboard' => $clicksAndImpressionData['dashboard'],
                                            'platform' => $clicksAndImpressionData['platform'],
                                            'creative_id' => $clicksAndImpressionData['creative_id'],
                                            'targetdate' => $d //# "2024/09/05",
                                        ]);

                                        if ($clicks_response->successful()) {
                                            $clck_imprs = $clicks_response->json();

                                            if (isset($clck_imprs['data']['clicks_and_impr']) && is_array($clck_imprs['data']['clicks_and_impr'])) {
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
                                            } else {
                                                Log::warning('clicks_and_impr data is missing or not in expected format.', ['Clicks And Imprs' => $clck_imprs]);
                                            }
                                        } else {
                                            return response()->json(['error' => 'Failed to fetch Clicks and Impression data'], 500);
                                        }
                                    }
                                }
                            } else {
                                // Log or handle the case where 'bo' data is missing or not an array
                                Log::warning('BO data is missing or not in expected format.', ['bo_data' => $item]);
                            }
                        }

                        // return response()->json(['result' => $data]);
                        // After the loop, return all results
                        // return response()->json(['result' => $allResults]);
                        $allResults[] = $data;
                    } else {
                        return response()->json(['error' => 'Failed to fetch BO data'], 500);
                    }

                    // Handle non-200 responses
                    // return response()->json([
                    //     "result" => [
                    //         'success' => false,
                    //         'error' => 'Failed to fetch data from the platform.',
                    //         'status_code' => $response->status()
                    //     ]
                    // ], $response->status());
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

            // After the loop, return all results
            return response()->json(['result' => $allResults]);
        }else{
            $data = [
                "status"=> 200,
                "title"=> "Automation for BO and FE data has been Skipping...",
                "text"=> "Automation Skipped: there is no records to our database!",
                "icon"=> "success",
                "data"=> [],
            ];
            // }
            $allResults[] = $data;
            return response()->json(['result' => $allResults]);
        }
    }

    //automate spreedsheet report
    public function Spreedsheet()
    {
        ini_set('max_execution_time', 1200); // Increase to 10 minutes
        $dataset = [];
        // dd('recieved..');
        $bos = BO::with(['fe', 'ftds', 'clicks_impression:b_o_s_id,creative_id,imprs,clicks,spending'])
            ->select('id', 'affiliate_username', 'nsu', 'ftd', 'active_player', 'total_deposit', 'total_withdrawal', 'total_turnover', 'profit_and_loss', 'total_bonus', 'target_date') // Replace with the columns you want to retrieve
            ->where('brand', 'ic88')
            ->where('is_merged', false)
            ->where('is_manual', false)
            ->whereDate('created_at', Carbon::today())
            ->latest()
            ->get();
        // dd($bos);

        // $keys = ["adxadbdt","adcash","trafficnombdt","exoclick",  'trafnomnpop'];
        $idToUsedKeywords = ['672477', '673437', '500658', '500702', '668180', '668181', '676083', '500702', '760898', "500658", "760898", "382857420", "402136020", '22210', '852417', '868539', '1007305', '1076509', '6072336', '6072337', '6079867', '55347', '6394024', '6705106', '8126375', '8391394', '2819554', '2822036', '2582325', '2383093', '2803097', '2803098', '2826736', '2488219', '2383092', '303343', '3275182', '3275412', '21993820'];
        foreach ($bos as $bo) {
            // dd($bo->clicks_impression);
            // dd($bo);
            $info = $this->currencyService->spreedsheetId($bo->affiliate_username, 'ic88');
            // Initialize an array to store processed impression and click data
            $impressions_data = [];

            // commented just for now to make a BO functional
            if (!empty($bo->clicks_impression)) {
                // Process each clicks_impression record and add keys
                foreach ($bo->clicks_impression as $impression) {
                    if (in_array($impression->creative_id, $idToUsedKeywords)) {
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
                    } else {

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
                'target_date' => $bo->target_date
            ];

            Log::info('Inserting dataset : ', ["dataset" => $dataset]);
        }
        // dd($dataset);
        $sp = Http::withOptions(['timeout' => 1200, 'connect_timeout' => 1200,])->post($this->url_sp, [
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
                if (isset($fd['status']) && $fd['status'] === 200) {
                    $bo = BO::where('affiliate_username', $fd['keyword'])->where('brand', 'ic88')
                        ->whereDate('target_date', $fd['target_date'])  // Use whereDate to match only the date part of created_at
                        ->latest()  // Get the most recent record
                        ->first();  // Fetch the first record

                    if ($bo) {
                        $bo->update(['is_merged' => true]);  // Update the is_merged column
                        Log::info('BO successfully updated the is_merged column.', ['BO' => $bo]);
                    } else {
                        Log::warning('Not found, BO failed to update the is_merged column.', ['keyword' => $fd['keyword']]);
                    }
                }
            }
            return response()->json(['result' => $sdata]);
        } else {
            return response()->json(['error' => 'Failed to fetch FE data'], 500);
        }
    }

    // private function for creative_id
    private function creativeId($cid)
    {
        $creative_id = [
            'iccatfnomads' => [
                'creative_id' => ['22158', '21726', '3268099'],
                'email' => 'aurorajbbd@gmail.com',
                'password' => 'id888!@#%^.',
                'link' => 'https://partners.trafficnomads.com/?login=adv',
                'dashboard' => 'https://partners.trafficnomads.com/stats/index',
                'platform' => 'trafficnomads'
            ],
            'iccapropads' => [],
            'iccadaoad' => [
                'creative_id' => ['314389', '305571'],
                'email' => 'aurorajbbd@gmail.com',
                'password' => 'id888!@#%^.',
                'link' => 'https://dao.ad/login',
                'dashboard' => 'https://dao.ad/manage/dashboard',
                'platform' => 'daoad'
            ],
            'iccaclickadu' => [
                'creative_id' => ['2961975', '2961976'],
                'email' => 'aurorajbbd@gmail.com',
                'password' => 'id888!@#%^.',
                'link' => 'https://www.clickadu.com/',
                'dashboard' => 'https://adv.clickadu.com/dashboard',
                'platform' => 'clickadu'
            ],
        ];

        return $creative_id[$cid];
    }

    private function cKeys($id)
    {
        $cid = CidCollection::where('cid', $id)->first();
        if ($cid) {
            return $cid->keyword;
        } else {
            return $id;
        }
    }

    private function campaignNsuId($id)
    {
        $cid = CidCollection::where('cid', $id)->first();
        $countNSU = FE::where('keywords', $cid->keyword ?? '')->count();

        return $countNSU;
    }

    private function campaignFtdId($id)
    {
        $cid = CidCollection::where('cid', $id)->first();
        $countNSU = FTD::where('keywords', $cid->keyword ?? '')->count();
        return $countNSU;
    }

    // private function for currency and associated keywords
    // private function currencyCollection($curr)
    // {
    //     // Mapping of currency codes to their respective values and keywords
    //     $currencyType = [
    //         'all' => [
    //             'index' => '-1',
    //             'keywords' => []
    //         ],
    //         'BDT' => [
    //             'index' => '8',
    //             'keywords' => ['jbrichads', 'jbadcash', 'jbtrafficnom', 'jbadsterrabdt', 'jbflatadbdt', 'jbclickadubdt', 'jbtrafficstars', 'jbhilltopads', 'jbtrafficshop', 'jbrichadpush']
    //         ],
    //         'VND' => [
    //             'index' => '2',
    //             'keywords' => ['88vnrichads', '88vnhtopads', '88vntfnmads', '88vnflatad', '88vnclickadu', '88vnrichadpush']
    //         ],
    //         'USD' => [
    //             'index' => '15',
    //             'keywords' => ['88khdaopush']
    //         ],
    //         'KHR' => [
    //             'index' => '15',
    //             'keywords' => ['88khdaopush']
    //         ],
    //         'INR' => [
    //             'index' => '7',
    //             'keywords' => ['keyword5', 'keyword6']
    //         ],
    //         'PKR' => [
    //             'index' => '17',
    //             'keywords' => ['jbpkrichadpush'],
    //         ],
    //         'PHP' => [
    //             'index' => '16',
    //             'keywords' => ['88phpadsterra', '88phtfnomads', '88phtfstars', '88phclickadu', '88phflatad', '88phadxad', '88phadxadpush']
    //         ],
    //         'KRW' => [
    //             'index' => '5',
    //             'keywords' => ['88krhtopads', '88krclickadu', '88krtfnomads', '88krpadsterra']
    //         ],
    //         'IDR' => [
    //             'index' => '6',
    //             'keywords' => ['88idriads', '88idflatad', '88idcadu', '88idriadspush']
    //         ],
    //         'NPR' => [
    //             'index' => '24',
    //             'keywords' => ['daonppop', 'trafnomnpop']
    //         ],
    //         'THB' => [
    //             'index' => '9',
    //             'keywords' => ['keyword7', 'keyword8']
    //         ],
    //         'CAD' => [
    //             'index' => '25',
    //             'keywords' => ['iccatfnomads', 'iccapropads', 'iccadaoad', 'iccaclickadu']
    //         ]

    //     ];

    //     return $currencyType[$curr];
    // }

    // private function for spreedsheet id
    // private function spreedsheetId($sid)
    // {
    //     $sheet_id = [
    //         'iccatfnomads' => [
    //             'spreed_id' => '1cSNdseJM6oDC4tAACbS25i3z06A6OsTrbfK2It8zWrw',
    //             'platform' => 'TrafficNomads'
    //         ],
    //         'iccapropads' => [
    //             'spreed_id' => '1cSNdseJM6oDC4tAACbS25i3z06A6OsTrbfK2It8zWrw',
    //             'platform' => 'PropellerAds'
    //         ],
    //         'iccadaoad' => [
    //             'spreed_id' => '1cSNdseJM6oDC4tAACbS25i3z06A6OsTrbfK2It8zWrw',
    //             'platform' => 'DaoAd'
    //         ],
    //         'iccaclickadu' => [
    //             'spreed_id' => '1cSNdseJM6oDC4tAACbS25i3z06A6OsTrbfK2It8zWrw',
    //             'platform' => 'ClickAdu'
    //         ],
    //     ];

    //     return $sheet_id[$sid];
    // }

    //fe accounts for baji
    private function feAccountBaji($key)
    {
        $accounts = [
            'iccatfnomads' => 'qaz123',
            'iccapropads' => 'qaz123',
            'iccadaoad' => 'qaz123',
            'iccaclickadu' => 'qaz123',
        ];
        return $accounts[$key];
    }

    public function getPreviousDays()
    {
        // Get today's date using Carbon
        $today = Carbon::now();

        // Check if today is Monday
        if ($today->isMonday()) {
            // If today is Monday, get the previous 4 days
            $previousDays = [];
            for ($i = 1; $i < 4; $i++) {
                // Format the date as 'YYYY/MM/DD'
                $previousDays[] = $today->copy()->subDays($i)->format('Y/m/d');
            }
            // echo "Today is Monday. Processing the last 4 days: " . implode(', ', $previousDays);
        } else {
            // If today is not Monday, get only yesterday
            $previousDays = [$today->subDay()->format('Y/m/d')];
            // echo "Today is not Monday. Processing only yesterday: " . implode(', ', $previousDays);
        }
        // dd($previousDays);
        return $previousDays;
    }
}
