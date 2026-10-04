window.ADMIN_VIEWS=window.ADMIN_VIEWS||{};window.ADMIN_VIEWS.dashboard=function dashboard(){
 const t=totals(),act=S.users.filter(u=>u.status=='active').length;
 return `<div class="head"><div><h2>Welcome back, Admin</h2><p>Here's how your learners are doing this month.</p></div><button class="btn pri" onclick="chapterModal()">${ic('plus')}Add chapter</button></div>
 <div class="grid g4" style="margin-bottom:18px">
  ${stat('Total users',S.users.length,'','#2f4bd8','#eaeefe','users')}
  ${stat('Chapters',S.chapters.length,'','#12a36b','#e3f6ee','book')}
  ${stat('Test attempts',t.att.toLocaleString(),'','#d98a0b','#fdf2dc','quiz')}
  ${stat('Average score',t.avg+'%','','#d64545','#fce8e8','chart')}
 </div>
 <div class="grid g2" style="margin-bottom:18px">
  <div class="card"><h3>Test attempts</h3><p class="sub">Last 7 weeks</p>${line(S.weeks.attempts,WEEKS)}</div>
  <div class="card"><h3>User status</h3><p class="sub">${act} active right now</p>${donut([['Active',act,'#12a36b'],['Inactive',S.users.filter(u=>u.status=='inactive').length,'#d98a0b'],['Blocked',S.users.filter(u=>u.status=='blocked').length,'#d64545']])}</div>
 </div>
 <div class="card tc"><div style="padding:18px 20px 6px"><h3>Recently joined</h3></div><div class="tw"><table><thead><tr><th>User</th><th>Joined</th><th>Chapters done</th><th>Status</th></tr></thead><tbody>
 ${[...S.users].sort((a,b)=>b.joined.localeCompare(a.joined)).slice(0,5).map(u=>`<tr><td><div class="cell"><div class="av">${ini(u.name)}</div><div><b>${esc(u.name)}</b><small>${esc(u.email)}</small></div></div></td><td>${fdate(u.joined)}</td><td>${u.done}/${S.chapters.length}</td><td>${sb(u.status)}</td></tr>`).join('')}</tbody></table></div></div>`;
};
