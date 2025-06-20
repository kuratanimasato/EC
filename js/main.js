"use strict";
document.addEventListener('DOMContentLoaded', function () {
  initHamburger();
  initFrontSplide();
  initAccordion();
  initCarousel();
});

function initHamburger() {
  const hamburger = document.querySelector(".hamburger");
  const nav = document.querySelector('.sp-nav');

  if (!hamburger || !nav) return;

  hamburger.addEventListener("click", () => {
    hamburger.classList.toggle("open");
    nav.classList.toggle("active");
  });

  window.addEventListener('resize', () => {
    if (window.innerWidth > 768) {
      hamburger.classList.remove('open');
      nav.classList.remove('active');
    }
  });
}

function initFrontSplide() {
  const frontSplide =document.querySelector('#image-splide');
  if(frontSplide){
    new Splide(frontSplide, {
      type: 'loop',
      perPage: 3,
      gap: 20,
      focus: 'center',
      autoplay: true,
      interval:5000,
      pagination: false,
      arrows: false,
      breakpoints: {
        768: {
          perPage: 1,
          gap: 10,
        }
      }
    }).mount();
  }
}
function initCarousel() {
  const mainElement = document.querySelector('.splide-main');
  const thumbnailElement = document.querySelector('.thumbnail-carousel');

  if (!mainElement || !thumbnailElement) {
    return;
  }
  
  // サムネイルスライダーのインスタンスを作成
  const thumbnailSplide = new Splide(thumbnailElement, {
    type: "loop",        // ループ再生
    perPage: 3,          // 表示するサムネイルの数
    pagination: false,   // ページネーションは非表示
    isNavigation: true,  // ナビゲーションとして機能させる
    focus: "center",     // アクティブなサムネイルを中央に配置
    rewind: true,        // ループの終端で先頭に戻る
    arrows: false,       // ナビゲーション矢印は非表示
    height: '500px'
  });

  // メインスライダーのインスタンスを作成
  const mainSplide = new Splide(mainElement, {
    type: "fade",        // フェード効果で切り替え
    rewind: true,        // スライドの終端で先頭に戻る
    pagination: false,   // ページネーションは非表示
    arrows: true,        // ナビゲーション矢印は表示
  });
  mainSplide.sync(thumbnailSplide);
  mainSplide.mount();
  thumbnailSplide.mount();
}

function initAccordion() {
  const accordionTriggers = document.querySelectorAll('.js-accordion-trigger');

  accordionTriggers.forEach(trigger => {
    const content = trigger.querySelector('.js-accordion-content');
    const triggerLink = trigger.querySelector('a');

    if (!content || !triggerLink || triggerLink.getAttribute('href') !== '#') return;

    const isInitiallyOpen = trigger.classList.contains('is-open');
    triggerLink.setAttribute('aria-expanded', isInitiallyOpen);
    content.setAttribute('aria-hidden', !isInitiallyOpen);

    if (!content.id) {
      content.id = `accordion-content-${Math.random().toString(36).substring(2, 9)}`;
    }

    triggerLink.setAttribute('aria-controls', content.id);

    triggerLink.addEventListener('click', (event) => {
      event.preventDefault();
      const isOpen = trigger.classList.toggle('is-open');
      content.classList.toggle('is-open', isOpen);
      triggerLink.setAttribute('aria-expanded', isOpen);
      content.setAttribute('aria-hidden', !isOpen);
    });
  });
}

function initCategoryDropdown() {
  const submenuItems = document.querySelectorAll('.category-item.has-submenu .submenu');
  submenuItems.forEach(submenu => submenu.style.display = 'none');

  const toggles = document.querySelectorAll('.category-item.has-submenu > a');
  toggles.forEach(toggle => {
    toggle.addEventListener('click', function (e) {
      e.preventDefault();
      const parent = this.parentElement;
      const submenu = parent.querySelector('.submenu');

      // 他のメニューを閉じる
      document.querySelectorAll('.category-item.has-submenu.open').forEach(item => {
        if (item !== parent) {
          item.classList.remove('open');
          const sub = item.querySelector('.submenu');
          if (sub) sub.style.display = 'none';
        }
      });

      if (submenu) {
        const isVisible = parent.classList.contains('open');
        submenu.style.display = isVisible ? 'none' : 'block';
        parent.classList.toggle('open');
      }
    });
  });
}
 

