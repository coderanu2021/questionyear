<script>
window.WEBSITE_LANGUAGE={{ Illuminate\Support\Js::from(app()->getLocale()) }};
window.WEBSITE_TRANSLATIONS={{ Illuminate\Support\Js::from(app()->getLocale() === 'hi' ? json_decode(file_get_contents(resource_path('lang/hi.json')), true, flags: JSON_THROW_ON_ERROR) : []) }};
window.websiteText=function(key,values={}){let text=window.WEBSITE_TRANSLATIONS[key]||key;for(const [name,value] of Object.entries(values))text=text.replaceAll(':'+name,String(value));return text;};
</script>
