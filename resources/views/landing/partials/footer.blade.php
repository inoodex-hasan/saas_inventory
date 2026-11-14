    @php
      $rawPhone = $siteSettings['contact_phone'] ?? '+880 1957-890788';
      $telDigits = preg_replace('/[^\d+]/', '', $rawPhone);
      $waDigits = $siteSettings['whatsapp_digits'] ?? preg_replace('/\D+/', '', $siteSettings['whatsapp_number'] ?? $rawPhone);
      if (str_starts_with($waDigits, '01') && strlen($waDigits) === 11) {
          $waDigits = '88' . $waDigits;
      }
      $waUrl = $siteSettings['whatsapp_url'] ?? (!empty($waDigits) ? 'https://wa.me/' . $waDigits : null);
    @endphp
    <footer class="site-footer position-relative" aria-label="Site Footer">
      <div class="container position-relative z-1">
        <!-- Main Footer Links Grid -->
        <div class="row footer-main-grid g-4 g-lg-5">
          <div class="col-lg-5 col-md-6 text-start footer-brand-column">
            <div class="footer-brand d-flex align-items-center gap-2 mb-3">
              <img src="{{ $siteSettings['logo_url'] ?? asset('assets/images/inomanage.png') }}" alt="{{ $siteSettings['brand_name'] ?? 'InoManage' }}" class="footer-brand-logo" width="30" height="30" style="width: 30px; height: 30px; object-fit: contain; border-radius: 8px;">
              <span class="brand-name text-white">{{ $siteSettings['brand_name'] ?? 'InoManage' }}</span>
            </div>
            <p class="footer-brand-desc mb-3">{{ $siteSettings['meta_description'] ?? 'Cloud inventory, supply chain management, and double-entry accounting software designed for resilient enterprises.' }}</p>


            <!-- Social Links -->
            <div class="d-flex align-items-center gap-2">
              @if(!empty($siteSettings['facebook_url']))
                <a href="{{ $siteSettings['facebook_url'] }}" target="_blank" rel="noopener noreferrer" aria-label="Facebook" class="footer-trust-badge text-decoration-none d-inline-flex align-items-center justify-content-center" style="width:34px;height:34px;padding:0;">
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
                </a>
              @endif
              @if(!empty($waUrl))
                <a href="{{ $waUrl }}" target="_blank" rel="noopener noreferrer" aria-label="WhatsApp" class="footer-trust-badge text-decoration-none d-inline-flex align-items-center justify-content-center" style="width:34px;height:34px;padding:0;">
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M17.5 14.4c-.2-.1-1.3-.6-1.5-.7-.2-.1-.4-.1-.5.1-.2.2-.6.7-.7.9-.1.2-.3.2-.5.1-.2-.1-.9-.3-1.7-1-.6-.6-1.1-1.3-1.2-1.5-.1-.2 0-.4.1-.5.1-.1.2-.2.3-.4.1-.1.1-.2.2-.3.1-.1 0-.3 0-.4 0-.1-.5-1.2-.7-1.6-.2-.4-.4-.4-.5-.4h-.5c-.2 0-.4.1-.6.3-.2.2-.9.9-.9 2.2 0 1.3.9 2.6 1.1 2.8.1.2 1.8 2.8 4.5 3.9.6.3 1.1.4 1.5.6.6.2 1.2.2 1.7.1.5-.1 1.5-.6 1.8-1.3.2-.6.2-1.2.2-1.3-.2-.1-.4-.2-.5-.3zM12 2C6.5 2 2 6.5 2 12c0 1.8.5 3.5 1.4 5L2 22l5.2-1.4c1.4.8 3.1 1.3 4.8 1.3 5.5 0 10-4.5 10-10S17.5 2 12 2zm0 18.2c-1.6 0-3.1-.4-4.4-1.2l-.3-.2-3.3.9.9-3.2-.2-.3C3.8 14.8 3.4 13.4 3.4 12c0-4.7 3.9-8.6 8.6-8.6s8.6 3.9 8.6 8.6-3.9 8.6-8.6 8.6z"/></svg>
                </a>
              @endif
              @if(!empty($siteSettings['linkedin_url']))
                <a href="{{ $siteSettings['linkedin_url'] }}" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn" class="footer-trust-badge text-decoration-none d-inline-flex align-items-center justify-content-center" style="width:34px;height:34px;padding:0;">
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/></svg>
                </a>
              @endif
              @if(!empty($siteSettings['youtube_url']))
                <a href="{{ $siteSettings['youtube_url'] }}" target="_blank" rel="noopener noreferrer" aria-label="YouTube" class="footer-trust-badge text-decoration-none d-inline-flex align-items-center justify-content-center" style="width:34px;height:34px;padding:0;">
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M22.54 6.42a2.78 2.78 0 0 0-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 0 0-1.94 2A29 29 0 0 0 1 11.75a29 29 0 0 0 .46 5.33A2.78 2.78 0 0 0 3.4 19c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 0 0 1.94-2 29 29 0 0 0 .46-5.25 29 29 0 0 0-.46-5.33z"/><polygon points="9.75 15.02 15.5 11.75 9.75 8.48 9.75 15.02" fill="#01241E"/></svg>
                </a>
              @endif
              @if(!empty($siteSettings['twitter_url']))
                <a href="{{ $siteSettings['twitter_url'] }}" target="_blank" rel="noopener noreferrer" aria-label="X (Twitter)" class="footer-trust-badge text-decoration-none d-inline-flex align-items-center justify-content-center" style="width:34px;height:34px;padding:0;">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                </a>
              @endif
            </div>
          </div>

          <div class="col-lg-2 col-md-6 col-6 text-start">
            <h4 class="footer-col-title">Platform</h4>
            <ul class="footer-links-list list-unstyled">
              <li><a href="{{ route('features') }}">Warehouse Engine</a></li>
              <li><a href="{{ route('features') }}">Double-Entry Accounting</a></li>
              <li><a href="{{ route('features') }}">Workshop Services</a></li>
              <li><a href="{{ route('features') }}">HR &amp; Payroll</a></li>
              <li><a href="{{ route('pricing') }}">Pricing Matrix</a></li>
            </ul>
          </div>

          <div class="col-lg-2 col-md-6 col-6 text-start">
            <h4 class="footer-col-title">Company &amp; Legal</h4>
            <ul class="footer-links-list list-unstyled">
              <li><a href="{{ route('reviews') }}">Customer Reviews</a></li>
              <li><a href="{{ route('home') }}#faq">FAQ</a></li>
              <li><a href="{{ route('terms.show') }}">Terms of Service</a></li>
              <li><a href="{{ route('policy.show') }}">Privacy Policy</a></li>
            </ul>
          </div>

          <div class="col-lg-3 col-md-6 text-start">
            <h4 class="footer-col-title">Contact Us</h4>
            <ul class="footer-contact-list list-unstyled">
              <li>
                <span class="footer-contact-icon" aria-hidden="true">&#9993;</span>
                <a href="mailto:{{ $siteSettings['support_email'] ?? 'support@inomanage.com' }}">{{ $siteSettings['support_email'] ?? 'support@inomanage.com' }}</a>
              </li>
              <li>
                <span class="footer-contact-icon" aria-hidden="true">&#9742;</span>
                <a href="tel:{{ $telDigits }}">{{ $rawPhone }}</a>
              </li>
              @if(!empty($waUrl))
              <li>
                <span class="footer-contact-icon" aria-hidden="true">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                </span>
                <a href="{{ $waUrl }}" target="_blank" rel="noopener noreferrer">WhatsApp Support</a>
              </li>
              @endif
              <li>
                <span class="footer-contact-icon" aria-hidden="true">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </span>
                <span>Sat&ndash;Thu: 10:00 AM &ndash; 7:00 PM BST</span>
              </li>
              <li>
                <span class="footer-contact-icon" aria-hidden="true"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"></path><circle cx="12" cy="10" r="2.5"></circle></svg></span>
                <span>{{ $siteSettings['contact_address'] ?? 'House: E1, Road: 16, Pallabi (2nd Phase), Dhaka-1216' }}</span>
              </li>
            </ul>
          </div>
        </div>

        <!-- Footer Bottom Legal Bar -->
        <div class="footer-bottom-bar d-flex flex-wrap align-items-center justify-content-between">
          <div class="footer-copyright">&copy; {{ date('Y') }} {{ $siteSettings['footer_copyright'] ?? 'InoManage. All rights reserved.' }}</div>
          <div class="footer-tagline">Designed for high-scale enterprise supply chains</div>
        </div>
      </div>
    </footer>
