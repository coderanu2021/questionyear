const el=id=>document.getElementById(id);
el('sb').onclick=()=>{el('mega').classList.toggle('open');el('sb').setAttribute('aria-expanded',el('mega').classList.contains('open'))};
el('mb').onclick=()=>el('nav').classList.toggle('show');
el('th').onclick=()=>{const theme=document.documentElement.dataset.theme==='dark'?'light':'dark';document.documentElement.dataset.theme=theme;localStorage.setItem('qh-theme',theme)};
document.documentElement.dataset.theme=localStorage.getItem('qh-theme')||'light';
if(window.CURRENT_USER){if(el('gs'))el('gs').hidden=true;el('si').hidden=true;el('um').hidden=false;el('uav').textContent=window.CURRENT_USER.name[0];el('unm').textContent=window.CURRENT_USER.name;el('uwn').textContent=window.CURRENT_USER.name;el('uwe').textContent=window.CURRENT_USER.email;el('umb').onclick=()=>el('umd').hidden=!el('umd').hidden;el('lo').onclick=()=>{const form=document.createElement('form');form.method='POST';form.action='/account/logout';const input=document.createElement('input');input.name='_token';input.value=document.querySelector('meta[name="csrf-token"]').content;form.appendChild(input);document.body.appendChild(form);form.submit()};}

document.querySelectorAll('.blog-content table').forEach(table=>{
    const wrapper=document.createElement('div');
    wrapper.className='blog-table';
    wrapper.tabIndex=0;
    wrapper.setAttribute('role','region');
    wrapper.setAttribute('aria-label','Article table: scroll horizontally to view all columns');
    table.before(wrapper);
    wrapper.appendChild(table);
});

const articleHeadings=[...document.querySelectorAll('.blog-content h2,.blog-content h3')];
const articleContents=document.getElementById('blog-toc-list');
if(articleContents&&articleHeadings.length){
    articleHeadings.forEach((heading,index)=>{
        heading.id=`article-section-${index+1}`;
        const item=document.createElement('li');
        if(heading.tagName==='H3')item.className='blog-toc-subsection';
        const link=document.createElement('a');
        link.href=`#${heading.id}`;
        link.textContent=heading.textContent;
        item.appendChild(link);
        articleContents.appendChild(item);
    });
    articleContents.closest('.blog-toc').hidden=false;
    const links=[...articleContents.querySelectorAll('a')];
    const updateActiveSection=()=>{
        let activeIndex=0;
        articleHeadings.forEach((heading,index)=>{if(heading.getBoundingClientRect().top<=150)activeIndex=index;});
        links.forEach((link,index)=>{
            if(index===activeIndex)link.setAttribute('aria-current','location');
            else link.removeAttribute('aria-current');
        });
    };
    let sectionUpdatePending=false;
    window.addEventListener('scroll',()=>{
        if(sectionUpdatePending)return;
        sectionUpdatePending=true;
        requestAnimationFrame(()=>{updateActiveSection();sectionUpdatePending=false;});
    },{passive:true});
    updateActiveSection();
}
