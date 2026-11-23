<?php
declare(strict_types=1);
$page = $_['page'] ?? [];
$invalid = !empty($_['invalid']);
$locked = !empty($_['passwordRequired']);
$layout = in_array($page['layout'] ?? 'hero', ['hero','split','gallery'], true) ? $page['layout'] : 'hero';
$effect = in_array(($page['designEffect'] ?? 'subtle'), ['subtle', 'glass', 'glossy'], true) ? $page['designEffect'] : 'subtle';
$columns = in_array((string)($page['tileColumns'] ?? 'auto'), ['auto','2','3','4','5'], true) ? (string)$page['tileColumns'] : 'auto';
$color = static fn($v,$fallback) => is_string($v) && preg_match('/^#[0-9a-fA-F]{6}$/D',$v) ? $v : $fallback;
$bg = $color($page['backgroundColor'] ?? null, '#000000');
$headerBg = $color($page['headerBackgroundColor'] ?? ($page['backgroundColor'] ?? null), $bg);
$heading = $color($page['headingColor'] ?? null, '#ffffff');
$description = $color($page['descriptionColor'] ?? null, '#ffffff');
$light = static fn($hex) => (299*hexdec(substr($hex,1,2)) + 587*hexdec(substr($hex,3,2)) + 114*hexdec(substr($hex,5,2))) >= 160000;
$headingStroke = $light($heading) ? 'rgba(0,0,0,.50)' : 'rgba(255,255,255,.68)';
$headingShadow = 'rgba(0,0,0,.40)';
$descriptionShadow = 'rgba(0,0,0,.20)';
$dividerColor = $light($headerBg) ? 'rgba(0,0,0,.30)' : 'rgba(255,255,255,.60)';
?>
<main id="ppi-public-startpage" data-layout="<?php p($layout); ?>" data-effect="<?php p($effect); ?>" data-cols="<?php p($columns); ?>"
 style="--page-bg:<?php p($bg); ?>;--header-bg:<?php p($headerBg); ?>;--title-color:<?php p($heading); ?>;--desc-color:<?php p($description); ?>;--title-shadow:<?php p($headingShadow); ?>;--title-stroke:<?php p($headingStroke); ?>;--desc-shadow:<?php p($descriptionShadow); ?>;--split-divider:<?php p($dividerColor); ?>;<?php if (!empty($_['backgroundUrl'])): ?>--bg-image:url('<?php p($_['backgroundUrl']); ?>');<?php endif; ?>">
<?php if ($invalid): ?>
 <?php $ppiShareKind = 'startpage'; include __DIR__ . '/public-share-unavailable.php'; ?>
<?php elseif ($locked): ?>
 <section class="ppi-public-start-message"><h1>Passwortgeschützte Startseite</h1>
 <p>Bitte gib das Passwort der Startseite ein.</p>
 <?php if (!empty($_['passwordError'])): ?><p role="alert">Passwort nicht korrekt.</p><?php endif; ?>
 <form action="<?php p($_['unlockUrl'] ?? ''); ?>" method="post">
  <label for="ppi-startpage-password">Passwort</label>
  <input id="ppi-startpage-password" name="password" type="password" required autocomplete="current-password" maxlength="128">
  <button type="submit">Startseite öffnen</button>
 </form></section>
<?php else: ?>
 <div class="ppi-public-start-inner">
  <header class="ppi-public-start-header">
   <div class="ppi-public-start-copy">
    <h1><?php p(($page['headline'] ?? '') ?: ($page['name'] ?? '360°-Startseite')); ?></h1>
    <?php if (!empty($page['description'])): ?><p><?php p($page['description']); ?></p><?php endif; ?>
   </div>
  </header>
  <section class="ppi-public-start-tour-section" aria-label="360°-Touren">
   <div class="ppi-public-start-grid-container">
   <div class="ppi-public-start-grid">
    <?php foreach ($_['tours'] ?? [] as $tour): ?>
     <a class="ppi-public-start-card" href="<?php p($tour['url']); ?>">
      <div class="ppi-public-start-card-image">
       <?php if (!empty($tour['image'])): ?>
        <img src="<?php p($tour['image']); ?>" alt="" loading="lazy" decoding="async">
       <?php else: ?><span aria-hidden="true">🌐</span><?php endif; ?>
      </div>
      <div class="ppi-public-start-card-content"><strong><?php p($tour['name']); ?></strong>
      <?php if (!empty($tour['description'])): ?><small><?php p($tour['description']); ?></small><?php endif; ?>
      <?php if (!empty($tour['locked'])): ?><small>🔒 Tour-Passwort erforderlich</small><?php endif; ?>
      </div>
     </a>
    <?php endforeach; ?>
    <?php if (empty($_['tours'])): ?>
     <p class="ppi-public-start-empty">Keine öffentlich freigegebenen Touren vorhanden. Gib zunächst eine der ausgewählten Touren frei.</p>
    <?php endif; ?>
   </div>
   </div>
  </section>
 </div>
