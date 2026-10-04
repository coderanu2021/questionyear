window.ADMIN_VIEWS=window.ADMIN_VIEWS||{};window.ADMIN_VIEWS.analytics=function analytics(){
 const t=totals(),pass=Math.round(S.users.filter(u=>u.score>=50).length/(S.users.length||1)*100);
 const dist=[['0-40',S.users.filter(u=>u.score<40).length],['40-60',S.users.filter(u=>u.score>=40&&u.score<60).length],['60-80',S.users.filter(u=>u.score>=60&&u.score<80).length],['80-100',S.users.filter(u=>u.score>=80).length]];
 const top=[...S.users].sort((a,b)=>b.score-a.score).slice(0,5);
 return `<div class="head"><div><h2>Analytics</h2><p>Complete picture of engagement, learning and test results.</p></div></div>
 <div class="grid g4" style="margin-bottom:18px">
  ${stat('Active users',S.users.filter(u=>u.status=='active').length,'','#2f4bd8','#eaeefe','users')}
  ${stat('Pass rate',pass+'%','','#12a36b','#e3f6ee','quiz')}
  ${stat('Avg score',t.avg+'%','','#d98a0b','#fdf2dc','chart')}
  ${stat('Completion',Math.round(S.users.reduce((a,u)=>a+u.done,0)/Math.max(1,S.users.length*S.chapters.length)*100)+'%','','#d64545','#fce8e8','book')}
 </div>
 <div class="grid g2" style="margin-bottom:18px">
  <div class="card"><h3>New signups</h3><p class="sub">Last 7 weeks</p>${line(S.weeks.signups,WEEKS,'#12a36b')}</div>
  <div class="card"><h3>Score distribution</h3><p class="sub">Users by average score</p>${dist.map(([n,v])=>`<div class="row"><div class="n" style="width:70px">${n}%</div><div class="bar"><i style="width:${v/(S.users.length||1)*100}%"></i></div><div class="v">${v}</div></div>`).join('')}</div>
 </div>
 <div class="grid g2e">
  <div class="card"><h3>Chapter performance</h3><p class="sub">Average test score per chapter</p>${S.chapters.map(c=>{const ts=S.tests.filter(t=>t.ch==c.id),a=ts.length?Math.round(ts.reduce((s,t)=>s+t.avg,0)/ts.length):0;return `<div class="row"><div class="n">${esc(c.title)}</div><div class="bar ok"><i style="width:${a}%"></i></div><div class="v">${a?a+'%':'—'}</div></div>`}).join('')}</div>
  <div class="card"><h3>Top performers</h3><p class="sub">Highest average score</p>${top.map((u,i)=>`<div class="row"><div class="av">${ini(u.name)}</div><div class="n" style="flex:1">${esc(u.name)}<small style="display:block;color:var(--mute);font-weight:400">${u.done} chapters done</small></div><div class="v" style="width:auto"><span class="badge b-ok">${u.score}%</span></div></div>`).join('')}</div>
 </div>`;
};
