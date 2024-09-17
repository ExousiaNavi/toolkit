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
use Database\Seeders\BOAccountSeeder;
use Database\Seeders\CidCollectionSeeder;
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
            'Richads' => 'http://richads.com/', //1
            'TrafficStars' => 'https://admin.trafficstars.com/', //2
            'Adcash' => 'https://adcash.com/', //3
            'TrafficNomads' => 'https://partners.trafficnomads.com/', //4
            'Adsterra' => 'https://partners.adsterra.com/login/?_ga=2.180619045.340336149.1687748770-40631359.1687748770',// 5
            'FlatAd' => 'https://dsp.mobshark.net/login',  // Replace with actual URL 6
            'ADxAD' => 'https://td.adxad.com/auth/login?lang=en',    // Replace with actual URL 7
            'Exoclick' => 'https://admin.exoclick.com/',// 8
            'PropellerAds' => 'https://partners.propellerads.com/#/auth', //9
            'ClickAdu' => 'https://www.clickadu.com/',  // Replace with actual URL 10
            'HilltopAds' => 'https://hilltopads.com/',  // Replace with actual URL 11
            'Trafficforce' => 'https://dashboard.trafficforce.com/guest/login',//12
            'AdMaven' => 'https://panel.ad-maven.com/advertiser/login?source_id=admaven_site_menu_2',//13
            'DaoAD' => 'https://dao.ad/en#start',    // Replace with actual URL 14
            'Onclicka' => 'https://app.onclicka.com/login/?ref=r2L1cv&_gl=1%2a1s7bhyo%2a_ga%2aMTQ3NTE3MTQzOC4xNzEzMTUwNjgy%2a_ga_Z2FPTLYR0L%2aMTcxMzE1MDY4Mi4xLjAuMTcxMzE1MDY4Mi42MC4wLjA.%2a_gcl_au%2aMTMwNDk1MjYyMi4xNzEzMTUwNjgy&_ga=2.125050242.921523895.1713150685-1475171438.1713150682',    // Replace with actual URL
            'TrafficShop' => 'https://trafficshop.com/',//16
        ];

        $affiliateKeys = [
            'richads' => 1,
            'richadspush'=>1,
            'richadspkr' => 1,//not working on fe
            'richadspkpush' => 1,
            '88vnrichads' => 1,
            '88vnrichadpush' => 1,
            '88idriadspush' => 1,
            '88idriads' => 1,
            's6srichpush' => 1,
            's6srichads' => 1,
            's6srichpkrpush' => 1,
            'jbpkrichadpush' => 1,
            'jbrichadpush' => 1,
            'jbrichads' => 1,
            'cthkrichads' => 1,
            'ctmyrichads' => 1,
            'jbpkrichads' => 1,
            'aff009a2' => 2,
            'trastarpkr' => 2,
            '88phtfstars' => 2,
            's6strafficstars' => 2,
            'jbtrafficstars' => 2,
            'adcash' => 3,
            'adcashpkr' => 3,
            's6sadcash' => 3,
            'jbadcash' => 3,
            'jbpkradcash' => 3,
            'trafficnombdt' => 4,
            'trafficnompkr' => 4,
            'trafnomnpop' => 4,
            '88krtfnomads' => 4,
            '88phtfnomads' => 4,
            '88vntfnmads' => 4,
            's6strafficnomads' => 4,
            'jbtrafficnom' => 4,
            'iccatfnomads' => 4,
            'jbpktrfnmd' => 4,
            'adsterra' => 5,
            '88krpadsterra' => 5,
            '88phpadsterra' => 5,
            's6adsterrabdt' => 5,
            'jbadsterrabdt' => 5,
            'cthkadsterra' => 5,
            'flatadbdt' => 6,
            '88phflatad' => 6,
            '88vnflatad' => 6,
            '88idflatad' => 6,
            'jbflatadbdt' => 6,
            'jbpkflatad' => 6,
            'adxadbdt' => 7,
            '88phadxad' => 7,
            '88phadxadpush' => 7,
            'ctsgadxpop' => 7,
            'exoclick' => 8,
            'ctsgexocpop' => 8,
            'propadsbdt' => 9,
            'iccapropads' => 9,
            'cthkpropadpop' => 9,
            'ctmypropads' => 9,
            'clickadu' => 10,
            '88krclickadu' => 10,
            '88phclickadu' => 10,
            '88vnclickadu' => 10,
            '88idcadu' => 10,
            's6clickadubdt' => 10,
            'jbclickadubdt' => 10,
            'iccaclickadu' => 10,
            'cthkclickadu' => 10,
            'ctsgcadupop' => 10,
            'hilltopads' => 11,
            '88krhtopads' => 11,
            '88vnhtopads' => 11,
            's6shilltopads' => 11,
            'jbhilltopads' => 11,
            'trafforcebdt' => 12,
            'tforcepushbdt' => 12,
            'admavenbdt' => 13,
            'daopkpush' => 14,//password qwert12345 not working
            'daoadpkr' => 14,
            'daonppop' => 14,
            '88khdaopush' => 14,
            's6daoadbdt' => 14,
            'iccadaoad' => 14,
            'ctmydaoad' => 14,
            'ctsgdaopop' => 14,
            'onclicbdtpush' => 15,
            'jbtrafficshop' => 16,
            'jbpktfshop' => 16,
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
            ],
            [
                'brand_id' => 1,
                'currency' => 'PKR',
            ],
            [
                'brand_id' => 1,
                'currency' => 'NPR',
            ],
            //BJ88
            [
                'brand_id' => 2,
                'currency' => 'IDR',
            ],
            [
                'brand_id' => 2,
                'currency' => 'PHP',
            ],
            [
                'brand_id' => 2,
                'currency' => 'KRW',
            ],
            [
                'brand_id' => 2,
                'currency' => 'VND',
            ],
            [
                'brand_id' => 2,
                'currency' => 'KHR',
            ],
            //SIX6s
            [
                'brand_id' => 3,
                'currency' => 'BDT',
            ],
            [
                'brand_id' => 3,
                'currency' => 'PKR',
            ],
            //JETTBUZZ
            [
                'brand_id' => 4,
                'currency' => 'BDT',
            ],
            [
                'brand_id' => 4,
                'currency' => 'PKR',
            ],
            //IC88
            [
                'brand_id' => 5,
                'currency' => 'CAD',
            ],
            //WINRS
            [
                'brand_id' => 6,
                'currency' => 'PKR',
            ],
            [
                'brand_id' => 6,
                'currency' => 'BDT',
            ],
            [
                'brand_id' => 6,
                'currency' => 'NPR',
            ],
            //CTN
            [
                'brand_id' => 7,
                'currency' => 'HKD',
            ],
            [
                'brand_id' => 7,
                'currency' => 'MYR',
            ],
            [
                'brand_id' => 7,
                'currency' => 'SGD',
            ],
        ];
        
        foreach ($currencies as $currency) {
            Currency::factory()->create($currency);
        }
           
        
        //to execute CidCollection seeder
        $this->call(CidCollectionSeeder::class);
        //to execute BO Account seeder
        $this->call(BOAccountSeeder::class);
    }
}