<?php endif; ?>
</main>
<style>
html:has(#ppi-public-startpage),body:has(#ppi-public-startpage){margin:0!important}
#content:has(#ppi-public-startpage),#app-content:has(#ppi-public-startpage){padding:0!important;max-width:none!important}
#ppi-public-startpage{--bg-image:none;position:relative;z-index:2;box-sizing:border-box;min-height:100dvh;width:100%;padding:32px 20px;background-color:var(--page-bg);background-image:linear-gradient(#0005,#0005),var(--bg-image);background-size:cover;background-position:center;color:#fff;font-family:system-ui,Arial,sans-serif}
#ppi-public-startpage *{box-sizing:border-box}
.ppi-public-start-inner{max-width:1200px;margin:auto}
.ppi-public-start-header{text-align:center;padding:55px 12px 32px}
.ppi-public-start-copy{max-width:900px;margin:auto;padding:21px}
.ppi-public-start-copy h1{margin:0 0 14px;color:var(--title-color)!important;font-size:clamp(25px,4vw,45px);line-height:1.2;font-weight:750;overflow-wrap:anywhere;-webkit-text-stroke:.65px var(--title-stroke);paint-order:stroke fill;text-shadow:0 2px 4px var(--title-shadow)}
.ppi-public-start-copy p{margin:0;color:var(--desc-color)!important;font-size:clamp(14px,1.7vw,18px);white-space:pre-wrap;line-height:1.65;text-shadow:0 1px 2px var(--desc-shadow)}
#ppi-public-startpage[data-effect=glass] .ppi-public-start-copy{border:1px solid #ffffff77;border-radius:17px;background:#19273645;backdrop-filter:blur(12px) saturate(125%);-webkit-backdrop-filter:blur(12px) saturate(125%);box-shadow:0 12px 28px #0005,inset 0 1px 0 #ffffff33}
.ppi-public-start-tour-section{padding:10px 10px 32px}
.ppi-public-start-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:20px}
.ppi-public-start-card{display:flex;flex-direction:column;min-width:0;overflow:hidden;border:3px solid #fff;border-radius:13px;background:#fff!important;color:#192735!important;text-decoration:none!important;box-shadow:0 10px 30px #0009}
.ppi-public-start-card:hover,.ppi-public-start-card:focus-visible{transform:translateY(-2px);box-shadow:0 13px 32px #000b}
.ppi-public-start-card-image{display:grid;place-items:center;aspect-ratio:16/9;background:linear-gradient(130deg,#153f60,#162531);font-size:44px;color:#fff}
.ppi-public-start-card-image img{width:100%;height:100%;object-fit:cover}
.ppi-public-start-card-content{padding:13px 15px;flex:1}
.ppi-public-start-card-content strong{display:block;color:#192735!important;font-size:18px;font-weight:700}
.ppi-public-start-card-content small{display:block;color:#505c68!important;margin-top:6px;line-height:1.4}
#ppi-public-startpage[data-cols=auto] .ppi-public-start-card:only-child{grid-column:1/-1}
#ppi-public-startpage[data-cols=auto] .ppi-public-start-card:first-child:nth-last-child(3){grid-column:1/-1}
#ppi-public-startpage[data-cols="3"] .ppi-public-start-grid{grid-template-columns:repeat(3,minmax(0,1fr))}
#ppi-public-startpage[data-cols="4"] .ppi-public-start-grid{grid-template-columns:repeat(4,minmax(0,1fr))}
#ppi-public-startpage[data-cols="5"] .ppi-public-start-grid{grid-template-columns:repeat(5,minmax(0,1fr))}
#ppi-public-startpage[data-layout=split]{background-image:none!important;padding:0 0 24px}
#ppi-public-startpage[data-layout=split] .ppi-public-start-header{background-image:linear-gradient(#0005,#0005),var(--bg-image);background-color:var(--page-bg);background-size:cover;background-position:center;box-shadow:0 8px 20px #0006;padding:65px 22px 52px}
#ppi-public-startpage[data-layout=gallery]{background-image:none}
#ppi-public-startpage[data-layout=gallery][data-effect=glass]{background-image:linear-gradient(#0005,#0005),var(--bg-image)}
#ppi-public-startpage[data-layout=gallery] .ppi-public-start-header{padding:10px 12px}
.ppi-public-start-message{max-width:550px;margin:10vh auto;padding:30px;border:1px solid #ffffff66;border-radius:17px;background:#142536eb;box-shadow:0 12px 35px #0007}
.ppi-public-start-message h1{color:#fff}
.ppi-public-start-message form{display:grid;gap:10px}
.ppi-public-start-message input,.ppi-public-start-message button{padding:12px;border-radius:8px}
.ppi-public-start-empty{grid-column:1/-1;text-align:center;color:#fff}
@media(max-width:900px){#ppi-public-startpage[data-cols="4"] .ppi-public-start-grid,#ppi-public-startpage[data-cols="5"] .ppi-public-start-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:650px){#ppi-public-startpage{padding:14px 8px}#ppi-public-startpage[data-cols] .ppi-public-start-grid{grid-template-columns:1fr}#ppi-public-startpage[data-layout=split] .ppi-public-start-header{padding:38px 12px}}
@media(prefers-reduced-transparency:reduce){#ppi-public-startpage[data-effect=glass] .ppi-public-start-copy{backdrop-filter:none;-webkit-backdrop-filter:none;background:#142536eb}}

/* Public landing must not inherit Nextcloud guest login's narrow central column. */
html:has(#ppi-public-startpage),body:has(#ppi-public-startpage) {overflow:hidden!important}
#ppi-public-startpage {
  position:fixed!important;inset:0!important;z-index:11000!important;
  width:100vw!important;max-width:none!important;
  height:100dvh!important;min-height:100dvh!important;
  overflow-x:hidden!important;overflow-y:auto!important;overscroll-behavior:contain;
  padding:clamp(20px,3.6vw,52px) clamp(14px,3vw,46px)!important;
  background-attachment:scroll!important;
}
#ppi-public-startpage .ppi-public-start-inner {
  width:min(100%,1360px)!important;max-width:1360px!important;margin-inline:auto!important;
}
#ppi-public-startpage .ppi-public-start-header {
  padding:clamp(18px,5vh,56px) 0 clamp(24px,4vh,42px)!important;
}
#ppi-public-startpage .ppi-public-start-copy {
  width:min(100%,990px)!important;max-width:990px!important;
  padding:clamp(20px,3.5vw,38px) clamp(18px,4vw,48px)!important;
  margin-inline:auto!important;
}
#ppi-public-startpage .ppi-public-start-tour-section {padding:16px 0 48px!important}
#ppi-public-startpage .ppi-public-start-grid {gap:clamp(14px,2vw,25px)!important}
#ppi-public-startpage .ppi-public-start-card-content {padding:clamp(14px,1.8vw,22px)!important}
#ppi-public-startpage .ppi-public-start-card-image {aspect-ratio:16/9!important}
#ppi-public-startpage[data-layout=split] {padding:0!important}
#ppi-public-startpage[data-layout=split] .ppi-public-start-header {
  padding:clamp(45px,8vh,110px) max(18px,4vw) clamp(45px,7vh,85px)!important;
}
#ppi-public-startpage[data-layout=split] .ppi-public-start-tour-section {padding:35px clamp(14px,3vw,46px) 48px!important}
#ppi-public-startpage[data-layout=gallery] .ppi-public-start-header {padding:18px 0 24px!important}
@media(max-width:650px) {
  #ppi-public-startpage {padding:14px 10px!important}
  #ppi-public-startpage .ppi-public-start-copy {padding:18px 14px!important}
  #ppi-public-startpage .ppi-public-start-tour-section {padding:10px 0 30px!important}
  #ppi-public-startpage[data-layout=split] .ppi-public-start-header {padding:40px 12px 32px!important}
  #ppi-public-startpage[data-layout=split] .ppi-public-start-tour-section {padding:25px 10px!important}
}

/* 0.4.62.112: full-width split header plus the selected page color beneath. */
#ppi-public-startpage[data-layout=split] {
  background:var(--page-bg,#000000)!important;
  background-image:none!important;
}
#ppi-public-startpage[data-layout=split] .ppi-public-start-inner {
  background:transparent!important;
}
#ppi-public-startpage[data-layout=split] .ppi-public-start-header {
  box-sizing:border-box;
  width:100vw!important;max-width:none!important;
  margin-left:calc(50% - 50vw)!important;margin-right:calc(50% - 50vw)!important;
  background-color:var(--page-bg,#000000)!important;
  background-image:linear-gradient(rgba(0,0,0,.32),rgba(0,0,0,.32)),var(--bg-image)!important;
  background-position:center!important;background-size:cover!important;
  box-shadow:0 9px 20px rgba(0,0,0,.24)!important;
}
#ppi-public-startpage[data-layout=split] .ppi-public-start-tour-section {
  background:transparent!important;
}

/* 0.4.62.113: unify hero and tour alignment; add contrast-aware split seam. */
#ppi-public-startpage[data-layout=split] .ppi-public-start-copy{
 width:calc(100% - 2 * clamp(14px,3vw,46px))!important;
 max-width:calc(1360px - 2 * clamp(14px,3vw,46px))!important;
}
#ppi-public-startpage[data-layout=split] .ppi-public-start-header{
 border-bottom:1px solid var(--split-divider,rgba(255,255,255,.60))!important;
 box-shadow:0 8px 18px rgba(0,0,0,.24),0 1px 2px var(--split-divider,rgba(255,255,255,.60))!important
}
#ppi-public-startpage[data-effect=glossy] .ppi-public-start-copy{
 position:relative;isolation:isolate;overflow:hidden;
 border:1px solid rgba(255,255,255,.62)!important;border-radius:18px!important;
 background:rgba(24,39,54,.23)!important;
 backdrop-filter:blur(15px) saturate(140%);-webkit-backdrop-filter:blur(15px) saturate(140%);
 box-shadow:inset 0 1px 0 rgba(255,255,255,.60),inset 0 -1px 0 rgba(255,255,255,.10),0 13px 30px rgba(0,0,0,.30)!important
}
#ppi-public-startpage[data-effect=glossy] .ppi-public-start-copy::before{
 content:"";pointer-events:none;position:absolute;inset:0;z-index:0;
 background:linear-gradient(157deg,rgba(255,255,255,.28) 0%,rgba(255,255,255,.11) 25%,transparent 39%,transparent 70%,rgba(255,255,255,.07) 100%);
 border-radius:inherit
}
#ppi-public-startpage[data-effect=glossy] .ppi-public-start-copy::after{
 content:"";pointer-events:none;position:absolute;inset:0 0 auto;z-index:0;height:40%;
 background:linear-gradient(to bottom,rgba(255,255,255,.16),rgba(255,255,255,0));
 border-radius:inherit
}
#ppi-public-startpage[data-effect=glossy] .ppi-public-start-copy>h1,
#ppi-public-startpage[data-effect=glossy] .ppi-public-start-copy>p{
 position:relative;z-index:1
}
@media(max-width:650px){
 #ppi-public-startpage[data-layout=split] .ppi-public-start-copy{width:100%!important}
}
@media(prefers-reduced-transparency:reduce){
 #ppi-public-startpage[data-effect=glossy] .ppi-public-start-copy{
 backdrop-filter:none;-webkit-backdrop-filter:none;background:rgba(17,25,32,.87)!important
 }
}


