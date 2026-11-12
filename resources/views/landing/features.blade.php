@extends('layouts.landing')

@section('title', 'InoManage ERP - Platform Features')

@section('content')
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
              Ten enterprise engines.<br />
              <span class="features-heading-emerald">One cohesive platform.</span>
            </h2>
            <p class="features-main-desc mb-0">
              Replace messy spreadsheets and fragmented apps. InoManage brings warehouse operations, sales, accounting, workshop services, and payroll together in one place.
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

        <!-- Core business feature cards -->
        <div class="features-reference-grid" aria-label="Platform Features">
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
              Supplier directory, multi-stage purchase orders, advance deposits, partial stock intake, and printable payment vouchers.
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
@endsection
