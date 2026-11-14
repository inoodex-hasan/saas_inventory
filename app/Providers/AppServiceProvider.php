<?php

namespace App\Providers;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if (file_exists(app_path('Helpers/NumberToWords.php'))) {
            require_once app_path('Helpers/NumberToWords.php');
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('*', function ($view) {
            $raw = [];
            try {
                if (Schema::hasTable('site_settings')) {
                    $raw = SiteSetting::getAll();
                }
            } catch (\Throwable $e) {
                // Ignore DB errors during bootstrap/migrations
            }

            $logoPath    = $raw['logo_path'] ?? null;
            $faviconPath = $raw['favicon_path'] ?? null;
            $defaultLogo = asset('assets/images/inomanage.png');
            $logoUrl = null;
            if ($logoPath) {
                if (Storage::disk('public')->exists($logoPath)) {
                    $logoUrl = asset(Storage::url($logoPath));
                } elseif (file_exists(public_path('storage/' . $logoPath))) {
                    $logoUrl = asset('storage/' . $logoPath);
                }
            }
            $logoUrl = $logoUrl ?: $defaultLogo;

            $brandName = $raw['brand_name'] ?? 'InoManage';

            $defaultTestimonials = [
                [
                    'name' => 'Rahim Chowdhury',
                    'role' => 'Managing Director',
                    'company' => 'Apex Retail Ltd',
                    'rating' => 5,
                    'quote' => 'Switching to ' . $brandName . ' solved our stock mismatch permanently. Real-time POS multi-warehouse deduction across our 4 Dhaka outlets stopped stockouts and overselling completely.',
                    'avatar_text' => 'RC',
                ],
                [
                    'name' => 'Tanvir Hasan',
                    'role' => 'Chief Executive',
                    'company' => 'ElectroTech Wholesale',
                    'rating' => 5,
                    'quote' => 'The double-entry accounting engine combined with automatic journal vouchers saves our finance team over 25 hours every single month. The localized PDF invoice printing is super crisp.',
                    'avatar_text' => 'TH',
                ],
                [
                    'name' => 'Sharmin Akter',
                    'role' => 'Head of Operations',
                    'company' => 'Prime Auto Services',
                    'rating' => 5,
                    'quote' => 'Our repair workshop handles 60+ devices daily. Service ticketing with spare parts consumption auto-deducted from our stock room has brought total transparency to our customers.',
                    'avatar_text' => 'SA',
                ],
                [
                    'name' => 'Mahmudul Karim',
                    'role' => 'Supply Chain Lead',
                    'company' => 'Bengal Agro Distributors',
                    'rating' => 5,
                    'quote' => 'The multi-stage purchase orders and vendor dues ledger helped us manage millions in inventory without a single discrepancy across our regional depots.',
                    'avatar_text' => 'MK',
                ],
            ];

            $testimonials = !empty($raw['testimonials']) ? json_decode($raw['testimonials'], true) : null;
            if (!is_array($testimonials) || empty($testimonials)) {
                $testimonials = $defaultTestimonials;
            }

            $defaultFaqs = [
                [
                    'question' => 'Is our company data completely isolated from other tenants?',
                    'answer' => 'Yes, absolutely. Every query executed in Inoodex passes through an automatic global tenant scope (`BelongsToTenant`) that enforces `WHERE team_id = current_team_id`. Cross-tenant ID enumeration returns 403 Forbidden or 404 Not Found at all times.',
                ],
                [
                    'question' => 'How does pessimistic row locking prevent overselling?',
                    'answer' => 'When an order reservation is placed, MySQL row-level locking (`lockForUpdate()`) serializes concurrent access within an ACID database transaction. No two orders can claim the same physical inventory unit simultaneously, preventing dirty reads and negative stock.',
                ],
                [
                    'question' => 'How do we pay with bKash, Nagad, or Bank Transfer?',
                    'answer' => 'In addition to card gateways via SSLCommerz, you can submit manual bKash or Nagad Transaction IDs (TrxID) directly inside your billing portal. Our Super Admin team verifies the transaction in the admin payment portal, instantly updating your plan quotas.',
                ],
                [
                    'question' => 'Can we print vector invoices with custom barcodes and logos?',
                    'answer' => 'Yes! Our integrated high-resolution vector PDF engine dynamically renders your company logo, tax registration number, custom currency symbol (৳, $, €, etc.), and barcode tracking for Sales Invoices, Packing Slips, POs, and Service Tokens.',
                ],
            ];

            $defaultHeroSlides = [
                [
                    'headline'           => 'Intelligent Inventory &',
                    'headline_highlight' => 'Cloud ERP Platform',
                    'subtext'            => 'Streamline procurement, multi-warehouse stock tracking, real-time analytics, and automated supply chains—built for modern high-velocity businesses.',
                    'primary_cta_text'   => 'Get Started Free',
                    'primary_cta_url'    => '/register',
                    'image_url'          => asset('landing/images/inomanage-laptop-dashboard.png'),
                ],
                [
                    'headline'           => 'Turn Every Device Into a',
                    'headline_highlight' => 'Next-Gen POS Checkout Terminal',
                    'subtext'            => 'Serve customers in seconds with sub-millisecond barcode lookups, digital customer receipts, instant bKash/Nagad and cash handling, and automatic stock deduction across all locations.',
                    'primary_cta_text'   => 'Start Free POS Trial',
                    'primary_cta_url'    => '/register',
                    'image_url'          => asset('landing/images/inomanage-laptop-dashboard.png'),
                ],
                [
                    'headline'           => 'Total Financial Clarity With',
                    'headline_highlight' => 'Automated Double-Entry Accounting',
                    'subtext'            => 'Eliminate manual bookkeeping. Every sales invoice, purchase order, return, and expense automatically writes balanced debit and credit entries to your Chart of Accounts with audit-grade precision.',
                    'primary_cta_text'   => 'Try Accounting Free',
                    'primary_cta_url'    => '/register',
                    'image_url'          => asset('landing/images/inomanage-laptop-dashboard.png'),
                ],
            ];

            $heroSlides = !empty($raw['hero_slides']) ? json_decode($raw['hero_slides'], true) : null;
            if (!is_array($heroSlides) || empty($heroSlides)) {
                $heroSlides = $defaultHeroSlides;
            }

            foreach ($heroSlides as &$slide) {
                if (!empty($slide['image_path']) && Storage::disk('public')->exists($slide['image_path'])) {
                    $slide['image_url'] = asset(Storage::url($slide['image_path']));
                }
            }
            unset($slide);

            $faqs = !empty($raw['faqs']) ? json_decode($raw['faqs'], true) : null;
            if (!is_array($faqs) || empty($faqs)) {
                $faqs = $defaultFaqs;
            }

            $view->with('site', [
                'logo_url'          => $logoUrl,
                'favicon_url'       => $faviconPath && Storage::disk('public')->exists($faviconPath)
                                         ? asset(Storage::url($faviconPath)) : $defaultLogo,
                'brand_name'        => $brandName,
                'brand_tagline'     => $raw['brand_tagline']     ?? 'Cloud Inventory Platform',
                'site_title'        => $raw['site_title']        ?? 'InoManage Cloud Inventory Management Software',
                'meta_description'  => $raw['meta_description']  ?? 'Next-generation multi-tenant ERP platform for modern businesses. Multi-warehouse tracking, atomic stock locking, double-entry accounting, POS billing, workshop service ticketing, and vectorized PDF invoicing.',
                'support_email'     => $raw['support_email']     ?? '',
                'contact_phone'     => $raw['contact_phone']     ?? '',
                'contact_address'   => $raw['contact_address']   ?? '',
                'footer_copyright'  => $raw['footer_copyright']  ?? 'InoManage. All rights reserved.',
                'hero_chip_text'    => $raw['hero_chip_text']    ?? 'Enterprise Multi-Tenant SaaS • 99.99% Uptime SLA • Zero Race Conditions',
                'hero_headline'     => $raw['hero_headline']     ?? 'The Operating System for Modern Inventory & Supply Chains',
                'hero_subtext'      => $raw['hero_subtext']      ?? 'Empower your organization with multi-warehouse tracking, atomic stock reservation, double-entry financial accounting, POS checkout, workshop service ticketing, and vectorized PDF invoicing — completely tenant-isolated.',
                'hero_slides'       => $heroSlides,
                'testimonials'      => $testimonials,
                'faqs'              => $faqs,
            ]);
        });

