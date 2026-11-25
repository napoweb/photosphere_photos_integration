/* Shared public share error display, used by tours and startpages. */
  #ppi-public-unavailable{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;
    box-sizing:border-box;padding:clamp(20px,5vw,48px);overflow:auto;z-index:20;
    background:radial-gradient(circle at 50% 30%,#17364d 0%,#0b2030 46%,#08121b 100%)}
  #ppi-public-unavailable .ppi-public-unavailable-card{width:min(100%,540px);box-sizing:border-box;
    padding:clamp(25px,5vw,48px);text-align:center;border:1px solid rgba(255,255,255,.2);
    background:rgba(8,20,32,.75);border-radius:20px;box-shadow:0 18px 60px rgba(0,0,0,.25)}
  #ppi-public-unavailable .ppi-public-unavailable-icon{font-size:48px;line-height:1.2;color:#cce6fa;margin-bottom:8px}
  #ppi-public-unavailable .ppi-public-unavailable-kicker{font-size:12px;font-weight:750;letter-spacing:.18em;color:#a7cae1}
  #ppi-public-unavailable h1{font:750 clamp(23px,4.5vw,32px)/1.25 system-ui,sans-serif;
    color:#fff;margin:14px 0 18px}
  #ppi-public-unavailable p{font:400 15px/1.65 system-ui,sans-serif;color:#d5e3ed;margin:0 0 12px}
/* The startpage has different default page padding, reset for identical error layout. */
#ppi-public-startpage:has(> #ppi-public-unavailable){padding:0!important;background:#08121b!important;overflow:hidden!important}
#ppi-public-startpage > #ppi-public-unavailable{position:absolute;inset:0;min-height:100%;}
