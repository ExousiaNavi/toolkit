<?php

namespace App\Http\Controllers;

use App\Models\BO;
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
        
        return view('admin.pages.bj88', compact("currencies", 'bo', 'username', 'completedTask', 'platforms'));
    }

    
    //fetch the bo for bj88
    public function bj88BO(Request $request){
        ini_set('max_execution_time', 1200); // Increase to 10 minutes

        // Call the currencyCollection method to get the array for the requested currency
        $currencyData = $this->currencyCollection($request->currency);
        try {
            // Fetch data from the first platform
            $response = Http::timeout(1200)->post($this->url, [
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
                                'brand' => 'bj88'
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

                                
                                // $pendingKeywords = ['adsterra','flatadbdt','propadsbdt','clickadu','hilltopads','trafforcebdt','admavenbdt','onclicbdtpush','tforcepushbdt'];
                                // $allowedUsernames = ['adcashpkr', 'trastarpkr', 'adxadbdt','trafficnompkr', 'exoclick'];
                                // if(!in_array($value['Affiliate Username'], $pendingKeywords)){
                                //     $clicksAndImpressionData = $this->creativeId($value['Affiliate Username']);
                                //     $clicks_response = Http::timeout(1200)->post($this->url_cai, [
                                //         'keywords' => $value['Affiliate Username'],
                                //         'email' => $clicksAndImpressionData['email'],
                                //         'password' => $clicksAndImpressionData['password'],
                                //         'link' => $clicksAndImpressionData['link'],
                                //         'dashboard' => $clicksAndImpressionData['dashboard'],
                                //         'platform' => $clicksAndImpressionData['platform'],
                                //         'creative_id' => $clicksAndImpressionData['creative_id'],
                                //     ]);

                                //     if($clicks_response->successful()){
                                //         $clck_imprs = $clicks_response->json();
        
                                //         if(isset($clck_imprs['data']['clicks_and_impr']) && is_array($clck_imprs['data']['clicks_and_impr'])){
                                //             foreach ($clck_imprs['data']['clicks_and_impr'] as $clim) {
                                //                 Log::info('Creative ID:.', ['Clicks And Imprs' => $clck_imprs['data']['clicks_and_impr']]);
                                //                 CLickAndImprs::create([
                                //                     'b_o_s_id' => $bo->id,
                                //                     'creative_id' => $clim['creative_id'],
                                //                     'imprs' => $clim['Impressions'],
                                //                     'clicks' => $clim['Clicks'],
                                //                     'spending' => $clim['Spending'],
                                                    
                                //                 ]);
                                //             }
                                //         }else{
                                //             Log::warning('clicks_and_impr data is missing or not in expected format.', ['Clicks And Imprs' => $clck_imprs]);
                                //         }
                                //     }else {
                                //         return response()->json(['error' => 'Failed to fetch Clicks and Impression data'], 500);
                                //     }
                                // }

                                


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
        ->where('brand','bj88')
        ->where('is_merged',false)
        ->whereDate('created_at', Carbon::today())
        ->latest()
        ->get();
        // dd($bos);

        // $keys = ["adxadbdt","adcash","trafficnombdt","exoclick",  'trafnomnpop'];
        // $idToUsedKeywords = ['672477','673437','500658','500702','668180','668181','676083','500702', '760898',"500658","760898","382857420","402136020",'22210','852417','868539','1007305','1076509','6072336','6072337','6079867','55347','6394024','6705106','8126375','8391394','2819554','2822036','2582325','2383093','2803097','2803098','2826736','2488219','2383092','303343','3275182','3275412','21993820'];
        foreach ($bos as $bo) {
            // dd($bo->clicks_impression);
            // dd($bo);
            $info = $this->spreedsheetId($bo->affiliate_username);
            // Initialize an array to store processed impression and click data
            $impressions_data = [];

            // commented just for now to make a BO functional
            // if (!empty($bo->clicks_impression)) {
            //     // Process each clicks_impression record and add keys
            //     foreach ($bo->clicks_impression as $impression) {
            //         if(in_array($impression->creative_id, $idToUsedKeywords)){
            //             // dd($this->cKeys($impression->creative_id));
            //             $impressions_data[] = [
            //                 'b_o_s_id' => $impression->b_o_s_id,
            //                 'creative_id' => $this->cKeys($impression->creative_id),
            //                 'imprs' => $impression->imprs,
            //                 'clicks' => $impression->clicks,
            //                 'spending' => $impression->spending,
            //                 // Add any additional keys you need
            //                 'nsu' => $this->campaignNsuId($impression->creative_id), // Example of an additional key
            //                 'ftd' => $this->campaignFtdId($impression->creative_id), // Another additional key
            //             ];
            //         }else{
                        
            //             $impressions_data[] = [
            //                 'b_o_s_id' => $impression->b_o_s_id,
            //                 'creative_id' => $impression->creative_id,
            //                 'imprs' => $impression->imprs,
            //                 'clicks' => $impression->clicks,
            //                 'spending' => $impression->spending,
            //                 // Add any additional keys you need
            //                 'nsu' => $this->campaignNsuId($impression->creative_id), // Example of an additional key
            //                 'ftd' => $this->campaignFtdId($impression->creative_id), // Another additional key
            //             ];
            //         }
            //     }
            // } else {
            //     // Handle the case where $bo->clicks_impression is empty, if needed
            //     Log::warning('CLicks and Impression is empty array [].', ['clicks_impression' => $bo->clicks_impression]);
            // }
            

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
                'spreed_id' => '1igx4Lvrz9R4N6APabjUrEwDAZKklbZPPL6p934iyV6s',
                'platform' => 'Richads'
            ],
            '88phadxadpush' => [
                'spreed_id' => '1SnixIG-5L3zGbX3xAJQPtYo6nQk7MxwqtPBkjA2buTg',
                'platform' => 'ADxAD'
            ],
            '88khdaopush' => [
                'spreed_id' => '1WRnWTNUWVBEcufOZ3sL6zUDNrMvainSp5Pc_130pHqo',
                'platform' => 'DaoAd'
            ],
            '88vnrichads' => [
                'spreed_id' => '1CkX3jyN9w9CqXneituUvL2-BvnTgWjJhbE0vuVmBGrA',
                'platform' => 'Richads'
            ],
            '88vnhtopads' => [
                'spreed_id' => '1CkX3jyN9w9CqXneituUvL2-BvnTgWjJhbE0vuVmBGrA',
                'platform' => 'HilltopAds'
            ],
            '88vntfnmads' => [
                'spreed_id' => '1CkX3jyN9w9CqXneituUvL2-BvnTgWjJhbE0vuVmBGrA',
                'platform' => 'TrafficNomads'
            ],
            '88vnflatad' => [
                'spreed_id' => '1CkX3jyN9w9CqXneituUvL2-BvnTgWjJhbE0vuVmBGrA',
                'platform' => 'Flatad'
            ],
            '88vnclickadu' => [
                'spreed_id' => '1CkX3jyN9w9CqXneituUvL2-BvnTgWjJhbE0vuVmBGrA',
                'platform' => 'ClickAdu'
            ],
            '88krhtopads' => [
                'spreed_id' => '1XVhhlbgx6WDZ4zvncIHImdNhG-tH04pRt6MTyJOJwJU',
                'platform' => 'Hilltopads'
            ],
            '88krclickadu' => [
                'spreed_id' => '1XVhhlbgx6WDZ4zvncIHImdNhG-tH04pRt6MTyJOJwJU',
                'platform' => 'ClickAdu'
            ],
            '88krtfnomads' => [
                'spreed_id' => '1XVhhlbgx6WDZ4zvncIHImdNhG-tH04pRt6MTyJOJwJU',
                'platform' => 'Traffic Nomads'
            ],
            '88krpadsterra' => [
                'spreed_id' => '1XVhhlbgx6WDZ4zvncIHImdNhG-tH04pRt6MTyJOJwJU',
                'platform' => 'Adsterra'
            ],
            '88idriadspush' => [
                'spreed_id' => '1Pvf-SCHUfCOAlEQb3PIhzPtSeMKBrPZPAl2zRVZzkYU',
                'platform' => 'Richads'
            ],
            '88idriads' => [
                'spreed_id' => '1OSl4sTDJR-7J7xbuKYiEcH0TFkjhB7k6GRUg0D72rEc',
                'platform' => 'Richads'
            ],
            '88idflatad' => [
                'spreed_id' => '1OSl4sTDJR-7J7xbuKYiEcH0TFkjhB7k6GRUg0D72rEc',
                'platform' => 'FlatAd'
            ],
            '88idcadu' => [
                'spreed_id' => '1OSl4sTDJR-7J7xbuKYiEcH0TFkjhB7k6GRUg0D72rEc',
                'platform' => 'ClickAdu'
            ],
            '88phpadsterra' => [
                'spreed_id' => '1iPctINsxRyWJ040DAfWn0EL-pA8SZDO7keYECQNZIs0',
                'platform' => 'Adsterra'
            ],
            '88phtfnomads' => [
                'spreed_id' => '1iPctINsxRyWJ040DAfWn0EL-pA8SZDO7keYECQNZIs0',
                'platform' => 'Traffic Nomads'
            ],
            '88phtfstars' => [
                'spreed_id' => '1iPctINsxRyWJ040DAfWn0EL-pA8SZDO7keYECQNZIs0',
                'platform' => 'TrafficStars'
            ],
            '88phclickadu' => [
                'spreed_id' => '1iPctINsxRyWJ040DAfWn0EL-pA8SZDO7keYECQNZIs0',
                'platform' => 'ClickAdu'
            ],
            '88phflatad' => [
                'spreed_id' => '1iPctINsxRyWJ040DAfWn0EL-pA8SZDO7keYECQNZIs0',
                'platform' => 'FlatAd'
            ],
            '88phadxad' => [
                'spreed_id' => '1iPctINsxRyWJ040DAfWn0EL-pA8SZDO7keYECQNZIs0',
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
