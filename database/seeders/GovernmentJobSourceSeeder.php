<?php

namespace Database\Seeders;

use App\Models\JobSource;
use Illuminate\Database\Seeder;

class GovernmentJobSourceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sources = [
            ['name' => 'Staff Selection Commission', 'url' => 'https://ssc.gov.in/', 'state' => null, 'category' => 'ssc'],
            ['name' => 'Union Public Service Commission', 'url' => 'https://www.upsc.gov.in/', 'terms_url' => 'https://www.upsc.gov.in/website-policy', 'state' => null, 'category' => 'upsc'],
            ['name' => 'Railway Recruitment Boards', 'url' => 'https://rrb.indianrailways.gov.in/', 'state' => null, 'category' => 'railway'],
            ['name' => 'Institute of Banking Personnel Selection', 'url' => 'https://www.ibps.in/', 'state' => null, 'category' => 'banking'],
            ['name' => 'Department of Posts, India Post', 'url' => 'https://www.indiapost.gov.in/', 'terms_url' => 'https://www.indiapost.gov.in/website-policy?tab=terms', 'state' => null, 'category' => 'postal'],
            ['name' => 'Uttar Pradesh Subordinate Services Selection Commission', 'url' => 'https://upsssc.gov.in/', 'state' => 'Uttar Pradesh', 'category' => 'state-commission'],
            ['name' => 'Uttar Pradesh Public Service Commission', 'url' => 'https://uppsc.up.nic.in/', 'terms_url' => 'https://uppsc.up.nic.in/FooterPages/tnc.aspx', 'state' => 'Uttar Pradesh', 'category' => 'state-commission'],
            ['name' => 'Bihar Public Service Commission', 'url' => 'https://bpsc.bihar.gov.in/', 'state' => 'Bihar', 'category' => 'state-commission'],
            ['name' => 'Rajasthan Public Service Commission', 'url' => 'https://rpsc.rajasthan.gov.in/', 'state' => 'Rajasthan', 'category' => 'state-commission'],
            ['name' => 'Delhi Subordinate Services Selection Board', 'url' => 'https://dsssb.delhi.gov.in/', 'state' => 'Delhi', 'category' => 'state-commission'],
        ];

        foreach ($sources as $source) {
            JobSource::firstOrCreate(['url' => $source['url']], [
                ...$source,
                'type' => 'html',
                'parser' => 'html',
                'active' => true,
                'trusted' => true,
                'terms_allows_automated_access' => false,
            ]);
        }
    }
}
