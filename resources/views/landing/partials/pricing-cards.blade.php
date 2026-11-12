@php
    $displayPlans = ($plans ?? collect())->where('slug', '!=', 'trial')->values();
    if ($displayPlans->isEmpty()) {
        $displayPlans = ($plans ?? collect())->values();
    }
@endphp

<div class="pricing-cards-grid">
    @forelse ($displayPlans as $index => $plan)
        @php
            $isFeatured = $plan->slug === 'professional' || ($displayPlans->count() >= 3 && $index === 1);
            $monthlyPrice = (int) round((float) $plan->price_bdt);
            $annualTotal = (int) round((float) $plan->annual_price_bdt);
            $annualPerMonth = $annualTotal > 0 ? (int) round($annualTotal / 12) : $monthlyPrice;
        @endphp

        <article class="pricing-tier-card {{ $isFeatured ? 'card-tier-featured' : '' }}">
            @if ($isFeatured)
                <div class="tier-popular-ribbon">MOST POPULAR &bull; BEST VALUE</div>
            @endif

            <div class="tier-badge-wrap">
                <span class="tier-category {{ $isFeatured ? 'category-emerald' : '' }}">
                    {{ strtoupper($plan->slug === 'enterprise' ? 'FOR LARGE ENTERPRISES' : ($isFeatured ? 'MULTI-WAREHOUSE SCALING' : 'FOR GROWING BUSINESSES')) }}
                </span>
            </div>

            <h3 class="tier-name">{{ $plan->name }}</h3>
            <p class="tier-desc">{{ $plan->description ?: 'Complete cloud inventory, POS billing, and double-entry financial control.' }}</p>

            <div class="tier-pricing">
                <span class="tier-currency">৳</span>
                <span
                    class="tier-price"
                    data-monthly="{{ number_format($monthlyPrice) }}"
                    data-annual="{{ number_format($annualPerMonth) }}"
                >{{ number_format($monthlyPrice) }}</span>
                <span class="tier-period">/month</span>
            </div>
            <div
                class="tier-billed-note"
                data-monthly="Billed monthly"
                data-annual="Billed annually (৳{{ number_format($annualTotal) }}/yr)"
            >Billed monthly</div>

            <a href="{{ route('register') }}" class="btn {{ $isFeatured ? 'btn-tier-primary' : 'btn-tier-secondary' }} w-100 mb-4">
                <span>Start {{ ($plan->trial_days ?? 3) > 0 ? $plan->trial_days : 3 }}-Day Free Trial</span>
                @if ($isFeatured)
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
                @endif
            </a>

            <div class="tier-divider"></div>
            <div class="tier-features-label">Plan Quotas &amp; Capabilities:</div>
            <ul class="tier-features-list list-unstyled">
                <li>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <span><strong>{{ $plan->max_products === -1 ? 'Unlimited' : number_format($plan->max_products) }}</strong> Products Catalog</span>
                </li>
                <li>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <span><strong>{{ $plan->max_warehouses === -1 ? 'Unlimited' : $plan->max_warehouses }}</strong> Warehouses &amp; Hubs</span>
                </li>
                <li>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <span><strong>{{ $plan->max_users === -1 ? 'Unlimited' : $plan->max_users }}</strong> Team User Seats</span>
                </li>
                <li>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <span><strong>{{ ($plan->max_quotations ?? -1) === -1 ? 'Unlimited' : number_format($plan->max_quotations) }}</strong> Quotations</span>
                </li>
                <li>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <span><strong>{{ ($plan->max_sales_orders ?? -1) === -1 ? 'Unlimited' : number_format($plan->max_sales_orders) }}</strong> Sales &amp; Invoices</span>
                </li>
                <li>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <span><strong>{{ ($plan->max_purchase_orders ?? -1) === -1 ? 'Unlimited' : number_format($plan->max_purchase_orders) }}</strong> Purchase Orders</span>
                </li>
                <li>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <span><strong>{{ ($plan->max_projects ?? -1) === -1 ? 'Unlimited' : number_format($plan->max_projects) }}</strong> Projects &amp; <strong>{{ ($plan->max_employees ?? -1) === -1 ? 'Unlimited' : number_format($plan->max_employees) }}</strong> Staff</span>
                </li>
                @if (!empty($plan->features) && is_array($plan->features))
                    @foreach ($plan->features as $featureKey => $enabled)
                        @if ($enabled)
                            <li>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                <span>{{ \Illuminate\Support\Str::headline($featureKey) }}</span>
                            </li>
                        @endif
                    @endforeach
                @endif
            </ul>
        </article>
    @empty
        <article class="pricing-tier-card card-tier-featured">
            <h3 class="tier-name">Monthly Pro</h3>
            <p class="tier-desc">All-in-one cloud ERP, inventory, POS, and accounting.</p>
            <div class="tier-pricing">
                <span class="tier-currency">৳</span>
                <span class="tier-price" data-monthly="999" data-annual="833">999</span>
                <span class="tier-period">/month</span>
            </div>
            <div class="tier-billed-note" data-monthly="Billed monthly" data-annual="Billed annually">Billed monthly</div>
            <a href="{{ route('register') }}" class="btn btn-tier-primary w-100 mb-4">Start 3-Day Free Trial</a>
        </article>
    @endforelse
</div>

