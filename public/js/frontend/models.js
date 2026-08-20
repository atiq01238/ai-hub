
(() => {
 const panel=document.querySelector('[data-model-filters]'), overlay=document.querySelector('[data-model-overlay]');
 const toggle=(on)=>{panel?.classList.toggle('open',on);overlay?.classList.toggle('open',on);document.body.style.overflow=on?'hidden':''};
 document.querySelector('[data-model-filter-open]')?.addEventListener('click',()=>toggle(true));
 document.querySelector('[data-model-filter-close]')?.addEventListener('click',()=>toggle(false));
 overlay?.addEventListener('click',()=>toggle(false));
 document.addEventListener('keydown',e=>{if(e.key==='Escape')toggle(false)});

 const grid=document.querySelector('[data-model-grid]');
 const buttons=[...document.querySelectorAll('[data-model-view]')];
 const isCompact=()=>window.matchMedia('(max-width: 900px)').matches;
 const setView=(mode, persist=true)=>{
   if(!grid)return;
   const resolved=isCompact() ? 'grid' : mode;
   grid.classList.toggle('list-view',resolved==='list');
   buttons.forEach(b=>b.classList.toggle('active',b.dataset.modelView===resolved));
   if(persist && !isCompact()){
     try{localStorage.setItem('aihub-model-view',resolved)}catch(_){}
   }
 };
 let preferred='grid';
 try{preferred=localStorage.getItem('aihub-model-view')||'grid'}catch(_){}
 setView(preferred,false);
 buttons.forEach(btn=>btn.addEventListener('click',()=>setView(btn.dataset.modelView)));
 window.addEventListener('resize',()=>setView((()=>{try{return localStorage.getItem('aihub-model-view')||'grid'}catch(_){return 'grid'}})(),false));
})();
