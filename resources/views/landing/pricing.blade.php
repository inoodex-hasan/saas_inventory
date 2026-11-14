@extends('layouts.landing')

@section('title', 'InoManage ERP - Pricing & Plans')

@section('content')
<section class="pricing-section position-relative" id="pricing" aria-labelledby="pricing-title">
      <div class="container position-relative z-1">
        <div class="pricing-intro text-center">
          <h2 id="pricing-title" class="pricing-main-heading mb-3">
            Predictable pricing for<br class="pricing-heading-break" />
            <span class="text-emerald-gradient">businesses that scale.</span>
          </h2>
          <p class="pricing-main-desc mb-4">
            No hidden per-transaction penalties. 14-day free trial on every tier with complimentary migration support.
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
    <section class="pricing-comparison-section" aria-labelledby="pricing-comparison-title">
      <div class="container">
        <div class="pricing-comparison-heading text-center">
          <span class="pricing-comparison-kicker" data-bn="সঠিক প্যাকেজ বেছে নিন">Choose the right fit</span>
          <h2 id="pricing-comparison-title" data-bn="এক নজরে সব প্ল্যানের তুলনা">Compare plans at a glance</h2>
          <p data-bn="প্রতিটি প্ল্যানে রয়েছে মূল প্ল্যাটফর্ম। সঠিক প্ল্যান খুঁজে পেতে ক্ষমতা ও সহায়তা তুলনা করুন।">Every plan includes the core platform. Compare capacity and support to find the right starting point.</p>
        </div>
        
        <div class="d-md-none text-center mb-2">
          <span class="badge rounded-pill" style="background:#e6f7f2;color:#065f46;border:1px solid #a7e7d5;font-size:0.72rem;font-weight:600;padding:5px 12px;" data-bn="&larr; তুলনা দেখতে পাশাপাশি স্ক্রল করুন &rarr;">
            &larr; Swipe sideways to compare &rarr;
          </span>
        </div>

        @php
          $comparisonPlans = ($plans ?? collect())->where('slug', '!=', 'trial')->values();
          if ($comparisonPlans->isEmpty()) {
              $comparisonPlans = ($plans ?? collect())->values();
          }
        @endphp

        <div class="table-responsive pricing-comparison-wrap">
          <table class="pricing-comparison-table">
            <thead>
              <tr>
                <th scope="col" data-bn="বৈশিষ্ট্য ও সীমা">Features &amp; Limits</th>
                @forelse ($comparisonPlans as $plan)
                  <th scope="col">
                    <div class="d-flex flex-column gap-1">
                      <span class="fw-bold text-dark">{{ $plan->name }}</span>
                      <span style="color: #066F58; font-size: 0.75rem; font-weight: 700;">৳{{ number_format((float) $plan->price_bdt) }}/mo</span>
                    </div>
                  </th>
                @empty
                  <th scope="col">Starter</th>
                  <th scope="col">Professional</th>
                  <th scope="col">Enterprise</th>
                @endforelse
              </tr>
            </thead>
            <tbody>
              @if ($comparisonPlans->isNotEmpty())
                <tr>
                  <th scope="row" data-bn="ওয়্যারহাউস ও হাব">Warehouses &amp; Hubs</th>
                  @foreach ($comparisonPlans as $plan)
                    <td>
                      @if ($plan->max_warehouses === -1)
                        <span class="badge bg-success-subtle text-success fw-bold px-2 py-1" data-bn="আনলিমিটেড">Unlimited</span>
                      @else
                        <span><strong>{{ $plan->max_warehouses }}</strong> {{ $plan->max_warehouses === 1 ? 'Hub' : 'Hubs' }}</span>
                      @endif
                    </td>
                  @endforeach
                </tr>
                <tr>
                  <th scope="row" data-bn="টিম ইউজার">Team Users</th>
                  @foreach ($comparisonPlans as $plan)
                    <td>
                      @if ($plan->max_users === -1)
                        <span class="badge bg-success-subtle text-success fw-bold px-2 py-1" data-bn="আনলিমিটেড">Unlimited</span>
                      @else
                        <span><strong>{{ $plan->max_users }}</strong> Users</span>
                      @endif
                    </td>
                  @endforeach
                </tr>
                <tr>
                  <th scope="row" data-bn="সক্রিয় এসকেইউ (SKUs)">Products / Active SKUs</th>
                  @foreach ($comparisonPlans as $plan)
                    <td>
                      @if ($plan->max_products === -1)
                        <span class="badge bg-success-subtle text-success fw-bold px-2 py-1" data-bn="আনলিমিটেড">Unlimited</span>
                      @else
                        <span><strong>{{ number_format($plan->max_products) }}</strong> SKUs</span>
                      @endif
                    </td>
                  @endforeach
                </tr>
                <tr>
                  <th scope="row" data-bn="কোটেশন">Quotations / Month</th>
                  @foreach ($comparisonPlans as $plan)
                    <td>
                      @if (($plan->max_quotations ?? -1) === -1)
                        <span class="badge bg-success-subtle text-success fw-bold px-2 py-1" data-bn="আনলিমিটেড">Unlimited</span>
                      @else
                        <span><strong>{{ number_format($plan->max_quotations) }}</strong></span>
                      @endif
                    </td>
                  @endforeach
                </tr>
                <tr>
                  <th scope="row" data-bn="বিক্রয় অর্ডার">Sales Orders / Month</th>
                  @foreach ($comparisonPlans as $plan)
                    <td>
                      @if (($plan->max_sales_orders ?? -1) === -1)
                        <span class="badge bg-success-subtle text-success fw-bold px-2 py-1" data-bn="আনলিমিটেড">Unlimited</span>
                      @else
                        <span><strong>{{ number_format($plan->max_sales_orders) }}</strong></span>
                      @endif
                    </td>
                  @endforeach
                </tr>
                <tr>
                  <th scope="row" data-bn="ক্রয় অর্ডার">Purchase Orders / Month</th>
                  @foreach ($comparisonPlans as $plan)
                    <td>
                      @if (($plan->max_purchase_orders ?? -1) === -1)
                        <span class="badge bg-success-subtle text-success fw-bold px-2 py-1" data-bn="আনলিমিটেড">Unlimited</span>
                      @else
                        <span><strong>{{ number_format($plan->max_purchase_orders) }}</strong></span>
                      @endif
                    </td>
                  @endforeach
                </tr>
                <tr>
                  <th scope="row" data-bn="প্রজেক্ট ও কর্মী">Projects &amp; Staff</th>
                  @foreach ($comparisonPlans as $plan)
                    <td>
                      @if (($plan->max_projects ?? -1) === -1 && ($plan->max_employees ?? -1) === -1)
                        <span class="badge bg-success-subtle text-success fw-bold px-2 py-1" data-bn="আনলিমিটেড">Unlimited</span>
                      @else
                        <span>{{ ($plan->max_projects ?? 1) == -1 ? 'Unlimited' : $plan->max_projects }} Proj / {{ ($plan->max_employees ?? 1) == -1 ? 'Unlimited' : $plan->max_employees }} Staff</span>
                      @endif
                    </td>
                  @endforeach
                </tr>
                <tr>
                  <th scope="row" data-bn="পিওএস বিলিং">POS Retail Billing</th>
                  @foreach ($comparisonPlans as $plan)
                    <td>
                      @if (!empty($plan->features['pos_billing']))
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                      @else
                        <span class="text-muted">—</span>
                      @endif
                    </td>
                  @endforeach
                </tr>
                <tr>
                  <th scope="row" data-bn="ডাবল-এন্ট্রি অ্যাকাউন্টিং">Double-Entry Accounting</th>
                  @foreach ($comparisonPlans as $plan)
                    <td>
                      @if (!empty($plan->features['accounting']))
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                      @else
                        <span class="text-muted">—</span>
                      @endif
                    </td>
                  @endforeach
                </tr>
                <tr>
                  <th scope="row" data-bn="সার্ভিস ওয়ার্কশপ">Workshop &amp; Service Ticketing</th>
                  @foreach ($comparisonPlans as $plan)
                    <td>
                      @if (!empty($plan->features['service_workshop']))
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                      @else
                        <span class="text-muted">—</span>
                      @endif
                    </td>
                  @endforeach
                </tr>
                <tr>
                  <th scope="row" data-bn="এইচআর ও পে-রোল">HR &amp; Payroll</th>
                  @foreach ($comparisonPlans as $plan)
                    <td>
                      @if (!empty($plan->features['payroll']))
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                      @else
                        <span class="text-muted">—</span>
                      @endif
                    </td>
                  @endforeach
                </tr>
                <tr>
                  <th scope="row" data-bn="সাপোর্ট">Customer Support</th>
                  @foreach ($comparisonPlans as $plan)
                    <td>
                      @if ($plan->slug === 'enterprise' || !empty($plan->features['priority_24_7_support']))
                        <span class="badge bg-success-subtle text-success fw-bold px-2 py-1" data-bn="২৪/৭ ফোন ও ভিআইপি SLA">24/7 Phone &amp; VIP SLA</span>
                      @elseif ($plan->slug === 'professional')
                        <span data-bn="অগ্রাধিকার ফোন ও চ্যাট">Priority Phone &amp; Chat</span>
                      @else
                        <span data-bn="ইমেইল ও হেল্পডেস্ক">Standard Email Support</span>
                      @endif
                    </td>
                  @endforeach
                </tr>
                <tr>
                  <th scope="row" data-bn="শুরু করুন">Get Started</th>
                  @foreach ($comparisonPlans as $plan)
                    <td>
                      <a href="{{ route('register') }}" class="btn {{ $plan->slug === 'professional' ? 'btn-tier-primary' : 'btn-tier-secondary' }} btn-sm px-3 py-1.5" style="font-size:0.78rem;">
                        <span>Choose {{ $plan->name }}</span>
                      </a>
                    </td>
                  @endforeach
                </tr>
              @else
                <tr><th scope="row">Warehouses &amp; hubs</th><td>Up to 1</td><td>Up to 3</td><td>Unlimited locations</td></tr>
                <tr><th scope="row">Team users</th><td>3 users</td><td>10 users</td><td>Unlimited users</td></tr>
                <tr><th scope="row">Active SKUs</th><td>500 SKUs</td><td>5,000 SKUs</td><td>Unlimited SKUs</td></tr>
                <tr><th scope="row">Support</th><td>Email support</td><td>Priority phone &amp; chat</td><td>Dedicated 24/7 SLA</td></tr>
              @endif
            </tbody>
          </table>
        </div>

        <p class="pricing-billing-note" data-bn="মাসিক ও বার্ষিক উভয় বিলিং সুবিধা উপলব্ধ। ">Monthly and annual billing are available.</p>
        <div class="pricing-guarantee-ribbon text-center mt-4">
          <div class="d-inline-flex flex-wrap align-items-center justify-content-center gap-3 gap-md-4 py-2 px-4 rounded-pill">
            <span><strong data-bn="৩০ দিনের মানি-ব্যাক গ্যারান্টি">30-Day Money-Back Guarantee</strong></span><span class="guarantee-dot">&bull;</span>
            <span><strong data-bn="১-ক্লিকে তাৎক্ষণিক বাতিল">1-Click Instant Cancellation</strong></span><span class="guarantee-dot">&bull;</span>
            <span><strong data-bn="বিনামূল্যে ডেটা মাইগ্রেশন">Free White-Glove Migration</strong></span>
          </div>
        </div>
      </div>
    </section>
@endsection
