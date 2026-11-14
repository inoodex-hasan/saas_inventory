@extends('layouts.landing')

@section('title', 'InoManage ERP - Customer Reviews')

@section('content')
<section class="reviews-section position-relative" id="review" aria-labelledby="reviews-title">
      <div class="container position-relative z-1">
        <div class="reviews-intro text-center">
          <div class="section-eyebrow d-inline-flex align-items-center gap-2 mb-3">
            <span class="stars-gold">★★★★★</span>
            <span data-bn="৪.৯ / ৫ সামগ্রিক রেটিং">4.9 / 5 OVERALL RATING</span>
            <span class="eyebrow-dot"></span>
          </div>
          <h2 id="reviews-title" class="reviews-main-heading mb-3" data-bn="শতভাগ নির্ভুল ইনভেন্টরি ও আধুনিক ব্যবসায়ের আস্থা">
            Trusted for zero stockout errors &amp;<br />
            <span class="text-emerald-gradient">audit-grade financial control.</span>
          </h2>
          <p class="reviews-main-desc mb-5" data-bn="ইনোম্যানেজ ব্যবহারের মাধ্যমে কীভাবে ব্যবসায়ীরা তাদের ইনভেন্টরি নির্ভুলতা, দ্রুত চেকআউট এবং আর্থিক অডিট রূপান্তর করেছেন তা দেখুন।">
            Discover how modern operators transformed their stock accuracy, checkout speed, and financial audits with {{ $siteSettings['brand_name'] ?? 'InoManage' }}.
          </p>
        </div>

        <!-- 3 Testimonial Cards Grid -->
        @include('landing.partials.reviews-cards')

      </div>
    </section>
@endsection
