@extends('layouts.landing')

@section('title', 'Privacy Policy | ' . ($brandName ?? 'InoManage'))

@section('content')
<section class="legal-section legal-section-alt" aria-labelledby="privacy-title">
      <div class="container">
        <div class="legal-page-heading">
          <div class="legal-heading-copy">
            <div class="section-eyebrow d-inline-flex align-items-center gap-2 mb-3">
              <span class="eyebrow-sparkle">✦</span>
              <span data-bn="আপনার তথ্য">YOUR INFORMATION</span>
              <span class="eyebrow-dot"></span>
            </div>
            <h1 class="legal-title" id="privacy-title" data-bn="গোপনীয়তা নীতি">Privacy Policy</h1>
            <p class="legal-lead" data-bn="ইনোম্যানেজ ব্যবহার করার সময় আপনার তথ্য কীভাবে পরিচালিত হয়, তার সহজ বিবরণ।">A straightforward overview of information handled when you use {{ $brandName ?? 'InoManage' }}.</p>
            <p class="legal-updated" data-bn="সর্বশেষ হালনাগাদ: ১২ নভেম্বর, ২০২৬">Last updated: November 12, 2026</p>
          </div>
          <aside class="legal-summary-card">
            <span class="legal-summary-label" data-bn="গোপনীয়তা এক নজরে">PRIVACY AT A GLANCE</span>
            <h2 data-bn="আপনার ব্যবসায়িক তথ্যের যত্ন নেওয়া আমাদের অঙ্গীকার।">Your business data deserves care.</h2>
            <p data-bn="আপনার প্রযোজ্য চুক্তি ও সেটিংস অনুযায়ী, পরিষেবা প্রদান, সহায়তা এবং সুরক্ষার কাজে আমরা তথ্য ব্যবহার করি।">We use information in connection with delivering, supporting, and protecting the service, subject to your applicable agreements and settings.</p>
            <a href="{{ route('terms.show') }}" class="legal-related-link"><span data-bn="আমাদের সেবার শর্তাবলি পড়ুন">Read our Terms of Service</span> <span aria-hidden="true">&rarr;</span></a>
          </aside>
        </div>

        <div class="legal-document-layout">
          @if(empty($hasCustomPolicy))
          <nav class="legal-toc" aria-label="Privacy Policy contents" data-bn-aria-label="গোপনীয়তা নীতির বিষয়সূচি">
            <span class="legal-toc-title" data-bn="এই পাতায়">ON THIS PAGE</span>
            <a href="#privacy-information" data-bn="আমরা যে তথ্য পরিচালনা করি">Information we handle</a>
            <a href="#privacy-use" data-bn="তথ্যের ব্যবহার">How it is used</a>
            <a href="#privacy-sharing" data-bn="তথ্য শেয়ার ও প্রক্রিয়াকরণ">Sharing &amp; processing</a>
            <a href="#privacy-security" data-bn="নিরাপত্তা ও সংরক্ষণ">Security &amp; retention</a>
            <a href="#privacy-choices" data-bn="আপনার পছন্দ">Your choices</a>
            <a href="#privacy-changes" data-bn="নীতিমালার হালনাগাদ">Policy updates</a>
            <a href="#privacy-contact" data-bn="যোগাযোগ">Contact</a>
          </nav>
          @endif

          <div class="legal-document" @if(!empty($hasCustomPolicy)) style="width: 100%; max-width: 100%;" @endif>
            @if(!empty($hasCustomPolicy))
              <article class="legal-article-section">
                <div class="legal-custom-body" style="line-height: 1.8; color: #cbd5e1;">
                  {!! $policy !!}
                </div>
              </article>
            @else
            <article class="legal-article-section" id="privacy-information">
              <span class="legal-section-number">01</span>
              <div>
                <h2 data-bn="আমরা যে তথ্য পরিচালনা করি">Information we handle</h2>
                <p data-bn="আপনি ইনোম্যানেজ কীভাবে ব্যবহার করেন তার ওপর নির্ভর করে, তথ্যের মধ্যে অ্যাকাউন্ট ও যোগাযোগের বিবরণ, প্রতিষ্ঠানের সেটিংস, সহায়তা-সংক্রান্ত বার্তা এবং আপনি বা আপনার প্রতিষ্ঠান পরিষেবায় জমা দেওয়া ব্যবসায়িক রেকর্ড থাকতে পারে। পরিষেবা চালানো ও সমস্যা সমাধানের জন্য প্রয়োজনীয় প্রযুক্তিগত ও ব্যবহার-সংক্রান্ত তথ্যও প্রক্রিয়াকৃত হতে পারে।">Depending on how you use {{ $brandName ?? 'InoManage' }}, information may include account and contact details, organization settings, support messages, and business records that you or your organization choose to enter into the service. The service may also process technical and usage information needed to operate and troubleshoot it.</p>
              </div>
            </article>

            <article class="legal-article-section" id="privacy-use">
              <span class="legal-section-number">02</span>
              <div>
                <h2 data-bn="তথ্যের ব্যবহার">How information is used</h2>
                <p data-bn="পরিষেবা প্রদান ও রক্ষণাবেক্ষণ, অনুরোধের জবাব, অ্যাকাউন্ট ও সিস্টেমের সুরক্ষা এবং প্রযোজ্য আইনি বাধ্যবাধকতা পূরণের কাজে তথ্য ব্যবহার করা হয়। অতিরিক্ত কোনো ব্যবহার আপনার অ্যাকাউন্টের প্রযোজ্য শর্ত ও সেটিংসের অধীন।">Information is used in connection with providing and maintaining the service, responding to requests, protecting accounts and systems, and meeting applicable legal obligations. Any additional uses are subject to the terms and settings that apply to your account.</p>
              </div>
            </article>

            <article class="legal-article-section" id="privacy-sharing">
              <span class="legal-section-number">03</span>
              <div>
                <h2 data-bn="তথ্য শেয়ার ও প্রক্রিয়াকরণ">Sharing &amp; processing</h2>
                <p data-bn="আপনার অ্যাকাউন্ট পরিচালনাকারী প্রতিষ্ঠান এবং ইনোম্যানেজকে সহায়তা করা পরিষেবা-প্রদানকারীদের পরিষেবা চালানোর প্রয়োজনে তথ্যের অ্যাক্সেস লাগতে পারে; এ ক্ষেত্রে প্রযোজ্য চুক্তি অনুসরণ করা হবে। আইনগতভাবে প্রয়োজন হলে বা পরিষেবা ও ব্যবহারকারীদের সুরক্ষায় তথ্য প্রকাশ করা হতে পারে।">Access to information may be needed by the organization that manages your account and by service providers supporting {{ $brandName ?? 'InoManage' }}, in each case as relevant to operating the service and subject to applicable agreements. Information may also be disclosed when required by law or to protect the service and its users.</p>
                <p data-bn="আপনি কোনো নিয়োগকর্তা বা প্রতিষ্ঠানের মাধ্যমে ইনোম্যানেজ ব্যবহার করলে, সেই প্রতিষ্ঠান অ্যাকাউন্ট এবং জমা দেওয়া ব্যবসায়িক তথ্য নিয়ন্ত্রণ করতে পারে।">If you use {{ $brandName ?? 'InoManage' }} through an employer or another organization, that organization may control the account and the business information submitted to it.</p>
              </div>
            </article>

            <article class="legal-article-section" id="privacy-security">
              <span class="legal-section-number">04</span>
              <div>
                <h2 data-bn="নিরাপত্তা ও সংরক্ষণ">Security &amp; retention</h2>
                <p data-bn="পরিষেবায় পরিচালিত তথ্য সুরক্ষার জন্য আমরা উপযুক্ত সুরক্ষা ব্যবস্থা ব্যবহার করি। কোনো অনলাইন পরিষেবাই সম্পূর্ণ নিরাপত্তার নিশ্চয়তা দিতে পারে না। পরিষেবার প্রয়োজন, আপনার অ্যাকাউন্টের সেটিংস ও চুক্তি এবং প্রযোজ্য আইনি শর্ত অনুযায়ী তথ্য সংরক্ষণ করা হয়।">We use safeguards intended to protect information handled by the service. No online service can guarantee absolute security. Information is retained as needed for the service, your account settings and agreements, and applicable legal requirements.</p>
              </div>
            </article>

            <article class="legal-article-section" id="privacy-choices">
              <span class="legal-section-number">05</span>
              <div>
                <h2 data-bn="আপনার পছন্দ">Your choices</h2>
                <p data-bn="আপনার অ্যাকাউন্টের তথ্য নিয়ে প্রশ্ন বা অনুরোধ থাকলে আমাদের সঙ্গে যোগাযোগ করতে পারেন। আপনার তথ্য কোনো প্রতিষ্ঠান পরিচালনা করলে প্রথমে সেই প্রতিষ্ঠানের প্রশাসকের সঙ্গে যোগাযোগ করুন। আপনার ক্ষেত্রে প্রযোজ্য আইন অনুযায়ী আপনার গোপনীয়তা-সংক্রান্ত অধিকার নির্ধারিত হবে।">You can contact us with questions or requests about your account information. If your information is managed by an organization, contact that organization’s administrator first. Any privacy rights available to you depend on the laws that apply to your situation.</p>
              </div>
            </article>

            <article class="legal-article-section" id="privacy-changes">
              <span class="legal-section-number">06</span>
              <div>
                <h2 data-bn="নীতিমালার হালনাগাদ">Policy updates</h2>
                <p data-bn="পরিষেবা বা প্রযোজ্য নিয়মের পরিবর্তনের সঙ্গে এই নীতিমালা হালনাগাদ হতে পারে। উপরের সর্বশেষ হালনাগাদের তারিখটি এই পৃষ্ঠার সাম্প্রতিক সংশোধনের সময় জানায়। প্রয়োজন হলে গুরুত্বপূর্ণ পরিবর্তনের বিষয়ে জানানো হবে।">We may update this policy as the service or applicable requirements change. The “Last updated” date above indicates when this page was most recently revised. Where required, we will provide notice of material changes.</p>
              </div>
            </article>

            <article class="legal-article-section legal-contact-section" id="privacy-contact">
              <span class="legal-section-number">07</span>
              <div>
                <h2 data-bn="গোপনীয়তা নিয়ে প্রশ্ন আছে?">Privacy questions?</h2>
                <p data-bn="এই নীতিমালা বা ইনোম্যানেজের মাধ্যমে পরিচালিত তথ্য নিয়ে প্রশ্ন থাকলে ইমেইলে আমাদের সঙ্গে যোগাযোগ করুন।">For questions about this policy or information handled through {{ $brandName ?? 'InoManage' }}, contact us by email.</p>
                <a class="legal-contact-link" href="mailto:{{ $supportEmail ?? 'support@inomanage.com' }}">{{ $supportEmail ?? 'support@inomanage.com' }}</a>
              </div>
            </article>
            @endif
          </div>
        </div>
      </div>
    </section>
  </main>
@endsection

@push('scripts')
  <script src="{{ asset('landing/legal-language.js') }}"></script>
@endpush