/* 0.4.62.114: Vollbild uses the available screen width for all tours and
   aligns headline/glass edges to the same card grid edges. */
#ppi-public-startpage[data-layout=hero] .ppi-public-start-inner {
 width:100%!important;max-width:none!important;margin-inline:auto!important
}
#ppi-public-startpage[data-layout=hero] .ppi-public-start-header {
 width:100%!important;padding-inline:0!important
}
#ppi-public-startpage[data-layout=hero] .ppi-public-start-copy {
 width:100%!important;max-width:none!important;margin-inline:0!important;
 box-sizing:border-box
}
#ppi-public-startpage[data-layout=hero] .ppi-public-start-tour-section {
 width:100%!important;padding-inline:0!important
}
#ppi-public-startpage[data-layout=hero] .ppi-public-start-grid {
 width:100%!important
}

/* 0.4.62.115: gallery header aligned exactly with tour-card outer edges.
   The matte background is separated from the reflective glossy glass. */
#ppi-public-startpage[data-layout=gallery] .ppi-public-start-inner {
  width:min(100%,1360px)!important;
  max-width:1360px!important;
}
#ppi-public-startpage[data-layout=gallery] .ppi-public-start-header {
  width:100%!important;
  box-sizing:border-box!important;
  padding:16px 0 20px!important;
}
#ppi-public-startpage[data-layout=gallery] .ppi-public-start-copy {
  box-sizing:border-box!important;
  width:100%!important;
  max-width:none!important;
  margin:0!important;
}
#ppi-public-startpage[data-layout=gallery] .ppi-public-start-tour-section {
  width:100%!important;
  box-sizing:border-box!important;
  padding-left:0!important;
  padding-right:0!important;
}
#ppi-public-startpage[data-layout=gallery] .ppi-public-start-grid {
  width:100%!important;
}
#ppi-public-startpage[data-effect=glossy] {
  /* A quieter layer under the bright glass: background itself stays selectable. */
  background-blend-mode:multiply,normal;
}
#ppi-public-startpage[data-effect=glossy] .ppi-public-start-copy {
  background:rgba(10,24,37,.15)!important;
  border:1px solid rgba(255,255,255,.80)!important;
  box-shadow:inset 0 2px 0 rgba(255,255,255,.72),
    inset 1px 0 0 rgba(255,255,255,.32),
    inset -1px 0 0 rgba(255,255,255,.18),
    inset 0 -1px 0 rgba(255,255,255,.25),
    0 15px 38px rgba(0,0,0,.34)!important;
  backdrop-filter:blur(17px) saturate(135%);
  -webkit-backdrop-filter:blur(17px) saturate(135%);
}
#ppi-public-startpage[data-effect=glossy] .ppi-public-start-copy::before {
  background:
    linear-gradient(117deg,transparent 0%,rgba(255,255,255,.09) 11%,rgba(255,255,255,.33) 22%,rgba(255,255,255,.06) 32%,transparent 43%) top left/100% 100%,
    linear-gradient(166deg,rgba(255,255,255,.30) 0%,rgba(255,255,255,.08) 19%,transparent 49%,rgba(255,255,255,.07) 100%)!important;
}
#ppi-public-startpage[data-effect=glossy] .ppi-public-start-copy::after {
  height:52%!important;
  background:radial-gradient(ellipse at 35% -30%,rgba(255,255,255,.46) 0%,rgba(255,255,255,.13) 35%,transparent 76%)!important;
}
@media (max-width:650px) {
  #ppi-public-startpage[data-layout=gallery] .ppi-public-start-header {padding:12px 0 18px!important}
}
@media (prefers-reduced-transparency:reduce) {
  #ppi-public-startpage[data-effect=glossy] .ppi-public-start-copy {
    backdrop-filter:none!important;-webkit-backdrop-filter:none!important;
    background:rgba(16,31,44,.90)!important;
  }
}

