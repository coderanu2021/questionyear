const el=id=>document.getElementById(id);
el('sb').onclick=()=>{el('mega').classList.toggle('open');el('sb').setAttribute('aria-expanded',el('mega').classList.contains('open'))};
el('mb').onclick=()=>el('nav').classList.toggle('show');
el('th').onclick=()=>{const theme=document.documentElement.dataset.theme==='dark'?'light':'dark';document.documentElement.dataset.theme=theme;localStorage.setItem('qh-theme',theme)};
document.documentElement.dataset.theme=localStorage.getItem('qh-theme')||'light';
if(window.CURRENT_USER){if(el('gs'))el('gs').hidden=true;el('si').hidden=true;el('um').hidden=false;el('uav').textContent=window.CURRENT_USER.name[0];el('unm').textContent=window.CURRENT_USER.name;el('uwn').textContent=window.CURRENT_USER.name;el('uwe').textContent=window.CURRENT_USER.email;el('umb').onclick=()=>el('umd').hidden=!el('umd').hidden;el('lo').onclick=()=>{const form=document.createElement('form');form.method='POST';form.action='/account/logout';const input=document.createElement('input');input.name='_token';input.value=document.querySelector('meta[name="csrf-token"]').content;form.appendChild(input);document.body.appendChild(form);form.submit()};}
