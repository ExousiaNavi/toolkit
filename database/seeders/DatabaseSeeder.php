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

        // $affiliates = [
        //     'richads' => 'Richads',
        //     'richadspkr' => 'Richads',
        //     'richadspkpush' => 'Richads',
        //     'aff009a2' => 'TrafficStars',
        //     'trastarpkr' => 'TrafficStars',
        //     'adcash' => 'Adcash',
        //     'adcashpkr' => 'Adcash',
        //     'trafficnombdt' => 'TrafficNomads',
        //     'trafficnompkr' => 'TrafficNomads',
        //     'trafnomnpop' => 'TrafficNomads',
        //     'adsterra' => 'Adsterra',
        //     'flatadbdt' => 'FlatAd',
        //     'adxadbdt' => 'ADxAD',
        //     'exoclick' => 'Exoclick',
        //     'propadsbdt' => 'PropellerAds',
        //     'clickadu' => 'ClickAdu',
        //     'hilltopads' => 'HilltopAds',
        //     'trafforcebdt' => 'Trafficforce',
        //     'admavenbdt' => 'AdMaven',
        //     'daopkpush' => 'DaoAD',
        //     'daoadpkr' => 'DaoAD',
        //     'daonppop' => 'DaoAD',
        // ];
        $platforms = ['Richads','TrafficStars','Adcash','TrafficNomads','Adsterra','FlatAd','ADxAD','Exoclick','PropellerAds','ClickAdu','HilltopAds','Trafficforce','AdMaven','DaoAD','Onclicka',
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

        foreach ($platforms as $p) {
            Platform::create(['platform' => $p]);
        }

        foreach ($affiliateKeys as $key => $value) {
            PlatformKey::create(['platform_id'=>$value, 'key'=>$key]);
        }


        #creating brand and currency

        Brand::factory()->create([
            'brand' => 'baji',
            'status' => 1,
        ]);

        $currencies = [
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
        ];
        
        foreach ($currencies as $currency) {
            Currency::factory()->create($currency);
        }
              
    }
}