        \Illuminate\Support\Facades\Event::listen(\Illuminate\Auth\Events\Login::class, function (\Illuminate\Auth\Events\Login $event) {
            $user = $event->user;
            if (!$user) {
                return;
            }

            // Super admins in platform mode don't need tenant expiration notices
            if ($user->isSuperAdmin() && !$user->current_team_id) {
                return;
            }

            $team = $user->currentTeam;
            if (!$team) {
                return;
            }

            $sub = $team->subscription;
            if (!$sub) {
                return;
            }

            $isExpired = $sub->isExpired() || in_array($sub->status, ['expired', 'past_due', 'canceled']);
            $hasExpiryDate = ($sub->status === 'trialing' && $sub->trial_ends_at) || ($sub->status === 'active' && $sub->ends_at);

            if (!$isExpired && !$hasExpiryDate) {
                return;
            }

            $days = $sub->days_remaining;
            $isExpiringSoon = $hasExpiryDate && $days <= 7;

            if ($isExpiringSoon || $isExpired) {
                session()->flash('subscription_login_alert', true);
                $planName = $sub->plan?->name ?? 'Workspace';
                if ($isExpired) {
                    session()->flash('warning', "Your {$planName} subscription has expired. Please renew to keep your workspace active.");
                } else {
                    $dayWord = $days === 1 ? 'day' : 'days';
                    session()->flash('warning', "Attention: Your {$planName} subscription expires in {$days} {$dayWord}. Please renew to prevent service interruption.");
                }
            }
        });

