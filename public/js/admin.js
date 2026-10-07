const $=s=>document.querySelector(s);
const ICON={
 dash:'<path d="M3 13h8V3H3zM13 21h8V11h-8zM13 3v6h8V3zM3 21h8v-6H3z"/>',
 book:'<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20V3H6.5A2.5 2.5 0 0 0 4 5.5zM4 19.5V21h16"/>',
 quiz:'<path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>',
 users:'<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8"/>',
 chart:'<path d="M3 3v18h18"/><path d="m7 15 4-4 3 3 6-7"/>',
 plus:'<path d="M12 5v14M5 12h14"/>',edit:'<path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/>',
 trash:'<path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6"/>',clock:'<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
 help:'<circle cx="12" cy="12" r="9"/><path d="M9.1 9a3 3 0 0 1 5.8 1c0 2-3 3-3 3M12 17h.01"/>'
};
const ic=n=>`<svg class="ic" viewBox="0 0 24 24">${ICON[n]}</svg>`;
const NAV=[['dashboard','Dashboard','dash'],['chapters','Chapters','book'],['tests','Tests & Quizzes','quiz'],['users','Users','users'],['analytics','Analytics','chart'],['messages','Messages','book'],['practice','Practice Quizzes','clock'],['settings','Settings','edit']];

