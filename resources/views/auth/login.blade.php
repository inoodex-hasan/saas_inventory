<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="theme-color" content="#FFFFFF" />
  <title>Sign in to {{ config('app.name', 'InoManage') }}</title>
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

    <section class="signup-card" aria-labelledby="signin-title">
      <header class="signup-heading">
        <h1 id="signin-title">Welcome back</h1>
        <p>Sign in to your InoManage account</p>
      </header>

      @if (session('status'))
        <p class="signup-status" role="status" aria-live="polite" style="margin-bottom: 12px;">{{ session('status') }}</p>
      @endif

      @if ($errors->any())
        <p class="signup-error" role="alert" aria-live="polite" style="margin-bottom: 12px;">
          {{ $errors->first() }}
        </p>
      @endif

      <form class="auth-form" method="POST" action="{{ route('login') }}">
        @csrf

        <label for="email">Email Address</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" placeholder="name@company.com" autocomplete="username" required autofocus />

        <label for="password">Password</label>
        <input id="password" name="password" type="password" placeholder="Enter your password" autocomplete="current-password" required />

        @if (Route::has('password.request'))
          <a class="auth-forgot" href="{{ route('password.request') }}">Forgot password?</a>
        @endif

        <button type="submit" class="signup-submit">Sign In <span aria-hidden="true">&rarr;</span></button>
      </form>

      @if (Route::has('register'))
        <div class="signup-signin">New to InoManage? <a href="{{ route('register') }}">Create an account <span aria-hidden="true">&rarr;</span></a></div>
      @endif
    </section>
  </main>
</body>
</html>
