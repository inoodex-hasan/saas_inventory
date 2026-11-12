/**
 * InoManage ERP - Modern Interactive Scripts (Navbar & Hero Section)
 */

document.addEventListener('DOMContentLoaded', () => {
  // 1. Header scroll effect (blur & subtle border shadow)
  const siteHeader = document.getElementById('siteHeader');
  const handleScroll = () => {
    if (window.scrollY > 20) {
      siteHeader.classList.add('scrolled');
    } else {
      siteHeader.classList.remove('scrolled');
    }
  };
  window.addEventListener('scroll', handleScroll, { passive: true });
  handleScroll();

  // 2. Navigation Active State Handling
  const navLinks = document.querySelectorAll('.nav-item-link, .mobile-nav-link');
  navLinks.forEach((link) => {
    link.addEventListener('click', () => {
      document.querySelectorAll('.nav-item-link').forEach((l) => l.classList.remove('active'));
      document.querySelectorAll('.mobile-nav-link').forEach((l) => l.classList.remove('active'));

      const targetHref = link.getAttribute('href');
      if (targetHref && targetHref.startsWith('#')) {
        document.querySelectorAll(`a[href="${targetHref}"]`).forEach((match) => {
          match.classList.add('active');
        });
      }

      closeMobileDrawer();
    });
  });

  // 3. Mobile Drawer Controls
  const mobileMenuToggle = document.getElementById('mobileMenuToggle');
  const mobileDrawer = document.getElementById('mobileDrawer');
  const closeDrawerBtn = document.getElementById('closeDrawerBtn');
  const drawerBackdrop = document.getElementById('drawerBackdrop');

  function openMobileDrawer() {
    if (mobileDrawer) {
      mobileDrawer.classList.add('active');
      document.body.style.overflow = 'hidden';
    }
  }

  function closeMobileDrawer() {
    if (mobileDrawer) {
      mobileDrawer.classList.remove('active');
      document.body.style.overflow = '';
    }
  }

  if (mobileMenuToggle) {
    mobileMenuToggle.addEventListener('click', openMobileDrawer);
  }
  if (closeDrawerBtn) {
    closeDrawerBtn.addEventListener('click', closeMobileDrawer);
  }
  if (drawerBackdrop) {
    drawerBackdrop.addEventListener('click', closeMobileDrawer);
  }

  // 4. Language Selector Interaction (English & বাংলা)
  const langDropdownItems = document.querySelectorAll('.custom-dropdown-menu .dropdown-item');
  const mobileLanguageSelects = document.querySelectorAll('.mobile-language-dropdown');
  const legalLanguageSelect = document.querySelector('.legal-language-dropdown');
  const LANG_STORAGE_KEY = 'inomanage-landing-language';

  const bnDictionary = {
    'Features': 'বৈশিষ্ট্যসমূহ',
    'Pricing': 'মূল্য তালিকা',
    'Review': 'রিভিউ',
    'FAQ': 'প্রশ্নোত্তর',
    'Sign In': 'সাইন ইন',
    'Free Trial': 'ফ্রি ট্রায়াল',
    'SELECT LANGUAGE': 'ভাষা নির্বাচন করুন',
    'CLOUD INVENTORY PLATFORM': 'ক্লাউড ইনভেন্টরি প্ল্যাটফর্ম',
    'Intelligent Inventory &': 'স্মার্ট ইনভেন্টরি ও',
    'Cloud ERP Platform': 'ক্লাউড ইআরপি প্ল্যাটফর্ম',
    'Get Started Free': 'বিনামূল্যে শুরু করুন',
    'Inventory Accuracy': 'ইনভেন্টরি নির্ভুলতা',
    'Fulfillment Speed': 'ডেলিভারি গতি',
    'Tenant Data Isolation': 'টেন্যান্ট ডেটা সুরক্ষা',
    'Enterprise Support': 'এন্টারপ্রাইজ সাপোর্ট',
    'Our Core Features': 'আমাদের মূল বৈশিষ্ট্য',
    'Ten enterprise engines.': 'দশটি এন্টারপ্রাইজ ইঞ্জিন।',
    'One cohesive platform.': 'একটি সমন্বিত প্ল্যাটফর্ম।',
    'Built for every department.': 'প্রতিটি বিভাগের জন্য নির্মিত।',
    'Powered by real-time sync.': 'রিয়েল-টাইম সিঙ্কে পরিচালিত।',
    'From stock receiving and barcode POS billing to BOM manufacturing, warranty claims, and automated financial statements—run your entire operation from one unified workspace.': 'স্টক রিসিভিং ও বারকোড পিওএস বিলিং থেকে শুরু করে বিওএম উৎপাদন, ওয়ারেন্টি ক্লেইম এবং স্বয়ংক্রিয় আর্থিক প্রতিবেদন—একটি সমন্বিত ওয়ার্কস্পেস থেকেই পুরো ব্যবসা পরিচালনা করুন।',
    'Multi-Warehouse Stock Engine': 'মাল্টি-ওয়্যারহাউস স্টক ইঞ্জিন',
    'Procurement & Vendor Dues': 'ক্রয় ও ভেন্ডর বকেয়া',
    'POS & Sales Management': 'পিওএস ও বিক্রয় ব্যবস্থাপনা',
    'Double-Entry Accounting': 'ডাবল-এন্ট্রি অ্যাকাউন্টিং',
    'Bank Reconciliation': 'ব্যাংক রিকনসিলিয়েশন',
    'Workshop & Repair Services': 'ওয়ার্কশপ ও মেরামত সেবা',
    'Warranties & Returns (RMA)': 'ওয়ারেন্টি ও রিটার্ন (RMA)',
    'Manufacturing & Assembly (BOM)': 'উৎপাদন ও অ্যাসেম্বলি (BOM)',
    'Predictable pricing for': 'সহজ ও স্বচ্ছ মূল্য',
    'businesses that scale.': 'বর্ধনশীল ব্যবসার জন্য।',
    'Monthly': 'মাসিক',
    'Annual': 'বার্ষিক',
    'SAVE 20%': '২০% সাশ্রয়',
    '/month': '/মাস',
    'Billed monthly': 'মাসিক বিলিং',
    'Plan Quotas & Capabilities:': 'প্ল্যান কোটা ও সুবিধাসমূহ:',
    '4.9 / 5 OVERALL RATING': '৪.৯ / ৫ সামগ্রিক রেটিং',
    'Trusted by 12,500+ warehouses &': '১২,৫০০+ ওয়্যারহাউস ও',
    'fast-growing enterprises.': 'দ্রুত বর্ধনশীল প্রতিষ্ঠানের আস্থা।',
    'FREQUENTLY ASKED QUESTIONS': 'সচরাচর জিজ্ঞাসিত প্রশ্ন',
    'Everything you need to know about': 'যা কিছু জানা প্রয়োজন',
    'InoManage Cloud ERP.': 'InoManage ক্লাউড ইআরপি সম্পর্কে।',
    'Platform': 'প্ল্যাটফর্ম',
    'Company & Legal': 'কোম্পানি ও আইনি',
    'Contact Us': 'যোগাযোগ করুন',
    'Accepted Payments': 'গৃহীত পেমেন্ট মাধ্যম',
    'Warehouse Engine': 'ওয়্যারহাউস ইঞ্জিন',
    'Workshop Services': 'ওয়ার্কশপ সেবা',
    'HR & Payroll': 'এইচআর ও পেরোল',
    'Pricing Matrix': 'মূল্য তালিকা',
    'Customer Reviews': 'গ্রাহক রিভিউ',
    'Start Free Trial': 'ফ্রি ট্রায়াল শুরু করুন',
    'Terms of Service': 'সেবার শর্তাবলী',
    'Privacy Policy': 'গোপনীয়তা নীতি',
    'WhatsApp Support': 'হোয়াটসঅ্যাপ সাপোর্ট',
    'Designed for high-scale enterprise supply chains': 'উচ্চ-সক্ষমতার এন্টারপ্রাইজ সাপ্লাই চেইনের জন্য নির্মিত',
  };

  // Collect text nodes that match our dictionary once
  const translatableTextNodes = [];
  const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT, null);
  let node;
  while ((node = walker.nextNode())) {
    const parentTag = node.parentElement ? node.parentElement.tagName : '';
    if (parentTag === 'SCRIPT' || parentTag === 'STYLE' || parentTag === 'NOSCRIPT') continue;
    const trimmed = node.nodeValue ? node.nodeValue.trim() : '';
    if (trimmed && Object.prototype.hasOwnProperty.call(bnDictionary, trimmed)) {
      translatableTextNodes.push({ node, original: node.nodeValue, trimmed });
    }
  }

  function applyTranslations(lang) {
    const isBn = lang === 'বাংলা';
    document.documentElement.lang = isBn ? 'bn' : 'en';

    // 1. Elements with explicit data-bn attributes (e.g. legal pages)
    document.querySelectorAll('[data-bn]').forEach((el) => {
      if (!('englishText' in el.dataset)) {
        el.dataset.englishText = el.textContent;
      }
      el.textContent = isBn ? el.dataset.bn : el.dataset.englishText;
    });

    // 2. Dictionary-matched UI text nodes
    translatableTextNodes.forEach((entry) => {
      if (isBn) {
        entry.node.nodeValue = entry.original.replace(entry.trimmed, bnDictionary[entry.trimmed]);
      } else {
        entry.node.nodeValue = entry.original;
      }
    });

    // 3. Sync legal language dropdown if present
    if (legalLanguageSelect && legalLanguageSelect.value !== (isBn ? 'বাংলা' : 'English')) {
      legalLanguageSelect.value = isBn ? 'বাংলা' : 'English';
      legalLanguageSelect.dispatchEvent(new Event('change'));
    }
  }

  function setActiveLanguage(lang) {
    const normalizedLang = lang === 'বাংলা' ? 'বাংলা' : 'English';

    langDropdownItems.forEach((item) => {
      const isSelected = item.getAttribute('data-lang') === normalizedLang;
      item.classList.toggle('active', isSelected);
      const check = item.querySelector('.check-icon, .check-mark');
      if (check) {
        check.classList.toggle('d-none', !isSelected);
      }
    });

    mobileLanguageSelects.forEach((select) => {
      select.value = normalizedLang;
    });

    const currentLangLabel = document.getElementById('currentLangLabel');
    if (currentLangLabel) {
      currentLangLabel.textContent = normalizedLang;
    }

    window.localStorage.setItem(LANG_STORAGE_KEY, normalizedLang);
    applyTranslations(normalizedLang);
  }

  langDropdownItems.forEach((item) => {
    item.addEventListener('click', (e) => {
      e.preventDefault();
      const selectedLang = item.getAttribute('data-lang');
      setActiveLanguage(selectedLang);
    });
  });

  mobileLanguageSelects.forEach((select) => {
    select.addEventListener('change', () => {
      setActiveLanguage(select.value);
    });
  });

  const savedLang = window.localStorage.getItem(LANG_STORAGE_KEY);
  if (savedLang === 'বাংলা') {
    setActiveLanguage('বাংলা');
  }

  // 5. Hero Stats Animated Counter (Counts up smoothly on view)
  const statNumbers = document.querySelectorAll('.stat-number[data-target]');
  let hasAnimatedStats = false;

  function animateCount(el) {
    const target = parseFloat(el.getAttribute('data-target'));
    const suffix = el.getAttribute('data-suffix') || '';
    const decimals = parseInt(el.getAttribute('data-decimals') || '0', 10);
    const useComma = el.getAttribute('data-comma') === 'true';

    const duration = 1800; // ms
    const startTime = performance.now();

    function update(currentTime) {
      const elapsed = currentTime - startTime;
      const progress = Math.min(elapsed / duration, 1);

      // Smooth ease-out cubic
      const easeProgress = 1 - Math.pow(1 - progress, 3);
      const currentVal = target * easeProgress;

      let formatted = currentVal.toFixed(decimals);
      if (useComma) {
        formatted = Math.floor(currentVal).toLocaleString('en-US');
      }

      el.textContent = `${formatted}${suffix}`;

      if (progress < 1) {
        requestAnimationFrame(update);
      } else {
        if (useComma) {
          el.textContent = `${Math.floor(target).toLocaleString('en-US')}${suffix}`;
        } else {
          el.textContent = `${target.toFixed(decimals)}${suffix}`;
        }
      }
    }

    requestAnimationFrame(update);
  }

  function triggerStatsAnimation() {
    if (hasAnimatedStats) return;
    hasAnimatedStats = true;
    statNumbers.forEach((statEl) => animateCount(statEl));
  }

  // Trigger stats animation after a brief delay on load or scroll
  setTimeout(triggerStatsAnimation, 250);

  // 6. Interactive 3D Subtle Tilt on Stat Cards
  const statCards = document.querySelectorAll('.stat-card');
  statCards.forEach((card) => {
    card.addEventListener('mousemove', (e) => {
      const rect = card.getBoundingClientRect();
      const x = e.clientX - rect.left - rect.width / 2;
      const y = e.clientY - rect.top - rect.height / 2;
      card.style.transform = `perspective(600px) rotateX(${-y * 0.04}deg) rotateY(${x * 0.04}deg) translateY(-3px)`;
    });

    card.addEventListener('mouseleave', () => {
      card.style.transform = '';
    });
  });

  // 7. Interactive Demo Play Pulse
  const startDemoVideoBtn = document.getElementById('startDemoVideoBtn');
  if (startDemoVideoBtn) {
    startDemoVideoBtn.addEventListener('click', () => {
      const overlay = startDemoVideoBtn.closest('.demo-preview-overlay');
      if (overlay) {
        overlay.innerHTML = `
          <div class="spinner-border text-primary mb-3" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
          <h6 class="text-white fw-bold mb-1">Connecting to Live Sandbox...</h6>
          <p class="text-white-50 small mb-0">Launching interactive ERP simulation module.</p>
        `;
        setTimeout(() => {
          overlay.innerHTML = `
            <div class="badge bg-success-subtle text-success mb-2 px-3 py-1">CONNECTED</div>
            <h5 class="text-white fw-bold mb-1">🎉 Simulation Ready!</h5>
            <p class="text-white-50 small mb-0">Welcome to InoManage Cloud ERP demo sandbox.</p>
          `;
        }, 1200);
      }
    });
  }

  // 9. Pricing Billing Toggle (Monthly vs Annual)
  const billingBtns = document.querySelectorAll('.billing-toggle-btn');
  const priceElements = document.querySelectorAll('.tier-price[data-monthly]');
  const billedNotes = document.querySelectorAll('.tier-billed-note');

  billingBtns.forEach((btn) => {
    btn.addEventListener('click', () => {
      billingBtns.forEach((b) => b.classList.remove('active'));
      btn.classList.add('active');

      const isAnnual = btn.getAttribute('data-billing') === 'annual';

      priceElements.forEach((priceEl) => {
        const targetPrice = isAnnual ? priceEl.getAttribute('data-annual') : priceEl.getAttribute('data-monthly');
        priceEl.textContent = targetPrice;
      });

      billedNotes.forEach((note) => {
        note.textContent = isAnnual ? note.dataset.annual : note.dataset.monthly;
        note.style.opacity = '1';
      });
    });
  });

  // 10. Interactive FAQ Accordion
  const faqHeaders = document.querySelectorAll('.faq-accordion-header');

  faqHeaders.forEach((header) => {
    header.addEventListener('click', () => {
      const isExpanded = header.getAttribute('aria-expanded') === 'true';
      const body = header.nextElementSibling;

      // Close all other accordion items
      faqHeaders.forEach((otherHeader) => {
        if (otherHeader !== header) {
          otherHeader.setAttribute('aria-expanded', 'false');
          if (otherHeader.nextElementSibling) {
            otherHeader.nextElementSibling.classList.remove('show');
          }
        }
      });

      // Toggle clicked item
      header.setAttribute('aria-expanded', !isExpanded);
      if (body) {
        body.classList.toggle('show', !isExpanded);
      }
    });
  });
});
