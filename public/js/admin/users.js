window.ADMIN_VIEWS=window.ADMIN_VIEWS||{};window.ADMIN_VIEWS.users=function users(){
 const q=(window._uq||'').toLowerCase(),st=window._us||'';
 const rows=S.users.filter(u=>(u.name+u.email).toLowerCase().includes(q)&&(!st||u.status==st));
 return `<div class="head"><div><h2>Users</h2><p>${S.users.length} registered learners</p></div></div>
 <div class="tools"><div class="search"><svg class="ic" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg><input id="uq" placeholder="Search by name or email" value="${esc(window._uq||'')}" oninput="window._uq=this.value;rerender('uq')"></div>
 <select onchange="window._us=this.value;rerender()">${['','active','inactive','blocked'].map(s=>`<option value="${s}" ${s==st?'selected':''}>${s?s[0].toUpperCase()+s.slice(1):'All statuses'}</option>`).join('')}</select></div>
 <div class="card tc"><div class="tw"><table><thead><tr><th>User</th><th>Joined</th><th>Progress</th><th>Avg score</th><th>Status</th><th></th></tr></thead><tbody>
 ${rows.map(u=>`<tr><td><div class="cell"><div class="av">${ini(u.name)}</div><div><b>${esc(u.name)}</b><small>${esc(u.email)}</small></div></div></td><td>${fdate(u.joined)}</td>
 <td><div class="cell"><div class="bar" style="flex:1"><i style="width:${u.done/Math.max(1,S.chapters.length)*100}%"></i></div><small>${u.done}/${S.chapters.length}</small></div></td><td><b>${u.score}%</b></td><td>${sb(u.status)}</td>
 <td><div class="acts"><button class="btn sm" onclick="toggleBlock(${u.id})">${u.status=='blocked'?'Unblock':'Block'}</button></div></td></tr>`).join('')}</tbody></table>
 ${rows.length?'':'<div class="empty">No users match your search.</div>'}</div></div>`;
};
