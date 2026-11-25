<?php

namespace App\Http\Controllers;

use App\Models\InvoiceTemplate;
use App\Models\Customer;
use App\Models\SalesOrderItem;
use App\Models\Plan;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf as PDF;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SiteSetting;
use App\Models\Team;
use App\Models\TenantSubscription;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\AccountingService;
use App\Services\SmsOtpService;
use App\Services\SubscriptionService;
use Carbon\Carbon;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class SuperAdminController extends Controller
{
    /**
     * Display the Platform Super-Admin Control Plane with real-time metrics and tenant controls.
     */
    public function index(Request $request)
    {
        $search = $request->input('search', '');
        $statusFilter = $request->input('status', '');
        $planFilter = $request->input('plan_id', '');
        $verifiedFilter = $request->input('verified', '');

        // 1. Query Tenants with owners and subscriptions
        $query = Team::withoutGlobalScopes()
            ->with(['owner', 'subscription.plan'])
            ->withCount(['users', 'teamInvitations'])
            ->whereHas('owner', fn ($q) => $q->where('is_super_admin', false));

        if ($request->filled('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('legal_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhereHas('owner', function ($oq) use ($search) {
                      $oq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $statusFilter);
        }

        if ($request->filled('plan_id')) {
            $query->whereHas('subscription', function ($sq) use ($planFilter) {
                $sq->where('plan_id', $planFilter);
            });
        }

        if ($request->filled('verified')) {
            if ($verifiedFilter === 'verified' || $verifiedFilter === '1') {
                $query->whereHas('owner', function ($oq) {
                    $oq->where(function ($vq) {
                        $vq->whereNotNull('phone_verified_at')
                           ->orWhereNotNull('email_verified_at');
                    });
                });
            } elseif ($verifiedFilter === 'unverified' || $verifiedFilter === '0') {
                $query->whereHas('owner', function ($oq) {
                    $oq->whereNull('phone_verified_at')
                       ->whereNull('email_verified_at');
                });
            }
        }

        $tenants = $query->latest('id')->paginate(12)->withQueryString();

        $teamIds = $tenants->pluck('id')->toArray();

        $productsCounts = DB::table('products')
            ->whereIn('team_id', $teamIds)
            ->whereNull('deleted_at')
            ->groupBy('team_id')
            ->select('team_id', DB::raw('count(*) as count'))
            ->pluck('count', 'team_id');

        $warehouseCounts = DB::table('warehouses')
            ->whereIn('team_id', $teamIds)
            ->whereNull('deleted_at')
            ->groupBy('team_id')
            ->select('team_id', DB::raw('count(*) as count'))
            ->pluck('count', 'team_id');

        $salesCounts = DB::table('sales_orders')
            ->whereIn('team_id', $teamIds)
            ->groupBy('team_id')
            ->select('team_id', DB::raw('count(*) as count'))
            ->pluck('count', 'team_id');

        // Transform tenants with product, warehouse, sales counts, and subscription info
        $tenants->getCollection()->transform(function ($team) use ($productsCounts, $warehouseCounts, $salesCounts) {
            $team->products_count = $productsCounts[$team->id] ?? 0;
            $team->warehouses_count = $warehouseCounts[$team->id] ?? 0;
            $team->sales_orders_count = $salesCounts[$team->id] ?? 0;

            $sub = $team->subscription;
            $effectiveDate = ($sub?->status === 'trialing' && $sub?->trial_ends_at)
                ? $sub->trial_ends_at
                : $sub?->ends_at;

            $team->subscription_info = [
                'plan_id' => $sub?->plan_id,
                'plan_name' => $sub?->plan?->name ?? 'Trial / Custom',
                'plan_slug' => $sub?->plan?->slug ?? 'trial',
                'status' => $sub?->status ?? ($team->status === 'suspended' ? 'suspended' : 'active'),
                'billing_cycle' => $sub?->billing_cycle ?? 'monthly',
                'ends_at' => $effectiveDate ? $effectiveDate->format('Y-m-d') : null,
                'formatted_ends_at' => $effectiveDate ? $effectiveDate->format('M d, Y') : 'N/A',
                'days_remaining' => $sub?->days_remaining ?? 0,
            ];

            return $team;
        });

        return Inertia::render('Admin/Dashboard', [
            'metrics' => fn () => Cache::remember('superadmin_system_metrics', 30, function () {
                $clientTeams = Team::withoutGlobalScopes()->whereHas('owner', fn ($q) => $q->where('is_super_admin', false));
                return [
                    'total_tenants' => (clone $clientTeams)->count(),
                    'active_tenants' => (clone $clientTeams)->where('status', '!=', 'suspended')->count(),
                    'suspended_tenants' => (clone $clientTeams)->where('status', 'suspended')->count(),
                    'total_users' => User::where('is_super_admin', false)->count(),
                    'total_products' => Product::withoutGlobalScopes()->count(),
                    'total_sales' => SalesOrder::withoutGlobalScopes()->count(),
                ];
            }),
            'tenants' => $tenants,
            'plans' => fn () => Cache::remember('superadmin_active_plans', 300, fn () => Plan::where('is_active', true)->orderBy('sort_order')->get()),
            'sms_balance' => fn () => app(SmsOtpService::class)->getBalance(),
            'filters' => [
                'search' => $search,
                'status' => $statusFilter,
                'plan_id' => $planFilter,
                'verified' => $verifiedFilter,
            ],
        ]);
    }

    /**
     * Provision and onboard a brand new tenant organization from the control plane.
     */
    public function storeTenant(Request $request, SubscriptionService $subscriptionService, AccountingService $accountingService)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'legal_name' => 'nullable|string|max:255',
            'owner_name' => 'required|string|max:255',
            'owner_email' => 'required|email|max:255|unique:users,email',
            'owner_password' => 'required|string|min:8',
            'plan_id' => 'required|exists:plans,id',
            'billing_cycle' => 'required|in:monthly,yearly',
            'validity_days' => 'required|integer|min:1|max:3650',
            'currency_code' => 'required|string|max:10',
            'currency_symbol' => 'required|string|max:10',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
        ]);

        return DB::transaction(function () use ($validated, $accountingService) {
            // 1. Create Tenant Owner User
            $owner = User::create([
                'name' => $validated['owner_name'],
                'email' => strtolower(trim($validated['owner_email'])),
                'password' => Hash::make($validated['owner_password']),
                'is_super_admin' => false,
            ]);

            // 2. Create Tenant Team
            $team = Team::forceCreate([
                'user_id' => $owner->id,
                'name' => $validated['name'],
                'legal_name' => (!empty($validated['legal_name'])) ? $validated['legal_name'] : $validated['name'],
                'email' => (!empty($validated['email'])) ? $validated['email'] : $validated['owner_email'],
                'phone' => $validated['phone'] ?? null,
                'city' => $validated['city'] ?? 'Dhaka',
                'country' => $validated['country'] ?? 'Bangladesh',
                'currency_code' => strtoupper($validated['currency_code']),
                'currency_symbol' => $validated['currency_symbol'],
                'personal_team' => true,
                'status' => 'active',
            ]);

            $owner->forceFill(['current_team_id' => $team->id])->save();

            // 3. Seed Roles & Assign Owner
            RolesAndPermissionsSeeder::seedRolesForTeam($team->id);
            setPermissionsTeamId($team->id);
            $owner->assignRole('owner');

            // 4. Provision Default Warehouse Hub
            Warehouse::firstOrCreate(
                ['team_id' => $team->id, 'is_default' => true],
                [
                    'name' => 'Main Warehouse',
                    'code' => 'WH-MAIN',
                    'address' => $team->formattedAddress() ?: 'Central Logistics Hub',
                    'is_active' => true,
                ]
            );

            // 5. Seed Chart of Accounts
            $accountingService->ensureDefaultAccounts($team->id);

            // 6. Provision Subscription
            $validityDays = (int) $validated['validity_days'];
            TenantSubscription::create([
                'team_id' => $team->id,
                'plan_id' => $validated['plan_id'],
                'status' => 'active',
                'billing_cycle' => $validated['billing_cycle'],
                'starts_at' => now(),
                'ends_at' => now()->addDays($validityDays),
                'trial_ends_at' => now()->addDays(14),
            ]);

            Cache::forget('superadmin_system_metrics');

            return redirect()->back()->with('success', "Tenant organization '{$team->name}' provisioned successfully for {$owner->name}.");
        });
    }

    /**
     * Update tenant company profile, contact information, and localization.
     */
    public function updateTenant(Request $request, Team $team)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'legal_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'tax_id' => 'nullable|string|max:50',
            'currency_code' => 'required|string|max:10',
            'currency_symbol' => 'required|string|max:10',
            'address_line1' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
        ]);

        $team->update([
            'name' => $validated['name'],
            'legal_name' => (!empty($validated['legal_name'])) ? $validated['legal_name'] : $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'tax_id' => $validated['tax_id'] ?? null,
            'currency_code' => strtoupper($validated['currency_code']),
            'currency_symbol' => $validated['currency_symbol'],
            'address_line1' => $validated['address_line1'] ?? null,
            'city' => $validated['city'] ?? null,
            'state' => $validated['state'] ?? null,
            'country' => $validated['country'] ?? null,
        ]);

        return redirect()->back()->with('success', "Tenant details for '{$team->name}' updated successfully.");
    }

    /**
     * Direct Super-Admin override of tenant subscription plan, expiration, and status.
     */
    public function updateSubscription(Request $request, Team $team, SubscriptionService $subscriptionService)
    {
        $validated = $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'status' => 'required|in:active,trialing,past_due,expired,suspended',
            'billing_cycle' => 'required|in:monthly,yearly',
            'extension_days' => 'nullable|integer|min:0|max:3650',
            'custom_ends_at' => 'nullable|date',
        ]);

        $sub = $team->subscription()->first() ?? $subscriptionService->ensureTenantSubscription($team);

        // Determine current expiration date to base extensions on
        $currentExpiry = ($sub->status === 'trialing' && $sub->trial_ends_at)
            ? $sub->trial_ends_at
            : $sub->ends_at;

        $endsAt = $currentExpiry;

        if (!empty($validated['custom_ends_at'])) {
            $endsAt = Carbon::parse($validated['custom_ends_at'])->endOfDay();
        } elseif (!empty($validated['extension_days']) && (int) $validated['extension_days'] > 0) {
            $base = ($currentExpiry && $currentExpiry->isFuture()) ? $currentExpiry : now();
            $endsAt = $base->copy()->addDays((int) $validated['extension_days'])->endOfDay();
        }

        $updateData = [
            'plan_id' => $validated['plan_id'],
            'status' => $validated['status'],
            'billing_cycle' => $validated['billing_cycle'],
            'ends_at' => $endsAt,
        ];

        // Keep trial_ends_at synchronized whenever status is trialing
        if ($validated['status'] === 'trialing') {
            $updateData['trial_ends_at'] = $endsAt;
        }

        $sub->update($updateData);

        // Sync team suspension status
        if ($validated['status'] === 'suspended' && $team->status !== 'suspended') {
            $team->update(['status' => 'suspended']);
            Cache::forget('superadmin_system_metrics');
        } elseif ($validated['status'] === 'active' && $team->status === 'suspended') {
            $team->update(['status' => 'active']);
            Cache::forget('superadmin_system_metrics');
        }

        $planName = Plan::find($validated['plan_id'])?->name ?? 'Selected';
        return redirect()->back()->with('success', "Subscription for '{$team->name}' updated to {$planName} ({$validated['status']}).");
    }

    /**
     * Permanently remove a tenant organization with security guards.
     */
    public function destroyTenant(Request $request, Team $team)
    {
        if ($team->owner && $team->owner->is_super_admin) {
            return redirect()->back()->with('error', 'Cannot delete platform Super-Admin organization.');
        }

        $name = $team->name;

        DB::transaction(function () use ($team) {
            $team->delete();
        });

        Cache::forget('superadmin_system_metrics');

        return redirect()->back()->with('success', "Tenant organization '{$name}' has been permanently deleted.");
    }

    /**
     * Toggle tenant suspension status with immediate lockout enforcement.
     */
    public function toggleStatus(Team $team)
    {
        if ($team->status === 'suspended') {
            $team->update(['status' => 'active']);
            $msg = "Tenant '{$team->name}' has been reactivated successfully.";
        } else {
            $team->update(['status' => 'suspended']);
            $msg = "Tenant '{$team->name}' has been suspended. User access is now blocked.";
        }

        Cache::forget('superadmin_system_metrics');

        return redirect()->back()->with('success', $msg);
    }

    /**
     * Impersonate a tenant by switching workspace context.
     */
    public function switchToTenant(Team $team)
    {
        $user = Auth::user();

        $user->forceFill([
            'current_team_id' => $team->id,
        ])->save();

        session()->put('impersonated_tenant_id', $team->id);

        return redirect()->route('dashboard')->with('success', "Switched workspace context to '{$team->name}'.");
    }

    /**
     * Show site settings management form for Super Admin.
     */
    public function siteSettings()
    {
        $logoPath    = SiteSetting::get('logo_path');
        $faviconPath = SiteSetting::get('favicon_path');
        $brandName   = SiteSetting::get('brand_name', 'InoManage');

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

        $heroSlidesJson = SiteSetting::get('hero_slides');
        $heroSlides = !empty($heroSlidesJson) ? json_decode($heroSlidesJson, true) : null;
        if (!is_array($heroSlides) || empty($heroSlides)) {
            $heroSlides = $defaultHeroSlides;
        }

        foreach ($heroSlides as &$slide) {
            if (!empty($slide['image_path']) && Storage::disk('public')->exists($slide['image_path'])) {
                $slide['image_url'] = asset(Storage::url($slide['image_path']));
            }
        }
        unset($slide);

        $testimonialsJson = SiteSetting::get('testimonials');
        $testimonials = !empty($testimonialsJson) ? json_decode($testimonialsJson, true) : null;
        if (!is_array($testimonials) || empty($testimonials)) {
            $testimonials = $defaultTestimonials;
        }

        $faqsJson = SiteSetting::get('faqs');
        $faqs = !empty($faqsJson) ? json_decode($faqsJson, true) : null;
        if (!is_array($faqs) || empty($faqs)) {
            $faqs = $defaultFaqs;
        }

        return Inertia::render('Admin/SiteSettings', [
            'settings' => [
                'logo_url'         => $logoPath && Storage::disk('public')->exists($logoPath) ? asset(Storage::url($logoPath)) : null,
                'favicon_url'      => $faviconPath && Storage::disk('public')->exists($faviconPath) ? asset(Storage::url($faviconPath)) : null,
                'brand_name'       => $brandName,
                'brand_tagline'    => SiteSetting::get('brand_tagline', 'Cloud Inventory Platform'),
                'site_title'       => SiteSetting::get('site_title', 'InoManage Cloud Inventory Management Software'),
                'meta_description' => SiteSetting::get('meta_description', 'Next-generation multi-tenant ERP platform for modern businesses. Multi-warehouse tracking, atomic stock locking, double-entry accounting, POS billing, workshop service ticketing, and vectorized PDF invoicing.'),
                'support_email'    => SiteSetting::get('support_email', ''),
                'contact_phone'    => SiteSetting::get('contact_phone', ''),
                'contact_address'  => SiteSetting::get('contact_address', ''),
                'footer_copyright' => SiteSetting::get('footer_copyright', 'InoManage. All rights reserved.'),
                'facebook_url'     => SiteSetting::get('facebook_url', 'https://facebook.com/inomanage'),
                'facebook_page'    => SiteSetting::get('facebook_page', 'inomanage'),
                'whatsapp_number'  => SiteSetting::get('whatsapp_number', '+880 1957-890788'),
                'linkedin_url'     => SiteSetting::get('linkedin_url', 'https://linkedin.com/company/inomanage'),
                'youtube_url'      => SiteSetting::get('youtube_url', 'https://youtube.com/@inomanage'),
                'twitter_url'      => SiteSetting::get('twitter_url', ''),
                'hero_chip_text'   => SiteSetting::get('hero_chip_text', 'Enterprise Multi-Tenant SaaS • 99.99% Uptime SLA • Zero Race Conditions'),
                'hero_headline'    => SiteSetting::get('hero_headline', 'The Operating System for Modern Inventory & Supply Chains'),
                'hero_subtext'     => SiteSetting::get('hero_subtext', 'Empower your organization with multi-warehouse tracking, atomic stock reservation, double-entry financial accounting, POS checkout, workshop service ticketing, and vectorized PDF invoicing — completely tenant-isolated.'),
                'hero_slides'      => $heroSlides,
                'testimonials'     => $testimonials,
                'faqs'             => $faqs,
                'terms_and_conditions' => SiteSetting::get('terms_and_conditions', ''),
                'privacy_policy'       => SiteSetting::get('privacy_policy', ''),

                // Platform Maintenance Mode
                'maintenance_mode'          => (bool) SiteSetting::get('maintenance_mode', false),
                'maintenance_title'         => SiteSetting::get('maintenance_title', 'System Upgrade in Progress'),
                'maintenance_message'       => SiteSetting::get('maintenance_message', 'We are performing scheduled infrastructure upgrades and database optimizations. The platform will be back online shortly.'),
                'maintenance_duration'      => SiteSetting::get('maintenance_duration', '15-30 minutes'),
                'maintenance_bypass_secret' => SiteSetting::get('maintenance_bypass_secret', ''),

                // Subscription Payment Accounts & Banking
                'payment_bank_name'           => SiteSetting::get('payment_bank_name', 'City Bank Ltd'),
                'payment_bank_account_number' => SiteSetting::get('payment_bank_account_number', '1102938475001'),
                'payment_bank_account_name'   => SiteSetting::get('payment_bank_account_name', 'SaaS Inventory BD Ltd'),
                'payment_bank_branch'         => SiteSetting::get('payment_bank_branch', 'Gulshan Branch'),
                'payment_bank_routing'        => SiteSetting::get('payment_bank_routing', '225261145'),
                'payment_bank_swift'          => SiteSetting::get('payment_bank_swift', 'CIBLBDDH'),
                'payment_bank_type'           => SiteSetting::get('payment_bank_type', 'Corporate Account'),
                'payment_bank_instructions'   => SiteSetting::get('payment_bank_instructions', 'Direct deposit, BEFTN, NPSB or RTGS to our corporate bank account'),

                'payment_bkash_name'          => SiteSetting::get('payment_bkash_name', 'bKash Merchant'),
                'payment_bkash_number'        => SiteSetting::get('payment_bkash_number', '01712-345678'),
                'payment_bkash_counter'       => SiteSetting::get('payment_bkash_counter', 'Counter 1'),
                'payment_bkash_type'          => SiteSetting::get('payment_bkash_type', 'Merchant Payment'),
                'payment_bkash_instructions'  => SiteSetting::get('payment_bkash_instructions', 'Select "Payment" option in bKash App'),

                'payment_nagad_name'          => SiteSetting::get('payment_nagad_name', 'Nagad Merchant'),
                'payment_nagad_number'        => SiteSetting::get('payment_nagad_number', '01812-345678'),
                'payment_nagad_counter'       => SiteSetting::get('payment_nagad_counter', 'Counter 1'),
                'payment_nagad_type'          => SiteSetting::get('payment_nagad_type', 'Merchant Payment'),
                'payment_nagad_instructions'  => SiteSetting::get('payment_nagad_instructions', 'Select "Merchant Pay" in Nagad App'),

                'payment_rocket_name'         => SiteSetting::get('payment_rocket_name', 'Rocket Biller'),
                'payment_rocket_number'       => SiteSetting::get('payment_rocket_number', '01912-345678-9'),
                'payment_rocket_counter'      => SiteSetting::get('payment_rocket_counter', 'Biller ID 4567'),
                'payment_rocket_type'         => SiteSetting::get('payment_rocket_type', 'Merchant / Biller'),
                'payment_rocket_instructions' => SiteSetting::get('payment_rocket_instructions', 'Pay via Biller ID or Merchant Pay'),
            ],
        ]);
    }

    /**
     * Update site branding and configuration settings.
     */
    public function updateSiteSettings(Request $request)
    {
        $request->validate([
            'logo'             => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,gif', 'max:2048'],
            'favicon'          => ['nullable', 'file', 'mimes:ico,png', 'max:512'],
            'brand_name'       => ['required', 'string', 'max:60'],
            'brand_tagline'    => ['required', 'string', 'max:80'],
            'site_title'       => ['required', 'string', 'max:120'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'support_email'    => ['nullable', 'email', 'max:100'],
            'contact_phone'    => ['nullable', 'string', 'max:30'],
            'contact_address'  => ['nullable', 'string', 'max:300'],
            'footer_copyright' => ['required', 'string', 'max:120'],
            'facebook_url'     => ['nullable', 'string', 'max:255'],
            'facebook_page'    => ['nullable', 'string', 'max:100'],
            'whatsapp_number'  => ['nullable', 'string', 'max:50'],
            'linkedin_url'     => ['nullable', 'string', 'max:255'],
            'youtube_url'      => ['nullable', 'string', 'max:255'],
            'twitter_url'      => ['nullable', 'string', 'max:255'],
            'hero_chip_text'   => ['nullable', 'string', 'max:150'],
            'hero_headline'    => ['nullable', 'string', 'max:150'],
            'hero_subtext'     => ['nullable', 'string', 'max:600'],
            'hero_slides'      => ['nullable', 'array'],
            'testimonials'     => ['nullable', 'array'],
            'faqs'             => ['nullable', 'array'],
            'terms_and_conditions' => ['nullable', 'string'],
            'privacy_policy'       => ['nullable', 'string'],

            // Subscription Payment Accounts & Banking validation
            'payment_bank_name'           => ['nullable', 'string', 'max:120'],
            'payment_bank_account_number' => ['nullable', 'string', 'max:60'],
            'payment_bank_account_name'   => ['nullable', 'string', 'max:150'],
            'payment_bank_branch'         => ['nullable', 'string', 'max:120'],
            'payment_bank_routing'        => ['nullable', 'string', 'max:60'],
            'payment_bank_swift'          => ['nullable', 'string', 'max:60'],
            'payment_bank_type'           => ['nullable', 'string', 'max:80'],
            'payment_bank_instructions'   => ['nullable', 'string', 'max:500'],

            'payment_bkash_name'          => ['nullable', 'string', 'max:120'],
            'payment_bkash_number'        => ['nullable', 'string', 'max:60'],
            'payment_bkash_counter'       => ['nullable', 'string', 'max:60'],
            'payment_bkash_type'          => ['nullable', 'string', 'max:80'],
            'payment_bkash_instructions'  => ['nullable', 'string', 'max:500'],

            'payment_nagad_name'          => ['nullable', 'string', 'max:120'],
            'payment_nagad_number'        => ['nullable', 'string', 'max:60'],
            'payment_nagad_counter'       => ['nullable', 'string', 'max:60'],
            'payment_nagad_type'          => ['nullable', 'string', 'max:80'],
            'payment_nagad_instructions'  => ['nullable', 'string', 'max:500'],

            'payment_rocket_name'         => ['nullable', 'string', 'max:120'],
            'payment_rocket_number'       => ['nullable', 'string', 'max:60'],
            'payment_rocket_counter'      => ['nullable', 'string', 'max:60'],
            'payment_rocket_type'         => ['nullable', 'string', 'max:80'],
            'payment_rocket_instructions' => ['nullable', 'string', 'max:500'],

            // Platform Maintenance Mode
            'maintenance_mode'          => ['nullable', 'boolean'],
            'maintenance_title'         => ['nullable', 'string', 'max:150'],
            'maintenance_message'       => ['nullable', 'string', 'max:1000'],
            'maintenance_duration'      => ['nullable', 'string', 'max:100'],
            'maintenance_bypass_secret' => ['nullable', 'string', 'max:100'],
        ]);

        // Handle logo upload or removal
        if ($request->boolean('remove_logo')) {
            $oldLogo = SiteSetting::get('logo_path');
            if ($oldLogo) {
                if (Storage::disk('public')->exists($oldLogo)) {
                    Storage::disk('public')->delete($oldLogo);
                }
                $pubFile = public_path('storage/' . $oldLogo);
                if (file_exists($pubFile)) {
                    @unlink($pubFile);
                }
            }
            SiteSetting::set('logo_path', null);
        } elseif ($request->hasFile('logo')) {
            $oldLogo = SiteSetting::get('logo_path');
            if ($oldLogo) {
                if (Storage::disk('public')->exists($oldLogo)) {
                    Storage::disk('public')->delete($oldLogo);
                }
                $pubFile = public_path('storage/' . $oldLogo);
                if (file_exists($pubFile)) {
                    @unlink($pubFile);
                }
            }
            $logoPath = $request->file('logo')->store('site', 'public');
            SiteSetting::set('logo_path', $logoPath);

            // Mirror to public/storage if public/storage is a real directory
            try {
                $destDir = public_path('storage/site');
                if (!is_dir($destDir)) {
                    @mkdir($destDir, 0755, true);
                }
                @copy(storage_path('app/public/' . $logoPath), public_path('storage/' . $logoPath));
            } catch (\Throwable $e) {}
        }

        // Handle favicon upload or removal
        if ($request->boolean('remove_favicon')) {
            $oldFavicon = SiteSetting::get('favicon_path');
            if ($oldFavicon) {
                if (Storage::disk('public')->exists($oldFavicon)) {
                    Storage::disk('public')->delete($oldFavicon);
                }
                $pubFile = public_path('storage/' . $oldFavicon);
                if (file_exists($pubFile)) {
                    @unlink($pubFile);
                }
            }
            SiteSetting::set('favicon_path', null);
        } elseif ($request->hasFile('favicon')) {
            $oldFavicon = SiteSetting::get('favicon_path');
            if ($oldFavicon) {
                if (Storage::disk('public')->exists($oldFavicon)) {
                    Storage::disk('public')->delete($oldFavicon);
                }
                $pubFile = public_path('storage/' . $oldFavicon);
                if (file_exists($pubFile)) {
                    @unlink($pubFile);
                }
            }
            $faviconPath = $request->file('favicon')->store('site', 'public');
            SiteSetting::set('favicon_path', $faviconPath);

            // Mirror to public/storage if public/storage is a real directory
            try {
                $destDir = public_path('storage/site');
                if (!is_dir($destDir)) {
                    @mkdir($destDir, 0755, true);
                }
                @copy(storage_path('app/public/' . $faviconPath), public_path('storage/' . $faviconPath));
            } catch (\Throwable $e) {}
        }

        // Save text settings
        $keys = [
            'brand_name',
            'brand_tagline',
            'site_title',
            'meta_description',
            'support_email',
            'contact_phone',
            'contact_address',
            'footer_copyright',
            'facebook_url',
            'facebook_page',
            'whatsapp_number',
            'linkedin_url',
            'youtube_url',
            'twitter_url',
            'hero_chip_text',
            'hero_headline',
            'hero_subtext',
            'terms_and_conditions',
            'privacy_policy',

            // Payment Accounts
            'payment_bank_name',
            'payment_bank_account_number',
            'payment_bank_account_name',
            'payment_bank_branch',
            'payment_bank_routing',
            'payment_bank_type',
            'payment_bank_instructions',

            'payment_bkash_name',
            'payment_bkash_number',
            'payment_bkash_counter',
            'payment_bkash_type',
            'payment_bkash_instructions',

            'payment_nagad_name',
            'payment_nagad_number',
            'payment_nagad_counter',
            'payment_nagad_type',
            'payment_nagad_instructions',

            'payment_rocket_name',
            'payment_rocket_number',
            'payment_rocket_counter',
            'payment_rocket_type',
            'payment_rocket_instructions',

            // Maintenance Mode text fields
            'maintenance_title',
            'maintenance_message',
            'maintenance_duration',
            'maintenance_bypass_secret',
        ];

        foreach ($keys as $key) {
            SiteSetting::set($key, $request->input($key));
        }

        SiteSetting::set('maintenance_mode', $request->boolean('maintenance_mode') ? '1' : '0');

        if ($request->has('hero_slides')) {
            $slidesVal = $request->input('hero_slides');
            if (is_string($slidesVal)) {
                $slidesVal = json_decode($slidesVal, true);
            }
            if (is_array($slidesVal)) {
                $slidesVal = array_values($slidesVal);

                // Handle slide image uploads
                if ($request->hasFile('slide_images')) {
                    foreach ($request->file('slide_images') as $idx => $file) {
                        if ($file && $file->isValid()) {
                            // Delete old image if exists
                            if (isset($slidesVal[$idx]['image_path']) && !empty($slidesVal[$idx]['image_path'])) {
                                if (Storage::disk('public')->exists($slidesVal[$idx]['image_path'])) {
                                    Storage::disk('public')->delete($slidesVal[$idx]['image_path']);
                                }
                                $oldPub = public_path('storage/' . $slidesVal[$idx]['image_path']);
                                if (file_exists($oldPub)) {
                                    @unlink($oldPub);
                                }
                            }

                            $path = $file->store('site/hero', 'public');
                            $slidesVal[$idx]['image_path'] = $path;
                            $slidesVal[$idx]['image_url']  = asset(Storage::url($path));

                            // Mirror to public/storage
                            try {
                                $destDir = public_path('storage/site/hero');
                                if (!is_dir($destDir)) {
                                    @mkdir($destDir, 0755, true);
                                }
                                @copy(storage_path('app/public/' . $path), public_path('storage/' . $path));
                            } catch (\Throwable $e) {}
                        }
                    }
                }

                // Handle slide image removals
                if ($request->has('remove_slide_images')) {
                    foreach ($request->input('remove_slide_images', []) as $idx => $shouldRemove) {
                        if ($shouldRemove && isset($slidesVal[$idx])) {
                            if (!empty($slidesVal[$idx]['image_path'])) {
                                if (Storage::disk('public')->exists($slidesVal[$idx]['image_path'])) {
                                    Storage::disk('public')->delete($slidesVal[$idx]['image_path']);
                                }
                                $oldPub = public_path('storage/' . $slidesVal[$idx]['image_path']);
                                if (file_exists($oldPub)) {
                                    @unlink($oldPub);
                                }
                            }
                            $slidesVal[$idx]['image_path'] = null;
                            $slidesVal[$idx]['image_url']  = null;
                        }
                    }
                }

                SiteSetting::set('hero_slides', json_encode($slidesVal));
            }
        }

        if ($request->has('testimonials')) {
            $testimonialsVal = $request->input('testimonials');
            SiteSetting::set('testimonials', is_array($testimonialsVal) ? json_encode(array_values($testimonialsVal)) : $testimonialsVal);
        }

        if ($request->has('faqs')) {
            $faqsVal = $request->input('faqs');
            SiteSetting::set('faqs', is_array($faqsVal) ? json_encode(array_values($faqsVal)) : $faqsVal);
        }

        return redirect()->route('admin.site-settings')->with('success', 'Site settings updated successfully.');
    }

    /**
     * Display the BulkSMS Portal and SMS Gateway management center.
     */
    public function smsGateway(Request $request, SmsOtpService $smsOtpService)
    {
        $balance = $smsOtpService->getBalance();
        $config = $smsOtpService->getGatewayConfig();

        $recentUsersWithPhone = User::query()
            ->whereNotNull('phone')
            ->select('id', 'name', 'phone', 'phone_verified_at', 'phone_otp_expires_at', 'created_at')
            ->latest('id')
            ->take(15)
            ->get()
            ->map(function ($u) use ($smsOtpService) {
                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'masked_phone' => $smsOtpService->maskPhone($u->phone),
                    'phone' => $u->phone,
                    'is_verified' => !is_null($u->phone_verified_at),
                    'verified_at' => $u->phone_verified_at ? $u->phone_verified_at->format('M d, Y h:i A') : null,
                    'last_otp_at' => $u->phone_otp_expires_at ? $u->phone_otp_expires_at->copy()->subMinutes(SmsOtpService::OTP_EXPIRY_MINUTES)->format('M d, Y h:i A') : 'N/A',
                ];
            });

        return Inertia::render('Admin/SmsGateway', [
            'balance' => $balance,
            'gatewayConfig' => $config,
            'recentUsers' => $recentUsersWithPhone,
        ]);
    }

    /**
     * Force refresh the live BulkSMS balance.
     */
    public function refreshSmsBalance(Request $request, SmsOtpService $smsOtpService)
    {
        $balance = $smsOtpService->getBalance(forceRefresh: true);

        if ($request->wantsJson()) {
            return response()->json($balance);
        }

        return redirect()->back()->with('success', 'SMS portal balance refreshed successfully.');
    }

    /**
     * Send a single direct test SMS from the control plane.
     */
    public function sendTestSms(Request $request, SmsOtpService $smsOtpService)
    {
        $validated = $request->validate([
            'phone' => 'required|string|min:11|max:20',
            'message' => 'required|string|max:160',
        ]);

        $result = $smsOtpService->sendTestSms($validated['phone'], $validated['message']);

        if ($result['success']) {
            return redirect()->back()->with('success', 'Test SMS successfully dispatched!');
        }

        return redirect()->back()->with('error', $result['message'] ?? 'Failed to dispatch test SMS.');
    }

    /**
     * Display the subscription payment accounts management page.
     */
    public function paymentAccounts()
    {
        return Inertia::render('Admin/PaymentAccounts', [
            'settings' => [
                'payment_bank_active'         => filter_var(SiteSetting::get('payment_bank_active', true), FILTER_VALIDATE_BOOLEAN),
                'payment_bank_name'           => SiteSetting::get('payment_bank_name', 'City Bank Ltd'),
                'payment_bank_account_number' => SiteSetting::get('payment_bank_account_number', '1102938475001'),
                'payment_bank_account_name'   => SiteSetting::get('payment_bank_account_name', 'SaaS Inventory BD Ltd'),
                'payment_bank_branch'         => SiteSetting::get('payment_bank_branch', 'Gulshan Branch'),
                'payment_bank_routing'        => SiteSetting::get('payment_bank_routing', '225261145'),
                'payment_bank_swift'          => SiteSetting::get('payment_bank_swift', 'CIBLBDDH'),
                'payment_bank_type'           => SiteSetting::get('payment_bank_type', 'Corporate Account'),
                'payment_bank_instructions'   => SiteSetting::get('payment_bank_instructions', 'Direct deposit, BEFTN, NPSB or RTGS to our corporate bank account'),

                'payment_bkash_active'        => filter_var(SiteSetting::get('payment_bkash_active', true), FILTER_VALIDATE_BOOLEAN),
                'payment_bkash_name'          => SiteSetting::get('payment_bkash_name', 'bKash Merchant'),
                'payment_bkash_number'        => SiteSetting::get('payment_bkash_number', '01712-345678'),
                'payment_bkash_counter'       => SiteSetting::get('payment_bkash_counter', 'Counter 1'),
                'payment_bkash_type'          => SiteSetting::get('payment_bkash_type', 'Merchant Payment'),
                'payment_bkash_instructions'  => SiteSetting::get('payment_bkash_instructions', 'Select "Payment" option in bKash App'),

                'payment_nagad_active'        => filter_var(SiteSetting::get('payment_nagad_active', true), FILTER_VALIDATE_BOOLEAN),
                'payment_nagad_name'          => SiteSetting::get('payment_nagad_name', 'Nagad Merchant'),
                'payment_nagad_number'        => SiteSetting::get('payment_nagad_number', '01812-345678'),
                'payment_nagad_counter'       => SiteSetting::get('payment_nagad_counter', 'Counter 1'),
                'payment_nagad_type'          => SiteSetting::get('payment_nagad_type', 'Merchant Payment'),
                'payment_nagad_instructions'  => SiteSetting::get('payment_nagad_instructions', 'Select "Merchant Pay" in Nagad App'),

                'payment_rocket_active'       => filter_var(SiteSetting::get('payment_rocket_active', true), FILTER_VALIDATE_BOOLEAN),
                'payment_rocket_name'         => SiteSetting::get('payment_rocket_name', 'Rocket Biller'),
                'payment_rocket_number'       => SiteSetting::get('payment_rocket_number', '01912-345678-9'),
                'payment_rocket_counter'      => SiteSetting::get('payment_rocket_counter', 'Biller ID 4567'),
                'payment_rocket_type'         => SiteSetting::get('payment_rocket_type', 'Merchant / Biller'),
                'payment_rocket_instructions' => SiteSetting::get('payment_rocket_instructions', 'Pay via Biller ID or Merchant Pay'),
            ],
        ]);
    }

    /**
     * Update subscription payment accounts and banking configurations.
     */
    public function updatePaymentAccounts(Request $request)
    {
        $validated = $request->validate([
            'payment_bank_active'         => ['nullable', 'boolean'],
            'payment_bank_name'           => ['nullable', 'string', 'max:120'],
            'payment_bank_account_number' => ['nullable', 'string', 'max:60'],
            'payment_bank_account_name'   => ['nullable', 'string', 'max:150'],
            'payment_bank_branch'         => ['nullable', 'string', 'max:120'],
            'payment_bank_routing'        => ['nullable', 'string', 'max:60'],
            'payment_bank_swift'          => ['nullable', 'string', 'max:60'],
            'payment_bank_type'           => ['nullable', 'string', 'max:80'],
            'payment_bank_instructions'   => ['nullable', 'string', 'max:500'],

            'payment_bkash_active'        => ['nullable', 'boolean'],
            'payment_bkash_name'          => ['nullable', 'string', 'max:120'],
            'payment_bkash_number'        => ['nullable', 'string', 'max:60'],
            'payment_bkash_counter'       => ['nullable', 'string', 'max:60'],
            'payment_bkash_type'          => ['nullable', 'string', 'max:80'],
            'payment_bkash_instructions'  => ['nullable', 'string', 'max:500'],

            'payment_nagad_active'        => ['nullable', 'boolean'],
            'payment_nagad_name'          => ['nullable', 'string', 'max:120'],
            'payment_nagad_number'        => ['nullable', 'string', 'max:60'],
            'payment_nagad_counter'       => ['nullable', 'string', 'max:60'],
            'payment_nagad_type'          => ['nullable', 'string', 'max:80'],
            'payment_nagad_instructions'  => ['nullable', 'string', 'max:500'],

            'payment_rocket_active'       => ['nullable', 'boolean'],
            'payment_rocket_name'         => ['nullable', 'string', 'max:120'],
            'payment_rocket_number'       => ['nullable', 'string', 'max:60'],
            'payment_rocket_counter'      => ['nullable', 'string', 'max:60'],
            'payment_rocket_type'         => ['nullable', 'string', 'max:80'],
            'payment_rocket_instructions' => ['nullable', 'string', 'max:500'],

            // Platform Maintenance Mode
            'maintenance_mode'          => ['nullable', 'boolean'],
            'maintenance_title'         => ['nullable', 'string', 'max:150'],
            'maintenance_message'       => ['nullable', 'string', 'max:1000'],
            'maintenance_duration'      => ['nullable', 'string', 'max:100'],
            'maintenance_bypass_secret' => ['nullable', 'string', 'max:100'],
        ]);

        $activeKeys = ['payment_bank_active', 'payment_bkash_active', 'payment_nagad_active', 'payment_rocket_active'];
        foreach ($activeKeys as $actKey) {
            SiteSetting::set($actKey, $request->boolean($actKey) ? '1' : '0');
        }

        foreach ($validated as $key => $value) {
            if (!in_array($key, $activeKeys)) {
                SiteSetting::set($key, $value);
            }
        }

        return redirect()->back()->with('success', 'Subscription payment accounts and banking details updated successfully.');
    }

    /**
     * Display Super Admin Invoice Templates Management.
     */
    public function invoiceTemplates()
    {
        $templates = InvoiceTemplate::orderBy('sort_order', 'asc')->get();

        $stats = [
            'total' => $templates->count(),
            'active' => $templates->where('is_active', true)->count(),
            'default' => $templates->firstWhere('is_default', true)?->name ?? 'None',
        ];

        return Inertia::render('Admin/InvoiceTemplates', [
            'templates' => $templates,
            'stats' => $stats,
        ]);
    }

    /**
     * Toggle active status of an invoice template.
     */
    public function toggleInvoiceTemplate(InvoiceTemplate $template)
    {
        if ($template->is_default && $template->is_active) {
            return redirect()->back()->with('error', 'The default invoice template cannot be deactivated. Please set another template as default first.');
        }

        $template->update(['is_active' => !$template->is_active]);

        $statusText = $template->is_active ? 'activated' : 'deactivated';
        return redirect()->back()->with('success', "Invoice template '{$template->name}' has been {$statusText}.");
    }

    /**
     * Set an invoice template as system default.
     */
    public function setDefaultInvoiceTemplate(InvoiceTemplate $template)
    {
        InvoiceTemplate::where('id', '!=', $template->id)->update(['is_default' => false]);
        $template->update(['is_default' => true, 'is_active' => true]);

        return redirect()->back()->with('success', "'{$template->name}' is now set as the system default invoice template.");
    }

    /**
     * Stream a live sample preview for an invoice template.
     */
    public function previewInvoiceTemplate(InvoiceTemplate $template)
    {
        $mockTeam = new Team([
            'name' => 'Demo Enterprise Logistics Ltd',
            'legal_name' => 'Demo Enterprise Logistics Ltd',
            'phone' => '+880 1812-345678',
            'email' => 'billing@demo-enterprise.com',
            'address_line1' => 'Plot 45, Gulshan Commercial Area',
            'city' => 'Dhaka',
            'country' => 'Bangladesh',
            'tax_id' => 'VAT-88492019-BIN',
            'currency_symbol' => '৳',
            'currency_code' => 'BDT',
            'invoice_prefix' => 'INV-',
            'invoice_template' => $template->key,
        ]);

        $sampleOrder = new SalesOrder([
            'order_number' => 'INV-DEMO-2026-001',
            'order_date' => now(),
            'payment_status' => 'paid',
            'subtotal' => 4500.00,
            'tax_amount' => 225.00,
            'discount_amount' => 150.00,
            'shipping_cost' => 100.00,
            'additional_charges' => [
                ['name' => 'Installation Charge', 'amount' => 200.00],
                ['name' => 'Handling Fee', 'amount' => 50.00],
            ],
            'additional_charges_total' => 250.00,
            'total_amount' => 4925.00,
            'paid_amount' => 4925.00,
            'tendered_amount' => 5000.00,
            'change_amount' => 75.00,
            'notes' => 'Sample preview of ' . $template->name . ' invoice template generated by Super Admin.',
        ]);

        $sampleOrder->setRelation('customer', new Customer([
            'name' => 'Apex Global Technologies Ltd',
            'company_name' => 'Apex Group',
            'phone' => '+880 1711-987654',
            'email' => 'accounts@apex-tech.com',
            'address' => 'Tejgaon Industrial Area, Dhaka-1208',
        ]));

        $sampleOrder->setRelation('warehouse', new Warehouse([
            'name' => 'Central Distribution Hub',
            'code' => 'CDH-01',
        ]));

        $item1 = new SalesOrderItem([
            'quantity' => 2,
            'unit_price' => 1500.00,
            'subtotal' => 3000.00,
            'warranty_days' => 365,
        ]);
        $item1->setRelation('product', new Product([
            'name' => 'Enterprise Smart Switch 24-Port Gigabit',
            'sku' => 'NET-SW-24G',
            'unit' => 'pcs',
        ]));

        $item2 = new SalesOrderItem([
            'quantity' => 1,
            'unit_price' => 1500.00,
            'subtotal' => 1500.00,
            'warranty_days' => 180,
        ]);
        $item2->setRelation('product', new Product([
            'name' => 'Commercial Dual-Band Wi-Fi 6 AP Pro',
            'sku' => 'WIFI-AP-AX3000',
            'unit' => 'pcs',
        ]));

        $sampleOrder->setRelation('items', collect([$item1, $item2]));
        $sampleOrder->setRelation('team', $mockTeam);
        $sampleOrder->setRelation('returns', collect());

        $viewName = view()->exists("pdf.invoices.{$template->key}")
            ? "pdf.invoices.{$template->key}"
            : (view()->exists('pdf.sales-invoice') ? 'pdf.sales-invoice' : 'pdf.invoices.invoice_pdf1');

        $pdf = PDF::loadView($viewName, [
            'order' => $sampleOrder,
            'sales' => $sampleOrder,
            'team' => $mockTeam,
            'customer' => $sampleOrder->customer,
            'items' => $sampleOrder->items,
            'returns' => collect(),
        ], [], [
            'title' => "Invoice-Preview-{$template->key}",
            'margin_top' => 14,
            'margin_bottom' => 14,
            'margin_left' => 14,
            'margin_right' => 14,
        ]);

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"preview-{$template->key}.pdf\"",
        ]);
    }
}
