  <!-- MAIN NAVBAR -->
  <header class="site-header" id="siteHeader">
    <nav class="navbar navbar-expand-lg inomanage-nav">
      <div class="container-fluid px-3 px-md-4 px-xl-5">
        <!-- Logo & Brand -->
        <a class="navbar-brand d-flex align-items-center gap-3" href="{{ route('home') }}" id="brandLogo">
          <div class="logo-icon-wrapper">
            <svg class="logo-icon-svg" viewBox="0 0 44 44" fill="none" xmlns="http://www.w3.org/2000/svg">
              <rect width="44" height="44" rx="11" fill="white" />
              <rect x="9.5" y="11" width="6.5" height="10" rx="3" fill="url(#cyanGrad)" />
              <rect x="18.75" y="11" width="6.5" height="10" rx="3" fill="url(#cyanGrad)" />
              <rect x="28" y="11" width="6.5" height="10" rx="3" fill="url(#cyanGrad)" />
              <rect x="9.5" y="23.5" width="6.5" height="9.5" rx="3" fill="url(#cyanGrad)" />
              <rect x="18.75" y="23.5" width="6.5" height="9.5" rx="3" fill="url(#cyanGrad)" />
              <rect x="28" y="23.5" width="6.5" height="9.5" rx="3" fill="url(#cyanGrad)" />
              <defs>
                <linearGradient id="cyanGrad" x1="9.5" y1="11" x2="34.5" y2="33" gradientUnits="userSpaceOnUse">
                  <stop stop-color="#0284c7" />
                  <stop offset="1" stop-color="#00d2ff" />
                </linearGradient>
              </defs>
            </svg>
          </div>

          <div class="brand-text-group">
            <div class="brand-top d-flex align-items-center gap-2">
              <span class="brand-name">{{ $siteSettings['brand_name'] ?? 'InoManage' }}</span>
              <span class="badge-erp">ERP</span>
            </div>
            <span class="brand-sub">{{ strtoupper($siteSettings['brand_tagline'] ?? 'CLOUD INVENTORY PLATFORM') }}</span>
          </div>
        </a>

        <!-- Mobile Hamburger Toggle -->
        <button class="navbar-toggler custom-toggler d-lg-none" type="button" id="mobileMenuToggle"
          aria-label="Toggle navigation" aria-expanded="false">
          <span class="toggler-line line-1"></span>
          <span class="toggler-line line-2"></span>
          <span class="toggler-line line-3"></span>
        </button>

        <!-- Desktop Navigation -->
        <div class="collapse navbar-collapse justify-content-between" id="navbarContent">
          <ul class="navbar-nav mx-auto align-items-lg-center gap-lg-1 gap-xl-2 nav-center-list">
            <li class="nav-item">
              <a class="nav-link nav-item-link {{ request()->routeIs('features') ? 'active' : '' }}" href="{{ route('features') }}">Features</a>
            </li>
            <li class="nav-item">
              <a class="nav-link nav-item-link {{ request()->routeIs('pricing') ? 'active' : '' }}" href="{{ route('pricing') }}">Pricing</a>
            </li>
            <li class="nav-item">
              <a class="nav-link nav-item-link {{ request()->routeIs('reviews') ? 'active' : '' }}" href="{{ route('reviews') }}">Review</a>
            </li>
            <li class="nav-item">
              <a class="nav-link nav-item-link" href="{{ route('home') }}#faq">FAQ</a>
            </li>

            <!-- Language Dropdown (Icon Only) -->
            <li class="nav-item dropdown language-dropdown ms-lg-2">
              <button class="btn-language d-flex align-items-center justify-content-center" type="button"
                id="languageDropdownBtn" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Select Language"
                title="Select Language">
                <svg class="icon-globe" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                  stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="12" cy="12" r="10"></circle>
                  <line x1="2" y1="12" x2="22" y2="12"></line>
                  <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
                </svg>
                <span id="currentLangLabel">English</span>
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
              </button>
              <ul class="dropdown-menu custom-dropdown-menu dropdown-menu-end" aria-labelledby="languageDropdownBtn">
                <li>
                  <a class="dropdown-item active d-flex align-items-center justify-content-between" href="#" data-lang="English">
                    <span class="lang-text">English</span>
                    <svg class="check-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                      <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                  </a>
                </li>
                <li>
                  <a class="dropdown-item d-flex align-items-center justify-content-between" href="#" data-lang="বাংলা">
                    <span class="lang-text">বাংলা</span>
                    <svg class="check-icon d-none" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                      <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                  </a>
                </li>
              </ul>
            </li>
          </ul>

          <!-- Right Actions (Sign In + Free Trial) -->
          <div class="nav-right-actions d-flex align-items-center gap-3">
            <a href="{{ route('login') }}" class="btn btn-signin">Sign In</a>
            <a href="{{ route('register') }}" class="btn btn-free-trial d-inline-flex align-items-center gap-2">
              <span>Free Trial</span>
            </a>
          </div>
        </div>
      </div>
    </nav>
  </header>