/* 0.4.62.116: one shared content width for split hero/glass and card grid.
   Remove double horizontal inset from the header at narrower viewports. */
#ppi-public-startpage[data-layout=split] .ppi-public-start-inner {
  --ppi-split-gutter:clamp(12px,2vw,26px);
  width:min(100%,1360px)!important;
  max-width:1360px!important;
  margin-inline:auto!important;
}
#ppi-public-startpage[data-layout=split] .ppi-public-start-header {
  padding-left:0!important;
  padding-right:0!important;
}
#ppi-public-startpage[data-layout=split] .ppi-public-start-copy {
  box-sizing:border-box!important;
  width:min(calc(100% - 2 * var(--ppi-split-gutter)),calc(1360px - 2 * var(--ppi-split-gutter)))!important;
  max-width:none!important;
  margin-left:auto!important;
  margin-right:auto!important;
}
#ppi-public-startpage[data-layout=split] .ppi-public-start-tour-section {
  box-sizing:border-box!important;
  width:100%!important;
  padding-left:var(--ppi-split-gutter)!important;
  padding-right:var(--ppi-split-gutter)!important;
}
#ppi-public-startpage[data-layout=split] .ppi-public-start-grid {
  width:100%!important;
}
@media(max-width:650px) {
 #ppi-public-startpage[data-layout=split] .ppi-public-start-inner {
   --ppi-split-gutter:10px;
 }
 #ppi-public-startpage[data-layout=split] .ppi-public-start-copy {
   width:calc(100% - 2 * var(--ppi-split-gutter))!important;
 }
}
/* The gallery's chosen background JPEG works with all three effect modes,
   including the shiny glass preset; the selected solid color is fallback. */
