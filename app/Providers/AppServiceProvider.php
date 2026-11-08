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
                    'chip'               => 'Enterprise Multi-Tenant SaaS • 99.99% Uptime SLA • Zero Race Conditions',
                    'headline_prefix'    => 'The Operating System for',
                    'headline_highlight' => 'Modern Inventory & Supply Chains',
                    'subtext'            => 'Empower your organization with multi-warehouse tracking, atomic stock reservation, double-entry financial accounting, POS checkout, workshop service ticketing, and vectorized PDF invoicing — completely tenant-isolated.',
                    'primary_cta_text'   => 'Start 14-Day Free Trial',
                    'primary_cta_url'    => '/register',
                    'secondary_cta_text' => 'Explore Modules',
                    'secondary_cta_url'  => '#features',
                    'mockup_type'        => 'inventory',
                    'trust_badges'       => [
                        'No Credit Card Required',
                        'Instant Setup & Seeding',
                        'Pessimistic Row-Locking',
                        'bKash, Nagad & Cards Supported',
                    ],
                ],
                [
                    'chip'               => 'High-Speed Retail Billing • Barcode Scanning • Multi-Store Ready',
                    'headline_prefix'    => 'Turn Every Device Into a',
                    'headline_highlight' => 'Next-Gen POS Checkout Terminal',
                    'subtext'            => 'Serve customers in seconds with sub-millisecond barcode lookups, digital customer receipts, instant bKash/Nagad and cash handling, and automatic stock deduction across all locations.',
                    'primary_cta_text'   => 'Start Free POS Trial',
                    'primary_cta_url'    => '/register',
                    'secondary_cta_text' => 'See POS Capabilities',
                    'secondary_cta_url'  => '#features',
                    'mockup_type'        => 'pos',
                    'trust_badges'       => [
                        'Thermal Printing Ready',
                        'Split Payments Supported',
                        'Barcode & QR Scanning',
                        'Offline-Resilient Ledger',
                    ],
                ],
                [
                    'chip'               => 'Audit-Grade ERP Ledger • Real-Time Vouchers • Vector PDF Statements',
                    'headline_prefix'    => 'Total Financial Clarity With',
                    'headline_highlight' => 'Automated Double-Entry Accounting',
                    'subtext'            => 'Eliminate manual bookkeeping. Every sales invoice, purchase order, return, and expense automatically writes balanced debit and credit entries to your Chart of Accounts with audit-grade precision.',
                    'primary_cta_text'   => 'Try Accounting Free',
                    'primary_cta_url'    => '/register',
                    'secondary_cta_text' => 'View Financial Reports',
                    'secondary_cta_url'  => '#features',
                    'mockup_type'        => 'accounting',
                    'trust_badges'       => [
                        'Debit/Credit Balance Validation',
                        'Automated Journal Entries',
                        'Real-Time Profit & Loss',
                        'Tax & VAT Ready',
                    ],
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
                'logo_url'          => $logoPath && Storage::disk('public')->exists($logoPath)
                                         ? asset(Storage::url($logoPath)) : null,
                'favicon_url'       => $faviconPath && Storage::disk('public')->exists($faviconPath)
                                         ? asset(Storage::url($faviconPath)) : null,
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
    }
}
