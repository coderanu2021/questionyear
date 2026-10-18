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
                $existingPost = Post::whereIn('slug', [$notice['slug'], $notice['slug'].'-hindi'])->first();
                $post = Post::updateOrCreate(['id' => Post::whereIn('slug', [$notice['slug'], $notice['slug'].'-hindi'])->value('id')], [
                    'slug' => $notice['slug'],
                    'type' => 'news',
                    'title' => $notice['title'],
                    'excerpt' => $notice['summary'],
                    'content' => $this->articleContent($notice),
                    'status' => 'published',
                    'published_at' => $existingPost?->published_at ?? now(),
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

    /** @param array{slug: string, title: string, summary: string, action: string, source: string} $notice */
    private function articleContent(array $notice): string
    {
        $content = '<p><strong>Source check: 18 October 2026.</strong> This guide uses short sentences to explain the official update. Read the linked notice before taking action.</p>';
        $content .= '<h2>What is the latest update?</h2><p>'.e($notice['summary']).'</p>';
        foreach ($this->articleSections($notice['slug']) as $heading => $paragraphs) {
            $content .= '<h2>'.e($heading).'</h2>';
            foreach ($paragraphs as $paragraph) {
                $content .= '<p>'.e($paragraph).'</p>';
            }
        }
        $content .= '<h2>What should you do now?</h2><p>'.e($notice['action']).'</p>';

        if (str_starts_with($notice['slug'], 'btsc-')) {
            $content .= <<<'HTML'
<h2>How to apply: simple steps</h2>
<ol>
<li>Open the official page linked below. Match the post name and advertisement number.</li>
<li>Download the advertisement. A PDF is a file you can read on your phone or computer. Save it for later.</li>
<li>Check the education and age rules. Also check any registration or work experience the post needs.</li>
<li>Read the fee and document rules for your category. Keep the required papers ready before you start.</li>
<li>Use the Apply Online link on the official page. Follow the instructions shown on that portal.</li>
<li>Check each detail before you submit. Look carefully at your name, date of birth and education details.</li>
<li>Complete any required payment. Save the final form and payment receipt. Check that the portal shows a successful submission.</li>
</ol>
<h2>Who can apply?</h2>
<p>You must meet the rules for this exact post. Being interested in the job is not enough. For example, a degree accepted for one post may not be accepted for another. Read the full education rule in the advertisement.</p>
<p>Eligibility means the conditions you must meet. An age cut-off is the date used to calculate your age. Age relaxation means extra age allowed under a rule. Check whether that rule applies to you and what proof it needs.</p>
<h2>Questions about fees, pay and selection</h2>
<p>The number of posts, salary, fee amount, category-wise seats and selection stages are not confirmed in this guide. Check these details in the official advertisement. Do not use the details of an older recruitment form.</p>
<h2>Does the last date mean the exam date?</h2>
<p>No. The last date is the deadline to send your form. The exam date is the day you take the test, if a test is part of the process. An admit card is a document used for entry to an exam. These are different things.</p>
<h2>What should you save after applying?</h2>
<p>Keep your application number, final form and fee receipt together. Use a phone number and email account that you can access. Check the official website for later notices. Sending a form does not by itself mean you have been selected.</p>
HTML;
        } else {
            $content .= <<<'HTML'
<h2>How to read the official notice</h2>
<ol>
<li>Open the official source below. Check the post name and advertisement number first.</li>
<li>Read the date on the notice. Then check the website for any later notice about the same recruitment.</li>
<li>Find the action asked of you. It may be a form correction, a document check or a change in the exam plan.</li>
<li>Check who the notice applies to. A notice for some roll numbers may not apply to every candidate.</li>
<li>Write down any confirmed deadline, place and required papers. Save the notice so you can read it again.</li>
</ol>
<h2>What if two notices show different information?</h2>
<p>Check whether the later notice changes the earlier one. Match the advertisement number and exam stage. If the change is unclear, use the help details on the official website. Do not choose a date only because it appears in a social media post.</p>
<h2>Easy meanings of common words</h2>
<p>An advertisement number is the reference number for a recruitment. Use it to find the correct notice. A roll number identifies a candidate in an exam or list. A deadline is the last time to complete an action.</p>
<p>A notice is an official update. It can change an earlier instruction. An admit card gives exam entry details. A notice about another step does not always mean a new admit card is ready.</p>
HTML;
        }

        return $content.'<h2>Official link and final check</h2><p><a href="'.e($notice['source']).'">Open the official notice or recruitment page</a>.</p><p>Check the website for changes before you submit a form, download an admit card or travel. Keep the correct advertisement number with you. Use the official notice for the final instructions.</p>';
    }

    /** @return array<string, list<string>> */
    private function articleSections(string $slug): array
    {
        return match ($slug) {
            'btsc-scientific-assistant-30-2026' => [
                'Which recruitment is this?' => ['BTSC means Bihar Technical Service Commission. This update is for Scientific Assistant, advertisement 30/2026. Use this number when you look for the form or a later notice. It helps you avoid opening a different recruitment.'],
                'Important application dates' => ['The official page gives 25 September 2026 as the start date. It lists 24 October 2026 at 11:55 PM as the last time for registration, application and payment. These are the dates shown when the source was checked.', '11:55 PM means late at night. It is five minutes before midnight. Try to finish earlier, so a slow website or payment problem does not leave you without time.'],
            ],
            'btsc-food-safety-officer-29-2026' => [
                'Which form should you look for?' => ['Look for Food Safety Officer under BTSC advertisement 29/2026. The title and number should both match. Do not open the Scientific Assistant form by mistake; that is a different advertisement.'],
                'Application and payment dates' => ['The listed start date is 25 September 2026. The application, registration and payment deadline on the official page is 24 October 2026 at 11:55 PM.', 'Plan to complete the form before the last evening. If a payment fails, first check the portal instructions and payment status before trying again. Keep any payment reference that you receive.'],
            ],
            'btsc-touring-veterinary-officer-27-2026' => [
                'Understand the post name' => ['The official title is Touring Veterinary Officer and Equivalent. The reference is BTSC advertisement 27/2026. Equivalent refers to the related posts covered by this advertisement; read the PDF for the exact list.'],
                'When does the form close?' => ['The listed application period starts on 24 September 2026. Registration, application and payment close on 23 October 2026 at 11:55 PM, according to the checked page.', 'This deadline is one day earlier than the dates listed for the Scientific Assistant and Food Safety Officer forms. Always follow the date for your own advertisement.'],
            ],
            'btsc-fishery-extension-officer-28-2026' => [
                'Identify the correct recruitment' => ['Look for Fishery Extension Officer, BTSC advertisement 28/2026. Keep the number 28/2026 in your notes. Match it on the advertisement and the online form before entering your details.'],
                'Dates to remember' => ['The official page lists 24 September 2026 as the opening date. It gives 23 October 2026 at 11:55 PM as the registration, application and payment deadline.', 'The date in this guide is an application deadline. It does not tell you when a written exam will happen. Read any later date-change notice before you apply.'],
            ],
            'bssc-field-assistant-mains-notice-16-october-2026' => [
                'Who should read this update?' => ['Candidates following Field Assistant recruitment under BSSC advertisement 03/25 should read this notice. BSSC means Bihar Staff Selection Commission. Its notice board lists this update under the Main Examination stage.'],
                'What does Main Examination mean?' => ['A main examination is a later exam stage in a selection process. A notice about this stage does not automatically invite everyone to apply. Read the detailed notice to check who can take part and what they must do.'],
                'What is confirmed and what still needs checking?' => ['The official board lists the notice on 16 October 2026 and provides advertisement and syllabus links. A syllabus is the list of topics for an exam.', 'This guide has not confirmed the detailed PDF dates. It therefore gives no new exam day, fee deadline or admit-card release date. Open the linked notice to check those details before taking action.'],
                'How can you prepare your next step?' => ['Keep your earlier application details nearby when reading the notice. Look for any instruction about the main-stage form, required papers and syllabus. Follow only the steps that the notice asks you to complete.'],
            ],
            'bssc-stenographer-edit-option-16-october-2026' => [
                'What does Edit Option mean?' => ['Edit means change or correct something. This update concerns the application edit option for Stenographer/Steno Typist Grade III, advertisement 07/25. It may be relevant if you already submitted a form for that recruitment.'],
                'Can you change every field?' => ['Do not assume that all fields are open for changes. Read the notice to learn which details can be edited, who can use the option and whether there are any conditions. This guide has not confirmed the edit deadline or a correction fee.'],
                'How should you check your form?' => ['Compare your submitted details with your original records. Note any error before opening the edit page. Use the correction link provided by the official website if the notice tells you to do so.', 'Before you save a change, read it again. If the portal gives you an updated form or receipt, save it. Do not assume there will be another chance to edit later.'],
                'Is this a new application or admit card?' => ['The listed heading is about editing an application. It does not by itself confirm fresh applications, an examination date or an admit-card download. Look for a separate notice for those steps.'],
            ],
            'bssc-sports-trainer-discipline-16-october-2026' => [
                'What is a sports discipline?' => ['A sports discipline means a particular sport or sporting event. The BSSC update concerns choosing a sports discipline for Sports Trainer recruitment, advertisement 08/25. Check the notice to see which choices are actually offered.'],
                'Who needs to take action?' => ['Candidates in this recruitment should read the full notice. The heading alone does not show whether every applicant must make a choice or whether only certain candidates are covered. Read that part before using the portal.'],
                'How should you make your choice?' => ['Read the rules for the sports options. Match the requirements with your own certificates and application details. Do not select an option only because its name seems close to the sport on your certificate.', 'Check the option carefully before you save it. Keep the confirmation if the portal provides one. The selection deadline and any chance to change your choice later have not been confirmed in this guide.'],
                'Does this give an exam date?' => ['No exam date or new admit-card release is confirmed by this guide. This update is about the sports discipline choice. Check the official website for a separate examination notice.'],
            ],
            'csbc-driver-constable-dv-27-october-2026' => [
                'Who has been called?' => ['This is for the 22 candidates listed in the CSBC notice for Driver Constable, advertisement 02/2025. First find your roll number in the PDF. Read the papers requested against your own entry.'],
                'Date, time and place' => ['The notice asks the listed candidates to attend on 27 October 2026 between 10 AM and 5 PM. The place is the CSBC office at Back Harding Road, Patna 800001, near Secretariat Halt.'],
                'What does document verification mean?' => ['Document verification, often called DV, means checking your papers. Officers compare the required original records with the details in your application. The notice relates to missing or required records found during checks after the Driving Efficiency Test, or DET.'],
                'What should you carry?' => ['Bring the original papers requested against your roll number, along with the DET admit card, identity proof and gate pass named in the notice. The required certificates differ between candidates. Use your own row in the PDF as the checklist.', 'The notice also says the candidates were informed through their registered email. Check that email account as well.'],
                'What if your roll number is not listed?' => ['This particular notice calls the listed candidates. Do not treat it as a call for all applicants. A document check is also different from a fresh written exam or a new application window.'],
            ],
            'bpssc-asi-technical-07-2026-exam-postponed' => [
                'Which examination does the notice cover?' => ['The notice covers Assistant Sub-Inspector, Technical, in Bihar Police Radio under BPSSC advertisement 07/2026. ASI is the short name for Assistant Sub-Inspector. Match the post and number before using this update.'],
                'What does postponed mean?' => ['Postponed means the exam will not take place on its earlier planned date. This notice moved the exam away from 5 August 2026. It says the new date will be shared later. It does not give a replacement day.'],
                'Is this a new October announcement?' => ['No. The source is an earlier notice dated 30 July 2026. It is included to explain why an old exam date or admit-card link should not be treated as a fresh exam announcement. A newer notice may provide later instructions.'],
                'Can you use an old admit card?' => ['Check the next official instructions. This guide cannot confirm whether an old card will be accepted or whether a fresh card will be required. An old download link alone does not answer that question.'],
                'What can you do while checking for the date?' => ['Keep your application and login details ready. Continue with your study plan using the official syllabus. Confirm the current date and exam-centre instructions before booking travel.'],
            ],
            'bpsc-72-cce-postponement-official-update-october-2026' => [
                'Understand the exam name' => ['BPSC means Bihar Public Service Commission. CCE means Combined Competitive Examination. Prelims is a short name for the preliminary examination, an early exam stage. This guide concerns the 72nd CCE Prelims.'],
                'What does the website tell us?' => ['The official BPSC website lists a postponement notice for this examination. Postponement means a planned exam has been moved from its earlier date. The listing used for this guide does not establish a confirmed replacement date.'],
                'Has a new admit card been confirmed?' => ['A new admit-card release was not confirmed in this check. Do not treat a predicted release date as an official announcement. Check the latest notice and use only the download link provided by the commission.'],
                'How do you find the correct update?' => ['Open the official website and look in Notifications for 72nd CCE. Check the date and the examination stage. A notice about another CCE cycle or a different stage may not apply to your exam.', 'Save the latest relevant notice. Look for the revised examination day, admit-card instructions and any action you need to take. If the notice does not state a date, do not fill the gap with a guess.'],
                'Should you stop preparing?' => ['You can keep studying while you check for the official schedule. Use your syllabus to plan revision. Keep your application details ready, and wait for confirmed instructions before making travel plans.'],
            ],
            default => [],
        };
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
