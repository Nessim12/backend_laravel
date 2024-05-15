<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Password</title>
    <script>
        function copyPassword() {
            // Create a temporary textarea element
            var tempInput = document.createElement("textarea");
            // Set the value of the textarea to the password
            tempInput.value = "{{ $data['new_password'] }}";
            // Append the textarea to the body
            document.body.appendChild(tempInput);
            // Select the textarea
            tempInput.select();
            // Copy the selected text
            document.execCommand("copy");
            // Remove the textarea from the body
            document.body.removeChild(tempInput);
            // Optionally, provide visual feedback to the user
            alert("Password copied to clipboard!");
        }
    </script>
</head>
<body>
    <p>Dear {{ $data['firstname'] }} {{ $data['lastname'] }},</p>
    
    <p>Your password has been successfully reset. Here is your new password:</p>
    
    <p><strong>New Password:</strong> <span style="cursor: pointer;" onclick="copyPassword()">{{ $data['new_password'] }}</span></p>

    <p>Tap the password above to copy it to your clipboard.</p>
    
    <p>Please keep this information secure and consider changing your password regularly.</p>
    
    <!-- Add more content as needed -->
</body>
</html>
