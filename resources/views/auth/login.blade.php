<!DOCTYPE html>
<html lang="en">
<head>	
        <title>{{ isset($title) ? $title . ' - ' : ''}} {{ config('app.name', 'Laravel') }}</title>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
	
        <link rel="icon" href="{{ asset('img/favicon.png') }}" type="image/x-icon">

	<link rel="stylesheet" type="text/css" href="{{ asset('login-v1/vendor/bootstrap/css/bootstrap.min.css') }}">

	<link rel="stylesheet" type="text/css" href="{{ asset('login-v1/fonts/font-awesome-4.7.0/css/font-awesome.min.css') }}">

	<link rel="stylesheet" type="text/css" href="{{ asset('login-v1/vendor/animate/animate.css') }}">

	<link rel="stylesheet" type="text/css" href="{{ asset('login-v1/vendor/css-hamburgers/hamburgers.min.css') }}">

	<link rel="stylesheet" type="text/css" href="{{ asset('login-v1/vendor/select2/select2.min.css') }}">

	<link rel="stylesheet" type="text/css" href="{{ asset('login-v1/css/util.css') }}">
	<link rel="stylesheet" type="text/css" href="{{ asset('login-v1/css/main.css') }}">
</head>
<body>
	
	<div class="limiter">
		<div class="container-login100">
			<div class="wrap-login100">
				<div class="login100-pic js-tilt" data-tilt>
					<img src="{{ asset('img/caap-logo.png') }}" alt="CAAP Logo">
				</div>

				<form method="POST" action="{{ route('login') }}" class="login100-form validate-form">
					@csrf
				
					<span class="login100-form-title">
						<span class="text-primary">CAAP</span><br>
						Data Tracking
					</span>

					
                                <x-validation-errors class="mb-4" />

                                @session('status')
                                <div class="mb-4 font-medium text-sm text-green-600">
                                        {{ $value }}
                                </div>
                                @endsession

				
					<div class="wrap-input100 validate-input" data-validate="Valid email is required: ex@abc.xyz">
						<input class="input100" type="email" name="email" value="{{ old('email') }}" required autofocus placeholder="Email">
						<span class="focus-input100"></span>
						<span class="symbol-input100">
							<i class="fa fa-envelope" aria-hidden="true"></i>
						</span>
					</div>
				
					<div class="wrap-input100 validate-input" data-validate="Password is required">
						<input id="password" class="input100" type="password" name="password" required placeholder="Password">
						<span class="focus-input100"></span>
						<span class="symbol-input100">
							<i class="fa fa-lock" aria-hidden="true"></i>
						</span>
					</div>
					<div class="text-center">
                                          <button type="button" onclick="togglePassword()" class="text-secondary text-decoration-underline">
                                             <u>show password<u/>
                                          </button>
					</div>
				
					<div class="container-login100-form-btn">
						<button type="submit" class="login100-form-btn">
							Login
						</button>
					</div>
				
					<div class="text-center p-t-12">
						<span class="txt1"></span>
						<a class="text-primary text-decoration-underline" href="{{ route('password.request') }}">
							Update your password?
						</a>
					</div>
				
					
				</form>
				
			</div>
		</div>
	</div>
	
	

	

	<script src="{{ asset('login-v1/vendor/jquery/jquery-3.2.1.min.js') }}"></script>

	<script src="{{ asset('login-v1/vendor/bootstrap/js/popper.js') }}"></script>
	<script src="{{ asset('login-v1/vendor/bootstrap/js/bootstrap.min.js') }}"></script>

	<script src="{{ asset('login-v1/vendor/select2/select2.min.js') }}"></script>
	<script src="{{ asset('login-v1/vendor/tilt/tilt.jquery.min.js') }}"></script>
	<script >
		$('.js-tilt').tilt({
			scale: 1.1
		})
	</script>
	<script src="{{ asset('login-v1/js/main.js') }}"></script>
	<script>
    function togglePassword() {
        const input = document.getElementById('password');
        input.type = input.type === 'password' ? 'text' : 'password';
    }
</script>


</body>
</html>