#ppi-public-startpage[data-layout=gallery] {
 background-color:var(--page-bg,#000000)!important;
 background-image:linear-gradient(rgba(0,0,0,.20),rgba(0,0,0,.20)),var(--bg-image,none)!important;
 background-position:center!important;
 background-size:cover!important;
}

/* 0.4.62.118: equal-size cards, more prominent names, compact description
   and a lightweight glass highlight over the image which clears on hover. */
#ppi-public-startpage .ppi-public-start-card {
  height:clamp(235px,24vw,310px)!important;
  min-height:235px!important;
  display:flex!important;flex-direction:column!important;
  overflow:hidden!important;
  transition:transform .18s ease,box-shadow .18s ease!important;
}
#ppi-public-startpage .ppi-public-start-card-image {
  position:relative!important;
  flex:1 1 auto!important;
  min-height:0!important;
  aspect-ratio:auto!important;
  overflow:hidden!important;
}
#ppi-public-startpage .ppi-public-start-card-image img {
  display:block!important;width:100%!important;height:100%!important;
  object-fit:cover!important;
}
#ppi-public-startpage .ppi-public-start-card-image::after {
  content:"";position:absolute;inset:0;pointer-events:none;
  background:linear-gradient(155deg,rgba(255,255,255,.23) 0%,rgba(255,255,255,.10) 23%,transparent 48%,rgba(255,255,255,.07) 100%);
  border-top:1px solid rgba(255,255,255,.53);
  box-shadow:inset 0 1px 0 rgba(255,255,255,.30);
  opacity:1;transition:opacity .18s ease;
}
@media(hover:hover) and (pointer:fine) {
  #ppi-public-startpage .ppi-public-start-card:hover .ppi-public-start-card-image::after {
    opacity:0;
  }
}
#ppi-public-startpage .ppi-public-start-card:focus-visible .ppi-public-start-card-image::after {
  opacity:0;
}
#ppi-public-startpage .ppi-public-start-card-content {
  height:78px!important;min-height:78px!important;max-height:78px!important;
  flex:0 0 78px!important;
  padding:9px 12px 8px!important;
  display:flex!important;flex-direction:column!important;
  align-items:center!important;justify-content:flex-start!important;
  gap:3px!important;overflow:hidden!important;text-align:center!important;
}
#ppi-public-startpage .ppi-public-start-card-content strong {
  display:-webkit-box!important;-webkit-box-orient:vertical!important;
  -webkit-line-clamp:2!important;
  overflow:hidden!important;max-width:100%!important;
  font-size:clamp(15px,1.25vw,19px)!important;
  line-height:1.2!important;font-weight:750!important;
  margin:0!important;white-space:normal!important;overflow-wrap:anywhere!important;
}
#ppi-public-startpage .ppi-public-start-card-content small {
  display:-webkit-box!important;-webkit-box-orient:vertical!important;
  -webkit-line-clamp:2!important;overflow:hidden!important;
  max-width:100%!important;margin:0!important;
  color:#5c6571!important;font-size:clamp(10px,.79vw,12px)!important;
  line-height:1.3!important;overflow-wrap:anywhere!important;
}
@media(max-width:650px) {
 #ppi-public-startpage .ppi-public-start-card {height:270px!important}
 #ppi-public-startpage .ppi-public-start-card-content strong {font-size:17px!important}
 #ppi-public-startpage .ppi-public-start-card-content small {font-size:11px!important}
}
@media(prefers-reduced-motion:reduce) {
 #ppi-public-startpage .ppi-public-start-card-image::after,
 #ppi-public-startpage .ppi-public-start-card {transition:none!important}
}


