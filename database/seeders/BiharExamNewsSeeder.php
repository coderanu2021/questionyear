<?php

namespace Database\Seeders;

use App\Models\Post;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BiharExamNewsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            foreach ($this->notices() as $notice) {
                $post = Post::updateOrCreate(['id' => Post::whereIn('slug', [$notice['slug'], $notice['slug'].'-hindi'])->value('id')], [
                    'slug' => $notice['slug'],
                    'type' => 'news',
                    'title' => $notice['title'],
                    'excerpt' => $notice['summary'],
                    'content' => '<p><strong>Checked on 18 October 2026.</strong></p><h2>What is the update?</h2><p>'.$notice['summary'].'</p><h2>What should you do?</h2><p>'.$notice['action'].'</p><p><strong>Official source:</strong> <a href="'.$notice['source'].'">Read the official notice</a>. Check for later changes before applying or downloading.</p>',
                    'status' => 'published',
                    'published_at' => now(),
                ]);

                DB::table('page_seo')->updateOrInsert(['page_key' => 'post:'.$post->id], [
                    'meta_title' => $notice['title'],
                    'meta_description' => $notice['summary'],
                    'meta_keywords' => 'Bihar exam update 2026, Bihar recruitment, Bihar admit card',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }

    /** @return list<array{slug: string, title: string, summary: string, action: string, source: string}> */
    private function notices(): array
    {
        return [
            [
                'slug' => 'btsc-scientific-assistant-30-2026',
                'title' => 'BTSC Scientific Assistant 2026: Apply by 24 October',
                'summary' => 'BTSC has published advertisement 30/2026 for regular Scientific Assistant appointments. The official page lists applications from 25 September to 24 October 2026, 11:55 PM. The fee payment deadline is also 24 October.',
                'action' => 'Read the advertisement PDF on the official page. Check your education, age, category rules and fees. If eligible, use its Apply Online link and keep your receipt. This application page does not confirm an exam date or a new admit card.',
                'source' => 'https://btsc.bihar.gov.in/node/1237',
            ],
            [
                'slug' => 'btsc-food-safety-officer-29-2026',
                'title' => 'BTSC Food Safety Officer 2026: Applications Close on 24 October',
                'summary' => 'BTSC advertisement 29/2026 covers regular Food Safety Officer appointments. The official page lists registration from 25 September to 24 October 2026, 11:55 PM. Applications and fee payment close at the same time.',
                'action' => 'Check the advertisement PDF for the required degree, age and other eligibility rules. Apply through the official page. Look for a separate notice about the examination schedule and admit card.',
                'source' => 'https://btsc.bihar.gov.in/node/1236',
            ],
            [
                'slug' => 'btsc-touring-veterinary-officer-27-2026',
                'title' => 'BTSC Touring Veterinary Officer 2026: Apply by 23 October',
                'summary' => 'BTSC advertisement 27/2026 covers Touring Veterinary Officer and equivalent posts. The official application window runs from 24 September to 23 October 2026, 11:55 PM. Fee payment also closes on 23 October.',
                'action' => 'Read the advertisement for veterinary qualifications and other conditions. Apply through the official page if eligible and keep proof of submission. The application deadline is separate from the examination date.',
                'source' => 'https://btsc.bihar.gov.in/node/1231',
            ],
            [
                'slug' => 'btsc-fishery-extension-officer-28-2026',
                'title' => 'BTSC Fishery Extension Officer 2026: Apply by 23 October',
                'summary' => 'BTSC advertisement 28/2026 covers regular Fishery Extension Officer appointments. The official application window runs from 24 September to 23 October 2026, 11:55 PM. This is also the fee payment deadline.',
                'action' => 'Check the PDF for education, age, fees and selection rules before applying through the official link. This application notice does not establish an exam date or an admit-card release.',
                'source' => 'https://btsc.bihar.gov.in/node/1233',
            ],
            [
                'slug' => 'bssc-field-assistant-mains-notice-16-october-2026',
                'title' => 'BSSC Field Assistant Mains: Notice Listed on 16 October 2026',
                'summary' => 'The BSSC notice board lists a Field Assistant Main Examination notice under advertisement 03/25, dated 16 October 2026.',
                'action' => 'Open the 03/25 entry and its advertisement and syllabus links. Dates in the detailed PDF have not been independently confirmed here. Check that notice for application, examination and admit-card dates.',
                'source' => 'https://bssc.bihar.gov.in/NoticeBoard.htm',
            ],
            [
                'slug' => 'bssc-stenographer-edit-option-16-october-2026',
                'title' => 'BSSC Stenographer 07/25: Application Correction Notice',
                'summary' => 'BSSC lists a notice dated 16 October 2026 about the edit option for Stenographer/Steno Typist Grade III applications under advertisement 07/25.',
                'action' => 'Read the 07/25 notice to check which details can be corrected and the deadline. This listing does not announce a new recruitment or admit-card release.',
                'source' => 'https://bssc.bihar.gov.in/NoticeBoard.htm',
            ],
            [
                'slug' => 'bssc-sports-trainer-discipline-16-october-2026',
                'title' => 'BSSC Sports Trainer 08/25: Sports Discipline Selection Notice',
                'summary' => 'BSSC lists a notice dated 16 October 2026 about selecting a sports discipline for Sports Trainer recruitment under advertisement 08/25.',
                'action' => 'Read the official notice for the selection steps and deadline. The notice-board heading alone does not confirm an examination date or admit-card release.',
                'source' => 'https://bssc.bihar.gov.in/NoticeBoard.htm',
            ],
            [
                'slug' => 'csbc-driver-constable-dv-27-october-2026',
                'title' => 'Bihar Driver Constable 02/2025: Document Check on 27 October',
                'summary' => 'The CSBC notice dated 17 October 2026 applies to 22 listed candidates under advertisement 02/2025. They must attend document verification on 27 October 2026 between 10 AM and 5 PM.',
                'action' => 'Find your roll number in the PDF and check the documents requested against it. Bring the relevant original certificates, DET admit card, identity proof and gate pass to the CSBC office at Back Harding Road, Patna 800001, near Secretariat Halt. This notice applies to the listed candidates; it does not announce a fresh written examination for everyone.',
                'source' => 'https://csbc.bihar.gov.in/Advt/Notice-02-2025-17-10-2026.pdf',
            ],
            [
                'slug' => 'bpssc-asi-technical-07-2026-exam-postponed',
                'title' => 'BPSSC ASI Technical 07/2026: Earlier Exam Postponed',
                'summary' => 'The official BPSSC notice dated 30 July 2026 postponed the ASI Technical preliminary written examination under advertisement 07/2026 that was scheduled for 5 August 2026. It says a new date will be announced later.',
                'action' => 'This is an earlier postponement notice. An old July admit-card link does not establish a new examination date. Check the latest commission notice for the current schedule and admit-card instructions.',
                'source' => 'https://bpssc.bihar.gov.in/Notices/Notice%20for%20postponement%20of%20examination.pdf',
            ],
            [
                'slug' => 'bpsc-72-cce-postponement-official-update-october-2026',
                'title' => 'BPSC 72nd Prelims: Check the Official Postponement Update',
                'summary' => 'The official BPSC website lists a postponement notice for the 72nd CCE Preliminary Examination. A revised examination date and availability of a new admit card were not confirmed in this check.',
                'action' => 'Read the latest 72nd CCE notice in the Notifications section of the official website. Check the revised schedule and admit-card instructions before making travel plans.',
                'source' => 'https://bpsc.bihar.gov.in/',
            ],
        ];
    }
}
