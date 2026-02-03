<?php
require_once __DIR__ . '/includes/init.php';
$page = 'About';
$active = 'about';
include __DIR__ . '/includes/header.php';
?>
<section class="page-hero page-hero--slim" style="--hero:url('https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?q=80&w=1600&auto=format&fit=crop');">
  <div class="hero-overlay">
    <h1>About Us</h1>
    <div class="crumbs"><a href="index.php">Home</a> › <span>About</span></div>
  </div>
</section>
<section class="section">
  <div class="container about-intro">
    <h2 class="about-title">Introduction</h2>
    <p class="about-text">
      Eros ludus laboramus ne eam. Mea inani utamur tibique eu, his ei assentior volumus. Integre dolorem mel an, mei nihil omittam et.
      Postea regione mentitum ne pro, debitis phaedrum conceptam has ut. Fugit choro scriptorem an mea, vel ex possit audire.
      Qui elit graeci referrentur ad, eu ludus laudem tincidunt vel, ad vim wisi graeci efficiendi.
    </p>
    <div class="about-author">— <strong>Robert Williams</strong> • Founder · CEO</div>
    <div class="about-images">
      <img class="about-img" src="https://images.unsplash.com/photo-1464306076886-da185f6a9d05?q=80&w=1400&auto=format&fit=crop" alt="Table with food" />
      <img class="about-img" src="https://images.unsplash.com/photo-1447933601403-0c6688de566e?q=80&w=1400&auto=format&fit=crop" alt="Restaurant interior" />
    </div>
  </div>
  </section>

<section class="section">
  <div class="container about-timeline">
    <div class="timeline-eyebrow">BEGIN HERE</div>
    <div class="timeline-grid">
      <div class="timeline-item">
        <img class="timg" src="https://images.unsplash.com/photo-1550317138-10000687a72b?q=80&w=1400&auto=format&fit=crop" alt="Drive Through" />
        <div class="tbody">
          <div class="ttitle">Drive Through Restaurant</div>
          <div class="tdate">November 1992</div>
          <div class="ttext">Auge legendos eam ne, pro quot definitionem te, viderer appareat atomorum ne mea. Melius adipisci cum id, mea cibo decore nemore eu.</div>
        </div>
      </div>

      <div class="timeline-item">
        <img class="timg" src="https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?q=80&w=1400&auto=format&fit=crop" alt="New Seating" />
        <div class="tbody">
          <div class="ttitle">Kudil With Seating Opened</div>
          <div class="tdate">April 1998</div>
          <div class="ttext">Auguque legendos eam ne, viderer appareat atomorum ne mea. Porro nemore mea, ius posse primis ea. Melius adipisci cum id.</div>
        </div>
      </div>

      <div class="timeline-item">
        <img class="timg" src="https://images.unsplash.com/photo-1544025162-d76694265947?q=80&w=1400&auto=format&fit=crop" alt="Spicy Burger" />
        <div class="tbody">
          <div class="ttitle">Spicy Burger Introduced</div>
          <div class="tdate">May 2002</div>
          <div class="ttext">Auge legendos eam ne, viderer appareat atomorum ne mea. Melius adipisci cum id, mea cibo decore nemore eu.</div>
        </div>
      </div>

      <div class="timeline-item">
        <img class="timg" src="https://images.unsplash.com/photo-1498579150354-977475b7ea0b?q=80&w=1400&auto=format&fit=crop" alt="Branches Opened" />
        <div class="tbody">
          <div class="ttitle">Branches Opened World‑Wide</div>
          <div class="tdate">August 2012</div>
          <div class="ttext">Auguque legendos eam ne, viderer appareat atomorum ne mea. Melius adipisci cum id, porro nemore mea, ius posse primis ea.</div>
        </div>
      </div>
    </div>
  </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