/* ---------- data ---------- */
let S=window.ADMIN_STATE;let persisted=JSON.stringify(S);
const save=async()=>{try{const r=await fetch('/admin/state',{method:'PUT',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content},body:JSON.stringify(S)});const result=await r.json();if(!r.ok)throw Error(result.message||'Save failed');S=result;persisted=JSON.stringify(S);return true;}catch(e){S=JSON.parse(persisted);toast(e.message);return false}};
const chName=id=>(S.chapters.find(c=>c.id==id)||{title:'—'}).title;
const nid=a=>a.reduce((m,x)=>Math.max(m,x.id),0)+1;
const esc=s=>String(s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const ini=n=>n.split(' ').map(w=>w[0]).join('').slice(0,2).toUpperCase();
const fdate=d=>new Date(d).toLocaleDateString('en-IN',{day:'2-digit',month:'short',year:'numeric'});
const sb=s=>`<span class="badge ${{published:'b-ok',active:'b-ok',draft:'b-warn',inactive:'b-warn',blocked:'b-bad'}[s]}">${s[0].toUpperCase()+s.slice(1)}</span>`;
const toast=m=>{const t=$('#toast');t.textContent=m;t.classList.add('show');setTimeout(()=>t.classList.remove('show'),2200)};

/* ---------- charts ---------- */
function line(vals,labels,color='#2f4bd8'){
 const W=620,H=220,p=32,max=Math.max(1,...vals)*1.15,x=i=>p+i*(W-p-10)/(vals.length-1),y=v=>H-24-(v/max)*(H-44);
 const pts=vals.map((v,i)=>[x(i),y(v)]),d=pts.map((q,i)=>(i?'L':'M')+q[0]+' '+q[1]).join(' ');
 let g='';for(let i=0;i<=4;i++){const yy=H-24-i*(H-44)/4;g+=`<line x1="${p}" x2="${W-10}" y1="${yy}" y2="${yy}" stroke="#e6eaf2"/><text x="0" y="${yy+4}">${Math.round(max*i/4)}</text>`}
 return `<svg viewBox="0 0 ${W} ${H}" width="100%" role="img">${g}<path d="${d} L${x(vals.length-1)} ${H-24} L${p} ${H-24}Z" fill="${color}" opacity=".08"/><path d="${d}" fill="none" stroke="${color}" stroke-width="2.5" stroke-linejoin="round"/>${pts.map(q=>`<circle cx="${q[0]}" cy="${q[1]}" r="3.5" fill="#fff" stroke="${color}" stroke-width="2"/>`).join('')}${labels.map((l,i)=>`<text x="${x(i)}" y="${H-5}" text-anchor="middle">${l}</text>`).join('')}</svg>`;
}
function donut(parts){
 const tot=parts.reduce((a,b)=>a+b[1],0);let a0=-Math.PI/2,out='';
 parts.forEach(([n,v,c])=>{const a1=a0+v/Math.max(1,tot)*2*Math.PI,r=62,cx=80,cy=80,big=a1-a0>Math.PI?1:0;
  out+=`<path d="M${cx+r*Math.cos(a0)} ${cy+r*Math.sin(a0)} A${r} ${r} 0 ${big} 1 ${cx+r*Math.cos(a1)} ${cy+r*Math.sin(a1)}" fill="none" stroke="${c}" stroke-width="22"/>`;a0=a1});
 return `<div style="display:flex;align-items:center;gap:22px;flex-wrap:wrap"><svg viewBox="0 0 160 160" width="150">${out}<text x="80" y="78" text-anchor="middle" style="font-size:22px;font-weight:800;fill:#14213d">${tot}</text><text x="80" y="95" text-anchor="middle">users</text></svg><div>${parts.map(([n,v,c])=>`<div style="margin-bottom:8px"><i style="display:inline-block;width:10px;height:10px;border-radius:3px;background:${c};margin-right:8px"></i>${n} <b>${v}</b></div>`).join('')}</div></div>`;
}

/* ---------- views ---------- */
const stat=(l,v,t,c,bg,i)=>`<div class="card stat"><div class="ico" style="background:${bg};color:${c}">${ic(i)}</div><div><b>${v}</b><span>${l}</span> <span class="trend">${t}</span></div></div>`;
const totals=()=>({att:S.tests.reduce((a,t)=>a+t.attempts,0),avg:Math.round(S.users.reduce((a,u)=>a+u.score,0)/(S.users.length||1))});
const WEEKS=window.ADMIN_STATE.weeks.labels;

/* ---------- modals & actions ---------- */
const VIEWS=window.ADMIN_VIEWS;
let editor=null;
const openM=(h,wide)=>{killEditor();$('#dlg').className='dlg'+(wide?' wide':'');$('#dlg').innerHTML=h;$('#modal').classList.add('show')};
const killEditor=()=>{if(editor){editor.destroy().catch(()=>{});editor=null}};
const closeM=()=>{if(window.TEST_PAGE){location.href=window.TEST_URLS.index;return}if(window.CHAPTER_PAGE){location.href='/admin/chapters';return}killEditor();$('#modal').classList.remove('show')};
$('#modal').addEventListener('click',e=>{if(e.target.id=='modal')closeM()});
document.addEventListener('keydown',e=>{if(e.key=='Escape'&&!window.CHAPTER_PAGE&&!window.TEST_PAGE)closeM()});

function chapterModal(id){
 location.href=id?`/admin/chapters/${id}/edit`:'/admin/chapters/create';
}
function openChapterPage(html){
 killEditor();$('#page').innerHTML=`<div class="head"><a class="btn" href="/admin/chapters">← Back to chapters</a></div><div class="card chapter-page">${html}</div>`;
}
function renderChapterForm(id){
 const c=S.chapters.find(x=>x.id==id)||{title:'',subject:'',lessons:1,status:'draft',desc:''};
 openChapterPage(`<div class="dh"><h3>${id?'Edit chapter':'Add chapter'}</h3></div>
 <div class="db"><div class="f"><label>Chapter title</label><input id="c_t" value="${esc(c.title)}" placeholder="e.g. Quadratic Equations"></div>
 <div class="two"><div class="f"><label>Subject</label><input id="c_s" list="subs" value="${esc(c.subject)}" placeholder="e.g. Maths"><datalist id="subs">${[...new Set(S.chapters.map(x=>x.subject))].map(s=>`<option>${esc(s)}`).join('')}</datalist></div>
 <div class="f"><label>Number of lessons</label><input id="c_l" type="number" min="1" value="${c.lessons}"></div></div>
 <div class="f"><label for="c_cat">History category (History chapters only)</label><select id="c_cat"><option value="">Select category</option>${['Ancient History','Medieval History','Modern History'].map(category=>`<option ${c.category===category?'selected':''}>${esc(category)}</option>`).join('')}</select></div><div class="f"><label>Description</label><textarea id="c_d" rows="3" placeholder="What will learners study in this chapter?">${esc(c.desc||'')}</textarea></div>
 <div class="f"><label for="c_mt">Meta title</label><input id="c_mt" maxlength="255" value="${esc(c.meta_title||'')}" placeholder="SEO title (ideally 50–60 characters)"></div><div class="f"><label for="c_md">Meta description</label><textarea id="c_md" maxlength="1000" rows="3" placeholder="A short search result description">${esc(c.meta_description||'')}</textarea></div><div class="f"><label for="c_mk">Meta keywords</label><input id="c_mk" maxlength="1000" value="${esc(c.meta_keywords||'')}" placeholder="Comma-separated keywords"></div><div class="f"><label>Chapter content</label><textarea id="c_c">${esc(c.content||'')}</textarea></div>
 <div class="f"><label>Status</label><select id="c_st"><option value="published" ${c.status=='published'?'selected':''}>Published</option><option value="draft" ${c.status=='draft'?'selected':''}>Draft</option></select></div></div>
 <div class="df"><button class="btn" onclick="closeM()">Cancel</button><button class="btn pri" onclick="saveChapter(${id||0})">${id?'Save changes':'Add chapter'}</button></div>`,true);
 initEditor();
}
function initEditor(){
 if(!window.ClassicEditor){$('#c_c').style.minHeight='220px';return toast('Editor could not load. Check your internet connection.')}
 ClassicEditor.create($('#c_c'),{placeholder:'Write the full chapter content here...',
  toolbar:['heading','|','bold','italic','underline','link','|','bulletedList','numberedList','outdent','indent','|','blockQuote','insertTable','mediaEmbed','|','undo','redo'],
  mediaEmbed:{previewsInData:true}}).then(e=>{editor=e}).catch(console.error);
}
function viewChapter(id){
 location.href=`/admin/chapters/${id}`;
}
function renderChapterPreview(id){
 const c=S.chapters.find(x=>x.id==id);
 openChapterPage(`<div class="dh"><div><h3>${esc(c.title)}</h3><small style="color:var(--mute)">${esc(c.subject)} · ${c.lessons} lessons</small></div></div>
 <div class="db content">${c.content||'<div class="empty">No content added yet. Edit this chapter to write its content.</div>'}</div>
 <div class="df"><button class="btn" onclick="closeM()">Close</button><button class="btn pri" onclick="chapterModal(${id})">Edit content</button></div>`,true);
}
async function saveChapter(id){
 const title=$('#c_t').value.trim(),subject=$('#c_s').value.trim();
 if(!title||!subject)return toast('Enter a title and subject');
 const d={title,subject,category:subject.toLowerCase()==='history'?$('#c_cat').value:null,lessons:+$('#c_l').value||1,desc:$('#c_d').value.trim(),content:editor?editor.getData():$('#c_c').value,status:$('#c_st').value,meta_title:$('#c_mt').value.trim(),meta_description:$('#c_md').value.trim(),meta_keywords:$('#c_mk').value.trim()};
 if(id)Object.assign(S.chapters.find(c=>c.id==id),d);else S.chapters.push({id:nid(S.chapters),...d});
 if(!await save()){return;}location.href='/admin/chapters';
}
async function delChapter(id){
 if(!confirm('Delete this chapter and its tests?'))return;
 S.chapters=S.chapters.filter(c=>c.id!=id);S.tests=S.tests.filter(t=>t.ch!=id);if(!await save()){rerender();return;}rerender();toast('Chapter deleted');
}

let draft=[];
function qHTML(){
 return draft.map((q,i)=>`<div class="q"><div class="qt"><input placeholder="Question ${i+1}" value="${esc(q.q)}" oninput="draft[${i}].q=this.value"><button class="btn sm dng" onclick="draft.splice(${i},1);$('#qs').innerHTML=qHTML()" aria-label="Remove question">${ic('trash')}</button></div>
 ${q.o.map((o,j)=>`<div class="opt"><input type="radio" name="r${i}" ${q.c==j?'checked':''} onchange="draft[${i}].c=${j}" title="Correct answer"><input type="text" placeholder="Option ${j+1}" value="${esc(o)}" oninput="draft[${i}].o[${j}]=this.value"></div>`).join('')}<div class="f"><label>Answer explanation</label><textarea placeholder="Explain the correct answer" oninput="draft[${i}].explanation=this.value">${esc(q.explanation||'')}</textarea></div></div>`).join('')||'<div class="empty" style="padding:20px">No questions yet.</div>';
}
function addQ(){draft.push({q:'',o:['','','',''],c:0});$('#qs').innerHTML=qHTML()}
function testModal(id){
 location.href=id?window.TEST_URLS.edit[id]:window.TEST_URLS.create;
}
function renderTestForm(id){
 const t=S.tests.find(x=>x.id==id)||{ch:S.chapters[0]?.id,title:'',dur:15,pass:40,qs:[]};
 draft=JSON.parse(JSON.stringify(t.qs));
 $('#page').innerHTML=`<div class="head"><a class="btn" href="${esc(window.TEST_URLS.index)}">← Back to tests</a></div><div class="card chapter-page"><div class="dh"><h3>${id?'Edit test':'Create test'}</h3></div>
 <div class="db"><div class="two"><div class="f"><label>Chapter</label><select id="t_c">${S.chapters.map(c=>`<option value="${c.id}" ${c.id==t.ch?'selected':''}>${esc(c.title)}</option>`).join('')}</select></div>
 <div class="f"><label>Test title</label><input id="t_t" value="${esc(t.title)}" placeholder="e.g. Chapter 1 Quiz"></div>
 <div class="f"><label>Duration (minutes)</label><input id="t_d" type="number" min="1" value="${t.dur}"></div>
 <div class="f"><label>Passing score (%)</label><input id="t_p" type="number" min="1" max="100" value="${t.pass}"></div></div>
 <div style="display:flex;justify-content:space-between;align-items:center;margin:6px 0 12px"><b>Questions</b><button class="btn sm" onclick="addQ()">${ic('plus')}Add question</button></div>
 <p style="color:var(--mute);font-size:12.5px;margin-bottom:10px">Select the radio button next to the correct option.</p><div id="qs">${qHTML()}</div></div>
 <div class="df"><button class="btn" onclick="closeM()">Cancel</button><button class="btn pri" onclick="saveTest(${id||0})">${id?'Save changes':'Create test'}</button></div></div>`;
}
async function saveTest(id){
 const title=$('#t_t').value.trim();if(!title)return toast('Enter a test title');
 if(!draft.length)return toast('Add at least one complete question');
 if(draft.some(q=>!q.q.trim()||!q.o.every(o=>o.trim())))return toast('Complete every question and all four options');
 const qs=draft;
 const d={ch:+$('#t_c').value,title,dur:+$('#t_d').value||15,pass:+$('#t_p').value||40,qs};
 if(id)Object.assign(S.tests.find(t=>t.id==id),d);else S.tests.push({id:nid(S.tests),attempts:0,avg:0,...d});
 if(!await save()){return;}location.href=window.TEST_URLS.index;
}
async function delTest(id){if(!confirm('Delete this test?'))return;S.tests=S.tests.filter(t=>t.id!=id);if(!await save()){rerender();return;}rerender();toast('Test deleted')}
async function toggleBlock(id){const u=S.users.find(x=>x.id==id);u.status=u.status=='blocked'?'active':'blocked';if(!await save()){rerender();return;}rerender();toast(u.status=='blocked'?'User blocked':'User unblocked')}

/* ---------- router ---------- */
let cur=window.ADMIN_PAGE;
function rerender(focusId){
 $('#page').innerHTML=VIEWS[cur]();
 if(focusId){const el=document.getElementById(focusId);el.focus();el.setSelectionRange(el.value.length,el.value.length)}
}
function go(v){
 cur=v;$('#title').textContent=NAV.find(n=>n[0]==v)[1];
 $('#nav').innerHTML=NAV.map(n=>`<button class="${n[0]==v?'on':''}" onclick="location.href='/admin/${n[0]}'">${ic(n[2])}${n[1]}</button>`).join('');
 $('#side').classList.remove('open');if(!['settings','practice'].includes(v))rerender();scrollTo(0,0);
}
$('#burger').onclick=()=>$('#side').classList.toggle('open');
go(window.ADMIN_PAGE);
if(window.TEST_PAGE){
 $('#title').textContent=window.TEST_PAGE.id?'Edit test & questions':'Add test & questions';
 renderTestForm(window.TEST_PAGE.id);
}
if(window.CHAPTER_PAGE){
 const chapterId=window.CHAPTER_PAGE.id;
 $('#title').textContent=window.CHAPTER_PAGE.mode==='show'?'View chapter':chapterId?'Edit chapter':'Add chapter';
 if(window.CHAPTER_PAGE.mode==='show')renderChapterPreview(chapterId);else renderChapterForm(chapterId);
}
