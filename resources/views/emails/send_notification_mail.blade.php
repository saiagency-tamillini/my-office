<!DOCTYPE html>
<html>
<head>
    <title>Welcome</title>
</head>
<body>
    <h2>Welcome {{ $user->username }}</h2>

    <p>You have successfully logged into the application.</p>

    <p>Login Time: {{ now() }}</p>

    <p>Thank you.</p>
</body>
</html>