<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="theme-color" content="#050812" />
  <title>Create your {{ config('app.name', 'InoManage') }} account</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="{{ asset('landing/signup.css') }}" />
</head>
<body class="signup-page">
  <main class="signup-layout">
    <a class="signup-brand" href="{{ route('home') }}" aria-label="InoManage home">
      <span class="signup-logo" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i><i></i></span>
      <span class="signup-wordmark">InoManage</span>
    </a>

    <section class="signup-card" aria-labelledby="signup-title">
      <header class="signup-heading">
        <h1 id="signup-title">Create your account</h1>
        <p>Get started with a 3-day free trial, no credit card required</p>
      </header>

      @if ($errors->any())
        <p class="signup-error" role="alert" aria-live="polite" style="margin-bottom: 12px;">
          {{ $errors->first() }}
        </p>
      @endif

      <form class="auth-form" method="POST" action="{{ route('register') }}">
        @csrf

        <label for="name">Full Name</label>
        <input id="name" name="name" type="text" value="{{ old('name') }}" placeholder="John Doe" autocomplete="name" required autofocus />

        <label for="email">Email Address</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" placeholder="name@company.com" autocomplete="username" required />

        <label for="phone">Phone Number (OTP Verification)</label>
        <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" placeholder="017XXXXXXXX" autocomplete="tel" required />

        <label for="password">Password</label>
        <input id="password" name="password" type="password" placeholder="At least 8 characters" autocomplete="new-password" minlength="8" required />

        <label for="password_confirmation">Confirm Password</label>
        <input id="password_confirmation" name="password_confirmation" type="password" placeholder="Re-enter password" autocomplete="new-password" minlength="8" required />

        @if (Laravel\Jetstream\Jetstream::hasTermsAndPrivacyPolicyFeature())
          <div style="display:flex;align-items:flex-start;gap:8px;margin:12px 0 6px;font-size:12px;color:#475569;">
            <input type="checkbox" name="terms" id="terms" required style="width:auto;height:auto;margin:2px 0 0;padding:0;flex-shrink:0;" />
            <label for="terms" style="margin:0;font-weight:500;cursor:pointer;">
              {!! __('I agree to the :terms_of_service and :privacy_policy', [
                'terms_of_service' => '<a target="_blank" href="'.route('terms.show').'">'.__('Terms of Service').'</a>',
                'privacy_policy' => '<a target="_blank" href="'.route('policy.show').'">'.__('Privacy Policy').'</a>',
              ]) !!}
            </label>
          </div>
        @endif

        <button type="submit" class="signup-submit">Create Free Account <span aria-hidden="true">&rarr;</span></button>
      </form>

      <div class="signup-signin">Already have an account? <a href="{{ route('login') }}">Sign In <span aria-hidden="true">&rarr;</span></a></div>
    </section>
  </main>
</body>
</html>
