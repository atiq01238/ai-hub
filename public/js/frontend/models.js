(() => {
 const panel=document.querySelector('[data-model-filters]'), overlay=document.querySelector('[data-model-overlay]');
 const toggle=(on)=>{panel?.classList.toggle('open',on);overlay?.classList.toggle('open',on);document.body.style.overflow=on?'hidden':''};
 document.querySelector('[data-model-filter-open]')?.addEventListener('click',()=>toggle(true));
 document.querySelector('[data-model-filter-close]')?.addEventListener('click',()=>toggle(false)); overlay?.addEventListener('click',()=>toggle(false));
 const grid=document.querySelector('[data-model-grid]'); document.querySelectorAll('[data-model-view]').forEach(btn=>btn.addEventListener('click',()=>{document.querySelectorAll('[data-model-view]').forEach(b=>b.classList.remove('active'));btn.classList.add('active');grid?.classList.toggle('list-view',btn.dataset.modelView==='list')}));
})();
