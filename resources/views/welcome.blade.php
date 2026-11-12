@extends('layouts.landing')

@section('title', 'InoManage ERP - Cloud Inventory Platform')

@section('content')
<section class="hero-section position-relative overflow-hidden">
      <!-- Background Luxury Golden Curved Wave and Bottom Emerald Swirl -->
      <div class="hero-bg-waves" aria-hidden="true">
        <svg class="hero-wave-svg" viewBox="0 0 1440 600" fill="none" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M520 0C680 180 820 280 1440 310" stroke="url(#goldStrokeGrad)" stroke-width="2" stroke-opacity="0.6" />
          <path d="M440 600C650 540 920 480 1440 460" stroke="url(#goldStrokeGrad)" stroke-width="1.5" stroke-opacity="0.4" />
          <path d="M480 600C750 560 1020 500 1440 515V600H480Z" fill="url(#emeraldCornerGrad)" />
          <path d="M480 600C750 560 1020 500 1440 515" stroke="url(#goldStrokeGrad)" stroke-width="2" />
          <defs>
            <linearGradient id="goldStrokeGrad" x1="500" y1="200" x2="1440" y2="500" gradientUnits="userSpaceOnUse">
              <stop stop-color="#E5C79E" />
              <stop offset="0.5" stop-color="#CFA775" />
              <stop offset="1" stop-color="#DFBD91" />
            </linearGradient>
            <linearGradient id="emeraldCornerGrad" x1="600" y1="520" x2="1440" y2="600" gradientUnits="userSpaceOnUse">
              <stop stop-color="#023028" />
              <stop offset="1" stop-color="#01241E" />
            </linearGradient>
          </defs>
        </svg>
      </div>

            <div class="container-fluid px-3 px-md-4 px-xl-5 position-relative z-1">
        @php $heroSlides = $siteSettings['hero_slides'] ?? []; @endphp
        <div id="heroCarousel" @if(count($heroSlides) > 1) class="carousel slide" data-bs-ride="carousel" data-bs-interval="6000" @endif>
          <div @if(count($heroSlides) > 1) class="carousel-inner" style="overflow: visible;" @endif>
            @foreach ($heroSlides as $slideIndex => $slide)
              <div @if(count($heroSlides) > 1) class="carousel-item {{ $slideIndex === 0 ? 'active' : '' }}" @endif>
                <div class="row align-items-center g-4 g-xl-5">
                  <div class="col-lg-6 col-xl-6 text-start order-2 order-lg-1">
                    <h1 class="hero-title mb-3">
                      <span class="hero-title-dark">{{ $slide['headline'] ?? 'Intelligent Inventory &' }}</span><br />
                      <span class="hero-title-emerald">{{ $slide['headline_highlight'] ?? 'Cloud ERP Platform' }}</span>
                    </h1>

                    <p class="hero-subtitle mb-4">
                      {{ $slide['subtext'] ?? 'Streamline procurement, multi-warehouse stock tracking, real-time analytics, and automated supply chains—built for modern high-velocity businesses.' }}
                    </p>

                    <div class="hero-actions d-flex flex-wrap align-items-center gap-3 mb-4 mb-xl-5">
                      <a href="{{ !empty($slide['primary_cta_url']) ? $slide['primary_cta_url'] : route('register') }}" class="btn btn-hero-primary d-inline-flex align-items-center gap-2">
                        <span>{{ $slide['primary_cta_text'] ?? 'Get Started Free' }}</span>
                        <svg class="btn-arrow-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                          <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                      </a>
                    </div>

                    <div class="hero-stats-box">
                      <div class="stat-col">
                        <div class="stat-icon-wrapper">
                          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                            <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                            <line x1="12" y1="22.08" x2="12" y2="12"></line>
                          </svg>
                        </div>
                        <div class="stat-number" data-target="99.98" data-suffix="%" data-decimals="2">99.98%</div>
                        <div class="stat-label">Inventory Accuracy</div>
                      </div>

                      <div class="stat-divider" aria-hidden="true"></div>

                      <div class="stat-col">
                        <div class="stat-icon-wrapper">
                          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
                          </svg>
                        </div>
                        <div class="stat-number" data-target="4.2" data-suffix="x" data-decimals="1">4.2x</div>
                        <div class="stat-label">Fulfillment Speed</div>
                      </div>

                      <div class="stat-divider" aria-hidden="true"></div>

                      <div class="stat-col">
                        <div class="stat-icon-wrapper">
                          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                            <path d="m9 12 2 2 4-4"></path>
                          </svg>
                        </div>
                        <div class="stat-number" data-target="100" data-suffix="%" data-decimals="0">100%</div>
                        <div class="stat-label">Tenant Data Isolation</div>
                      </div>

                      <div class="stat-divider" aria-hidden="true"></div>

                      <div class="stat-col">
                        <div class="stat-icon-wrapper">
                          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M3 18v-6a9 9 0 0 1 18 0v6"></path>
                            <path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"></path>
                          </svg>
                        </div>
                        <div class="stat-number static-stat">24/7</div>
                        <div class="stat-label">Enterprise Support</div>
                      </div>
                    </div>
                  </div>

                  <div class="col-lg-6 col-xl-6 text-center text-lg-end order-1 order-lg-2">
                    <div class="hero-visual-wrapper position-relative">
                      <img
                        src="{{ !empty($slide['image_url']) ? $slide['image_url'] : asset('landing/images/inomanage-laptop-dashboard.png') }}"
                        alt="{{ $slide['headline'] ?? 'InoManage inventory and sales dashboard' }}"
                        class="img-fluid hero-visual-img"
                        loading="eager"
                      />
                    </div>
                  </div>
                </div>
              </div>
            @endforeach
          </div>

          @if(count($heroSlides) > 1)
            <div class="d-flex justify-content-center gap-2 mt-3">
              @foreach($heroSlides as $slideIndex => $slide)
                <button
                  type="button"
                  data-bs-target="#heroCarousel"
                  data-bs-slide-to="{{ $slideIndex }}"
                  class="{{ $slideIndex === 0 ? 'active' : '' }}"
                  aria-current="{{ $slideIndex === 0 ? 'true' : 'false' }}"
                  aria-label="Slide {{ $slideIndex + 1 }}"
                  style="width:10px;height:10px;border-radius:999px;border:0;background:{{ $slideIndex === 0 ? '#059669' : '#cbd5e1' }};"
                ></button>
              @endforeach
            </div>
          @endif
        </div>
      </div>
    </section>
    <!-- ========================================================================
         Core Features Section — Exact Reference Match
         ======================================================================== -->
    <section class="features-section position-relative" id="features" aria-labelledby="features-title">
      <!-- Ambient Aurora Background Accents -->
      <div class="features-aurora-bg" aria-hidden="true">
        <div class="aurora-wave aurora-left"></div>
        <div class="aurora-wave aurora-right"></div>
      </div>

      <div class="container position-relative z-1">
        <!-- Top Row: Left Title & Right 3D Laptop/Boxes Illustration -->
        <div class="row align-items-center mb-4 mb-md-5 g-4">
          <!-- Left Column: Title & Subtitle -->
          <div class="col-lg-6 col-xl-6 text-start">
            <div class="features-pill-badge mb-3">
              <span>Our Core Features</span>
            </div>
            <h2 id="features-title" class="features-main-heading mb-3">
              Built for every department.<br />
              <span class="features-heading-emerald">Powered by real-time sync.</span>
            </h2>
            <p class="features-main-desc mb-0">
              From stock receiving and barcode POS billing to BOM manufacturing, warranty claims, and automated financial statements—run your entire operation from one unified workspace.
            </p>
          </div>

          <!-- Right Column: 3D Laptop + Plant + Shipping Boxes Showcase -->
          <div class="col-lg-6 col-xl-6 text-center text-lg-end">
            <div class="features-showcase-wrapper">
              <img
                src="{{ asset('landing/images/features-laptop-dashboard-plant.png') }}"
                alt="Laptop showing the InoManage ERP dashboard beside a potted plant and books"
                class="img-fluid features-showcase-img"
                loading="lazy"
              />
            </div>
          </div>
        </div>

        <!-- Cards Grid: 6 Cards (3 columns x 2 rows) -->
        <div class="features-reference-grid" aria-label="Core Business Features">
          <!-- Card 01: Multi-Warehouse Stock Engine (Featured Dark Emerald) -->
          <article class="ref-feature-card card-clean-white">
            <div class="ref-card-header">
              <div class="ref-icon-box icon-box-mint">
                <!-- 🏢 Warehouse Outline Icon -->
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M3 21h18M5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16M9 7h2m2 0h2M9 11h2m2 0h2M10 21v-5h4v5"></path>
                </svg>
              </div>
            </div>
            <h3 class="ref-card-title text-navy">Multi-Warehouse Stock Engine</h3>
            <p class="ref-card-desc text-slate">
              Unlimited distribution hubs, default fulfillment routing, inter-warehouse transfers, and cycle-count stock adjustments with audit reasons.
            </p>
            <div class="ref-card-tags">
              <span class="ref-tag-pill tag-light">
                <!-- 📦 Package Box Icon -->
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
                Pessimistic row-locking
              </span>
              <span class="ref-tag-pill tag-light">
                <!-- 📍 Map Pin Icon -->
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                Bin locations
              </span>
            </div>
          </article>

          <!-- Card 02: Procurement & Vendor Dues -->
          <article class="ref-feature-card card-clean-white">
            <div class="ref-card-header">
              <div class="ref-icon-box icon-box-mint">
                <!-- 🛍️ Shopping Bag Icon -->
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                  <line x1="3" y1="6" x2="21" y2="6"></line>
                  <path d="M16 10a4 4 0 0 1-8 0"></path>
                </svg>
              </div>
            </div>
            <h3 class="ref-card-title text-navy">Procurement &amp; Vendor Dues</h3>
            <p class="ref-card-desc text-slate">
              Supplier directory, multi-stage purchase orders, advance deposits, partial stock atock-in, and printable payment vouchers.
            </p>
            <div class="ref-card-tags">
              <span class="ref-tag-pill tag-light">
                <!-- 👥 Users Icon -->
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                Vendor ledger
              </span>
              <span class="ref-tag-pill tag-light">
                <!-- 📚 Payable Tracking / Layers Icon -->
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline></svg>
                Payable tracking
              </span>
            </div>
          </article>

          <!-- Card 03: Sales, POS & Customer CRM -->
          <article class="ref-feature-card card-clean-white">
            <div class="ref-card-header">
              <div class="ref-icon-box icon-box-mint">
                <!-- 🛒 Shopping Cart Icon -->
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="9" cy="21" r="1"></circle>
                  <circle cx="20" cy="21" r="1"></circle>
                  <path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6"></path>
                </svg>
              </div>
            </div>
            <h3 class="ref-card-title text-navy">Sales, POS &amp; Customer CRM</h3>
            <p class="ref-card-desc text-slate">
              Quotations with one-click PO/SO conversion, fast-checkout POS with barcode support, stock reservations, and customer due ledgers.
            </p>
            <div class="ref-card-tags">
              <span class="ref-tag-pill tag-light">
                <!-- 📦 Stock Reservation Icon -->
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline></svg>
                Stock reservation
              </span>
              <span class="ref-tag-pill tag-light">
                <!-- 💳 Credit Limits Icon -->
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
                Credit limits
              </span>
            </div>
          </article>

          <!-- Card 04: Double-Entry Accounting -->
          <article class="ref-feature-card card-clean-white">
            <div class="ref-card-header">
              <div class="ref-icon-box icon-box-mint">
                <!-- 🪙 Coins Stack Icon -->
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                  <ellipse cx="12" cy="5" rx="9" ry="3"></ellipse>
                  <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"></path>
                  <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path>
                </svg>
              </div>
            </div>
            <h3 class="ref-card-title text-navy">Double-Entry Accounting</h3>
            <p class="ref-card-desc text-slate">
              Standardized Chart of Accounts, automated journal entries, Trial Balance, real-time Profit &amp; Loss, and Balance Sheet generation.
            </p>
            <div class="ref-card-tags">
              <span class="ref-tag-pill tag-light">
                <!-- 📖 Book Open Icon -->
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path></svg>
                General ledger
              </span>
              <span class="ref-tag-pill tag-light">
                <!-- ⚖️ Balanced Scales Icon -->
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m16 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"></path><path d="m2 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"></path><path d="M7 21h10"></path><path d="M12 3v18"></path><path d="M3 7h18"></path></svg>
                Balanced debits / credits
              </span>
            </div>
          </article>

          <!-- Card 05: Bank & Account Reconciliation -->
          <article class="ref-feature-card card-clean-white">
            <div class="ref-card-header">
              <div class="ref-icon-box icon-box-mint">
                <!-- 🏛️ Bank / Pillar Landmark Icon -->
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                  <line x1="3" y1="21" x2="21" y2="21"></line>
                  <line x1="3" y1="10" x2="21" y2="10"></line>
                  <polyline points="3 10 12 3 21 10"></polyline>
                  <line x1="6" y1="10" x2="6" y2="21"></line>
                  <line x1="10" y1="10" x2="10" y2="21"></line>
                  <line x1="14" y1="10" x2="14" y2="21"></line>
                  <line x1="18" y1="10" x2="18" y2="21"></line>
                </svg>
              </div>
            </div>
            <h3 class="ref-card-title text-navy">Bank &amp; Account Reconciliation</h3>
            <p class="ref-card-desc text-slate">
              Bank and cash account ledgers, interactive statement audits, discrepancy variance calculation, and audit-state verification.
            </p>
            <div class="ref-card-tags">
              <span class="ref-tag-pill tag-light">
                <!-- 🔗 Link / Chain Icon -->
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg>
                Audit logs
              </span>
              <span class="ref-tag-pill tag-light">
                <!-- 🛡️ Shield Checkmark Icon -->
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><path d="m9 12 2 2 4-4"></path></svg>
                Statement verification
              </span>
            </div>
          </article>

          <!-- Card 06: Workshop & Repair Services -->
          <article class="ref-feature-card card-clean-white">
            <div class="ref-card-header">
              <div class="ref-icon-box icon-box-mint">
                <!-- 🔧 Wrench Tool Icon -->
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M14.7 6.3a5 5 0 0 0-6.6 6.6L3 18v3h3l5.1-5.1a5 5 0 0 0 6.6-6.6L14 13l-3-3 3.7-3.7Z"></path>
                  <path d="m16 4 4 4"></path>
                </svg>
              </div>
            </div>
            <h3 class="ref-card-title text-navy">Workshop &amp; Repair Services</h3>
            <p class="ref-card-desc text-slate">
              Manage service tickets, assign technicians, and track spare-parts consumption as inventory updates automatically.
            </p>
            <div class="ref-card-tags">
              <span class="ref-tag-pill tag-light">
                <!-- ⚙️ Gear Cog Icon -->
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2v-.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2v.09a1.65 1.65 0 0 0-1.6 1.51z"></path></svg>
                Service tickets
              </span>
              <span class="ref-tag-pill tag-light">
                <!-- 👥 Users Parts Icon -->
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                Parts usage
              </span>
            </div>
          </article>

          <!-- Card 07: Warranties & Returns (RMA) -->
          <article class="ref-feature-card card-clean-white">
            <div class="ref-card-header">
              <div class="ref-icon-box icon-box-mint">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                  <path d="m9 12 2 2 4-4"></path>
                </svg>
              </div>
            </div>
            <h3 class="ref-card-title text-navy">Warranties &amp; Returns (RMA)</h3>
            <p class="ref-card-desc text-slate">
              Serial-number warranty lookups, customer defective claim registration, supplier return dispatch, and scrap or restock routing.
            </p>
            <div class="ref-card-tags">
              <span class="ref-tag-pill tag-light">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7V5a2 2 0 0 1 2-2h2"></path><path d="M17 3h2a2 2 0 0 1 2 2v2"></path><path d="M21 17v2a2 2 0 0 1-2 2h-2"></path><path d="M7 21H5a2 2 0 0 1-2-2v-2"></path><path d="m9 12 2 2 4-4"></path></svg>
                Serial Validation
              </span>
              <span class="ref-tag-pill tag-light">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"></polyline><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path></svg>
                RMA Claims
              </span>
            </div>
          </article>

          <!-- Card 08: Manufacturing & Assembly (BOM) -->
          <article class="ref-feature-card card-clean-white">
            <div class="ref-card-header">
              <div class="ref-icon-box icon-box-mint">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M2 20a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8l-7 5V8l-7 5V4a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z"></path>
                  <path d="M17 18h1"></path>
                  <path d="M12 18h1"></path>
                  <path d="M7 18h1"></path>
                </svg>
              </div>
            </div>
            <h3 class="ref-card-title text-navy">Manufacturing &amp; Assembly (BOM)</h3>
            <p class="ref-card-desc text-slate">
              Bill of Materials (BOM) recipes, raw material consumption runs, finished goods production orders, scrap tracking, and output valuation.
            </p>
            <div class="ref-card-tags">
              <span class="ref-tag-pill tag-light">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                Assembly Recipes
              </span>
              <span class="ref-tag-pill tag-light">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                Yield Calculations
              </span>
            </div>
          </article>
        </div>
      </div>
    </section>

    <!-- ========================================================================
         Pricing Section — Luxury Warm Ivory & Emerald Theme
         ======================================================================== -->
    <section class="pricing-section position-relative" id="pricing" aria-labelledby="pricing-title">
      <div class="container position-relative z-1">
        <div class="pricing-intro text-center">
          <h2 id="pricing-title" class="pricing-main-heading mb-3">
            Predictable pricing for<br class="pricing-heading-break" />
            <span class="text-emerald-gradient">businesses that scale.</span>
          </h2>
          <p class="pricing-main-desc mb-4">
            No hidden per-transaction penalties. 3-day free trial on every tier with complimentary migration support.
          </p>

          <!-- Billing Toggle -->
          <div class="billing-toggle-container d-inline-flex align-items-center gap-1 p-1 rounded-pill mb-4 mb-lg-5">
            <button type="button" class="billing-toggle-btn active" id="monthlyBillingBtn" data-billing="monthly">Monthly</button>
            <button type="button" class="billing-toggle-btn d-inline-flex align-items-center gap-2" id="annualBillingBtn" data-billing="annual">
              <span>Annual</span>
              <span class="badge-save">SAVE 20%</span>
            </button>
          </div>
        </div>

        @include('landing.partials.pricing-cards', ['plans' => $plans ?? collect()])

      </div>
    </section>

    <!-- ========================================================================
         Customer Reviews / Testimonials Section
         ======================================================================== -->
    <section class="reviews-section position-relative" id="review" aria-labelledby="reviews-title">
      <div class="container position-relative z-1">
        <div class="reviews-intro text-center">
          <div class="section-eyebrow d-inline-flex align-items-center gap-2 mb-3">
            <span class="stars-gold">★★★★★</span>
            <span>4.9 / 5 OVERALL RATING</span>
            <span class="eyebrow-dot"></span>
          </div>
          <h2 id="reviews-title" class="reviews-main-heading mb-3">
            Trusted by 12,500+ warehouses &amp;<br />
            <span class="text-emerald-gradient">fast-growing enterprises.</span>
          </h2>
          <p class="reviews-main-desc mb-5">
            Discover how modern operators transformed their stock accuracy, checkout speed, and financial audits with InoManage.
          </p>
        </div>

        <!-- 3 Testimonial Cards Grid (Auto-slide on homepage) -->
        @include('landing.partials.reviews-cards', ['carousel' => true])

      </div>
    </section>

    <!-- ========================================================================
         FAQ Section (Frequently Asked Questions)
         ======================================================================== -->
    <section class="faq-section position-relative" id="faq" aria-labelledby="faq-title">
      <div class="container position-relative z-1">
        <div class="faq-layout">
          <div class="faq-intro text-start">
            <div class="section-eyebrow d-inline-flex align-items-center gap-2 mb-3">
              <span class="eyebrow-sparkle">✦</span>
              <span>FREQUENTLY ASKED QUESTIONS</span>
              <span class="eyebrow-dot"></span>
            </div>
            <h2 id="faq-title" class="faq-main-heading mb-3">
              Everything you need to know about
              <span class="text-emerald-gradient">InoManage Cloud ERP.</span>
            </h2>
            <p class="faq-main-desc mb-4">
              Clear answers about multi-warehouse setup, offline POS checkout, data migration, and financial audit security.
            </p>

          </div>

          @include('landing.partials.faq-list')
        </div>
      </div>
    </section>

    <!-- ========================================================================
         High-Conversion CTA Banner Section
         ======================================================================== -->
@endsection
