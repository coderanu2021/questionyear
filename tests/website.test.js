import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

const source = readFileSync(new URL('../public/js/website.js', import.meta.url), 'utf8');
const renderChaps = source.match(/^function renderChaps\(\)\{[\s\S]*?(?=\nfunction subjectPage)/m)[0];
const helpers = ['const mcqText=', 'const tr=', 'const subjectQuizzes=', 'function esc(']
    .map(prefix => source.split('\n').find(line => line.startsWith(prefix))).join('\n');

function render(subject, search = '') {
    const elements = {cf: {value: search}, cl: {innerHTML: ''}};
    const editions = [
        {title: '7 October Current Affairs Quiz', count: 10, url: '/quiz/current-affairs/8-october', date_label: '07 Oct 2026'},
        {title: '6 October Current Affairs', count: 10, url: '/quiz/current-affairs/10-october', date_label: '06 Oct 2026'},
    ];
    const context = {
        curSub: {id: subject, ch: editions.map(edition => edition.title)},
        window: {websiteText: key => key, CURRICULUM: {tests: {[subject + ':0']: [editions[0]], [subject + ':1']: [editions[1]]}}},
        $: id => elements[id],
    };
    vm.runInNewContext(helpers + '\n' + renderChaps + '\nrenderChaps();', context);
    return elements.cl.innerHTML;
}

for (const subject of ['current-affairs', 'general-knowledge']) {
    test(subject + ' renders its published editions without a runtime error', () => {
        const html = render(subject);
        assert.match(html, /7 October Current Affairs MCQ/);
        assert.match(html, /6 October Current Affairs/);
        assert.match(html, /10 questions and answers/);
        assert.match(html, /href="\/quiz\/current-affairs\/8-october"/);
    });

    test(subject + ' search filters editions and handles no matches', () => {
        const html = render(subject, '7 october');
        assert.match(html, /7 October/);
        assert.doesNotMatch(html, /6 October/);
        assert.match(render(subject, 'missing'), /No MCQs match your search/);
    });
}