/* 0.4.62.119: stronger opposing outline and active cast shadow on heading. */
#ppi-public-startpage .ppi-public-start-copy h1 {
 -webkit-text-stroke:1.15px var(--title-stroke)!important;
 paint-order:stroke fill;
 text-shadow:0 2px 4px var(--title-shadow),0 5px 12px rgba(0,0,0,.55)!important;
}
/* Split layout has independently selectable top and lower colors.
   The uploaded image, when present, remains visible above the top color. */
#ppi-public-startpage[data-layout=split] .ppi-public-start-header {
 background-color:var(--header-bg,var(--page-bg,#000000))!important;
}
#ppi-public-startpage .ppi-public-start-card-content{
 height:105px!important;min-height:105px!important;max-height:105px!important;
 flex:0 0 105px!important;padding:9px 12px 10px!important;
}
#ppi-public-startpage .ppi-public-start-card-content strong{
 font-size:clamp(17px,1.5vw,22px)!important;line-height:1.18!important;
}
#ppi-public-startpage .ppi-public-start-card-content small{
 font-size:clamp(15px,1.05vw,16px)!important;line-height:1.4!important;
}
@media(max-width:900px){
 #ppi-public-startpage[data-cols="4"] .ppi-public-start-card:last-child:nth-child(4n + 3){
  grid-column:auto!important;
 }
}
@media(max-width:650px){
 #ppi-public-startpage[data-cols] .ppi-public-start-card {grid-column:auto!important}
 #ppi-public-startpage .ppi-public-start-card-content strong{font-size:20px!important}
 #ppi-public-startpage .ppi-public-start-card-content small{font-size:15px!important}
}


/* 0.4.62.121: subdued images by default; reveal the original on hover.
   Only image media is shaded, never text or interactive links. */
#ppi-public-startpage .ppi-public-start-card-image::after {
 background:linear-gradient(135deg,rgba(8,18,26,.32),rgba(8,18,26,.40))!important;
 box-shadow:inset 0 1px 0 rgba(255,255,255,.15)!important;
 opacity:1!important;
 transition:opacity .22s ease!important;
}
@media (hover:hover) and (pointer:fine){
 #ppi-public-startpage .ppi-public-start-card:hover .ppi-public-start-card-image::after {
  opacity:0!important;
 }
}
#ppi-public-startpage .ppi-public-start-card:focus-visible .ppi-public-start-card-image::after{
 opacity:0!important;
}
/* Text stays inside the fixed-height area but uses more horizontal space. */
#ppi-public-startpage .ppi-public-start-card-content {
 padding-left:5px!important;padding-right:5px!important;
}
#ppi-public-startpage .ppi-public-start-card-content small{
 font-size:clamp(14px,1vw,15px)!important;
}
@media(prefers-reduced-motion:reduce){
 #ppi-public-startpage .ppi-public-start-card-image::after {transition:none!important}
}


/* 0.4.62.123 – Square mosaic. Tracks derive from the exact content width
   of the grid wrapper (not the viewport). A 2x2 or 3x3 tile is a true
   square: combined columns and rows include the same gap. */
