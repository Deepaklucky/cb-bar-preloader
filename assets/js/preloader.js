// ...existing code...
(function(){
  'use strict';

  // Config from PHP (wp_localize_script)
  var cfg = window.DP_PRELOADER_CONFIG || {};
  var baseDuration = parseInt(cfg.duration, 10) || 3200;
  // Scale factor relative to the original example (3200ms)
  var scale = baseDuration / 3200;

  // Ensure gsap is available
  if (typeof gsap === 'undefined') return;

  var innerBars = document.querySelectorAll('.dp-inner-bar');
  var overlay = document.querySelector('.dp-preloader-overlay');
  var preloader = document.querySelector('.dp-preloader');
  var mainContent = document.body;
  var revealTargets = [ mainContent ];
  if (!preloader || innerBars.length === 0) {
    // If nothing to animate, ensure preloader hidden and allow scroll
    if (preloader) preloader.classList.add('hidden');
    document.documentElement.style.overflow = '';
    document.body.style.overflow = '';
    return;
  }

  // Lock scroll while preloader is active
  document.documentElement.style.overflow = 'hidden';
  document.body.style.overflow = 'hidden';
  preloader.setAttribute('aria-hidden', 'false');
  if (mainContent) mainContent.setAttribute('aria-hidden', 'true');

  var increment = 0;
  var gsapBarDur = 0.2 * scale;              // seconds for individual GSAP width tweens
  var pairDelayMs = Math.max(120, Math.round(200 * scale)); // ms between staged pairs
  var finalOverlayDur = Math.max(300, Math.round(500 * scale)); // ms

  function animateBars(){
    for (var i = 0; i < 2; i++) {
      var idx = i + increment;
      if (!innerBars[idx]) break;
      var randomWidth = Math.floor(Math.random() * 101);
      gsap.to(innerBars[idx], {
        width: randomWidth + '%',
        duration: gsapBarDur,
        ease: 'none'
      });
    }

    setTimeout(function(){
      for (var j = 0; j < 2; j++) {
        var idx2 = j + increment;
        if (!innerBars[idx2]) break;
        gsap.to(innerBars[idx2], {
          width: '100%',
          duration: gsapBarDur,
          ease: 'none'
        });
      }

      increment += 2;

      if (increment < innerBars.length) {
        animateBars();
      } else {
        // Final sequence: slide overlay in, then reveal site-main with display:block -> fade+slide
  var tl = gsap.timeline({
    onComplete: function() {
      if (preloader && preloader.parentNode) {
        preloader.classList.add('hidden');
      }
      preloader.setAttribute('aria-hidden', 'true');
      if (mainContent) {
        mainContent.setAttribute('aria-hidden', 'false');
      }
      document.documentElement.style.overflow = '';
      document.body.style.overflow = '';
    }
  });

  // animate overlay from -100% to 0 using GSAP xPercent (consistent with CSS)
  tl.to(overlay, {
    xPercent: 100,
    duration: finalOverlayDur / 1000,
    ease: 'none',
    delay: 0.4 * scale
  });

  // reveal main content if present: set display & fade in
  if (mainContent) {
    tl.call(function(){
      // ensure main is visible for sites that hide it initially
      mainContent.style.display = mainContent.style.display || 'block';
    });
    tl.to(mainContent, { opacity: 1, y: 0, duration: 0.4 * scale, ease: 'power1.out' }, '-=0.05');
  }
  // prepare mainContent start state so transition looks smooth
  if (mainContent) {
    // ensure element exists and start hidden for GSAP
    mainContent.style.display = mainContent.style.display || 'block';
    gsap.set(mainContent, { opacity: 0, y: 20 });
    mainContent.setAttribute('aria-hidden', 'true');
  }

  var tl = gsap.timeline();

  // slide overlay across
  tl.to(overlay, {
    xPercent: 100,
    duration: finalOverlayDur / 1000,
    ease: 'none',
    delay: 0.4 * scale
  });

  // Immediately ensure .site-main is display:block (duration:0), then animate opacity + translateY
  if (mainContent) {
    // set display:block immediately
    tl.to(mainContent, { css: { display: 'block' }, duration: 0 });
    // animate exactly as your snippet: opacity -> 1, transform translateY(0)
    tl.to(mainContent, {
      opacity: 1,
      y: 0,
      duration: 0.4,         // 0.4s as in your example
      ease: 'none'
    }, '-=0'); // start immediately after ensuring display
  }

  // cleanup: hide preloader and restore scroll
  tl.call(function(){
    if (preloader && preloader.parentNode) {
      preloader.classList.add('hidden');
      preloader.setAttribute('aria-hidden', 'true');
    }
    if (mainContent) mainContent.setAttribute('aria-hidden', 'false');
    document.documentElement.style.overflow = '';
    document.body.style.overflow = '';
  });
      }
    }, pairDelayMs);
  }

  // Start the staged animation after load + small delay (matches example)
  window.addEventListener('load', function(){
    // small start delay; scale with configured duration
    var startDelay = Math.max(400, Math.round(1000 * (scale)));
    setTimeout(function(){
      animateBars();
    }, startDelay);
  });

  // Failsafe: after 10s, if still visible, force hide and restore scroll
  setTimeout(function(){
    if (preloader && !preloader.classList.contains('hidden')) {
      preloader.classList.add('hidden');
      preloader.setAttribute('aria-hidden', 'true');
      document.documentElement.style.overflow = '';
      document.body.style.overflow = '';
      if (mainContent) mainContent.setAttribute('aria-hidden', 'false');
    }
  }, 10000);

})();


jQuery(function($){
  var frame;
  $('#dp-preloader-upload-btn').on('click', function(e){
    e.preventDefault();
    if (frame) { frame.open(); return; }
    frame = wp.media({
      title: 'Select or Upload Logo',
      button: { text: 'Use this logo' },
      multiple: false
    });
    frame.on('select', function(){
      var attachment = frame.state().get('selection').first().toJSON();
      $('#dp_preloader_options_logo').val(attachment.url).trigger('change');
    });
    frame.open();
  });

  $('#dp-preloader-clear-btn').on('click', function(e){
    e.preventDefault();
    $('#dp_preloader_options_logo').val('').trigger('change');
  });
});
// ...existing