<?php

namespace Database\Seeders;

use App\Models\Post;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class IdbiBankNewsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $post = Post::firstOrCreate(['slug' => 'idbi-bank-fairfax-stake-sale-2026-explained'], [
                'type' => 'news',
                'title' => 'IDBI Bank Stake Sale Approved: Fairfax Deal Explained',
                'excerpt' => 'The Cabinet has approved a Fairfax-linked buyer for a 60.72% IDBI Bank stake. Understand the decision, remaining steps and key terms in simple English.',
                'status' => 'published',
                'published_at' => now(),
                'content' => <<<'HTML'
<figure><img src="/idbi-bank-stake-sale-banner.png" alt="IDBI Bank stake sale approval explained with an original bank-themed illustration"><figcaption>Original AI-generated editorial illustration for QuestionYear.in; not a photograph of an IDBI branch.</figcaption></figure>
<p><strong>Verified on 15 October 2026.</strong> This original explainer uses official government sources linked below.</p>
<h2>What is the latest IDBI Bank news?</h2>
<p>On 14 October 2026, the Cabinet Committee on Economic Affairs approved 10955230 Canada Inc., whose parent is Fairfax Financial Holdings, as the buyer for a 60.72% stake in IDBI Bank. The approved price is about ₹52,878.08 crore.</p>
<p>The Ministry of Finance announcement describes an approval. It also sets out further RBI-related requirements for the buyer, so this article does not describe the transaction as already completed.</p>
<h2>Which shares are part of the deal?</h2>
<table><thead><tr><th>Seller</th><th>Stake included in the transaction</th><th>Shareholding left after this transaction</th></tr></thead><tbody><tr><td>Government of India</td><td>30.48%</td><td>15%</td></tr><tr><td>Life Insurance Corporation of India (LIC)</td><td>30.24%</td><td>19%</td></tr><tr><td>Combined stake being acquired</td><td>60.72%</td><td>Government and LIC retain separate holdings</td></tr></tbody></table>
<p><strong>Source:</strong> <a href="https://www.pib.gov.in/PressReleasePage.aspx?PRID=2323318&amp;lang=1&amp;reg=48">Ministry of Finance release published by PIB on 14 October 2026</a>.</p>
<h2>What does a stake sale mean?</h2>
<p>A share is a small unit of ownership in a company. A stake is the portion held by an owner. For example, a 10% stake means ten out of every hundred ownership shares. Selling a stake changes who owns those shares; it is different from announcing new job vacancies.</p>
<p>DIPAM explains strategic disinvestment as the sale of all or a substantial part of government ownership together with management control. A minority share sale can take place without transferring management control. Read <a href="https://www.dipam.gov.in/disinvestment-policy">DIPAM's official disinvestment policy</a> for the distinction.</p>
<h2>Is IDBI Bank already classified as a private sector bank?</h2>
<p>Yes, for regulatory purposes. RBI's release dated 14 March 2019 says IDBI Bank was reclassified as a private sector bank with effect from 21 January 2019 after LIC acquired a 51% stake. That earlier classification and the new stake-sale approval are separate developments.</p>
<p><strong>Source:</strong> <a href="https://www.rbi.org.in/commonman/Upload/English/PressRelease/PDFs/PR2194_14032019.pdf">RBI's IDBI Bank reclassification announcement</a>.</p>
<h2>What happens next?</h2>
<p>The Finance Ministry says the buyer must present its credentials to RBI and provide a roadmap for meeting the prescribed regulatory conditions. The release does not provide a final completion date.</p>
<h2>Why should exam aspirants follow this story?</h2>
<p>Use the news to revise three ideas: ownership, management control and bank regulation. When reading a question, first identify whether it asks about a proposed sale, an approved buyer or a completed transaction. These are different stages.</p>
<p>Create a short revision card with the decision, buyer, stake and source date. Then explain strategic disinvestment in your own words. This helps you understand the topic instead of memorising a headline.</p>
<h2>Frequently asked questions</h2>
<h3>Has the government sold its entire IDBI Bank holding?</h3>
<p>The approved transaction leaves a 15% government holding. The release separately permits the relevant mechanism to undertake later minority sales; it does not say those later sales have already happened.</p>
<h3>Is this a new IDBI recruitment notice?</h3>
<p>No. This article covers the ownership transaction. Check the bank's official careers section for recruitment updates.</p>
<h3>Does a stake-sale headline tell me what will happen to jobs or salaries?</h3>
<p>It does not establish individual employment terms. This explainer makes no claim about new vacancies, job losses, salary changes or future hiring.</p>
<h2>Conclusion</h2>
<p>Read the announcement as a stake-sale approval with further conditions, and use official updates to track the next stage. Keep that distinction clear when preparing current-affairs notes.</p>
HTML,
            ]);

            DB::table('page_seo')->insertOrIgnore([
                'page_key' => 'post:'.$post->id,
                'meta_title' => 'IDBI Bank Stake Sale: Fairfax Deal Explained Simply',
                'meta_description' => 'Read the IDBI Bank stake-sale approval in simple English: the Fairfax-linked buyer, 60.72% stake, deal value, remaining steps and key exam facts.',
                'meta_keywords' => 'IDBI Bank news, IDBI Bank stake sale 2026, Fairfax IDBI deal, strategic disinvestment, banking current affairs',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }
}
