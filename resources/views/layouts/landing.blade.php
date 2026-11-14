<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="theme-color" content="#faf8f5" />
  <title>@yield('title', $siteSettings['site_title'] ?? 'InoManage ERP - Cloud Inventory Platform')</title>
  <link rel="icon" type="image/png" href="{{ $siteSettings['favicon_url'] ?? asset('assets/images/inomanage.png') }}" />
  @hasSection('meta_description')
    <meta name="description" content="@yield('meta_description')" />
  @endif

  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link
    href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Sans:wght@400;500;600;700&display=swap"
    rel="stylesheet" />

  <!-- Bootstrap 5.3.3 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />

  <!-- Custom CSS -->
  <link rel="stylesheet" href="{{ asset('landing/style.css') }}?v={{ @filemtime(public_path('landing/style.css')) }}" />
  @stack('styles')
</head>

<body>
  @if(request()->has('preview') || (auth()->check() && auth()->user()->isSuperAdmin()))
    <div style="background:#0f172a;color:#fff;padding:8px 16px;font-size:12px;display:flex;align-items:center;justify-content:space-between;position:relative;z-index:1060;">
      <span><strong>Landing Page Preview:</strong> Viewing public landing page as Super Admin.</span>
      <a href="{{ route('admin.dashboard') }}" style="background:#f59e0b;color:#0f172a;padding:4px 12px;border-radius:6px;font-weight:700;text-decoration:none;">&larr; Return to Admin Console</a>
    </div>
  @endif

  @include('landing.partials.navbar')

  <main class="main-content" id="home">
    @yield('content')
    @include('landing.partials.footer')
  </main>

  <!-- Live Demo Modal -->
  <div class="modal fade" id="demoModal" tabindex="-1" aria-labelledby="demoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content custom-dark-modal">
        <div class="modal-header border-0 pb-0">
          <div class="d-flex align-items-center gap-2">
            <div class="demo-modal-pill">LIVE PLATFORM DEMO</div>
            <span class="text-white-50 small">5 min interactive tour</span>
          </div>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body py-4">
          <div class="demo-video-placeholder position-relative overflow-hidden rounded-3">
            <div class="demo-preview-overlay d-flex flex-column align-items-center justify-content-center text-center p-4">
              <div class="demo-play-pulse mb-3" id="startDemoVideoBtn">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="currentColor">
                  <polygon points="5 3 19 12 5 21 5 3"></polygon>
                </svg>
              </div>
              <h5 class="text-white fw-bold mb-1">InoManage Cloud ERP Dashboard Tour</h5>
              <p class="text-white-50 small mb-0">Multi-warehouse inventory synchronization, predictive reordering &amp; supplier workflow automation.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Mobile Offcanvas Navigation Drawer -->
  <div class="mobile-drawer d-lg-none" id="mobileDrawer">
    <div class="mobile-drawer-backdrop" id="drawerBackdrop"></div>
    <div class="mobile-drawer-content">
      <div class="mobile-drawer-header d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
          <span class="brand-name fs-5">InoManage</span>
          <span class="badge-erp">ERP</span>
        </div>
        <button class="btn-close-drawer" id="closeDrawerBtn" aria-label="Close menu">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>

      <div class="mobile-drawer-body">
        <ul class="mobile-nav-list list-unstyled">
          <li><a href="{{ route('features') }}" class="mobile-nav-link {{ request()->routeIs('features') ? 'active' : '' }}">Features</a></li>
          <li><a href="{{ route('pricing') }}" class="mobile-nav-link {{ request()->routeIs('pricing') ? 'active' : '' }}">Pricing</a></li>
          <li><a href="{{ route('reviews') }}" class="mobile-nav-link {{ request()->routeIs('reviews') ? 'active' : '' }}">Review</a></li>
          <li><a href="{{ route('home') }}#faq" class="mobile-nav-link">FAQ</a></li>
        </ul>

        <hr class="drawer-divider" />

        <div class="mobile-language-select mb-4">
          <label class="form-label text-muted small fw-semibold" for="mobileLanguageSelect">SELECT LANGUAGE</label>
          <div class="language-select-wrap">
            <select class="mobile-language-dropdown" id="mobileLanguageSelect" aria-label="Select language">
              <option value="English">English</option>
              <option value="বাংলা">বাংলা</option>
            </select>
            <svg class="language-select-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
              <polyline points="6 9 12 15 18 9"></polyline>
            </svg>
          </div>
        </div>

        <div class="d-grid gap-2">
          <a href="{{ route('login') }}" class="btn btn-signin-mobile text-center text-decoration-none">Sign In</a>
          <a href="{{ route('register') }}" class="btn btn-free-trial-mobile d-flex align-items-center justify-content-center gap-2 text-decoration-none">
            <span>Free Trial</span>
          </a>
        </div>
      </div>
    </div>
  </div>

  <!-- Bootstrap 5.3.3 JS Bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Custom JS -->
  <script src="{{ asset('landing/main.js') }}"></script>
  @stack('scripts')
</body>

</html>

