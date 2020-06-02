<body>
  <h1>New {{ $app }} User Registration</h1>
  <p>Name: {{ $user->first_name }} {{ $user->last_name }}</p>
  <p>To view the user click the link below</p>
  <p><a href="{{ $link }}">VIEW PROFILE</a></p>
  <span>You are receiving this email because you are configured at the admin contact for {{ $app }}.</span>
</body>
