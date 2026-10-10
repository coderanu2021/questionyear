<article class="legal-content box" aria-label="{{ $title }}">
<p class="legal-updated">Last updated: 10 October 2026</p>
@if($page === 'privacy')
<h2>1. About this policy</h2>
<p>This Privacy Policy explains how {{ $siteSettings['site_title'] }} handles information when you browse our learning material, create an account, practise MCQs or contact us.</p>
<h2>2. Information we collect</h2>
<ul>
<li><strong>Account information:</strong> your name, email address and securely hashed password when you register with email. If you choose Google sign-in, we receive your Google account identifier, name and email address to create or connect your account.</li>
<li><strong>Learning activity:</strong> submitted answers, scores, time taken, practice history, progress, bookmarks and question reports. Guest quiz submissions may also be stored.</li>
<li><strong>Messages:</strong> your name, email, message and any feedback rating you submit through our contact or feedback forms.</li>
<li><strong>Technical information:</strong> session information and, depending on server configuration, IP addresses, browser information and request logs used to operate and protect the website.</li>
</ul>
<h2>3. How we use information</h2>
<p>We use this information to manage accounts, authenticate sign-ins, save learning progress, show results, respond to messages, review reported questions and protect the service against misuse. Password-reset emails and other service messages help you manage your account.</p>
<h2>4. Leaderboards and visibility</h2>
<p>Leaderboard pages may display a participating learner's name, practice scores and activity totals publicly. Your password and email address are not displayed on the leaderboard. Contact us if you have concerns about your displayed name or participation.</p>
<h2>5. Cookies and browser storage</h2>
<p>We use session cookies for sign-in and request security, and a remember-me cookie when you choose to stay logged in. Your theme preference is saved in browser storage. Guest practice limits and answers may be held in your session. You can manage cookies and site storage through your browser; disabling them may affect sign-in and practice features. Read our <a href="{{ route('page', ['page' => 'cookies']) }}">Cookies page</a> for more information.</p>
<h2>6. Service providers and external links</h2>
<p>Hosting and email providers may process information needed to deliver the service. Contact and feedback messages may be emailed to the website administrator. Google processes sign-in information when you choose Google login under its own privacy policy.</p>
<p>When available, Hindi explanation translations send the question, correct answer and explanation to Google's Gemini service. The translation request does not include your account name, email or submitted answer. External websites linked from our pages have their own privacy practices.</p>
<h2>7. Storage and security</h2>
<p>We retain account and learning records to provide your account and history, and messages to handle support requests. Retention may also depend on security, backup and applicable legal requirements. Passwords are stored as hashes. No online service can promise complete security; do not send passwords or sensitive documents through contact forms.</p>
<h2>8. Your information and requests</h2>
<p>To request access to, correction of or deletion of your personal information or account, email <a href="mailto:{{ $siteSettings['contact_email'] }}">{{ $siteSettings['contact_email'] }}</a> from your registered address where possible. We may need to verify that the account belongs to you. We will explain any information that must be retained and handle requests in accordance with applicable requirements.</p>
<h2>9. Younger learners</h2>
<p>If you are a minor, involve a parent or guardian before creating an account or sending personal information. Parents and guardians can contact us with questions or requests concerning a child's information.</p>
<h2>10. Updates and contact</h2>
<p>We may update this policy when the service or its data practices change. The date above identifies the latest revision. For privacy questions, contact <a href="mailto:{{ $siteSettings['contact_email'] }}">{{ $siteSettings['contact_email'] }}</a>.</p>
@else
<h2>1. Using the website</h2>
<p>These Terms &amp; Conditions govern your use of {{ $siteSettings['site_title'] }}. Please read them together with our <a href="{{ route('page', ['page' => 'privacy']) }}">Privacy Policy</a>. If you do not agree with these terms, please stop using the service. Minors should use the service with a parent or guardian's involvement.</p>
<h2>2. Educational purpose</h2>
<p>Our chapter notes, MCQs, explanations, current affairs and practice results are provided for learning and revision. We do not guarantee examination results, admission, employment or selection. Unless explicitly stated, we are not affiliated with examination authorities, government bodies or educational boards.</p>
<h2>3. Accounts and security</h2>
<p>Provide accurate account information and keep your login details private. You are responsible for activity you authorise through your account. Contact us if you suspect unauthorised access. Guest practice limits and account requirements may apply to some features.</p>
<h2>4. Acceptable use</h2>
<ul>
<li>Use the website for lawful study and practice.</li>
<li>Do not harass others, impersonate anyone or submit abusive, misleading or unlawful messages.</li>
<li>Do not attempt to access another account, bypass practice limits, manipulate leaderboards or interfere with the website's security or operation.</li>
<li>Do not bulk copy, scrape, redistribute or sell our material without permission or another lawful basis.</li>
</ul>
<h2>5. Content and permissions</h2>
<p>You may read and use the material for personal learning. Website branding and original content belong to their respective owners. Third-party names and trademarks remain the property of their owners. Contact us to request reuse permission or report a copyright concern, identifying the relevant page and your claim.</p>
<h2>6. Accuracy and generated explanations</h2>
<p>Questions, answers and explanations may contain errors or become outdated. Some practice material or translations may be generated with AI. Check important facts against official sources, particularly examination notices, dates and eligibility rules. Report incorrect questions through the available report feature or our contact page.</p>
<h2>7. Your submissions</h2>
<p>Only submit feedback, messages and reports that you are entitled to share. You allow us to store and use those submissions to respond, investigate issues and improve the service. Avoid including confidential information or another person's personal information without permission.</p>
<h2>8. Availability and account restrictions</h2>
<p>Features and content may change, and access may be interrupted for maintenance or technical reasons. We may restrict or suspend accounts that misuse the service or breach these terms. Contact us if you believe a restriction was applied in error.</p>
<h2>9. External services and responsibility</h2>
<p>External links and Google sign-in are subject to the relevant provider's terms. To the extent permitted by applicable law, we are not responsible for losses resulting from reliance on educational content, service interruptions or third-party websites. These terms do not exclude rights or liabilities that cannot lawfully be excluded.</p>
<h2>10. Changes and contact</h2>
<p>We may revise these terms as the service changes. The latest version and revision date will appear here. For questions, permission requests or complaints, email <a href="mailto:{{ $siteSettings['contact_email'] }}">{{ $siteSettings['contact_email'] }}</a> or use our <a href="{{ route('page', ['page' => 'contact']) }}">contact page</a>.</p>
@endif
</article>