#ppi-public-startpage .ppi-public-start-grid-container {
  container-type:inline-size;
  width:100%;
  min-width:0;
}
#ppi-public-startpage .ppi-public-start-grid {
  --ppi-mosaic-columns:2;
  --ppi-mosaic-gap:clamp(14px,2vw,25px);
  width:100%!important;
  margin:0!important;
  gap:var(--ppi-mosaic-gap)!important;
  grid-template-columns:repeat(var(--ppi-mosaic-columns),minmax(0,1fr))!important;
  grid-auto-rows:calc((100cqw - (var(--ppi-mosaic-columns) - 1) * var(--ppi-mosaic-gap)) / var(--ppi-mosaic-columns))!important;
  grid-auto-flow:row!important;
  align-items:stretch!important;
}
#ppi-public-startpage[data-cols="3"] .ppi-public-start-grid{--ppi-mosaic-columns:3}
#ppi-public-startpage[data-cols="4"] .ppi-public-start-grid{--ppi-mosaic-columns:4}
#ppi-public-startpage[data-cols="5"] .ppi-public-start-grid{--ppi-mosaic-columns:5}
#ppi-public-startpage .ppi-public-start-grid > .ppi-public-start-card {
  grid-column:span 1!important;
  grid-row:span 1!important;
  width:100%!important;
  height:100%!important;
  min-height:0!important;
  max-height:none!important;
  min-width:0!important;
  aspect-ratio:1/1!important;
}
@media(min-width:901px) {
  /* Two columns: large square replaces two rows, only with enough tours. */
  #ppi-public-startpage[data-cols="auto"] .ppi-public-start-grid:has(> .ppi-public-start-card:nth-child(5)) > .ppi-public-start-card:nth-child(7n + 1),
  #ppi-public-startpage[data-cols="2"] .ppi-public-start-grid:has(> .ppi-public-start-card:nth-child(5)) > .ppi-public-start-card:nth-child(7n + 1) {
    grid-column:span 2!important;grid-row:span 2!important;
  }
  /* Three columns: 2x2 square followed by two 1x1 cards at its right. */
  #ppi-public-startpage[data-cols="3"] .ppi-public-start-grid > .ppi-public-start-card:nth-child(3n + 1):nth-last-child(n + 3) {
    grid-column:span 2!important;grid-row:span 2!important;
  }
  /* Four columns: occasional 3x3 hero, then 2x2 accents only when full. */
  #ppi-public-startpage[data-cols="4"] .ppi-public-start-grid:has(> .ppi-public-start-card:nth-child(10)) > .ppi-public-start-card:first-child {
    grid-column:span 3!important;grid-row:span 3!important;
  }
  #ppi-public-startpage[data-cols="4"] .ppi-public-start-grid > .ppi-public-start-card:nth-child(5n + 5):nth-last-child(n + 5) {
    grid-column:span 2!important;grid-row:span 2!important;
  }
  #ppi-public-startpage[data-cols="4"] .ppi-public-start-grid:not(:has(> .ppi-public-start-card:nth-child(10))) > .ppi-public-start-card:first-child:nth-last-child(n + 5) {
    grid-column:span 2!important;grid-row:span 2!important;
  }
  /* Five columns: 3x3 square plus six small cards, then 2x2 features. */
  #ppi-public-startpage[data-cols="5"] .ppi-public-start-grid:has(> .ppi-public-start-card:nth-child(10)) > .ppi-public-start-card:first-child {
    grid-column:span 3!important;grid-row:span 3!important;
  }
  #ppi-public-startpage[data-cols="5"] .ppi-public-start-grid > .ppi-public-start-card:nth-child(7n + 8):nth-last-child(n + 7) {
    grid-column:span 2!important;grid-row:span 2!important;
  }
  #ppi-public-startpage[data-cols="5"] .ppi-public-start-grid:not(:has(> .ppi-public-start-card:nth-child(10))) > .ppi-public-start-card:first-child:nth-last-child(n + 7) {
    grid-column:span 2!important;grid-row:span 2!important;
  }
}
@media(min-width:651px) and (max-width:900px) {
  #ppi-public-startpage[data-cols] .ppi-public-start-grid {--ppi-mosaic-columns:2}
  #ppi-public-startpage[data-cols] .ppi-public-start-grid:has(> .ppi-public-start-card:nth-child(5)) > .ppi-public-start-card:nth-child(7n + 1) {
    grid-column:span 2!important;grid-row:span 2!important;
  }
}
@media(max-width:650px) {
  #ppi-public-startpage[data-cols] .ppi-public-start-grid {--ppi-mosaic-columns:1}
  #ppi-public-startpage[data-cols] .ppi-public-start-grid > .ppi-public-start-card {
    grid-column:span 1!important;grid-row:span 1!important;
  }
}

/* 0.4.62.124: unobtrusive inner shadow around each tour thumbnail.
   It is independent of the dark-at-rest overlay and remains on hover. */
