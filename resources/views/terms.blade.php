@extends('layouts.landing')

@section('title', 'Terms & Conditions | InoManage')

@section('content')
<section class="legal-section" aria-labelledby="terms-title">
      <div class="container">
        <div class="legal-page-heading">
          <div class="legal-heading-copy">
            <div class="section-eyebrow d-inline-flex align-items-center gap-2 mb-3">
              <span class="eyebrow-sparkle">✦</span>
              <span data-bn="আইনি তথ্য">LEGAL INFORMATION</span>
              <span class="eyebrow-dot"></span>
            </div>
            <h1 class="legal-title" id="terms-title" data-bn="সেবার শর্তাবলি">Terms of Service</h1>
            <p class="legal-lead" data-bn="ইনোম্যানেজ ক্লাউড ERP ব্যবহার ও অ্যাক্সেসের শর্তাবলি।">The terms that guide access to and use of InoManage Cloud ERP.</p>
            <p class="legal-updated" data-bn="সর্বশেষ হালনাগাদ: ১২ নভেম্বর, ২০২৬">Last updated: November 12, 2026</p>
          </div>
          <aside class="legal-summary-card">
            <span class="legal-summary-label" data-bn="সংক্ষেপে">THE SHORT VERSION</span>
            <h2 data-bn="স্বচ্ছ ও পরিষ্কার সম্পর্কের জন্য তৈরি।">Built for a clear working relationship.</h2>
            <p data-bn="প্ল্যাটফর্মটি দায়িত্বশীলভাবে ব্যবহার করুন, আপনার অ্যাকাউন্ট সুরক্ষিত রাখুন এবং পরিকল্পনা-নির্দিষ্ট তথ্যের জন্য অর্ডার বা পরিষেবা চুক্তি দেখুন।">Use the platform responsibly, protect your account, and refer to your order or service agreement for plan-specific details.</p>
            <a href="{{ route('policy.show') }}" class="legal-related-link"><span data-bn="আমাদের গোপনীয়তা নীতি পড়ুন">Read our Privacy Policy</span> <span aria-hidden="true">&rarr;</span></a>
          </aside>
        </div>

        <div class="legal-document-layout">
          <nav class="legal-toc" aria-label="Terms of Service contents" data-bn-aria-label="সেবার শর্তাবলির বিষয়সূচি">
            <span class="legal-toc-title" data-bn="এই পাতায়">ON THIS PAGE</span>
            <a href="#terms-agreement" data-bn="চুক্তি">Agreement</a>
            <a href="#terms-account" data-bn="অ্যাকাউন্ট ও প্রবেশাধিকার">Accounts &amp; access</a>
            <a href="#terms-subscriptions" data-bn="পরিকল্পনা ও বিলিং">Plans &amp; billing</a>
            <a href="#terms-content" data-bn="আপনার কনটেন্ট">Your content</a>
            <a href="#terms-use" data-bn="গ্রহণযোগ্য ব্যবহার">Acceptable use</a>
            <a href="#terms-service" data-bn="পরিষেবা পরিবর্তন ও সমাপ্তি">Service &amp; termination</a>
            <a href="#terms-contact" data-bn="যোগাযোগ">Contact</a>
          </nav>

          <div class="legal-document">
            <article class="legal-article-section" id="terms-agreement">
              <span class="legal-section-number">01</span>
              <div>
                <h2 data-bn="চুক্তি">Agreement</h2>
                <p data-bn="ইনোম্যানেজ ক্লাউড ERP এবং সংশ্লিষ্ট পরিষেবা ব্যবহার বা অ্যাক্সেসের ক্ষেত্রে এই সেবার শর্তাবলি প্রযোজ্য। পরিষেবাটি ব্যবহার করলে আপনি এই শর্তগুলোতে সম্মত হচ্ছেন। কোনো প্রতিষ্ঠানের পক্ষে ইনোম্যানেজ ব্যবহার করলে, সেই প্রতিষ্ঠানের হয়ে এই শর্ত গ্রহণের অনুমতি আপনার আছে বলে নিশ্চিত করছেন।">These Terms of Service apply when you access or use InoManage Cloud ERP and related services. By using the service, you agree to these terms. If you use InoManage on behalf of a business, you confirm that you are authorized to accept these terms for that business.</p>
                <p data-bn="আপনার সাবস্ক্রিপশনের জন্য স্বাক্ষরিত অর্ডার ফর্ম বা আলাদা পরিষেবা চুক্তি থাকলে, এই সাধারণ শর্তের সঙ্গে বিরোধ হলে সেই চুক্তিই প্রাধান্য পাবে।">If a signed order form or separate service agreement applies to your subscription, that agreement takes precedence where it conflicts with these general terms.</p>
              </div>
            </article>

            <article class="legal-article-section" id="terms-account">
              <span class="legal-section-number">02</span>
              <div>
                <h2 data-bn="অ্যাকাউন্ট ও প্রবেশাধিকার">Accounts &amp; access</h2>
                <p data-bn="আপনার অ্যাকাউন্টের তথ্য সঠিক রাখা, সাইন-ইন পরিচয়-তথ্য সুরক্ষিত রাখা এবং দলের সদস্যদের প্রবেশাধিকার পরিচালনার দায়িত্ব আপনার। অ্যাকাউন্টে অননুমোদিত ব্যবহার সন্দেহ হলে দ্রুত আমাদের জানান।">You are responsible for keeping your account details accurate, safeguarding your sign-in credentials, and managing access for your team. Please notify us promptly if you suspect unauthorized use of your account.</p>
                <p data-bn="আপনার অ্যাকাউন্টের মাধ্যমে হওয়া কার্যকলাপের জন্য এবং প্রত্যেক ব্যবহারকারী এই শর্তগুলো মেনে চলছেন কি না তা নিশ্চিত করার জন্য আপনি দায়ী।">You are responsible for activity carried out through your account and for ensuring that each user follows these terms.</p>
              </div>
            </article>

            <article class="legal-article-section" id="terms-subscriptions">
              <span class="legal-section-number">03</span>
              <div>
                <h2 data-bn="পরিকল্পনা ও বিলিং">Plans &amp; billing</h2>
                <p data-bn="সাবস্ক্রিপশনের সুবিধা, ফি, বিলিংয়ের সময়কাল এবং ট্রায়ালের শর্ত—যদি থাকে—সাইন-আপের সময় বা প্রযোজ্য অর্ডার ফর্মে দেখানো হয়। পেমেন্ট ও বাতিলের শর্ত ওই তথ্য এবং আপনার সঙ্গে ইনোম্যানেজের পৃথক চুক্তি অনুযায়ী নির্ধারিত হবে।">Subscription features, fees, billing intervals, and any trial terms are shown at sign-up or in your applicable order form. Payment and cancellation terms are governed by those details and any separate agreement between you and InoManage.</p>
                <p data-bn="প্রযোজ্য কর পরিশোধ এবং প্রয়োজন হলে বিলিংয়ের তথ্য হালনাগাদ রাখার দায়িত্ব আপনার।">You are responsible for applicable taxes and for keeping billing information current, where relevant.</p>
              </div>
            </article>

            <article class="legal-article-section" id="terms-content">
              <span class="legal-section-number">04</span>
              <div>
                <h2 data-bn="আপনার কনটেন্ট">Your content</h2>
                <p data-bn="পরিষেবায় জমা দেওয়া ব্যবসায়িক তথ্য ও কনটেন্টের অধিকার আপনার কাছেই থাকবে। পরিষেবা প্রদান ও সহায়তার জন্য যতটুকু প্রয়োজন, অথবা আপনার সঙ্গে অন্যভাবে সম্মত হলে, সেই সীমার মধ্যে কনটেন্ট হোস্ট, প্রক্রিয়াকরণ ও প্রদর্শনের অনুমতি আপনি ইনোম্যানেজকে দিচ্ছেন।">You retain your rights to the business information and content you submit to the service. You give InoManage permission to host, process, and display that content only as needed to provide and support the service, or as otherwise agreed with you.</p>
                <p data-bn="আপনার কনটেন্ট জমা দেওয়ার প্রয়োজনীয় অধিকার ও অনুমতি থাকা এবং আপনার রেকর্ডের উপযুক্ত কপি সংরক্ষণের দায়িত্ব আপনার।">You are responsible for having the rights and permissions needed to submit your content and for maintaining appropriate copies of your records.</p>
              </div>
            </article>

            <article class="legal-article-section" id="terms-use">
              <span class="legal-section-number">05</span>
              <div>
                <h2 data-bn="গ্রহণযোগ্য ব্যবহার">Acceptable use</h2>
                <p data-bn="পরিষেবাটি বেআইনিভাবে ব্যবহার করবেন না, এর কার্যক্রমে বাধা দেবেন না, অননুমোদিত প্রবেশের চেষ্টা করবেন না বা অন্যের অধিকার লঙ্ঘনে ব্যবহার করবেন না। প্রযোজ্য আইন অনুমতি না দিলে বা আমরা লিখিত অনুমতি না দিলে পরিষেবাটি কপি, পুনর্বিক্রয় বা রিভার্স-ইঞ্জিনিয়ার করবেন না।">Do not use the service unlawfully, interfere with its operation, attempt unauthorized access, or use it to infringe another person’s rights. Do not copy, resell, or reverse engineer the service except where applicable law permits or we have given written permission.</p>
              </div>
            </article>

            <article class="legal-article-section" id="terms-service">
              <span class="legal-section-number">06</span>
              <div>
                <h2 data-bn="পরিষেবা পরিবর্তন ও সমাপ্তি">Service changes &amp; termination</h2>
                <p data-bn="সময়ের সঙ্গে আমরা পরিষেবার রক্ষণাবেক্ষণ বা হালনাগাদ করতে পারি। প্রাপ্যতা-সংক্রান্ত প্রতিশ্রুতি থাকলে তা প্রযোজ্য পরিষেবা-স্তর চুক্তি বা অর্ডার ফর্মে উল্লেখ থাকবে। পরিষেবা সুরক্ষা, আইন মেনে চলা বা গুরুত্বপূর্ণ শর্তভঙ্গ মোকাবিলার যুক্তিসঙ্গত প্রয়োজনে আমরা প্রবেশাধিকার সীমিত বা স্থগিত করতে পারি; সম্ভব হলে আগে জানানো হবে।">We may maintain or update the service over time. Availability commitments, if any, are described in your applicable service-level agreement or order form. We may restrict or suspend access when reasonably needed to protect the service, comply with law, or address a material breach, with notice where practicable.</p>
                <p data-bn="দায়বদ্ধতার সীমা বা অন্যান্য প্রতিকার আপনার প্রযোজ্য চুক্তি ও আইনের অধীন। আইনত যে দায় সীমিত করা যায় না, এই শর্তাবলি তা সীমিত করে না।">Any limits on liability or other remedies are subject to your applicable agreement and the laws that apply. Nothing in these terms limits liability that cannot legally be limited.</p>
              </div>
            </article>

            <article class="legal-article-section legal-contact-section" id="terms-contact">
              <span class="legal-section-number">07</span>
              <div>
                <h2 data-bn="এই শর্তাবলি নিয়ে প্রশ্ন আছে?">Questions about these terms?</h2>
                <p data-bn="আপনার প্রতিষ্ঠানের নামসহ আমাদের সঙ্গে যোগাযোগ করুন, তাহলে আমরা আপনার প্রশ্নটি সঠিক দলের কাছে পাঠাতে পারব।">Contact our team and include your organization name so we can direct your question to the right person.</p>
                <a class="legal-contact-link" href="mailto:support@inomanage.com">support@inomanage.com</a>
              </div>
            </article>
          </div>
        </div>
      </div>
    </section>
  </main>
@endsection

@push('scripts')
  <script src="{{ asset('landing/legal-language.js') }}"></script>
@endpush
