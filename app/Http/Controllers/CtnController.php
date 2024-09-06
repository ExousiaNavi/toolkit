<?php

namespace App\Http\Controllers;

use App\Models\BO;
use App\Models\FE;
use Carbon\Carbon;
use App\Models\FTD;
use App\Models\Currency;
use App\Models\Platform;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\ConnectionException;

class CtnController extends Controller
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
        $currencies = Currency::where('brand_id', 7)->get();
        // $bo = BO::with(['fe','ftds', 'clicks_impression'])->whereDate('created_at', Carbon::today())->latest()->paginate(10);
        $bo = BO::with(['fe','ftds', 'clicks_impression'])->where('brand','ctn')->latest()->paginate(10);
        // dd($bo);
        $completedTask = BO::whereDate('created_at', Carbon::today())->where('brand','ctn')->distinct()->pluck('currency')->toArray();
        $platforms = Platform::with('platformKeys')->get()->toArray();
        
        return view('admin.pages.ctn', compact("currencies", 'bo', 'username', 'completedTask', 'platforms'));
    }

    //fetch the bo for bj88
    public function ctnBO(Request $request){
        ini_set('max_execution_time', 1200); // Increase to 10 minutes

        // Call the currencyCollection method to get the array for the requested currency
        $currencyData = $this->currencyCollection($request->currency);
        try {
            // Fetch data from the first platform
            $response = Http::timeout(1200)->post($this->url, [
                'email' => 'exousianavi',
                'password' => 'DataAnalyst2024',
                'link' => 'https://citicps.com/page/manager/login.jsp',
                'fe_link' => '',
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
                                'brand' => 'ctn'
                            ]);

                            
                            



                            // Fetch data from the second platform using the affiliate username
                            // $accountData = $this->feAccountBaji($value['Affiliate Username']);
                            // $fe_response = Http::timeout(1200)->post($this->url_fe, [
                            //     'username' => $value['Affiliate Username'],
                            //     'password' => $accountData,
                            //     'link' => 'https://jeetbuzzpartners.com/page/affiliate/login.jsp',
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
                'keywords' => ['jbrichads','jbadcash','jbtrafficnom','jbadsterrabdt','jbflatadbdt','jbclickadubdt','jbtrafficstars','jbhilltopads','jbtrafficshop','jbrichadpush']
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
                'keywords' => ['jbpkrichadpush'],
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
            'HKD' => [
                'index' => '11',
                'keywords' => ['cthkrichads', 'cthkclickadu','cthkadsterra','cthkpropadpop']
            ],
            'MYR' => [
                'index' => '1',
                'keywords' => ['ctmyrichads', 'ctmydaoad','ctmypropads']
            ],
            'SGD' => [
                'index' => '4',
                'keywords' => ['ctsgdaopop', 'ctsgexocpop','ctsgadxpop','ctsgcadupop']
            ],
        ];

        return $currencyType[$curr];
    }

    //fe accounts for baji
    private function feAccountBaji($key)
    {
        $accounts = [
            'jbpkrichads' => 'qaz123',
            'jbpkradcash' => 'qaz123',
            'jbpkflatad' => 'qaz123',
            'jbpktrfnmd' => 'qaz123',
            'jbpktfshop' => 'qaz123',
            'jbrichads' => 'qaz123',
            'jbadcash' => 'qaz123',
            'jbtrafficnom' => 'qaz123',
            'jbadsterrabdt' => 'qaz123',
            'jbflatadbdt' => 'qaz123',
            'jbclickadubdt' => 'qaz123',
            'jbtrafficstars' => 'qaz123',
            'jbhilltopads' => 'qaz123',
            'jbtrafficshop' => 'qaz123',
            'jbrichadpush' => 'qaz123',
            'jbpkrichadpush' => 'qaz123',
        ];
        return $accounts[$key];
    }
}