#ppi-public-startpage .ppi-public-start-card-image{
 position:relative!important;
 isolation:isolate;
}
#ppi-public-startpage .ppi-public-start-card-image::before{
 content:"";
 position:absolute;
 inset:0;
 z-index:2;
 border-radius:inherit;
 pointer-events:none;
 box-shadow:inset 0 0 9px 2px rgba(0,0,0,.36),inset 0 0 2px rgba(0,0,0,.28);
}
#ppi-public-startpage .ppi-public-start-card-image::after{z-index:1;}
/* Explicit two-column preset: no wide or tall tiles, even with one tour.
   Auto mode and the mosaics with 3-5 columns are unaffected. */
#ppi-public-startpage[data-cols="2"] .ppi-public-start-grid:not(#ppi-mosaic-wide-override) > .ppi-public-start-card:nth-child(n){
 grid-column:span 1!important;
 grid-row:span 1!important;
}
@media(min-width:651px) and (max-width:900px){
 #ppi-public-startpage[data-cols="2"] .ppi-public-start-grid:not(#ppi-mosaic-wide-override) > .ppi-public-start-card:nth-child(n){
  grid-column:span 1!important;grid-row:span 1!important;
 }
}



/* 0.4.62.126: matching 6px white frame for public tour tiles.
   Border belongs inside the square mosaic box, not outside the track. */
#ppi-public-startpage .ppi-public-start-grid > .ppi-public-start-card{
 box-sizing:border-box!important;
 border:6px solid #fff!important;
}


/* 0.4.62.128: the split-mode separator remains white on any cover image
   or header color, and spans the full viewport-width hero container. */
#ppi-public-startpage[data-layout="split"] .ppi-public-start-header{
 box-sizing:border-box!important;
 border-bottom:2px solid #fff!important;
 box-shadow:0 8px 18px rgba(0,0,0,.32),0 2px 3px rgba(0,0,0,.45)!important;
}

<?php include __DIR__ . '/public-share-unavailable.css.php'; ?>

/* PPI STARTPAGE PASSWORD DESIGN FIX */

#ppi-public-startpage:has(.ppi-public-start-message) {
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
}

#ppi-public-startpage .ppi-public-start-message {
    width: min(100%, 480px) !important;
    max-width: 480px !important;
    margin: auto !important;
    padding: clamp(22px, 4vw, 36px) !important;
    text-align: center !important;
    background: #142536 !important;
    border: 1px solid #536274 !important;
    border-radius: 16px !important;
    box-shadow: 0 12px 32px rgba(0,0,0,.28) !important;
}

#ppi-public-startpage .ppi-public-start-message h1 {
    margin: 0 0 12px !important;
    font-size: clamp(21px, 3vw, 27px) !important;
    line-height: 1.3 !important;
    color: #fff !important;
}

#ppi-public-startpage .ppi-public-start-message p {
    margin: 0 0 22px !important;
    color: #d5dfeb !important;
    line-height: 1.5 !important;
}

#ppi-public-startpage .ppi-public-start-message form {
    display: flex !important;
    flex-direction: column !important;
    align-items: stretch !important;
    gap: 12px !important;
    width: 100% !important;
    margin: 0 !important;
}

#ppi-public-startpage .ppi-public-start-message label {
    display: block !important;
    width: 100% !important;
    margin: 0 !important;
    text-align: left !important;
    color: #fff !important;
    font-size: 13px !important;
    font-weight: 600 !important;
}

#ppi-public-startpage #ppi-startpage-password {
    display: block !important;
    width: 100% !important;
    min-width: 0 !important;
    max-width: none !important;
    height: 44px !important;
    box-sizing: border-box !important;
    margin: 0 !important;
    padding: 10px 12px !important;
    border: 1px solid #9baebe !important;
    border-radius: 8px !important;
    background: #fff !important;
    color: #172536 !important;
    font-size: 16px !important;
}

#ppi-public-startpage #ppi-startpage-password:focus {
    border-color: #62b8ee !important;
    outline: 2px solid #62b8ee !important;
    outline-offset: 1px !important;
}

#ppi-public-startpage .ppi-public-start-message button[type="submit"] {
    display: block !important;
    width: 100% !important;
    min-height: 44px !important;
    margin: 4px 0 0 !important;
    padding: 11px 15px !important;
    border: 0 !important;
    border-radius: 8px !important;
    background: #e1edf5 !important;
    color: #102b43 !important;
    font-weight: 700 !important;
    cursor: pointer !important;
}

#ppi-public-startpage .ppi-public-start-message button[type="submit"]:hover {
    background: #c8ddeb !important;
}

@media (max-width: 480px) {
    #ppi-public-startpage .ppi-public-start-message {
        padding: 22px !important;
    }
}

</style>