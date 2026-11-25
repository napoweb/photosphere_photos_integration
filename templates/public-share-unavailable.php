<?php
/** Shared, deliberately non-disclosing public share error card. */
$ppiShareKind = ($ppiShareKind ?? '') === 'startpage' ? 'startpage' : 'tour';
$ppiIsStartPage = $ppiShareKind === 'startpage';
?>
<section id="ppi-public-unavailable" role="alert" aria-labelledby="ppi-public-unavailable-title">
 <div class="ppi-public-unavailable-card">
  <div class="ppi-public-unavailable-icon" aria-hidden="true">◎</div>
  <span class="ppi-public-unavailable-kicker"><?php echo $ppiIsStartPage ? '360°-STARTSEITE' : '360°-TOUR'; ?></span>
  <h1 id="ppi-public-unavailable-title"><?php echo $ppiIsStartPage ? 'Diese Startseite ist nicht mehr verfügbar' : 'Diese 360°-Tour ist nicht mehr verfügbar'; ?></h1>
  <p>Dieser Freigabelink ist ungültig, wurde erneuert oder ist abgelaufen.</p>
  <p>Bitte fordere beim Besitzer einen neuen Freigabelink an.</p>
 </div>
</section>
