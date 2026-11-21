document.addEventListener('DOMContentLoaded',()=>{
  const header=document.getElementById('site-header');
  const toggle=document.getElementById('menu-toggle');
  const mobile=document.getElementById('mobile-nav');
  const setMenu=open=>{if(!toggle||!mobile)return;mobile.classList.toggle('hidden',!open);toggle.setAttribute('aria-expanded',String(open));toggle.setAttribute('aria-label',open?'Close menu':'Open menu');toggle.querySelector('i')?.classList.replace(open?'fa-bars':'fa-xmark',open?'fa-xmark':'fa-bars');};
  toggle?.addEventListener('click',()=>{setMenu(toggle.getAttribute('aria-expanded')!=='true');setSearch(false);});
  window.matchMedia('(min-width: 1024px)').addEventListener('change',event=>{if(event.matches)setMenu(false);});
  const searchToggle=document.querySelector('[data-search-toggle]');
  const searchPanel=document.getElementById('site-search');
  const setSearch=open=>{if(!searchToggle||!searchPanel)return;searchPanel.classList.toggle('hidden',!open);searchToggle.setAttribute('aria-expanded',String(open));if(open)searchPanel.querySelector('input')?.focus();};
  searchToggle?.addEventListener('click',()=>{setSearch(searchToggle.getAttribute('aria-expanded')!=='true');setMenu(false);});
  // Hover opens the Services menu on desktop; the button also toggles it for touch and keyboard users.
  const dropdowns=[...document.querySelectorAll('[data-dropdown]')];
  const closeDropdowns=except=>dropdowns.forEach(dropdown=>{if(dropdown===except)return;dropdown.classList.remove('is-open');dropdown.querySelector('button')?.setAttribute('aria-expanded','false');});
  dropdowns.forEach(dropdown=>{const button=dropdown.querySelector('button');button?.addEventListener('click',()=>{const open=!dropdown.classList.contains('is-open');closeDropdowns(dropdown);dropdown.classList.toggle('is-open',open);button.setAttribute('aria-expanded',String(open));});});
  document.addEventListener('click',event=>{if(!event.target.closest('[data-dropdown]'))closeDropdowns();});
  document.addEventListener('keydown',event=>{
    if(event.key!=='Escape')return;
    if(toggle?.getAttribute('aria-expanded')==='true'){setMenu(false);toggle.focus();}
    if(searchToggle?.getAttribute('aria-expanded')==='true'){setSearch(false);searchToggle.focus();}
    const openDropdown=dropdowns.find(dropdown=>dropdown.classList.contains('is-open'));
    if(openDropdown){closeDropdowns();openDropdown.querySelector('button')?.focus();}
  });
  const backToTop=document.getElementById('back-to-top');
  const onScroll=()=>{const y=window.scrollY;header?.classList.toggle('is-scrolled',y>8);backToTop?.classList.toggle('is-visible',y>600);};
  window.addEventListener('scroll',onScroll,{passive:true});onScroll();
  backToTop?.addEventListener('click',event=>{event.preventDefault();window.scrollTo({top:0,behavior:window.matchMedia('(prefers-reduced-motion: reduce)').matches?'auto':'smooth'});document.getElementById('main')?.focus({preventScroll:true});});
  // Bag quantities save as soon as they change; the "Update bag" button remains for no-JS visitors.
  document.querySelectorAll('form[data-auto-submit]').forEach(form=>form.querySelectorAll('input[type="number"]').forEach(input=>input.addEventListener('change',()=>{if(input.checkValidity())form.requestSubmit(form.querySelector('[data-update-button]'));})));
  document.querySelectorAll('[data-toggle-password]').forEach(button=>button.addEventListener('click',()=>{
    const input=button.parentElement.querySelector('input');
    const show=input.type==='password';
    input.type=show?'text':'password';
    button.setAttribute('aria-pressed',String(show));
    button.setAttribute('aria-label',show?'Hide password':'Show password');
    button.querySelector('i')?.classList.replace(show?'fa-eye':'fa-eye-slash',show?'fa-eye-slash':'fa-eye');
  }));
  // Service CTAs pre-select the form option. If the form is on another page the link just navigates there.
  document.querySelectorAll('[data-fill-select]').forEach(link=>link.addEventListener('click',event=>{
    const select=document.querySelector(link.dataset.fillSelect);
    const target=link.hash?document.querySelector(link.hash):null;
    if(!select||!target)return;
    event.preventDefault();
    if(link.dataset.fillValue)select.value=link.dataset.fillValue;
    target.scrollIntoView({behavior:window.matchMedia('(prefers-reduced-motion: reduce)').matches?'auto':'smooth'});
    select.focus({preventScroll:true});
  }));
  if('IntersectionObserver' in window){
    document.documentElement.classList.add('reveal-ready');
    const observer=new IntersectionObserver(entries=>entries.forEach(entry=>{if(entry.isIntersecting){entry.target.classList.add('is-visible');observer.unobserve(entry.target);}}),{rootMargin:'0px 0px -8% 0px'});
    document.querySelectorAll('main > section').forEach(section=>observer.observe(section));
  }
  const search=document.getElementById('search');
  const suggestions=document.getElementById('search-suggestions');
  if(search&&suggestions){
    let timer;
    search.addEventListener('input',()=>{
      clearTimeout(timer);
      timer=setTimeout(async()=>{
        const query=search.value.trim();
        if(query.length<2){suggestions.replaceChildren();suggestions.classList.add('hidden');return;}
        try{
          const response=await fetch(`${window.YUCCA.base}/api/search.php?q=${encodeURIComponent(query)}`);
          const result=await response.json();
          suggestions.replaceChildren();
          for(const item of result.results||[]){const link=document.createElement('a');link.className='block border-b border-black/5 px-4 py-3 text-sm hover:bg-sand';link.href=`${window.YUCCA.base}/product-details.php?slug=${encodeURIComponent(item.slug)}`;link.textContent=item.colour?`${item.name} · ${item.colour}`:item.name;suggestions.appendChild(link);}
          suggestions.classList.toggle('hidden',suggestions.childElementCount===0);
        }catch(error){suggestions.replaceChildren();suggestions.classList.add('hidden');notify('Live search is temporarily unavailable.',true);}
      },180);
    });
    document.addEventListener('click',event=>{if(!search.contains(event.target)&&!suggestions.contains(event.target))suggestions.classList.add('hidden');});
  }
  const heroSlides=[...document.querySelectorAll('[data-hero-slide]')];
  if(heroSlides.length>1){
    let active=0,paused=window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const count=document.querySelector('[data-hero-count]');
    const render=()=>{heroSlides.forEach((slide,index)=>slide.classList.toggle('opacity-60',index===active));heroSlides.forEach((slide,index)=>slide.classList.toggle('opacity-0',index!==active));if(count)count.textContent=`${String(active+1).padStart(2,'0')} / ${String(heroSlides.length).padStart(2,'0')}`;};
    const next=()=>{active=(active+1)%heroSlides.length;render();};
    document.querySelector('[data-hero-prev]')?.addEventListener('click',()=>{active=(active+heroSlides.length-1)%heroSlides.length;render();});
    document.querySelector('[data-hero-next]')?.addEventListener('click',next);
    const pause=document.querySelector('[data-hero-toggle]');
    if(pause){pause.setAttribute('aria-pressed',String(paused));pause.textContent=paused?'Play':'Pause';}
    pause?.addEventListener('click',()=>{paused=!paused;pause.setAttribute('aria-pressed',String(paused));pause.textContent=paused?'Play':'Pause';});
    setInterval(()=>{if(!paused)next();},6000);
  }
  document.querySelectorAll('form[data-ajax-form]').forEach(form=>form.addEventListener('submit',async event=>{
    event.preventDefault();
    const button=form.querySelector('button[type="submit"],button:not([type])');
    if(button)button.disabled=true;
    try{const response=await fetch(form.action,{method:'POST',headers:{'X-CSRF-Token':window.YUCCA.csrf,'Accept':'application/json'},body:new FormData(form)});const result=await response.json();if(!response.ok)throw new Error(result.error||'Please check the form and try again.');notify(result.message||'Thank you.');form.reset();if(form.dataset.reload==='true')window.location.reload();}
    catch(error){notify(error.message,true);}finally{if(button)button.disabled=false;}
  }));
  document.querySelectorAll('[data-add-cart]').forEach(button=>button.addEventListener('click',async()=>{
    const form=new FormData();form.append('action','add');form.append('product_id',button.dataset.addCart);form.append('quantity',document.querySelector('[name="quantity"]')?.value||1);
    button.disabled=true;
    try{const response=await fetch(`${window.YUCCA.base}/api/cart.php`,{method:'POST',headers:{'X-CSRF-Token':window.YUCCA.csrf},body:form});const result=await response.json();if(!response.ok)throw new Error(result.error||'Unable to update your bag.');const count=document.getElementById('cart-count');if(count)count.textContent=result.count;notify(result.message||'Added to your bag.');}
    catch(error){notify(error.message,true);}finally{button.disabled=false;}
  }));
  document.querySelectorAll('[data-add-wishlist],[data-remove-wishlist]').forEach(button=>button.addEventListener('click',async()=>{
    const form=new FormData();form.append('action',button.hasAttribute('data-remove-wishlist')?'remove':'add');form.append('product_id',button.dataset.addWishlist||button.dataset.removeWishlist);
    try{const response=await fetch(`${window.YUCCA.base}/api/wishlist.php`,{method:'POST',headers:{'X-CSRF-Token':window.YUCCA.csrf},body:form});const result=await response.json();if(!response.ok)throw new Error(result.error||'Unable to update favourites.');notify(result.message);if(button.hasAttribute('data-remove-wishlist'))button.closest('.relative')?.remove();}
    catch(error){notify(error.message,true);}
  }));
  document.querySelectorAll('[data-confirm]').forEach(form=>form.addEventListener('submit',event=>{if(!confirm(form.dataset.confirm)){event.preventDefault();}}));
});
function notify(message,isError=false){const toast=document.createElement('div');toast.className='toast';toast.style.background=isError?'#8f4038':'#3F5320';toast.setAttribute('role',isError?'alert':'status');toast.textContent=message;document.body.appendChild(toast);setTimeout(()=>toast.remove(),3500);}
