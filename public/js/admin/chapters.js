window.ADMIN_VIEWS=window.ADMIN_VIEWS||{};window.ADMIN_VIEWS.chapters=function chapters(){
 const q=(window._cq||'').toLowerCase(),sub=window._cs||'';
 const subs=[...new Set(S.chapters.map(c=>c.subject))];
 const rows=S.chapters.filter(c=>c.title.toLowerCase().includes(q)&&(!sub||c.subject==sub));
 return `<div class="head"><div><h2>Chapters</h2><p>${S.chapters.length} chapters across ${subs.length} subjects</p></div><button class="btn pri" onclick="chapterModal()">${ic('plus')}Add chapter</button></div>
 <div class="tools"><div class="search"><svg class="ic" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg><input id="cq" placeholder="Search chapters" value="${esc(window._cq||'')}" oninput="window._cq=this.value;rerender('cq')"></div>
 <select onchange="window._cs=this.value;rerender()"><option value="">All subjects</option>${subs.map(s=>`<option ${s==sub?'selected':''}>${esc(s)}</option>`).join('')}</select></div>
 <div class="card tc"><div class="tw"><table><thead><tr><th>Chapter</th><th>Subject</th><th>Lessons</th><th>Tests</th><th>Status</th><th></th></tr></thead><tbody>
 ${rows.map(c=>`<tr><td><div class="cell"><div class="av" style="border-radius:10px">${c.id}</div><div><b>${esc(c.title)}</b><small>${esc((c.desc||'').slice(0,50))}</small></div></div></td><td><span class="badge b-info">${esc(c.subject)}</span></td><td>${c.lessons}</td><td>${S.tests.filter(t=>t.ch==c.id).length}</td><td>${sb(c.status)}</td>
 <td><div class="acts"><button class="btn sm" onclick="viewChapter(${c.id})">View</button><button class="btn sm" onclick="chapterModal(${c.id})">${ic('edit')}Edit</button><button class="btn sm dng" onclick="delChapter(${c.id})">${ic('trash')}</button></div></td></tr>`).join('')||''}</tbody></table>
 ${rows.length?'':'<div class="empty">No chapters found. Add your first chapter to get started.</div>'}</div></div>`;
};
