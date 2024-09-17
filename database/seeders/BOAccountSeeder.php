<?php

namespace Database\Seeders;

use App\Models\BoAccount;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BOAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $accounts = [
            [
                // for baji
                'brand' => 'baji',
                'email' => 'exousianavi',
                'password' => 'DataAnalys2024',  // Fix typo: should be 'password' not 'passaword'
                'link' => 'https://www.1xoffer.com/page/manager/login.jsp',
                'fe_link' => 'https://bajipartners.com/page/affiliate/login.jsp'
            ],
            [
                // for bj88
                'brand' => 'bj88',
                'email' => 'exousianavi',
                'password' => 'DataAnalys2024',  // Fix typo
                'link' => 'https://www.1xoffer.com/page/manager/login.jsp',
                'fe_link' => 'https://bajipartners.com/page/affiliate/login.jsp'
            ],
            [
                // for six6s
                'brand' => 'six6s',
                'email' => 'exousianavi',
                'password' => 'DataAnalyst2024',  // Fix typo
                'link' => 'https://six6scps.com/page/manager/login.jsp',
                'fe_link' => 'https://6saffiliates.com/page/affiliate/login.jsp'
            ],
            [
                // for jeetbuzz
                'brand' => 'jeetbuzz',
                'email' => 'exousianavi',
                'password' => 'DataAnalys2024',  // Fix typo
                'link' => 'https://www.jeet.buzz/page/manager/login.jsp',
                'fe_link' => 'https://jeetbuzzpartners.com/page/affiliate/login.jsp'
            ],
            [
                // for ic88
                'brand' => 'ic88',
                'email' => 'exousianavi',
                'password' => 'DataAnalyst2024',  // Fix typo
                'link' => 'https://interbo88.com/page/manager/login.jsp',
                'fe_link' => ''
            ],
            [
                // for ctn
                'brand' => 'ctn',
                'email' => 'exousianavi',
                'password' => 'DataAnalyst2024',  // Fix typo
                'link' => 'https://citicps.com/page/manager/login.jsp',
                'fe_link' => ''
            ],
        ];
        
        foreach ($accounts as $account) {
            BoAccount::create($account);
        }
        
    }
}
