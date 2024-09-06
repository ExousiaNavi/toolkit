<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Currency;
use App\Models\IP;
use App\Models\Platform;
use App\Models\PlatformKey;
use App\Models\User;
use Database\Factories\BrandFactory;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        User::factory()->create([
            'role' => 'admin',
            'name' => 'Administrator',
            'email' => 'administrator@example.com',
            'email_verified_at' => now(),
        ]);

        IP::factory()->create([
            'user_id' => 1,
            'ip_address' => '192.168.1.21',
            'status' => 1,
        ]);

        User::factory(20)->create();
        // Create 20 IP records with random user IDs from the predefined list
        for ($i=2; $i <=21 ; $i++) { 
            Ip::factory()->create([
                'user_id' => $i,
                'ip_address' => '192.168.1.'.rand(20,200),
            ]);
        }

        // $platforms = ['Richads','TrafficStars','Adcash','TrafficNomads','Adsterra','FlatAd','ADxAD','Exoclick','PropellerAds','ClickAdu','HilltopAds','Trafficforce','AdMaven','DaoAD','Onclicka'];
        $platforms = [
            'Richads' => 'http://richads.com/',
            'TrafficStars' => 'https://admin.trafficstars.com/',
            'Adcash' => 'https://adcash.com/',
            'TrafficNomads' => 'https://partners.trafficnomads.com/',
            'Adsterra' => 'https://partners.adsterra.com/login/?_ga=2.180619045.340336149.1687748770-40631359.1687748770',
            'FlatAd' => 'https://dsp.mobshark.net/login',  // Replace with actual URL
            'ADxAD' => 'https://td.adxad.com/auth/login?lang=en',    // Replace with actual URL
            'Exoclick' => 'https://admin.exoclick.com/',
            'PropellerAds' => 'https://partners.propellerads.com/#/auth',
            'ClickAdu' => 'https://www.clickadu.com/',  // Replace with actual URL
            'HilltopAds' => 'https://hilltopads.com/',  // Replace with actual URL
            'Trafficforce' => 'https://dashboard.trafficforce.com/guest/login',
            'AdMaven' => 'https://panel.ad-maven.com/advertiser/login?source_id=admaven_site_menu_2',
            'DaoAD' => 'https://dao.ad/en#start',    // Replace with actual URL
            'Onclicka' => 'https://app.onclicka.com/login/?ref=r2L1cv&_gl=1%2a1s7bhyo%2a_ga%2aMTQ3NTE3MTQzOC4xNzEzMTUwNjgy%2a_ga_Z2FPTLYR0L%2aMTcxMzE1MDY4Mi4xLjAuMTcxMzE1MDY4Mi42MC4wLjA.%2a_gcl_au%2aMTMwNDk1MjYyMi4xNzEzMTUwNjgy&_ga=2.125050242.921523895.1713150685-1475171438.1713150682'    // Replace with actual URL
        ];

        $affiliateKeys = [
            'richads' => 1,
            'richadspush'=>1,
            'richadspkr' => 1,//not working on fe
            'richadspkpush' => 1,
            'aff009a2' => 2,
            'trastarpkr' => 2,
            'adcash' => 3,
            'adcashpkr' => 3,
            'trafficnombdt' => 4,
            'trafficnompkr' => 4,
            'trafnomnpop' => 4,
            'adsterra' => 5,
            'flatadbdt' => 6,
            'adxadbdt' => 7,
            'exoclick' => 8,
            'propadsbdt' => 9,
            'clickadu' => 10,
            'hilltopads' => 11,
            'trafforcebdt' => 12,
            'tforcepushbdt' => 12,
            'admavenbdt' => 13,
            'daopkpush' => 14,//password qwert12345 not working
            'daoadpkr' => 14,
            'daonppop' => 14,
            'onclicbdtpush' => 15,
        ];

        foreach ($platforms as $key => $p) {
            Platform::create(['platform' => $key, 'link' => $p]);
        }

        foreach ($affiliateKeys as $key => $value) {
            PlatformKey::create(['platform_id'=>$value, 'key'=>$key]);
        }


        #creating brand and currency

        $brands= ['baji','bj88','six6s','jeetbuzz', 'ic88', 'winrs', 'ctn'];

        for ($i=0; $i < 7; $i++) { 
            Brand::factory()->create([
                'brand' => $brands[$i],
                'status' => 1,
            ]);
        }

        $currencies = [
            //BAJI
            [
                'brand_id' => 1,
                'currency' => 'BDT',
                'url' => 'https://docs.google.com/spreadsheets/d/1dkVVwuyLmvfpzYmhYVyCBK0XglN7SIc-jRMObmnt9w0/edit?gid=379053111#gid=379053111',
                'email' => 'test@email.com',
                'password' => 'password'
            ],
            [
                'brand_id' => 1,
                'currency' => 'PKR',
                'url' => 'https://docs.google.com/spreadsheets/d/1dkVVwuyLmvfpzYmhYVyCBK0XglN7SIc-jRMObmnt9w0/edit?gid=379053111#gid=379053111',
                'email' => 'test@email.com',
                'password' => 'password'
            ],
            [
                'brand_id' => 1,
                'currency' => 'NPR',
                'url' => 'https://docs.google.com/spreadsheets/d/1dkVVwuyLmvfpzYmhYVyCBK0XglN7SIc-jRMObmnt9w0/edit?gid=379053111#gid=379053111',
                'email' => 'test@email.com',
                'password' => 'password'
            ],
            //BJ88
            [
                'brand_id' => 2,
                'currency' => 'IDR',
                'url' => 'https://docs.google.com/spreadsheets/d/1dkVVwuyLmvfpzYmhYVyCBK0XglN7SIc-jRMObmnt9w0/edit?gid=379053111#gid=379053111',
                'email' => 'test@email.com',
                'password' => 'password'
            ],
            [
                'brand_id' => 2,
                'currency' => 'PHP',
                'url' => 'https://docs.google.com/spreadsheets/d/1dkVVwuyLmvfpzYmhYVyCBK0XglN7SIc-jRMObmnt9w0/edit?gid=379053111#gid=379053111',
                'email' => 'test@email.com',
                'password' => 'password'
            ],
            [
                'brand_id' => 2,
                'currency' => 'KRW',
                'url' => 'https://docs.google.com/spreadsheets/d/1dkVVwuyLmvfpzYmhYVyCBK0XglN7SIc-jRMObmnt9w0/edit?gid=379053111#gid=379053111',
                'email' => 'test@email.com',
                'password' => 'password'
            ],
            [
                'brand_id' => 2,
                'currency' => 'VND',
                'url' => 'https://docs.google.com/spreadsheets/d/1dkVVwuyLmvfpzYmhYVyCBK0XglN7SIc-jRMObmnt9w0/edit?gid=379053111#gid=379053111',
                'email' => 'test@email.com',
                'password' => 'password'
            ],
            [
                'brand_id' => 2,
                'currency' => 'KHR',
                'url' => 'https://docs.google.com/spreadsheets/d/1dkVVwuyLmvfpzYmhYVyCBK0XglN7SIc-jRMObmnt9w0/edit?gid=379053111#gid=379053111',
                'email' => 'test@email.com',
                'password' => 'password'
            ],
            //SIX6s
            [
                'brand_id' => 3,
                'currency' => 'BDT',
                'url' => 'https://docs.google.com/spreadsheets/d/1dkVVwuyLmvfpzYmhYVyCBK0XglN7SIc-jRMObmnt9w0/edit?gid=379053111#gid=379053111',
                'email' => 'test@email.com',
                'password' => 'password'
            ],
            [
                'brand_id' => 3,
                'currency' => 'PKR',
                'url' => 'https://docs.google.com/spreadsheets/d/1dkVVwuyLmvfpzYmhYVyCBK0XglN7SIc-jRMObmnt9w0/edit?gid=379053111#gid=379053111',
                'email' => 'test@email.com',
                'password' => 'password'
            ],
            //JETTBUZZ
            [
                'brand_id' => 4,
                'currency' => 'BDT',
                'url' => 'https://docs.google.com/spreadsheets/d/1dkVVwuyLmvfpzYmhYVyCBK0XglN7SIc-jRMObmnt9w0/edit?gid=379053111#gid=379053111',
                'email' => 'test@email.com',
                'password' => 'password'
            ],
            [
                'brand_id' => 4,
                'currency' => 'PKR',
                'url' => 'https://docs.google.com/spreadsheets/d/1dkVVwuyLmvfpzYmhYVyCBK0XglN7SIc-jRMObmnt9w0/edit?gid=379053111#gid=379053111',
                'email' => 'test@email.com',
                'password' => 'password'
            ],
            //IC88
            [
                'brand_id' => 5,
                'currency' => 'CAD',
                'url' => 'https://docs.google.com/spreadsheets/d/1dkVVwuyLmvfpzYmhYVyCBK0XglN7SIc-jRMObmnt9w0/edit?gid=379053111#gid=379053111',
                'email' => 'test@email.com',
                'password' => 'password'
            ],
            //WINRS
            [
                'brand_id' => 6,
                'currency' => 'PKR',
                'url' => 'https://docs.google.com/spreadsheets/d/1dkVVwuyLmvfpzYmhYVyCBK0XglN7SIc-jRMObmnt9w0/edit?gid=379053111#gid=379053111',
                'email' => 'test@email.com',
                'password' => 'password'
            ],
            [
                'brand_id' => 6,
                'currency' => 'BDT',
                'url' => 'https://docs.google.com/spreadsheets/d/1dkVVwuyLmvfpzYmhYVyCBK0XglN7SIc-jRMObmnt9w0/edit?gid=379053111#gid=379053111',
                'email' => 'test@email.com',
                'password' => 'password'
            ],
            [
                'brand_id' => 6,
                'currency' => 'NPR',
                'url' => 'https://docs.google.com/spreadsheets/d/1dkVVwuyLmvfpzYmhYVyCBK0XglN7SIc-jRMObmnt9w0/edit?gid=379053111#gid=379053111',
                'email' => 'test@email.com',
                'password' => 'password'
            ],
            //CTN
            [
                'brand_id' => 7,
                'currency' => 'HKD',
                'url' => 'https://docs.google.com/spreadsheets/d/1dkVVwuyLmvfpzYmhYVyCBK0XglN7SIc-jRMObmnt9w0/edit?gid=379053111#gid=379053111',
                'email' => 'test@email.com',
                'password' => 'password'
            ],
            [
                'brand_id' => 7,
                'currency' => 'MYR',
                'url' => 'https://docs.google.com/spreadsheets/d/1dkVVwuyLmvfpzYmhYVyCBK0XglN7SIc-jRMObmnt9w0/edit?gid=379053111#gid=379053111',
                'email' => 'test@email.com',
                'password' => 'password'
            ],
            [
                'brand_id' => 7,
                'currency' => 'SGD',
                'url' => 'https://docs.google.com/spreadsheets/d/1dkVVwuyLmvfpzYmhYVyCBK0XglN7SIc-jRMObmnt9w0/edit?gid=379053111#gid=379053111',
                'email' => 'test@email.com',
                'password' => 'password'
            ],
        ];
        
        foreach ($currencies as $currency) {
            Currency::factory()->create($currency);
        }
              
    }
}