        View::composer(['layouts.landing', 'landing.*', 'welcome', 'privacy', 'terms', 'auth.*'], function ($view) {
            $hasTable = Schema::hasTable('site_settings');
            $get = fn (string $key, $default = null) => $hasTable ? SiteSetting::get($key, $default) : $default;

            $logoPath = $get('logo_path');
            $faviconPath = $get('favicon_path');
            $defaultLogo = asset('assets/images/inomanage.png');
            $logoUrl = null;
            if ($logoPath) {
                if (Storage::disk('public')->exists($logoPath)) {
                    $logoUrl = asset(Storage::url($logoPath));
                } elseif (file_exists(public_path('storage/' . $logoPath))) {
                    $logoUrl = asset('storage/' . $logoPath);
                }
            }
            $logoUrl = $logoUrl ?: $defaultLogo;

            $heroSlides = json_decode((string) $get('hero_slides', ''), true);
            if (! is_array($heroSlides) || empty($heroSlides)) {
                $heroSlides = [[
                    'headline' => 'Intelligent Inventory &',
                    'headline_highlight' => 'Cloud ERP Platform',
                    'subtext' => 'Streamline procurement, multi-warehouse stock tracking, real-time analytics, and automated supply chains—built for modern high-velocity businesses.',
                    'primary_cta_text' => 'Get Started Free',
                    'primary_cta_url' => route('register'),
                    'image_url' => asset('landing/images/inomanage-laptop-dashboard.png'),
                ]];
            } else {
                foreach ($heroSlides as &$slide) {
                    if (! empty($slide['image_path']) && Storage::disk('public')->exists($slide['image_path'])) {
                        $slide['image_url'] = asset(Storage::url($slide['image_path']));
                    } elseif (empty($slide['image_url'])) {
                        $slide['image_url'] = asset('landing/images/inomanage-laptop-dashboard.png');
                    }
                }
                unset($slide);
            }

            $testimonials = json_decode((string) $get('testimonials', ''), true);
            if (! is_array($testimonials) || empty($testimonials)) {
                $testimonials = [];
            }

            $faqs = json_decode((string) $get('faqs', ''), true);
            if (! is_array($faqs) || empty($faqs)) {
                $faqs = [];
            }

            $waRaw = $get('whatsapp_number') ?: $get('contact_phone') ?: '+880 1957-890788';
            $waDigits = preg_replace('/\D+/', '', $waRaw);
            if (str_starts_with($waDigits, '01') && strlen($waDigits) === 11) {
                $waDigits = '88' . $waDigits;
            }

            $fbPage = $get('facebook_page');
            if (empty($fbPage) && $get('facebook_url')) {
                $fbPath = parse_url($get('facebook_url'), PHP_URL_PATH);
                $fbPage = trim((string) $fbPath, '/');
            }
            if (empty($fbPage)) {
                $fbPage = 'inomanage';
            }

            $view->with('siteSettings', [
                'brand_name' => $get('brand_name') ?: 'InoManage',
                'brand_tagline' => $get('brand_tagline') ?: 'CLOUD INVENTORY PLATFORM',
                'site_title' => $get('site_title') ?: 'InoManage ERP - Cloud Inventory Platform',
                'meta_description' => $get('meta_description') ?: 'Cloud inventory, supply chain management, and double-entry accounting software designed for resilient enterprises.',
                'support_email' => $get('support_email') ?: 'support@inomanage.com',
                'contact_phone' => $get('contact_phone') ?: '+880 1957-890788',
                'contact_address' => $get('contact_address') ?: 'House: E1, Road: 16, Pallabi (2nd Phase), Dhaka-1216',
                'footer_copyright' => $get('footer_copyright') ?: 'InoManage. All rights reserved.',
                'logo_url' => $logoUrl,
                'favicon_url' => $faviconPath && Storage::disk('public')->exists($faviconPath) ? asset(Storage::url($faviconPath)) : $defaultLogo,
                'facebook_url' => $get('facebook_url') ?: 'https://facebook.com/inomanage',
                'facebook_page' => $fbPage,
                'whatsapp_number' => $waRaw,
                'whatsapp_digits' => $waDigits,
                'whatsapp_url' => 'https://wa.me/' . $waDigits,
                'linkedin_url' => $get('linkedin_url') ?: 'https://linkedin.com',
                'youtube_url' => $get('youtube_url') ?: 'https://youtube.com',
                'twitter_url' => $get('twitter_url') ?: '',
                'hero_slides' => $heroSlides,
                'testimonials' => $testimonials,
                'faqs' => $faqs,
            ]);
        });
    }
}
