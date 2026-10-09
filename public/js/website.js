const tr=(key,values={})=>window.websiteText(key,values);
async function api(url,data){const response=await fetch(url,{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content},body:JSON.stringify(data)});const result=await response.json();if(!response.ok){const error=Error(result.message||'Request failed');error.code=result.code;throw error}return result;}
const SITE_TITLE=window.SITE_TITLE||"questionyear";const S=window.CURRICULUM.S;
const CH=`<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>`;
const CC={"Humanities":"#0078d4","Science":"#107c10","Maths & Tech":"#8661c5","General":"#ca5010"};
const slug=n=>n.toLowerCase().replace(/&/g,"").replace(/[^a-z0-9]+/g,"-").replace(/^-|-$/g,"");
const SUB=S.map(x=>({n:x[0],cat:x[1],d:x[2],ch:x[3],c:CC[x[1]],id:slug(x[0])}));
const subjectQuizzes=x=>x.ch.flatMap((_,i)=>window.CURRICULUM.tests[x.id+":"+i]||[]);
const cats=["All",...new Set(SUB.map(x=>x.cat))];let cat="All",term="";
const gr=document.getElementById("gr"),fl=document.getElementById("fl"),em=document.getElementById("em");
function esc(t){return String(t??"").replace(/[&<>"']/g,c=>({"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#39;"}[c]))}
function mono(n){const w=n.replace("&","").split(/\s+/).filter(Boolean);return w.length>1?(w[0][0]+w[1][0]).toUpperCase():n.slice(0,2)}
function render(){
  fl.innerHTML=cats.map(c=>`<button class="chip" aria-pressed="${c===cat}" data-c="${c}">${esc(tr(c))}</button>`).join("");
  const r=SUB.filter(x=>(cat==="All"||x.cat===cat)&&(x.n+" "+tr(x.n)).toLowerCase().includes(term));
  gr.innerHTML=r.map(x=>`<a href="/subject/${x.id}" class="card" style="--c:${x.c}"><div class="card-h"><div class="ic">${mono(x.n)}</div><div><small>${esc(tr(x.cat))}</small><h3>${esc(tr(x.n))}</h3></div></div><p>${esc(x.d)}</p><div class="card-f"><span>${["current-affairs","general-knowledge"].includes(x.id)?subjectQuizzes(x).length+" quizzes":x.ch.length+" chapters"}</span><b>View ${["current-affairs","general-knowledge"].includes(x.id)?"quizzes":"chapters"} ${CH}</b></div></a>`).join("");
  em.style.display=r.length?"none":"block";
}
fl.onclick=e=>{const b=e.target.closest("[data-c]");if(b){cat=b.dataset.c;render()}};
document.getElementById("q").oninput=e=>{term=e.target.value.trim().toLowerCase();render();if(term)document.getElementById("subjects").scrollIntoView()};
render();
const sb=document.getElementById("sb"),mg=document.getElementById("mega"),nav=document.getElementById("nav");
sb.onclick=()=>{nav.classList.remove("show");const o=mg.classList.toggle("open");sb.setAttribute("aria-expanded",o)};
document.querySelectorAll("[data-s]").forEach(a=>a.onclick=()=>{const i=document.getElementById("q");i.value=a.dataset.s;term=a.dataset.s;render()});
document.addEventListener("click",e=>{if(!e.target.closest("header")){mg.classList.remove("open");sb.setAttribute("aria-expanded","false")}});
document.addEventListener("keydown",e=>{if(e.key==="Escape"){mg.classList.remove("open");sb.setAttribute("aria-expanded","false")}});
document.getElementById("mb").onclick=()=>{mg.classList.remove("open");sb.setAttribute("aria-expanded","false");nav.classList.toggle("show")};
document.getElementById("th").onclick=()=>{
  const r=document.documentElement,d=matchMedia("(prefers-color-scheme:dark)").matches;
  const cur=r.dataset.theme||(d?"dark":"light");r.dataset.theme=cur==="dark"?"light":"dark";
  try{localStorage.setItem("qh-theme",r.dataset.theme)}catch(e){}};
try{const t=localStorage.getItem("qh-theme");if(t)document.documentElement.dataset.theme=t}catch(e){}
document.querySelectorAll("[data-n]").forEach(el=>{
  const n=+el.dataset.n,t0=performance.now();
  (function f(t){const p=Math.min((t-t0)/1200,1);el.textContent=Math.round(n*p).toLocaleString();if(p<1)requestAnimationFrame(f)})(t0)});
const Q=[
[tr("History"),"Who was the first Emperor of the Mauryan Empire?",["Ashoka","Chandragupta Maurya","Bindusara","Harsha"],1,"Chandragupta Maurya founded the empire around 321 BCE."],
[tr("Geography"),"Which is the longest river in Africa?",["Congo","Niger","Nile","Zambezi"],2,"The Nile flows about 6,650 km to the Mediterranean."],
[tr("Science"),"What is the chemical symbol for sodium?",["So","Na","Sd","S"],1,"Na comes from the Latin word natrium."]];
let i=0,sc=0;
const $=id=>document.getElementById(id),dn=$("dn");
function load(){
  const q=Q[i];$("dq").textContent=`${tr("Question :number of :total",{number:i+1,total:Q.length})}`;$("dsub").textContent=q[0];$("dt").textContent=q[1];
  $("fb").textContent="";dn.hidden=true;
  $("do").innerHTML=q[2].map((o,k)=>`<button class="opt" data-k="${k}">${o}</button>`).join("");}
$("do").onclick=e=>{
  const b=e.target.closest(".opt");if(!b||b.disabled)return;
  const k=+b.dataset.k,q=Q[i],all=[...document.querySelectorAll(".opt")];
  all.forEach(x=>x.disabled=true);all[q[3]].classList.add("ok");
  if(k===q[3]){sc++}else b.classList.add("bad");
  $("fb").textContent=(k===q[3]?"Correct. ":"Not quite. ")+q[4];$("ds").textContent="Score: "+sc;
  dn.textContent=i<Q.length-1?tr("Next question"):tr("Try again");dn.hidden=false;};
dn.onclick=()=>{if(i<Q.length-1)i++;else{i=0;sc=0;$("ds").textContent="Score: 0"}load()};
load();
window.addEventListener("scroll",()=>document.querySelector("header").classList.toggle("scrolled",scrollY>4),{passive:true});
const dt=new Date();document.getElementById("dd").textContent=dt.getDate();
document.getElementById("dm").textContent=dt.toLocaleDateString(window.WEBSITE_LANGUAGE==="hi"?"hi-IN":"en-IN",{month:"long",year:"numeric"});
document.getElementById("dw").textContent=dt.toLocaleDateString(window.WEBSITE_LANGUAGE==="hi"?"hi-IN":"en-IN",{weekday:"long"});

/* ===== QUESTIONS: key = subjectId:chapterIndex ===== */
const QB=window.CURRICULUM.QB;const LN=window.CURRICULUM.LN;
const HT=document.title;let curSub=null,curId="",curIdx=0,qs=null,qi=0,qsc=0,qans=[],tmr=null,t0=0;
function linkify(sel){document.querySelectorAll(sel).forEach(a=>{const o=SUB.find(x=>(x.n===a.textContent.trim()||tr(x.n)===a.textContent.trim()));if(o)a.href="/subject/"+o.id;else if(sel===".mega a")a.href="/#subjects"})}
linkify(".mega a");linkify("footer .fcols>div:nth-child(2) a");

/* chapter list: opens the Learn page first */
function renderChaps(){
  const x=curSub,f=$("cf").value.trim().toLowerCase();
  if(["current-affairs","general-knowledge"].includes(x.id)){
    const quizzes=subjectQuizzes(x).filter(t=>t.title.toLowerCase().includes(f));
    $("cl").innerHTML=quizzes.length?quizzes.map(t=>{const parts=t.date_label?.split(" ");return `<a class="ca-edition" href="${esc(t.url)}"><div class="ca-edition-date">${parts?`<strong>${esc(parts[0])}</strong><span>${esc(parts[1])}</span>`:`<i class="fa-regular fa-file-lines" aria-hidden="true"></i>`}</div><div class="ca-edition-body"><h3>${esc(t.title)}</h3><small>${t.count} questions and answers${t.date_label?` · ${esc(t.date_label)}`:""}</small></div><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>`}).join(""):`<p class="empty" style="display:block">${tr("No quizzes match your search.")}</p>`;
    return;
  }
  const it=x.ch.map((t,i)=>({t,i})).filter(o=>o.t.toLowerCase().includes(f)&&(!$("chapter-category").value||window.CURRICULUM.categories[x.id+":"+o.i]===$("chapter-category").value));
  $("cl").innerHTML=it.length?it.map(({t,i})=>{const b=QB[x.id+":"+i],n=LN[x.id+":"+i];
    return (n||b)?`<a class="chap" style="--c:${x.c}" href="${n?esc(window.CURRICULUM.chapterUrls[x.id+":"+i]):`${esc(window.CURRICULUM.quizUrls[x.id+":"+i])}`}"><span class="cn">${i+1}</span><div><h3>${esc(t)}</h3><small>${n?tr("Chapter notes"):tr("Practice quiz")}${b?" + "+b.length+" practice questions":""}</small></div><span class="go"><i class="fa-solid fa-book-open"></i> ${n?tr("Read chapter"):tr("Start quiz")}</span></a>`
    :`<div class="chap off" style="--c:${x.c}"><span class="cn">${i+1}</span><div><h3>${esc(t)}</h3><small>${tr("Notes and questions are being added")}</small></div><span class="soon">${tr("Coming soon")}</span></div>`}).map((markup,k)=>{const index=it[k].i;const tests=window.CURRICULUM.tests[x.id+":"+index]||[];return markup+tests.map(t=>`<a class="chap" href="${esc(t.url)}"><span class="cn"><i class="fa-solid fa-play"></i></span><div><h3>${esc(t.title)}</h3><small>${t.quiz_date?`<time datetime="${esc(t.quiz_date)}">${esc(t.date_label)}</time> · `:""}${t.count} questions · ${t.duration} minutes · Pass ${t.pass}%</small></div></a>`).join("")}).join(""):`<p class="empty" style="display:block">${tr("No chapters match your search.")}</p>`;
}
function subjectPage(x){
  const quizOnly=["current-affairs","general-knowledge"].includes(x.id);
  curSub=x;document.title=window.PAGE_SEO_TITLE||x.n+(quizOnly?" quizzes – ":" chapters – ")+SITE_TITLE;
  $("subject-list-title").textContent=quizOnly?tr("Quizzes"):tr("Chapters");
  $("cf").placeholder=quizOnly?tr("Search quizzes"):tr("Search chapters");
  $("cf").setAttribute("aria-label",$("cf").placeholder);
  $("sbc").textContent=tr(x.n);$("sn").textContent=tr(x.n);$("sd").textContent=x.d;
  const ic=$("sic");ic.textContent=mono(x.n);ic.style.setProperty("--c",x.c);
  const ready=x.ch.filter((_,i)=>LN[x.id+":"+i]||QB[x.id+":"+i]).length;
  $("ss").innerHTML=quizOnly?`<div><b>${subjectQuizzes(x).length}</b>quizzes</div>`:`<div><b>${x.ch.length}</b>chapters</div><div><b>${ready}</b>ready to learn and practice</div>`;
  $("chapter-categories").hidden=x.id!=="history";$("chapter-category").value="";
  $("cf").value="";renderChaps();
  $("sl").innerHTML=SUB.map(o=>`<a href="/subject/${o.id}" class="${o.id===x.id?"on":""}" style="--c:${o.c}"><i></i>${esc(tr(o.n))}</a>`).join("");
}
$("cf").oninput=renderChaps;
$("chapter-category").onchange=renderChaps;

/* learn page: book-style chapter */
const CO={tip:["fa-lightbulb",tr("Exam tip")],key:["fa-key",tr("Key point")],note:["fa-circle-info",tr("Did you know?")]};
function learnPage(x,i){
  const d=LN[x.id+":"+i],t=x.ch[i],q=QB[x.id+":"+i];
  document.title=window.PAGE_SEO_TITLE||window.CHAPTER_SEO_TITLE||t+" – "+x.n+" – "+SITE_TITLE;
  const secs=d.s.map((s,n)=>{
    const ps=s.p.map((p,k)=>/<\/?(?:p|h[1-6]|ul|ol|table|div|section|figure|blockquote)\b/i.test(p)?p:`<p${n===0&&k===0?' class="drop"':""}>${p}</p>`).join("");
    const tb=s.t?`<div class="bk-t"><table><thead><tr>${s.t.h.map(h=>`<th>${h}</th>`).join("")}</tr></thead><tbody>${s.t.r.map(r=>`<tr>${r.map(c=>`<td>${c}</td>`).join("")}</tr>`).join("")}</tbody></table></div>`:"";
    const c=s.n?`<div class="co co-${s.n.type}"><i class="fa-solid ${CO[s.n.type][0]}"></i><div><strong>${CO[s.n.type][1]}</strong>${s.n.x}</div></div>`:"";
    return `<section class="bk-s" id="bk${n}"><div class="bk-h"><span class="bk-no">SECTION ${String(n+1).padStart(2,"0")}</span><h2><i class="fa-solid ${s.i}"></i>${esc(s.h)}</h2></div>${ps}${tb}${c}</section>`}).join("");
  const sib=k=>LN[x.id+":"+k]?window.CURRICULUM.chapterUrls[x.id+":"+k]:null,pv=sib(i-1),nx=sib(i+1);
  $("learn").innerHTML=`<div class="rp"><i id="rpi"></i></div>
  <div class="bk-hero"><div class="wrap"><div class="crumb"><a href="/">${tr("Home")}</a><span>/</span><a href="/subject/${x.id}">${esc(tr(x.n))}</a><span>/</span><b>${esc(t)}</b></div>
  <span class="bk-lab">Chapter ${i+1} &middot; ${esc(tr(x.n))}</span><h1>${esc(t)}</h1><p class="bk-sub">${esc(d.sub)}</p>
  <div class="bk-meta"><span><i class="fa-regular fa-clock"></i>${esc(d.time)}</span><span><i class="fa-solid fa-list"></i>${d.s.length} sections</span>${q?`<span><i class="fa-solid fa-circle-question"></i>${q.length} practice questions</span>`:""}</div></div><i class="fa-solid ${d.icon} bk-wm"></i></div>
  <div class="wrap bk-lay"><article class="bk-art">${secs}
  <div class="bk-sum"><h2><i class="fa-solid fa-list-check"></i>${tr("Chapter summary")}</h2><ul>${d.sum.map(z=>`<li><i class="fa-solid fa-circle-check"></i><span>${z}</span></li>`).join("")}</ul></div>
  ${q?`<div class="band bk-cta"><div><h2><i class="fa-solid fa-pen-to-square"></i> Ready to test yourself?</h2><p>${q.length} practice questions based on this chapter.</p></div><a class="btn btn-o" href="${esc(window.CURRICULUM.quizUrls[x.id+":"+i])}" style="height:46px;padding:0 26px">${tr("Start practice quiz")}</a></div>`:""}
  <div class="bk-nav">${pv?`<a href="${pv}"><small><i class="fa-solid fa-arrow-left"></i> Previous</small><b>${esc(x.ch[i-1])}</b></a>`:"<span></span>"}${nx?`<a href="${nx}" style="text-align:right"><small>Next <i class="fa-solid fa-arrow-right"></i></small><b>${esc(x.ch[i+1])}</b></a>`:"<span></span>"}</div>
  </article>
  <aside class="bk-toc box"><h3><i class="fa-solid fa-book-open"></i> In this chapter</h3>${d.s.map((s,n)=>`<button data-go="bk${n}"><i class="fa-solid ${s.i}"></i>${esc(s.h)}</button>`).join("")}<button data-go="bkend"><i class="fa-solid fa-list-check"></i>${tr("Chapter summary")}</button>${q?`<a class="btn btn-o" href="${esc(window.CURRICULUM.quizUrls[x.id+":"+i])}" style="width:100%;margin-top:14px"><i class="fa-solid fa-play"></i> Practice quiz</a>`:""}</aside></div>`;
  const sm=document.querySelector(".bk-sum");if(sm)sm.id="bkend";
  const article=$("learn").querySelector(".bk-art");
  article.querySelectorAll("table").forEach(table=>{
    if(table.parentElement.closest("table"))return;
    let container=table.closest(".bk-t");
    if(!container){container=document.createElement("div");container.className="bk-t";table.before(container);container.append(table)}
    container.tabIndex=0;
    container.setAttribute("role","region");
    container.setAttribute("aria-label","Chapter table — scroll horizontally to see all columns");
    if(!table.tHead&&table.rows.length)table.rows[0].classList.add("bk-table-header");
  });
  article.querySelectorAll("h1").forEach(heading=>{const replacement=document.createElement("h2");replacement.innerHTML=heading.innerHTML;heading.replaceWith(replacement)});
  const toc=$("learn").querySelector(".bk-toc");
  toc.querySelectorAll("button[data-go]").forEach(button=>button.remove());
  const headings=[...article.querySelectorAll(".bk-s h2, .bk-s h3, .bk-s h4")];
  const contentHeadings=headings.filter(heading=>!heading.closest(".bk-h"));
  (contentHeadings.length?contentHeadings:headings).forEach((heading,index)=>{
    heading.id="chapter-heading-"+index;heading.style.scrollMarginTop="100px";
    const button=document.createElement("button");button.type="button";button.dataset.go=heading.id;
    const icon=document.createElement("i");icon.className="fa-solid fa-book";button.append(icon,document.createTextNode(heading.textContent.trim()));
    toc.insertBefore(button,toc.querySelector("a"));
  });
  if(d.sum.length){const button=document.createElement("button");button.type="button";button.dataset.go="bkend";button.textContent="Chapter summary";sm.style.scrollMarginTop="100px";toc.insertBefore(button,toc.querySelector("a"))}else if(sm){sm.remove()}
  requestAnimationFrame(upd);
}
function highlightChapterSection(id){
  $("learn").querySelectorAll(".bk-toc button[data-go]").forEach(button=>{
    if(button.dataset.go===id)button.setAttribute("aria-current","location");else button.removeAttribute("aria-current");
  });
}
function upd(){const L=$("learn"),b=$("rpi");if(!b||L.hidden)return;const a=document.querySelector(".bk-art");if(!a)return;
  const r=a.getBoundingClientRect(),h=r.height-innerHeight*.6;b.style.width=Math.max(0,Math.min(100,(-r.top+innerHeight*.2)/h*100))+"%";
  const headings=[...L.querySelectorAll(".bk-toc button[data-go]")].map(button=>document.getElementById(button.dataset.go)).filter(Boolean);
  if(!headings.length)return;
  const threshold=Math.max(120,document.querySelector("header").getBoundingClientRect().bottom+24);
  let current=headings[0];
  for(const heading of headings){if(heading.getBoundingClientRect().top<=threshold)current=heading;else break}
  if(scrollY+innerHeight>=document.documentElement.scrollHeight-2)current=headings[headings.length-1];
  highlightChapterSection(current.id);
}
let chapterScrollFrame=null;
function scheduleChapterUpdate(){if(chapterScrollFrame!==null)return;chapterScrollFrame=requestAnimationFrame(()=>{chapterScrollFrame=null;upd()})}
addEventListener("scroll",scheduleChapterUpdate,{passive:true});
addEventListener("resize",scheduleChapterUpdate);
$("learn").addEventListener("click",e=>{const b=e.target.closest("[data-go]");if(b){const el=document.getElementById(b.dataset.go);if(el){highlightChapterSection(el.id);history.replaceState(null,"","#"+el.id);el.scrollIntoView({behavior:"smooth"})}}});

const fmt=(sec=Math.round((Date.now()-t0)/1000))=>Math.floor(sec/60)+":"+String(sec%60).padStart(2,"0");
function quizSettings(){return (window.CURRICULUM.tests[curId+":"+curIdx]||[]).find(t=>t.id===window.QUIZ_IDS[curId+":"+curIdx])}
function questionTools(index,canExplain=false){
  if(!window.CURRENT_USER)return `<p><a href="/login">Sign in to save questions and report errors</a></p>`;
  const source='quiz:'+window.QUIZ_IDS[curId+":"+curIdx]+':'+index;
  return `<div class="learning-tools" data-source="${esc(source)}"><button type="button" class="btn btn-l" data-bookmark="1">${tr("Save question")}</button>${canExplain?`<button type="button" class="btn btn-l" data-explanation="en">${tr("English explanation")}</button><button type="button" class="btn btn-l" data-explanation="hi">हिंदी व्याख्या</button>`:''}<details><summary>${tr("Report wrong answer")}</summary><label>${tr("What needs correcting?")}<textarea data-report-reason rows="3" maxlength="2000"></textarea></label><button type="button" class="btn btn-l" data-report>${tr("Send report")}</button></details><p data-tool-message role="status" aria-live="polite"></p><p data-translated hidden></p></div>`;
}
function tick(){const remaining=Math.max(0,(quizSettings()?.duration||15)*60-Math.round((Date.now()-t0)/1000));const e=$("tm");if(e)e.textContent=tr("Time left :time",{time:fmt(remaining)});if(!remaining){for(let k=0;k<qs.length;k++)if(qans[k]===undefined)qans[k]=-1;results()}}
function drawQ(){
  if(!window.CURRENT_USER&&window.GUEST_QUESTIONS_USED>=25){accountGate();return}
  const q=qs[qi];
  $("qb").innerHTML=`<div class="qtop"><span>${tr("Question :number of :total",{number:qi+1,total:qs.length})}</span><span id="tm">${fmt()}</span></div><div class="prog"><i style="width:${qi/qs.length*100}%"></i></div><h2 class="qt">${esc(q[0])}</h2>`+q[1].map((o,k)=>`<button class="qo" data-k="${k}"><b>${"ABCD"[k]}</b>${esc(o)}</button>`).join("")+`<div id="qe">${questionTools(qi)}</div><div class="qf"><a href="/subject/${curId}" class="btn btn-l">${tr("Exit quiz")}</a><button class="btn btn-o" id="qn" hidden>${tr("Next question")}</button></div>`;
}
function accountGate(){clearInterval(tmr);$("qb").innerHTML=`<div class="res"><h2>${tr("Create an account to continue")}</h2><p>You have completed your 25 free guest questions. Log in or create a free account to solve more questions.</p><div class="cta2"><a class="btn btn-o" href="/register">${tr("Create free account")}</a><a class="btn btn-l" href="/login">${tr("Log in")}</a></div></div>`}
function startQuiz(){qi=0;qsc=0;qans=[];t0=Date.now();clearInterval(tmr);if(!window.CURRENT_USER&&window.GUEST_QUESTIONS_USED>=25){accountGate();return}tmr=setInterval(tick,1000);drawQ()}
function quizPage(x,i){
  curId=x.id;curIdx=i;qs=QB[x.id+":"+i];const settings=quizSettings(),t=["current-affairs","general-knowledge"].includes(x.id)?settings?.title||x.ch[i]:x.ch[i];document.title=window.PAGE_SEO_TITLE||window.CHAPTER_SEO_TITLE||t+" quiz – "+SITE_TITLE;
  const ln=LN[x.id+":"+i]?`<span>/</span><a href="/learn/${x.id}/${i}">${tr("Notes")}</a>`:"";
  $("qc").innerHTML=`<a href="/">${tr("Home")}</a><span>/</span><a href="/subject/${x.id}">${esc(tr(x.n))}</a>${ln}<span>/</span><b>${esc(t)}</b>${settings?.quiz_date?`<span>/</span><time datetime="${esc(settings.quiz_date)}">${esc(settings.date_label)}</time>`:''}`;
  if(["current-affairs","general-knowledge"].includes(x.id))initCurrentAffairsReader();else startQuiz();
}
function initCurrentAffairsReader(){
  const input=$("ca-search");if(!input||input.dataset.ready)return;input.dataset.ready="true";
  const questions=[...document.querySelectorAll("[data-ca-question]")],links=[...document.querySelectorAll("[data-ca-nav]")];
  const highlight=id=>links.forEach(link=>{if(link.dataset.caNav===id)link.setAttribute("aria-current","location");else link.removeAttribute("aria-current")});
  highlight(questions[0]?.dataset.caQuestion);
  links.forEach(link=>link.addEventListener("click",()=>highlight(link.dataset.caNav)));
  input.addEventListener("input",()=>{
    const term=input.value.trim().toLowerCase();let count=0;
    questions.forEach(question=>{const matches=(question.querySelector("h2").textContent+" "+question.querySelector("p").textContent).toLowerCase().includes(term);question.hidden=!matches;if(matches)count++;const link=links.find(link=>link.dataset.caNav===question.dataset.caQuestion);if(link)link.hidden=!matches});
    $("ca-result-count").textContent=count+(count===1?" question":" questions");$("ca-no-results").hidden=count>0;document.querySelector(".ca-end").hidden=count===0;
    highlight(questions.find(question=>!question.hidden)?.dataset.caQuestion);
  });
  if("IntersectionObserver" in window){const observer=new IntersectionObserver(entries=>{const visible=entries.find(entry=>entry.isIntersecting&&!entry.target.hidden);if(visible)highlight(visible.target.dataset.caQuestion)},{rootMargin:"-125px 0px -55% 0px"});questions.forEach(question=>observer.observe(question))}
}
async function grade(k){
 document.querySelectorAll("#qb .qo").forEach(b=>b.disabled=true);
 try{const answer=await api("/quizzes/"+window.QUIZ_IDS[curId+":"+curIdx]+"/answer",{question:qi,answer:k});qs[qi][2]=answer.correct;qs[qi][3]=answer.explanation;if(!window.CURRENT_USER)window.GUEST_QUESTIONS_USED=25-answer.guest_remaining;}catch(error){if(error.code==='ACCOUNT_REQUIRED'){accountGate();return}document.querySelectorAll("#qb .qo").forEach(b=>b.disabled=false);alert(error.message);return}
  const q=qs[qi],all=[...document.querySelectorAll("#qb .qo")],ok=k===q[2];
  all.forEach(b=>b.disabled=true);all[q[2]].classList.add("ok");if(!ok)all[k].classList.add("bad");
  qans[qi]=k;if(ok)qsc++;
  $("qe").innerHTML=`<div class="qe"><b>${ok?tr("Correct."):tr("Not quite.")}</b> ${esc(q[3])}</div>${questionTools(qi,true)}`;
  const n=$("qn");n.textContent=qi<qs.length-1?tr("Next question"):tr("See results");n.hidden=false;
}
async function results(){
 clearInterval(tmr);const next=$("qn");if(next)next.disabled=true;
 try{const result=await api("/quizzes/"+window.QUIZ_IDS[curId+":"+curIdx]+"/attempts",{answers:qans,seconds:Math.round((Date.now()-t0)/1000)});qsc=result.score;result.review.forEach((q,k)=>{qs[k][2]=q.c;qs[k][3]=q.explanation||''});}catch(error){if(error.code==="ACCOUNT_REQUIRED"){accountGate();return}if(next)next.disabled=false;alert(error.message);return}
  clearInterval(tmr);const pct=Math.round(qsc/qs.length*100);
  const msg=pct>=80?tr("Excellent work"):pct>=50?tr("Good effort"):tr("Keep practicing");
  const rev=LN[curId+":"+curIdx]?`<a class="btn btn-l" href="/learn/${curId}/${curIdx}">${tr("Revise notes")}</a>`:"";
  $("qb").innerHTML=`<div class="res"><div class="sb">${qsc}<span>/${qs.length}</span></div><h2>${msg}</h2><p class="sub" style="margin:0 auto 20px">${tr("You scored :score% in :time.",{score:pct,time:fmt()})}</p><div class="cta2"><button class="btn btn-o" id="qr">${tr("Try again")}</button>${rev}<a class="btn btn-l" href="/my-learning">Revise mistakes / Next 10 questions</a><a class="btn btn-l" href="/subject/${curId}">Back to ${["current-affairs","general-knowledge"].includes(curId)?"quizzes":"chapters"}</a></div></div><div class="rv"><h3>${tr("Review answers")}</h3>`+qs.map((q,i)=>{const ok=qans[i]===q[2];return `<div><div class="t">${i+1}. ${esc(q[0])}</div><span style="color:var(--${ok?"ok":"bad"});font-weight:600">${ok?tr("Correct"):esc(tr("Your answer: :answer",{answer:q[1][qans[i]]??tr("Not answered")}))}</span><div>${esc(tr("Correct answer: :answer",{answer:q[1][q[2]]}))}</div>${questionTools(i,true)}</div>`}).join("")+`</div>`;
}
$("qb").addEventListener("click",e=>{
  const o=e.target.closest(".qo");
  if(o){if(!o.disabled)grade(+o.dataset.k);return}
  if(e.target.closest("#qn")){qi++;qi<qs.length?drawQ():results();return}
  if(e.target.closest("#qr"))startQuiz();
});
let CU=window.CURRENT_USER,amode="login";
function field(id,label,icon,type,ph,ac){return `<label class="inp"><span>${esc(tr(label))}</span><div class="inw"><i class="fa-solid ${icon}"></i><input id="${id}" type="${type}" placeholder="${esc(tr(ph))}" autocomplete="${ac}">${type==="password"?`<button type="button" class="eye" data-eye aria-label="${esc(tr('Show password'))}"><i class="fa-regular fa-eye"></i></button>`:''}</div></label>`}
function authPage(mode){
  amode=mode;const L=mode==="login";
  document.title=window.PAGE_SEO_TITLE||(L?tr("Log in"):tr("Create account"))+" – "+SITE_TITLE;
  $("auth").innerHTML=`<div class="au"><div class="au-side"><div><h2>${tr("Learn. Practice. Improve.")}</h2><p>${L?"Welcome back! Pick up your practice right where you left off.":"Join "+esc(SITE_TITLE)+" for free and start practicing in minutes."}</p>
  <ul><li><i class="fa-solid fa-book-open"></i>Chapter notes written like a textbook</li><li><i class="fa-solid fa-circle-check"></i>Instant answers with explanations</li><li><i class="fa-solid fa-chart-line"></i>Track your score topic by topic</li><li><i class="fa-solid fa-fire"></i>Build a daily practice streak</li></ul></div></div>
  <div class="au-main"><div class="au-card"><h1>${L?esc(tr("Log in to :site",{site:SITE_TITLE})):tr("Create your account")}</h1><p class="lead">${L?tr("Enter your details to continue."):tr("It takes less than a minute.")}</p>
  <a class="btn btn-l au-go" href="${esc(window.GOOGLE_LOGIN_URL)}"><i class="fa-brands fa-google" aria-hidden="true"></i> Sign in with Google</a>
  <p class="sw">${tr("Or continue with email")}</p>
  ${L?'':`<p class="sw">By continuing with Google, you agree to the <a href="/terms">${tr("Terms of use")}</a> and <a href="/privacy">${tr("Privacy policy")}</a>.</p>`}
  <form id="af" novalidate>${L?"":field("an",tr("Full name"),"fa-user","text",tr("Your name"),"name")}${field("ae",tr("Email address"),"fa-envelope","email","you@example.com","email")}${field("ap",tr("Password"),"fa-lock","password",L?tr("Your password"):tr("At least 8 characters"),L?"current-password":"new-password")}
  ${L?`<div class="rowf"><label class="ck"><input type="checkbox" id="ar" checked> Keep me logged in</label><a href="/forgot-password">${tr("Forgot password?")}</a></div>`:`<label class="ck"><input type="checkbox" id="at"> I agree to the <a href="/terms">${tr("Terms of use")}</a> and <a href="/privacy">${tr("Privacy policy")}</a></label>`}
  <div class="msg" id="am" role="alert" aria-live="polite"></div>
  <button class="btn btn-l" id="verification-resend" type="button" hidden>${tr("Resend verification email")}</button>
  <button class="btn btn-o au-go" type="submit">${L?tr("Log in"):tr("Create account")}</button></form>
  <p class="sw">${L?`${esc(tr('New to :site?',{site:SITE_TITLE}))} <a href="/register">${tr('Create account')}</a>`:`${tr('Already have an account?')} <a href="/login">${tr('Log in')}</a>`}</p></div></div></div>`;

  if(L&&new URLSearchParams(location.search).get("verified")==="1")amsg("Email verified. You can now log in.",true);
  if(window.GOOGLE_LOGIN_ERROR)amsg(window.GOOGLE_LOGIN_ERROR);
}
function amsg(t,ok){const e=$("am");e.textContent=t;e.className="msg"+(t?(ok?" ok":" bad"):"")}
function renderUser(){
  const on=!!CU;
  ["si","gs","gb","msi"].forEach(id=>{const e=$(id);if(e)e.hidden=on});
  $("um").hidden=!on;if(!on){$("umd").hidden=true;return}
  const av=$("uav");av.textContent="";
  if(CU.pic){const im=new Image();im.src=CU.pic;im.alt="";im.referrerPolicy="no-referrer";av.appendChild(im)}else av.textContent=(CU.name||CU.email||"U").trim()[0].toUpperCase();
  $("unm").textContent=(CU.name||"").split(" ")[0];$("uwn").textContent=CU.name||"";$("uwe").textContent=CU.email||"";
}
async function logout(){await api('/account/logout',{});location.href='/';}
$("auth").addEventListener("click",e=>{
  const resend=e.target.closest("#verification-resend");
  if(resend){resendVerification(resend);return}
  const eye=e.target.closest("[data-eye]");
  if(eye){const i=eye.previousElementSibling,s=i.type==="password";i.type=s?"text":"password";eye.innerHTML=`<i class="fa-regular fa-eye${s?"-slash":""}"></i>`;return}
});
async function resendVerification(button){
  button.disabled=true;
  try{const result=await api('/email/verification-notification',{email:$("ae").value,password:$("ap").value});amsg(result.message,true)}catch(error){amsg(error.message)}finally{button.disabled=false}
}
$("auth").addEventListener("submit",async e=>{
  e.preventDefault();amsg("");$("verification-resend").hidden=true;
  try{
    if(amode==="register"&&!$("at").checked)throw Error("Please accept the terms.");
    const user=await api('/account/'+amode,{email:$("ae").value,password:$("ap").value,name:$("an")?.value,remember:$("ar")?.checked});
    if(user.code==="VERIFICATION_REQUIRED"){
      const email=$("ae").value,password=$("ap").value;
      authPage("login");$("ae").value=email;$("ap").value=password;amsg(user.message,true);$("verification-resend").hidden=false;return;
    }
    CU=user;location.href=user.redirect||'/';
  }catch(error){amsg(error.message);$("verification-resend").hidden=error.code!=="EMAIL_NOT_VERIFIED"}
});
$("umb").onclick=()=>{const d=$("umd");d.hidden=!d.hidden;$("umb").setAttribute("aria-expanded",!d.hidden)};
$("lo").onclick=logout;
document.addEventListener("click",e=>{if(!e.target.closest("#um"))$("umd").hidden=true});
renderUser();

function route(){
  const h="#"+location.pathname;let m=h.match(/^#\/(subject|quiz|learn)\/([^/]+)(?:\/(\d+))?$/);
  if(!m){const entry=Object.entries(window.CURRICULUM.chapterUrls).find(([key,url])=>new URL(url,location.origin).pathname===location.pathname);if(entry){const [subject,index]=entry[0].split(":");m=["","learn",subject,index]}}
  if(!m){const entry=Object.entries(window.CURRICULUM.quizUrls).find(([key,url])=>new URL(url,location.origin).pathname===location.pathname);if(entry){const [subject,index]=entry[0].split(":");m=["","quiz",subject,index]}}
  clearInterval(tmr);mg.classList.remove("open");sb.setAttribute("aria-expanded","false");nav.classList.remove("show");
  const P=$("home"),SP=$("subj"),QP=$("quiz"),LP=$("learn"),AP=$("auth");P.hidden=SP.hidden=QP.hidden=LP.hidden=AP.hidden=true;
  if(h==="#/login"||h==="#/register"){if(CU){location.href="/";return}authPage(h.slice(2));AP.hidden=false;scrollTo(0,0);return}
  const x=m&&SUB.find(o=>o.id===m[2]);
  if(x&&m[1]==="subject"){subjectPage(x);SP.hidden=false;scrollTo(0,0);return}
  if(x&&m[1]==="learn"&&LN[x.id+":"+m[3]]){learnPage(x,+m[3]);LP.hidden=false;scrollTo(0,0);return}
  if(x&&m[1]==="quiz"&&QB[x.id+":"+m[3]]){quizPage(x,+m[3]);QP.hidden=false;scrollTo(0,0);return}
  P.hidden=false;document.title=HT;
  const id=location.hash.replace(/^#\/?/,""),el=id&&document.getElementById(id);
  if(el)el.scrollIntoView();else scrollTo(0,0);
}
addEventListener("hashchange",route);route();
