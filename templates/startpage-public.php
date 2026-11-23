<?php
declare(strict_types=1);
$page = $_['page'] ?? [];
$invalid = !empty($_['invalid']);
$locked = !empty($_['passwordRequired']);
$layout = in_array($page['layout'] ?? 'hero', ['hero','split','gallery'], true) ? $page['layout'] : 'hero';
$effect = ($page['designEffect'] ?? 'subtle') === 'glass' ? 'glass' : 'subtle';
$columns = in_array((string)($page['tileColumns'] ?? 'auto'), ['auto','2','3','4','5'], true) ? (string)$page['tileColumns'] : 'auto';
$color = static fn($v,$fallback) => is_string($v) && preg_match('/^#[0-9a-fA-F]{6}$/D',$v) ? $v : $fallback;
$bg = $color($page['backgroundColor'] ?? null, '#000000');
$heading = $color($page['headingColor'] ?? null, '#ffffff');
$description = $color($page['descriptionColor'] ?? null, '#ffffff');
$light = static fn($hex) => (299*hexdec(substr($hex,1,2)) + 587*hexdec(substr($hex,3,2)) + 114*hexdec(substr($hex,5,2))) >= 160000;
$headingStroke = $light($heading) ? 'rgba(0,0,0,.50)' : 'rgba(255,255,255,.68)';
$headingShadow = 'rgba(0,0,0,.40)';
$descriptionShadow = 'rgba(0,0,0,.20)';
?>
<main id="ppi-public-startpage" data-layout="<?php p($layout); ?>" data-effect="<?php p($effect); ?>" data-cols="<?php p($columns); ?>"
 style="--page-bg:<?php p($bg); ?>;--title-color:<?php p($heading); ?>;--desc-color:<?php p($description); ?>;--title-shadow:<?php p($headingShadow); ?>;--title-stroke:<?php p($headingStroke); ?>;--desc-shadow:<?php p($descriptionShadow); ?>;<?php if (!empty($_['backgroundUrl'])): ?>--bg-image:url('<?php p($_['backgroundUrl']); ?>');<?php endif; ?>">
<?php if ($invalid): ?>
 <section class="ppi-public-start-message"><h1>Startseite nicht verfügbar</h1><p>Der Link ist ungültig, abgelaufen oder deaktiviert.</p></section>
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
</style>