<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class ExamNotificationController extends Controller
{
    public const CATEGORIES = [
        'all' => 'Latest Government Jobs',
        'upcoming' => 'Upcoming Government Jobs 2027',
        '10th-pass' => '10th Pass Government Jobs',
        '12th-pass' => '12th Pass Government Jobs',
        'graduate' => 'Graduate Government Jobs',
        'ssc' => 'SSC Jobs',
        'railway' => 'Railway Jobs',
        'banking' => 'Banking Jobs',
        'upsc' => 'UPSC Jobs',
        'admit-cards' => 'Admit Cards',
        'results' => 'Results',
    ];

    public function hub(string $category = 'all'): View
    {
        return $this->index(null, $category);
    }

    /** @var array<string, array{title: string, summary: string, status: string}> */
    public const NOTICES = [
        'upsc' => ['title' => 'UPSC Civil Services 2027', 'summary' => 'Calendar dates, prior-year eligibility and attempt rules.', 'status' => '2027 calendar published'],
        'rrb-ntpc-graduate-cen-06-2026' => ['title' => 'RRB NTPC Graduate — CEN 06/2026', 'summary' => 'Application dates, degree rules and the 1 January 2027 age cut-off.', 'status' => 'Published 2026 notice'],
        'rrb-ntpc-undergraduate-cen-07-2025' => ['title' => 'RRB NTPC Undergraduate — CEN 07/2025', 'summary' => 'Class 12 entry rules from the prior notice. Fresh 2027 terms are not verified.', 'status' => 'Prior-year reference'],
        'rrb-group-d-cen-09-2025' => ['title' => 'RRB Group D / Level 1 — CEN 09/2025', 'summary' => 'Prior-notice education, age and fitness rules for planning your next step.', 'status' => 'Prior-year reference'],
        'ssc-selection-posts-phase-xiv-2026' => ['title' => 'SSC Selection Posts Phase XIV 2026', 'summary' => 'The published application window was 13 April to 4 May 2026. Check post-specific qualifications in the official notice.', 'status' => 'Application window ended'],
        'canara-bank-graduate-apprentices-2026' => ['title' => 'Canara Bank Graduate Apprentices 2026', 'summary' => 'The IBPS recruitment directory lists registration from 1 to 17 October 2026. Read the bank notification for training terms and eligibility.', 'status' => 'Published application schedule'],
        'upsc-civil-services-mains-2026-admit-card' => ['title' => 'UPSC Civil Services Mains 2026 Admit Card', 'summary' => 'Archived e-admit-card update for the 2026 Mains examination. This is not a new application opportunity or a promise that downloads remain available.', 'status' => 'Archived admit card notice'],
        'upsc-engineering-services-mains-2026-result' => ['title' => 'UPSC Engineering Services Mains 2026 Written Result', 'summary' => 'UPSC lists the written result and named result on 24 July 2026. A written result is not a final appointment list.', 'status' => 'Written result published'],
    ];

    public function index(?string $exam = null, string $category = 'all'): View
    {
        abort_unless($exam === null || $exam === 'railways' || isset(self::NOTICES[$exam]), 404);
        abort_unless(isset(self::CATEGORIES[$category]), 404);

        $jobDetails = [
            'upsc' => ['category' => 'upsc', 'qualification' => 'graduate', 'organization' => 'Union Public Service Commission', 'vacancies' => 'To be announced', 'deadline' => 'See calendar in guide', 'source' => 'https://www.upsc.gov.in/', 'upcoming' => true],
            'rrb-ntpc-graduate-cen-06-2026' => ['category' => 'railway', 'qualification' => 'graduate', 'organization' => 'Railway Recruitment Boards', 'vacancies' => '3,548', 'deadline' => '6 November 2026', 'source' => 'https://www.pib.gov.in/PressReleasePage.aspx?PRID=2321873', 'upcoming' => false],
            'rrb-ntpc-undergraduate-cen-07-2025' => ['category' => 'railway', 'qualification' => '12th-pass', 'organization' => 'Railway Recruitment Boards', 'vacancies' => 'Prior-year reference', 'deadline' => 'Prior-year reference', 'source' => 'https://www.rrbchennai.gov.in/', 'upcoming' => false],
            'rrb-group-d-cen-09-2025' => ['category' => 'railway', 'qualification' => '10th-pass', 'organization' => 'Railway Recruitment Boards', 'vacancies' => 'See prior notice', 'deadline' => 'Prior-year reference', 'source' => 'https://rrbajmer.gov.in/', 'upcoming' => false],
            'ssc-selection-posts-phase-xiv-2026' => ['category' => 'ssc', 'qualification' => 'all', 'organization' => 'Staff Selection Commission', 'vacancies' => 'Check post-wise notice', 'deadline' => '4 May 2026 (closed)', 'source' => 'https://ssc.gov.in/api/attachment/uploads/masterData/NoticeBoards/Notice_of_RHQ_2026_phase_xiv.pdf', 'upcoming' => false, 'verified' => '2026-10-15', 'verification' => 'Official SSC notification indexed in search; direct PDF fetch unavailable.', 'details' => [
                'Recruitment overview' => 'This is the Phase XIV/2026 Selection Posts cycle. Each advertised post has its own requirements. The original application period has ended; this page does not invite fresh applications.',
                'Important dates' => 'The notice gives an application window of 13 April to 4 May 2026 and a tentative computer-based examination month of June 2026. The tentative month is not a confirmed exam day or a current schedule.',
                'Eligibility and vacancies' => 'Use the exact post code in the official notice to check vacancies, education, age and experience. No combined vacancy number or common eligibility rule is confirmed by this summary.',
                'What to check next' => 'Check SSC for later notices, corrections, results and post-specific updates. Fees, pay, category relaxation and the detailed exam scheme have not been independently verified for this guide.',
            ]],
            'canara-bank-graduate-apprentices-2026' => ['category' => 'banking', 'qualification' => 'graduate', 'organization' => 'Canara Bank', 'vacancies' => 'Not verified', 'deadline' => '17 October 2026', 'source' => 'https://www.ibps.in/index.php/recruitment/', 'upcoming' => false, 'verified' => '2026-10-15', 'verification' => 'Official IBPS recruitment directory indexed in search; full bank notification not reviewed.', 'details' => [
                'Recruitment overview' => 'The official IBPS recruitment directory includes Canara Bank Engagement of Graduate Apprentices. Apprenticeship is a training opportunity; do not treat this listing as confirmation of a permanent bank appointment.',
                'Important dates' => 'The directory lists registration from 1 October to 17 October 2026. Check the bank notice for the closing time, any extension and payment deadlines.',
                'Eligibility, vacancies and stipend' => 'The listing identifies graduate apprentices, but the full eligibility conditions, age, vacancies, stipend, training duration and selection rules have not been verified for this summary. Read the bank notification before applying.',
                'How to find the application' => 'Open the official IBPS recruitment directory and select the Canara Bank graduate apprentice entry. Read its linked notice before entering personal details or making a payment.',
            ]],
            'upsc-civil-services-mains-2026-admit-card' => ['category' => 'upsc', 'qualification' => 'graduate', 'organization' => 'Union Public Service Commission', 'vacancies' => 'Not applicable to this update', 'deadline' => 'Archived 2026 exam', 'source' => 'https://www.upsc.gov.in/examinations/Civil%20Services%20%28Main%29%20Examination%2C%202026', 'upcoming' => false, 'type' => 'admit-cards', 'verified' => '2026-10-15', 'verification' => 'Official UPSC examination page indexed in search; active download availability not verified.', 'details' => [
                'Admit card overview' => 'The official Civil Services (Main) Examination, 2026 page lists an e-admit-card entry uploaded on 14 August 2026. This entry relates to that examination cycle.',
                'Download availability' => 'This is an archived update. An old admit-card entry does not mean its download portal is still open. Use the official examination page to check the current position.',
                'How to check your admit card' => 'Open the official examination page and follow its e-admit-card link if available. Use only the official login page. Check your name, examination details and instructions on your own document.',
                'Documents and instructions' => 'Follow the identity-proof and document instructions printed on the official admit card. This guide does not independently confirm the accepted documents or examination-centre rules.',
            ]],
            'upsc-engineering-services-mains-2026-result' => ['category' => 'upsc', 'qualification' => 'all', 'organization' => 'Union Public Service Commission', 'vacancies' => 'Not applicable to this update', 'deadline' => 'Published 24 July 2026', 'source' => 'https://www.upsc.gov.in/examinations/Engineering%20Services%20%28Main%29%20Examination%2C%202026', 'upcoming' => false, 'type' => 'results', 'verified' => '2026-10-15', 'verification' => 'Official UPSC examination page indexed in search; individual candidate outcomes not checked.', 'details' => [
                'Result overview' => 'UPSC lists Written Result and Written Result (with name) for Engineering Services (Main) Examination, 2026, both uploaded on 24 July 2026.',
                'How to check the result' => 'Open the official examination page, choose the written result document and search for your roll number. Read the instructions and any later updates on the same official website.',
                'What the result means' => 'This notice concerns the written examination stage. Inclusion in a written result does not by itself confirm final selection, medical clearance or appointment.',
                'Next steps' => 'Follow only the dates and document instructions in the official result and subsequent notices. This summary does not announce an interview date or confirm an individual candidate outcome.',
            ]],
        ];
        $salaryGuides = [
            'upsc' => [
                'summary' => 'UPSC does not have one salary for all successful candidates. As an IAS example, Level 10 basic pay runs from ₹56,100 to ₹1,77,500 per month under the IAS Pay Rules, 2016. ₹56,100 is the first cell; the upper figure is reached through pay progression, not on joining.',
                'note' => 'This is an existing IAS pay-rule reference, not confirmation of every service or the final CSE 2027 terms. Service, grade, posting, allowances and deductions affect actual pay.',
                'sources' => ['DoPT: IAS Pay Rules, 2016, pay matrix' => 'https://dopt.gov.in/sites/default/files/IAS_PAY_Rules2016.pdf'],
            ],
            'rrb-ntpc-graduate-cen-06-2026' => [
                'summary' => 'Initial basic pay is ₹29,200–₹35,400 per month across the advertised posts. Chief Commercial cum Ticket Supervisor starts at ₹35,400 (Level 6). Goods Train Manager, Junior Accounts Assistant cum Typist and Senior Clerk cum Typist start at ₹29,200 (Level 5).',
                'note' => 'This is the range of starting basic pay across posts, not an in-hand salary range. Applicable allowances and deductions change the amount received.',
                'sources' => ['Ministry of Railways: CEN 06/2026 pay details' => 'https://www.pib.gov.in/PressReleaseIframePage.aspx?PRID=2321873&lang=2&reg=48'],
            ],
            'rrb-ntpc-undergraduate-cen-07-2025' => [
                'summary' => 'The CEN 07/2025 post table gives starting basic pay of ₹19,900–₹21,700 per month. Commercial cum Ticket Clerk starts at ₹21,700 (Level 3). Accounts Clerk cum Typist, Junior Clerk cum Typist and Trains Clerk start at ₹19,900 (Level 2).',
                'note' => 'These figures belong to the cited 2025 notice. They do not confirm pay for a fresh 2026 or 2027 notice. Starting basic pay excludes applicable allowances and deductions.',
                'sources' => ['RRB Chennai: CEN 07/2025, Annexure A' => 'https://www.rrbchennai.gov.in/downloads/CEN-07-2025-NTPC-UnderGraduate-English.pdf'],
            ],
            'rrb-group-d-cen-09-2025' => [
                'summary' => 'The CEN 09/2025 indicative-advertisement corrigendum gives ₹18,000 per month as initial basic pay for Level 1 posts. This source states a starting amount, not a take-home salary range.',
                'note' => 'This is a prior-notice reference. Check the applicable detailed notice and pay rules for progression, allowances and deductions; a new cycle may have different terms.',
                'sources' => ['RRB Ajmer: CEN 09/2025 indicative-advertisement corrigendum' => 'https://www.rrbajmer.gov.in/Upload_PDF/corrigendum%20for%20indicative%20ad%2009-2025-639045045354077724.pdf'],
            ],
            'ssc-selection-posts-phase-xiv-2026' => [
                'summary' => 'SSC Selection Posts do not have one common salary. The Phase XIV/2026 annexure lists Library Attendant (CR13126) at Level 1 and Senior Research Assistant (CR13426) at Level 7. The government pay-matrix reference gives starting basic pay of ₹18,000 at Level 1 and ₹44,900 at Level 7.',
                'note' => 'These are two post examples, not the minimum and maximum for every Selection Post. Match your post code to its pay level. In-hand pay depends on allowances and deductions.',
                'sources' => ['SSC: Phase XIV/2026 post annexure' => 'https://ssc.gov.in/api/attachment/uploads/masterData/NoticeBoards/Notice_of_RHQ_2026_phase_xiv.pdf', 'Department of Expenditure: Seventh CPC pay matrix reference' => 'https://doe.gov.in/files/cenetral-pay_document/7cpc_report_eng.pdf'],
            ],
            'canara-bank-graduate-apprentices-2026' => [
                'summary' => 'The 2026 apprentice stipend has not been verified in this guide. As a prior-cycle reference only, Canara Bank notification CB/AT/2025 provides a monthly stipend of ₹15,000, including the applicable government subsidy. Do not treat that as confirmed 2026 pay.',
                'note' => 'A stipend is a training payment. The cited 2025 notice says apprentices are not bank employees and do not receive employee benefits. Read the 2026 notification for its own amount and conditions.',
                'sources' => ['Canara Bank: CB/AT/2025 apprenticeship notice (prior-cycle reference)' => 'https://canarabank.bank.in/documents/d/guest/notification-app-22'],
            ],
            'upsc-engineering-services-mains-2026-result' => [
                'summary' => 'This written-result notice does not establish a salary range. Service-specific Engineering Services pay has not been verified for this guide. Check the relevant service rules and appointment terms before relying on a figure.',
                'note' => 'A written result is an examination-stage update. It does not itself confirm appointment or entitlement to pay. IAS pay figures should not be used as Engineering Services pay.',
                'sources' => ['UPSC: Engineering Services (Main) Examination, 2026' => $jobDetails['upsc-engineering-services-mains-2026-result']['source']],
            ],
        ];
        $salaryGuides['upsc-civil-services-mains-2026-admit-card'] = $salaryGuides['upsc'];
        $salaryGuides['upsc-civil-services-mains-2026-admit-card']['note'] = 'This existing IAS pay-rule example relates to a possible service after selection. An admit card is not an appointment or salary entitlement; other Civil Services have their own rules.';

        $faqs = [
            'upsc' => [
                'What is UPSC?' => 'UPSC stands for Union Public Service Commission. It conducts examinations and recruitment for various Union government services and posts. Civil Services is one of its examinations.',
                'What is the UPSC salary?' => $salaryGuides['upsc']['summary'],
                'Is UPSC the same as IAS?' => 'No. UPSC is the recruiting commission; IAS is the Indian Administrative Service. Civil Services candidates may be allocated different services under the applicable rules.',
                'Are all CSE 2027 rules confirmed?' => 'The calendar is a planning reference. This guide has not verified the complete CSE 2027 notice; its prior-year eligibility information must be checked against the new notice.',
            ],
            'rrb-ntpc-graduate-cen-06-2026' => [
                'What is RRB NTPC Graduate recruitment?' => 'RRB means Railway Recruitment Board. NTPC means Non-Technical Popular Categories. Graduate-level posts require the degree qualification and other conditions stated in the applicable notice.',
                'What is the RRB NTPC Graduate salary?' => $salaryGuides['rrb-ntpc-graduate-cen-06-2026']['summary'],
                'Is the quoted Railway salary an in-hand amount?' => 'No. The figures shown are basic pay. Allowances may be added and deductions may apply, so actual take-home pay varies.',
                'Does a 2027 age cut-off make this a 2027 recruitment?' => 'No. This guide concerns CEN 06/2026. The date used to calculate age does not change the notice number or recruitment cycle.',
            ],
            'rrb-ntpc-undergraduate-cen-07-2025' => [
                'What is RRB NTPC Undergraduate recruitment?' => 'It is the undergraduate-level Non-Technical Popular Categories recruitment conducted by Railway Recruitment Boards. The cited notice uses a Class 12 entry route; undergraduate here does not mean a university degree is required.',
                'What is the RRB NTPC Undergraduate salary?' => $salaryGuides['rrb-ntpc-undergraduate-cen-07-2025']['summary'],
                'Is a degree required for the cited undergraduate posts?' => 'The cited posts use Class 12 or equivalent qualifications with the applicable marks rules and exceptions. Read the exact post requirements, including typing requirements where relevant.',
                'Can I use this pay figure for a new notification?' => 'Use it only as a reference for CEN 07/2025. A fresh notice must confirm its own pay, vacancies, eligibility and dates.',
            ],
            'rrb-group-d-cen-09-2025' => [
                'What is Railway Group D recruitment?' => 'Group D is the common name used for Railway Level 1 recruitment. The exact posts and requirements are listed in the relevant Centralised Employment Notice.',
                'What is the Railway Group D salary?' => $salaryGuides['rrb-group-d-cen-09-2025']['summary'],
                'Is ₹18,000 the amount received after deductions?' => 'No. It is initial basic pay in the cited source. Applicable allowances and deductions determine the amount received.',
                'Are these confirmed terms for Group D 2027?' => 'No. This page uses CEN 09/2025 as a prior-notice reference. Read a fresh official notice before applying for a later cycle.',
            ],
            'ssc-selection-posts-phase-xiv-2026' => [
                'What is SSC?' => 'SSC stands for Staff Selection Commission. It recruits for posts in various government ministries, departments and organisations through its notified selection processes.',
                'What are SSC Selection Posts?' => 'Selection Posts are individual advertised posts with their own post codes, qualifications and conditions. Check the row for the exact post you want.',
                'What is the SSC Selection Posts salary?' => $salaryGuides['ssc-selection-posts-phase-xiv-2026']['summary'],
                'Is Phase XIV/2026 open for fresh applications?' => 'The original application window listed in this guide ended on 4 May 2026. Check SSC for any later official correction or reopening notice.',
            ],
            'canara-bank-graduate-apprentices-2026' => [
                'What is the Canara Bank graduate apprenticeship?' => 'It is a graduate apprenticeship opportunity listed in the official IBPS recruitment directory. Read the bank notice for the training conditions; do not assume it is a permanent bank job.',
                'What is the Canara Bank apprentice salary or stipend?' => $salaryGuides['canara-bank-graduate-apprentices-2026']['summary'],
                'Does an apprenticeship guarantee a permanent job?' => 'Do not assume that it does. The cited prior-cycle notice treats apprentices as trainees rather than bank employees. Check the current contract and notification for its terms.',
                'Where should I verify the 2026 stipend?' => 'Read the bank notification linked from the official recruitment entry. The application-directory dates alone do not confirm the stipend or employee benefits.',
            ],
            'upsc-civil-services-mains-2026-admit-card' => [
                'What is UPSC?' => 'UPSC stands for Union Public Service Commission. This page concerns its Civil Services (Main) Examination, 2026 admit-card update.',
                'What is the UPSC Civil Services salary?' => $salaryGuides['upsc-civil-services-mains-2026-admit-card']['summary'],
                'Does an admit card confirm selection or salary?' => 'No. An admit card relates to admission to an examination under its conditions. Selection and appointment are separate stages.',
                'Can I still download this archived admit card?' => 'Current download availability has not been verified. Check the official examination page; an archived entry does not guarantee an active download.',
            ],
            'upsc-engineering-services-mains-2026-result' => [
                'What is UPSC Engineering Services?' => 'It is the Engineering Services examination conducted by the Union Public Service Commission. This page covers the 2026 Main written-result stage, not the Civil Services examination.',
                'What is the UPSC Engineering Services salary?' => $salaryGuides['upsc-engineering-services-mains-2026-result']['summary'],
                'Does the written result confirm a government appointment?' => 'No. Follow the official result instructions and later stages. A written-result listing alone does not confirm final appointment or pay.',
                'How can I check my written result?' => 'Open the official UPSC Engineering Services examination page and follow its written-result document. Search your roll number and read the accompanying instructions.',
            ],
        ];

        $notices = self::NOTICES;
        if ($exam === 'railways') {
            $notices = array_filter($notices, fn (string $slug): bool => $jobDetails[$slug]['category'] === 'railway', ARRAY_FILTER_USE_KEY);
        } elseif ($exam === null && $category !== 'all') {
            $notices = array_filter($notices, fn (string $slug): bool => match ($category) {
                'admit-cards', 'results' => ($jobDetails[$slug]['type'] ?? 'recruitment') === $category,
                'upcoming' => $jobDetails[$slug]['upcoming'],
                default => ($jobDetails[$slug]['type'] ?? 'recruitment') === 'recruitment' && in_array($category, [$jobDetails[$slug]['category'], $jobDetails[$slug]['qualification']], true),
            }, ARRAY_FILTER_USE_KEY);
        }

        return view('website.notifications', [
            'exam' => $exam,
            'category' => $category,
            'categories' => self::CATEGORIES,
            'jobDetails' => $jobDetails,
            'salaryGuides' => $salaryGuides,
            'faqs' => $faqs,
            'notices' => $notices,
            'title' => $exam === null ? self::CATEGORIES[$category] : match ($exam) {
                'upsc' => 'UPSC CSE 2027: dates and eligibility',
                'railways' => 'Railway exams for 2027: notices and eligibility',
                default => self::NOTICES[$exam]['title'] ?? 'Government Job Notifications: UPSC and Railway Updates',
            },
            'description' => $exam === null ? self::CATEGORIES[$category].' on QuestionYear. Browse recruitment guides, check eligibility and follow official sources for application dates and updates.' : match ($exam) {
                'upsc' => 'Check UPSC CSE 2027 calendar dates, prior-year age and degree rules, attempt limits and official sources. Final 2027 rules need the full notice.',
                'railways' => 'Plan for Railway exams with NTPC and Group D eligibility notes. See CEN 06/2026 dates, its 2027 age cut-off and clearly marked prior-year rules.',
                default => self::NOTICES[$exam]['summary'] ?? 'Find UPSC and Railway exam updates for 2027. Compare confirmed dates, eligibility references and pending notices, with links to official sources.',
            },
        ]);
    }
}
