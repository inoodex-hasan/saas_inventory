<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="theme-color" content="#FFFFFF" />
  <title>Verify your phone — {{ config('app.name', 'InoManage') }}</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="{{ asset('landing/signup.css') }}" />
</head>
<body class="signup-page">
  <main class="signup-layout signin-layout">
    <a class="signup-brand" href="{{ route('home') }}" aria-label="InoManage home">
      <span class="signup-logo" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i><i></i></span>
      <span class="signup-wordmark">InoManage</span>
    </a>

    <section class="signup-card" aria-labelledby="verify-title">
      <header class="signup-heading">
        <h1 id="verify-title">Verify your phone number</h1>
        @if ($phone)
          <p>We sent a 6-digit verification code to <strong>{{ $maskedPhone }}</strong>. Enter it below to access your dashboard.</p>
        @else
          <p>Please add and verify your mobile number to activate your free trial and access your dashboard.</p>
        @endif
      </header>

      @if (session('status'))
        <p class="signup-status" role="status" aria-live="polite" style="margin-bottom: 12px;">{{ session('status') }}</p>
      @endif

      @if ($errors->any())
        <p class="signup-error" role="alert" aria-live="polite" style="margin-bottom: 12px;">{{ $errors->first() }}</p>
      @endif

      @if ($phone)
        <form class="auth-form" method="POST" action="{{ route('phone.verify.confirm') }}">
          @csrf
          <label for="otp">6-Digit OTP Code</label>
          <input
            id="otp"
            name="otp"
            type="text"
            inputmode="numeric"
            pattern="[0-9]{6}"
            maxlength="6"
            required
            autofocus
            autocomplete="one-time-code"
            placeholder="123456"
            style="text-align:center;font-size:18px;letter-spacing:0.35em;font-weight:700;"
          />
          <button type="submit" class="signup-submit">Verify &amp; Access Dashboard <span aria-hidden="true">&rarr;</span></button>
        </form>

        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-top:14px;font-size:13px;">
          <form method="POST" action="{{ route('phone.verify.resend') }}">
            @csrf
            <button
              id="resend-otp-btn"
              type="submit"
              @if ($cooldown > 0) disabled @endif
              style="background:none;border:0;padding:0;color:#0284c7;font-weight:600;cursor:pointer;"
            >
              @if ($cooldown > 0)
                Resend OTP in <span id="otp-countdown">{{ $cooldown }}</span>s
              @else
                Resend OTP
              @endif
            </button>
          </form>

          <button
            type="button"
            onclick="const box = document.getElementById('change-phone-box'); box.style.display = box.style.display === 'none' ? 'block' : 'none';"
            style="background:none;border:0;padding:0;color:#64748b;text-decoration:underline;cursor:pointer;"
          >
            Change phone number
          </button>
        </div>
      @endif

      <div id="change-phone-box" style="{{ $phone ? 'display:none;margin-top:16px;padding-top:16px;border-top:1px solid #e2e8f0;' : 'margin-top:8px;' }}">
        <form class="auth-form" method="POST" action="{{ route('phone.verify.update') }}">
          @csrf
          @method('PUT')
          <label for="update_phone">Mobile Number</label>
          <input id="update_phone" name="phone" type="tel" required value="{{ old('phone', $phone) }}" placeholder="017XXXXXXXX" />
          <button type="submit" class="signup-submit" style="margin-top:10px;">
            {{ $phone ? 'Update Number & Send OTP' : 'Send Verification OTP' }}
          </button>
        </form>
      </div>

      <div class="signup-signin">
        <form method="POST" action="{{ route('logout') }}" style="display:inline;">
          @csrf
          <button type="submit" style="background:none;border:0;padding:0;color:#64748b;text-decoration:underline;cursor:pointer;font-size:13px;">
            Log Out
          </button>
        </form>
      </div>
    </section>
  </main>

  @if ($cooldown > 0)
    <script>
      (function () {
        let remaining = {{ (int) $cooldown }};
        const btn = document.getElementById('resend-otp-btn');
        const counter = document.getElementById('otp-countdown');
        if (!btn || !counter) return;

        const timer = setInterval(() => {
          remaining -= 1;
          if (remaining <= 0) {
            clearInterval(timer);
            btn.disabled = false;
            btn.textContent = 'Resend OTP';
          } else {
            counter.textContent = String(remaining);
          }
        }, 1000);
      })();
    </script>
  @endif
</body>
</html>
