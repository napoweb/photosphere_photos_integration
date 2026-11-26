/* Photo Sphere 0.4.64 – account-scoped overview options. */
(() => {
 'use strict';
 const start=()=>{
  const root=document.getElementById('ppi-tours-app');
  if(!root || !root.dataset.globalSettingsUrl) return;
  const by=id=>document.getElementById(id);
  const modal=by('ppi-global-settings-backdrop');
  const gear=by('ppi-global-settings-open');
  if(!modal||!gear)return;
  // Render above every Nextcloud stacking context, preserving the underlying tab.
  document.body.append(modal);
  const defaults={socialPreview:'cover',tileLayout:'adaptive',tileSort:'original'};
  let settings={...defaults};let previousFocus=null;let busy=false;
  const readForm=()=>({socialPreview:modal.querySelector('input[name="ppi-global-social"]:checked')?.value||'cover',tileLayout:modal.querySelector('input[name="ppi-global-layout"]:checked')?.value||'adaptive',tileSort:by('ppi-global-sort').value});
  const fillForm=()=>{
   for(const [name,value] of [['ppi-global-social',settings.socialPreview],['ppi-global-layout',settings.tileLayout]]){
    const radio=[...modal.querySelectorAll('input[type=radio]')].find(el=>el.name===name&&el.value===value);
    if(radio)radio.checked=true;
   }
   by('ppi-global-sort').value=settings.tileSort;
  };
  function sortTiles(list){
   if(!list)return;
   const tiles=[...list.querySelectorAll(':scope > .ppi-overview-tile')];
   if(tiles.length<2)return;
   const collator=new Intl.Collator(undefined,{numeric:true,sensitivity:'base'});
   const expected=[...tiles].sort((a,b)=>{
    const mode=settings.tileSort;
    if(mode==='original')return Number(a.dataset.ppiOriginalIndex||0)-Number(b.dataset.ppiOriginalIndex||0);
    if(mode==='name_asc')return collator.compare(a.dataset.ppiName||'',b.dataset.ppiName||'');
    if(mode==='name_desc')return collator.compare(b.dataset.ppiName||'',a.dataset.ppiName||'');
    const aDate=a.dataset.ppiModified||'', bDate=b.dataset.ppiModified||'';
    return mode==='newest'?bDate.localeCompare(aDate):aDate.localeCompare(bDate);
   });
   if(expected.every((el,i)=>el===tiles[i]))return;
   list.replaceChildren(...expected);
  }
  function apply(){
   root.classList.toggle('ppi-global-compact',settings.tileLayout==='compact');
   for(const id of ['ppi-tour-list','ppi-start-pages-list'])sortTiles(by(id));
  }
  // Editor refresh rebuilds the lists asynchronously; sorting follows each rebuild.
  for(const id of ['ppi-tour-list','ppi-start-pages-list']){
   const list=by(id);if(!list)continue;
   let pending=false;
   new MutationObserver(()=>{
    if(pending)return;
    pending=true;
    requestAnimationFrame(()=>{pending=false;apply();});
   }).observe(list,{childList:true});
  }
  async function api(method='GET',payload=null){
   const headers={Accept:'application/json'};
   const token=window.OC?.requestToken||document.querySelector('head[data-requesttoken]')?.dataset.requesttoken;
   if(token)headers.requesttoken=token;
   if(payload)headers['Content-Type']='application/x-www-form-urlencoded;charset=UTF-8';
   const resp=await fetch(root.dataset.globalSettingsUrl,{method,headers,credentials:'same-origin',body:payload?new URLSearchParams(payload).toString():undefined});
   const raw=await resp.text();
   let result;
   try{result=JSON.parse(raw);}catch(_){throw Error('Serverantwort ist kein JSON (HTTP '+resp.status+').');}
   if(!resp.ok||!result.success)throw Error(result.message||'Einstellungen konnten nicht geladen werden (HTTP '+resp.status+').');
   return result.settings;
  }
  const status=by('ppi-global-settings-status');
  const show=async()=>{
   previousFocus=document.activeElement;
   gear.disabled=true;
   try{settings={...defaults,...await api()};fillForm();status.textContent='';modal.hidden=false;document.body.style.overflow='hidden';by('ppi-global-settings-close').focus();}
   catch(e){status.textContent=e.message;alert('Globale Einstellungen: '+e.message);}
   finally{gear.disabled=false;}
  };
  const close=()=>{if(busy)return;modal.hidden=true;document.body.style.removeProperty('overflow');previousFocus?.focus?.();};
  gear.addEventListener('click',show);
  by('ppi-global-settings-close').addEventListener('click',close);
  modal.addEventListener('click',e=>{if(e.target===modal)close();});
  modal.addEventListener('keydown',e=>{
   if(e.key==='Escape'){e.preventDefault();close();return;}
   if(e.key!=='Tab')return;
   const items=[...modal.querySelectorAll('button,input,select')].filter(el=>!el.disabled);
   if(!items.length)return;
   if(e.shiftKey&&document.activeElement===items[0]){e.preventDefault();items.at(-1).focus();}
   if(!e.shiftKey&&document.activeElement===items.at(-1)){e.preventDefault();items[0].focus();}
  });
  by('ppi-global-settings-save').addEventListener('click',async()=>{
   if(busy)return;
   busy=true;const button=by('ppi-global-settings-save');button.disabled=true;status.textContent='Speichere …';
   try{settings={...defaults,...await api('POST',readForm())};apply();status.textContent='Gespeichert';setTimeout(()=>{busy=false;close();},400);}
   catch(e){status.textContent='Fehler: '+e.message;busy=false;}
   finally{button.disabled=false;}
  });
  // The original app hides the create buttons when inside an editor.
  // Mirror that state so the gear is shown in BOTH overviews, not in editors.
  const updateGear=()=>{
   const tour=by('ppi-tour-create'),page=by('ppi-start-page-create');
   const shouldHide=!!(tour?.hidden && page?.hidden);
   if(gear.hidden!==shouldHide)gear.hidden=shouldHide;
  };
  // Observe only the two create buttons, not the gear itself (avoid mutation feedback loop).
  for(const button of [by('ppi-tour-create'),by('ppi-start-page-create')]){
   if(button)new MutationObserver(updateGear).observe(button,{attributes:true,attributeFilter:['hidden']});
  }
  updateGear();
  api().then(data=>{settings={...defaults,...data};apply();}).catch(e=>console.warn('Photo Sphere: global settings unavailable',e));
 };
 if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',start,{once:true});else start();
})();
