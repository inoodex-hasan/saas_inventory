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
          <span class="pricing-comparison-kicker">Choose the right fit</span>
          <h2 id="pricing-comparison-title">Compare plans at a glance</h2>
          <p>Every plan includes the core platform. Compare capacity and support to find the right starting point.</p>
        </div>
        <div class="table-responsive pricing-comparison-wrap">
          <table class="pricing-comparison-table">
            <thead><tr><th scope="col">Included</th><th scope="col">Starter Core</th><th scope="col">Growth Pro</th><th scope="col">Enterprise Scale</th></tr></thead>
            <tbody>
              <tr><th scope="row">Warehouses &amp; hubs</th><td>Up to 2</td><td>Unlimited</td><td>Unlimited locations</td></tr>
              <tr><th scope="row">Team users</th><td>5 users</td><td>25 users</td><td>Unlimited users</td></tr>
              <tr><th scope="row">Active SKUs</th><td>Up to 5,000</td><td>Up to 50,000</td><td>Discuss your requirements</td></tr>
              <tr><th scope="row">Support</th><td>Email &amp; community</td><td>Priority 24/7 phone &amp; chat</td><td>Dedicated account director</td></tr>
            </tbody>
          </table>
        </div>
        <p class="pricing-billing-note">Monthly and annual billing are available. Every plan includes a 14-day trial and complimentary migration support.</p>
        <div class="pricing-guarantee-ribbon text-center mt-4">
          <div class="d-inline-flex flex-wrap align-items-center justify-content-center gap-3 gap-md-4 py-2 px-4 rounded-pill">
            <span><strong>30-Day Money-Back Guarantee</strong></span><span class="guarantee-dot">&bull;</span>
            <span><strong>1-Click Instant Cancellation</strong></span><span class="guarantee-dot">&bull;</span>
            <span><strong>Free White-Glove Migration</strong></span>
          </div>
        </div>
      </div>
    </section>
@endsection
