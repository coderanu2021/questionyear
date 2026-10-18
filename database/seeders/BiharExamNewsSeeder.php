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
                $post = Post::updateOrCreate(['id' => $existingPost?->id], [
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
        $content = '<figure><img src="/bihar-exam-updates-cover.png" alt="Bihar exam updates illustration with exam papers, a calendar and an admit card"><figcaption>AI-generated illustration for QuestionYear. This is not an official notice or an actual admit card.</figcaption></figure>';
        $content .= '<p><strong>Source check: 18 October 2026.</strong> This guide uses short sentences to explain the official update. Read the linked notice before taking action.</p>';
        $content .= '<h2>What is the latest update?</h2><p>'.e($notice['summary']).'</p>';
        foreach ($this->articleSections($notice['slug']) as $heading => $paragraphs) {
            $content .= '<h2>'.e($heading).'</h2>';
            foreach ($paragraphs as $paragraph) {
                $content .= '<p>'.e($paragraph).'</p>';
            }
        }
        $content .= '<h2>What should you do now?</h2><p>'.e($notice['action']).'</p>';

        if (str_starts_with($notice['slug'], 'btsc-')) {
            $advertisementFile = match ($notice['slug']) {
                'btsc-scientific-assistant-30-2026' => '30_2026.pdf',
                'btsc-food-safety-officer-29-2026' => '29_2026.pdf',
                'btsc-touring-veterinary-officer-27-2026' => '27_2026_0.pdf',
                'btsc-fishery-extension-officer-28-2026' => '28_2026.pdf',
            };
            $content .= '<p><a href="https://btsc.bihar.gov.in/sites/default/files/Advertisement/'.$advertisementFile.'">Read the full official advertisement PDF</a>.</p>';
            foreach ($this->recruitmentDetails($notice['slug']) as $heading => $paragraphs) {
                $content .= '<h2>'.e($heading).'</h2>';
                foreach ($paragraphs as $paragraph) {
                    $content .= '<p>'.e($paragraph).'</p>';
                }
            }
            $content .= '<h2>Application process</h2><ol><li>Open the official page below and its advertisement PDF.</li><li>Check the qualification, age and reservation rules for your post.</li><li>Use Apply Online, enter your details and upload the required records.</li><li>Pay online, complete submission and save the final form and receipt.</li></ol>';
        }

        return $content.'<h2>Official link and final check</h2><p><a href="'.e($notice['source']).'">Open the official notice or recruitment page</a>.</p><p>Check the website for changes before you submit a form, download an admit card or travel. Keep the correct advertisement number with you. Use the official notice for the final instructions.</p>';
    }

    /** @return array<string, list<string>> */
    private function recruitmentDetails(string $slug): array
    {
        $details = match ($slug) {
            'btsc-scientific-assistant-30-2026' => [
                'Department and vacancies' => ['This recruitment is for the Bihar Drug Control Laboratory under the Health Department. The advertisement lists 6 posts: UR 3, SC 1, EBC 1 and BC 1. Pay is Level 5.'],
                'Education and required experience' => ['You need B.Sc. in Chemistry or B.Pharm from a recognised university, plus one year of drug testing and analysis experience. The experience condition applies to both degree routes. A degree alone does not meet this requirement.'],
                'Selection and exam pattern' => ['Merit gives 75 marks to the computer-based exam and up to 25 to eligible experience. The test has 100 questions, 400 marks and two hours. Each correct answer gives 4 marks; each wrong answer loses 1 mark. Questions are in Hindi and English.'],
                'Experience marks' => ['Eligible contract service earns 5 marks per year, up to 25. Experience is counted up to 25 September 2026. Use the prescribed certificate format.'],
                'Official advertisement PDF' => ['The facts above were checked on pages 1, 4 and 5 of advertisement 30/2026. The separate syllabus will provide the subject topics.'],
            ],
            'btsc-food-safety-officer-29-2026' => [
                'Department, vacancies and pay' => ['The Health Department recruitment lists 105 posts: UR 41, EWS 11, SC 17, ST 2, EBC 18, BC 12 and BC women 4. Pay is Level 6.'],
                'Accepted education routes' => ['The notice accepts specified qualifications in food technology, dairy technology, biotechnology, oil technology, agriculture, veterinary science, biochemistry, microbiology, chemistry and medicine. It also lists specific food, agriculture and medical degree routes. Match your exact degree with pages 1 and 2; a general science degree is not automatically accepted.'],
                'Selection and exam pattern' => ['Selection uses a computer-based written examination. There are 100 multiple-choice questions for 100 marks, with two hours to answer. A correct answer gives 1 mark; a wrong answer loses 0.25 mark. Questions are in Hindi and English.'],
                'Training after selection' => ['The advertisement provides for the required food-safety training before appointment, with arrangements involving FSSAI. Read its training and qualification conditions together.'],
                'Official advertisement PDF' => ['Pages 1, 2 and 5 of advertisement 29/2026 contain the qualification, vacancy and examination details used here.'],
            ],
            'btsc-touring-veterinary-officer-27-2026' => [
                'Department, vacancies and salary scale' => ['The Dairy, Fisheries and Animal Resources Department recruitment lists 787 posts. Pay Level 9 is Rs 53,100 to Rs 1,67,800. This is a pay scale, not a confirmed take-home salary.'],
                'Veterinary degree and registration' => ['You need B.V.Sc. or B.V.Sc. and A.H. from a university recognised by the Veterinary Council of India. Permanent registration with the Bihar Veterinary Council is also required.'],
                'Selection and experience weight' => ['Final merit uses 75 marks for the written test and up to 25 for eligible contract experience, at 5 marks per year. Experience is counted up to 24 September 2026.'],
                'Exam pattern and subject level' => ['The computer-based test has 100 questions, 100 marks and two hours. It follows the B.V.Sc. and A.H. level syllabus. A correct answer gives 1 mark; a wrong answer loses 0.25 mark. Questions are in Hindi and English.'],
                'Official advertisement PDF' => ['Read advertisement 27/2026, especially pages 1, 2, 4, 5 and 6, for category seats, special age provisions and experience proof.'],
            ],
            'btsc-fishery-extension-officer-28-2026' => [
                'Department, vacancies and pay' => ['This recruitment is for the Fisheries Directorate and its offices. It lists 231 posts: UR 96, EWS 23, SC 35, ST 2, EBC 41, BC 27 and BC women 7. Pay is Level 7.'],
                'Required fisheries degree' => ['You need a four-year degree in Fisheries Science from a fisheries college under an agricultural university recognised by ICAR, New Delhi. A general graduation degree is not enough.'],
                'Selection and experience marks' => ['Merit uses 75 marks for the written test and up to 25 for eligible contract experience. Experience is counted up to 24 September 2026.'],
                'Exam pattern and qualifying marks' => ['The computer-based test has 100 questions for 100 marks and lasts two hours. Each correct answer gives 1 mark; each wrong answer loses 0.25 mark. Questions are in Hindi and English. The stated minimum qualifying score is 30%.'],
                'Official advertisement PDF' => ['Pages 1, 4 and 5 of advertisement 28/2026 give the vacancy, degree, fee and examination details summarised here.'],
            ],
            default => [],
        };

        $details['Age limit and category rules'] = ['Age is calculated on 1 August 2026. Minimum: 21. Standard maximum: UR men 37; UR women and BC/EBC candidates 40; SC/ST candidates 42. Special relaxation rules also exist; check the age section before claiming one.'];
        $details['Application fee'] = ['The advertisement sets the fee at Rs 100, payable online. Bank charges may be extra.'];
        $details['Understanding the selection marks'] = ['A qualifying score is the minimum needed to stay in the process. It does not promise selection. The final cut-off can depend on marks, seats and the applicable category.', 'Where experience marks are allowed, only the experience covered by that advertisement counts. Do not treat all private work as eligible contract service. Read the employer and certificate conditions.'];
        $details['Documents and reservation claims'] = ['Match the degree details on your form with the original marksheets and certificate. If you claim a category or experience benefit, keep the proof in the format asked for in the advertisement.', 'Reservation seats form part of the total vacancy count. A separate women or disability row should not automatically be added to the total as extra posts. Check the full roster and any later changes.'];
        $details['Exam preparation and marking'] = ['Use the syllabus for this exact post when planning your study. A computer-based test means you answer on a computer. Multiple-choice questions give you answer options to choose from.', 'Negative marking means a wrong answer reduces your score. A minimum pass mark and the score needed for final selection are different. Read the latest exam notice for the date and admit-card instructions.'];

        return $details;
    }

    /** @return array<string, list<string>> */
    private function articleSections(string $slug): array
    {
        return match ($slug) {
            'btsc-scientific-assistant-30-2026' => [
                'Recruitment overview' => ['BTSC means Bihar Technical Service Commission. This update is for Scientific Assistant, advertisement 30/2026. Use this number when you look for the form or a later notice. It helps you avoid opening a different recruitment.'],
                'Important application dates' => ['The official page gives 25 September 2026 as the start date. It lists 24 October 2026 at 11:55 PM as the last time for registration, application and payment. These are the dates shown when the source was checked.', '11:55 PM means late at night. It is five minutes before midnight. Try to finish earlier, so a slow website or payment problem does not leave you without time.'],
            ],
            'btsc-food-safety-officer-29-2026' => [
                'Recruitment reference' => ['Look for Food Safety Officer under BTSC advertisement 29/2026. The title and number should both match. Do not open the Scientific Assistant form by mistake; that is a different advertisement.'],
                'Application and payment dates' => ['The listed start date is 25 September 2026. The application, registration and payment deadline on the official page is 24 October 2026 at 11:55 PM.', 'Plan to complete the form before the last evening. If a payment fails, first check the portal instructions and payment status before trying again. Keep any payment reference that you receive.'],
            ],
            'btsc-touring-veterinary-officer-27-2026' => [
                'Understand the post name' => ['The official title is Touring Veterinary Officer and Equivalent. The reference is BTSC advertisement 27/2026. Equivalent refers to the related posts covered by this advertisement; read the PDF for the exact list.'],
                'Application deadline' => ['The listed application period starts on 24 September 2026. Registration, application and payment close on 23 October 2026 at 11:55 PM, according to the checked page.', 'This deadline is one day earlier than the dates listed for the Scientific Assistant and Food Safety Officer forms. Always follow the date for your own advertisement.'],
            ],
            'btsc-fishery-extension-officer-28-2026' => [
                'Identify the correct recruitment' => ['Look for Fishery Extension Officer, BTSC advertisement 28/2026. Keep the number 28/2026 in your notes. Match it on the advertisement and the online form before entering your details.'],
                'Dates to remember' => ['The official page lists 24 September 2026 as the opening date. It gives 23 October 2026 at 11:55 PM as the registration, application and payment deadline.', 'The date in this guide is an application deadline. It does not tell you when a written exam will happen. Read any later date-change notice before you apply.'],
            ],
            'bssc-field-assistant-mains-notice-16-october-2026' => [
                'Candidates covered by the update' => ['Candidates following Field Assistant recruitment under BSSC advertisement 03/25 should read this notice. BSSC means Bihar Staff Selection Commission. Its notice board lists this update under the Main Examination stage.'],
                'Main examination stage' => ['A main examination is a later exam stage in a selection process. A notice about this stage does not automatically invite everyone to apply. Read the detailed notice to check who can take part and what they must do.'],
                'Confirmed notice and available syllabus' => ['The official board lists the notice on 16 October 2026 and provides advertisement and syllabus links. A syllabus is the list of topics for an exam.', 'This guide has not confirmed the detailed PDF dates. It therefore gives no new exam day, fee deadline or admit-card release date. Open the linked notice to check those details before taking action.'],
                'Next step for Field Assistant candidates' => ['Keep your earlier application details nearby when reading the notice. Look for any instruction about the main-stage form, required papers and syllabus. Follow only the steps that the notice asks you to complete.'],
            ],
            'bssc-stenographer-edit-option-16-october-2026' => [
                'Application correction update' => ['Edit means change or correct something. This update concerns the application edit option for Stenographer/Steno Typist Grade III, advertisement 07/25. It may be relevant if you already submitted a form for that recruitment.'],
                'Correction scope and pending details' => ['Do not assume that all fields are open for changes. Read the notice to learn which details can be edited, who can use the option and whether there are any conditions. This guide has not confirmed the edit deadline or a correction fee.'],
                'Checking and saving a correction' => ['Compare your submitted details with your original records. Note any error before opening the edit page. Use the correction link provided by the official website if the notice tells you to do so.', 'Before you save a change, read it again. If the portal gives you an updated form or receipt, save it. Do not assume there will be another chance to edit later.'],
                'Current scope of the notice' => ['The listed heading is about editing an application. It does not by itself confirm fresh applications, an examination date or an admit-card download. Look for a separate notice for those steps.'],
            ],
            'bssc-sports-trainer-discipline-16-october-2026' => [
                'Sports discipline selection' => ['A sports discipline means a particular sport or sporting event. The BSSC update concerns choosing a sports discipline for Sports Trainer recruitment, advertisement 08/25. Check the notice to see which choices are actually offered.'],
                'Candidates and selection conditions' => ['Candidates in this recruitment should read the full notice. The heading alone does not show whether every applicant must make a choice or whether only certain candidates are covered. Read that part before using the portal.'],
                'Matching the sport with your records' => ['Read the rules for the sports options. Match the requirements with your own certificates and application details. Do not select an option only because its name seems close to the sport on your certificate.', 'Check the option carefully before you save it. Keep the confirmation if the portal provides one. The selection deadline and any chance to change your choice later have not been confirmed in this guide.'],
                'Exam and admit-card status' => ['No exam date or new admit-card release is confirmed by this guide. This update is about the sports discipline choice. Check the official website for a separate examination notice.'],
            ],
            'csbc-driver-constable-dv-27-october-2026' => [
                'Candidates called for document verification' => ['This is for the 22 candidates listed in the CSBC notice for Driver Constable, advertisement 02/2025. First find your roll number in the PDF. Read the papers requested against your own entry.'],
                'Date, time and place' => ['The notice asks the listed candidates to attend on 27 October 2026 between 10 AM and 5 PM. The place is the CSBC office at Back Harding Road, Patna 800001, near Secretariat Halt.'],
                'Why the document check is being held' => ['Document verification, often called DV, means checking your papers. Officers compare the required original records with the details in your application. The notice relates to missing or required records found during checks after the Driving Efficiency Test, or DET.'],
                'Required documents and email notice' => ['Bring the original papers requested against your roll number, along with the DET admit card, identity proof and gate pass named in the notice. The required certificates differ between candidates. Use your own row in the PDF as the checklist.', 'The notice also says the candidates were informed through their registered email. Check that email account as well.'],
                'Limits of this candidate list' => ['This particular notice calls the listed candidates. Do not treat it as a call for all applicants. A document check is also different from a fresh written exam or a new application window.'],
            ],
            'bpssc-asi-technical-07-2026-exam-postponed' => [
                'Examination covered by the notice' => ['The notice covers Assistant Sub-Inspector, Technical, in Bihar Police Radio under BPSSC advertisement 07/2026. ASI is the short name for Assistant Sub-Inspector. Match the post and number before using this update.'],
                'Change to the earlier examination plan' => ['Postponed means the exam will not take place on its earlier planned date. This notice moved the exam away from 5 August 2026. It says the new date will be shared later. It does not give a replacement day.'],
                'Date and history of the notice' => ['No. The source is an earlier notice dated 30 July 2026. It is included to explain why an old exam date or admit-card link should not be treated as a fresh exam announcement. A newer notice may provide later instructions.'],
                'Admit-card instructions still to check' => ['Check the next official instructions. This guide cannot confirm whether an old card will be accepted or whether a fresh card will be required. An old download link alone does not answer that question.'],
                'Candidate preparation and travel' => ['Keep your application and login details ready. Continue with your study plan using the official syllabus. Confirm the current date and exam-centre instructions before booking travel.'],
            ],
            'bpsc-72-cce-postponement-official-update-october-2026' => [
                'Understand the exam name' => ['BPSC means Bihar Public Service Commission. CCE means Combined Competitive Examination. Prelims is a short name for the preliminary examination, an early exam stage. This guide concerns the 72nd CCE Prelims.'],
                'Official postponement listing' => ['The official BPSC website lists a postponement notice for this examination. Postponement means a planned exam has been moved from its earlier date. The listing used for this guide does not establish a confirmed replacement date.'],
                'Admit-card status' => ['A new admit-card release was not confirmed in this check. Do not treat a predicted release date as an official announcement. Check the latest notice and use only the download link provided by the commission.'],
                'Finding the latest 72nd CCE notice' => ['Open the official website and look in Notifications for 72nd CCE. Check the date and the examination stage. A notice about another CCE cycle or a different stage may not apply to your exam.', 'Save the latest relevant notice. Look for the revised examination day, admit-card instructions and any action you need to take. If the notice does not state a date, do not fill the gap with a guess.'],
                'Preparation while awaiting the schedule' => ['You can keep studying while you check for the official schedule. Use your syllabus to plan revision. Keep your application details ready, and wait for confirmed instructions before making travel plans.'],
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
