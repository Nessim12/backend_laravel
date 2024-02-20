<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Confirmation</title>
</head>
<body>
    <p>Dear {{ $data['firstname'] }} {{ $data['lastname'] }},</p>
    
    <p>Thank you for registering with our website. Your registration has been successful!</p>
    
    <p>Here are your registration details:</p>
    
    <ul>
        <li><strong>First Name:</strong> {{ $data['firstname'] }}</li>
        <li><strong>Last Name:</strong> {{ $data['lastname'] }}</li>
        <li><strong>Email:</strong> {{ $data['email'] }}</li>
        <li><strong>Password:</strong> {{ $data['password'] }}</li>
    </ul>
    
    <p>Please keep this information secure and do not share your password with anyone.</p>
    
    <!-- Add more content as needed -->
</body>
</html>
