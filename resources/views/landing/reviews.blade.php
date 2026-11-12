@extends('layouts.landing')

@section('title', 'InoManage ERP - Customer Reviews')

@section('content')
<section class="reviews-section position-relative" id="review" aria-labelledby="reviews-title">
      <div class="container position-relative z-1">
        <div class="reviews-intro text-center">
          <div class="section-eyebrow d-inline-flex align-items-center gap-2 mb-3">
            <span class="stars-gold">★★★★★</span>
            <span>4.9 / 5 OVERALL RATING</span>
            <span class="eyebrow-dot"></span>
          </div>
          <h2 id="reviews-title" class="reviews-main-heading mb-3">
            Trusted by 12,500+ warehouses &amp;<br />
            <span class="text-emerald-gradient">fast-growing enterprises.</span>
          </h2>
          <p class="reviews-main-desc mb-5">
            Discover how modern operators transformed their stock accuracy, checkout speed, and financial audits with InoManage.
          </p>
        </div>

        <!-- 3 Testimonial Cards Grid -->
        @include('landing.partials.reviews-cards')

      </div>
    </section>
@endsection
