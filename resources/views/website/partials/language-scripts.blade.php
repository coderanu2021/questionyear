<script>
window.WEBSITE_LANGUAGE={{ Illuminate\Support\Js::from(app()->getLocale()) }};
window.WEBSITE_TRANSLATIONS={{ Illuminate\Support\Js::from(app()->getLocale() === 'hi' ? App\HindiContent::dictionary() : []) }};
window.websiteText=function(key,values={}){let text=window.WEBSITE_TRANSLATIONS[key]||key;for(const [name,value] of Object.entries(values))text=text.replaceAll(':'+name,String(value));return text;};
if(window.WEBSITE_LANGUAGE==='hi'){
    const translateNode=root=>{
        const translateText=node=>{
            if(node.parentElement?.closest('script,style,textarea,[data-no-translate]'))return;
            const source=node.nodeValue.trim();
            let translated=window.WEBSITE_TRANSLATIONS[source];
            if(!translated){
                translated=source.replace(/(\d+) (questions and answers|practice questions|questions|chapters|sections|minutes|min read)\b/g,(_,count,key)=>count+' '+window.websiteText(key));
                translated=translated.replace(/\b(Chapter|SECTION) (\d+)/g,(_,key,count)=>window.websiteText(key)+' '+count);
            }
            if(translated&&translated!==source)node.nodeValue=node.nodeValue.replace(source,translated);
        };
        if(root.nodeType===Node.TEXT_NODE){translateText(root);return;}
        if(root.nodeType!==Node.ELEMENT_NODE||root.closest('script,style,textarea,[data-no-translate]'))return;
        const walker=document.createTreeWalker(root,NodeFilter.SHOW_TEXT);
        while(walker.nextNode())translateText(walker.currentNode);
        [root,...root.querySelectorAll('[placeholder],[aria-label],[alt],[title]')].forEach(element=>{
            for(const attribute of ['placeholder','aria-label','alt','title']){
                const source=element.getAttribute(attribute);
                const translated=window.WEBSITE_TRANSLATIONS[source];
                if(translated&&translated!==source)element.setAttribute(attribute,translated);
            }
        });
    };
    document.addEventListener('DOMContentLoaded',()=>{
        translateNode(document.body);
        new MutationObserver(records=>records.forEach(record=>{
            if(record.type==='characterData')translateNode(record.target);
            else record.addedNodes.forEach(translateNode);
        })).observe(document.body,{childList:true,subtree:true,characterData:true});
    });
}
</script>
