<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouveau Mot de Passe</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #e0e0e0; /* Background color for the email body */
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff; /* Background color for the container */
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }
        .header h1 {
            margin: 0;
            color: #0056b3;
        }
        .content {
            padding: 20px 0;
        }
        .content p {
            line-height: 1.6;
        }
        .content p strong {
            color: #0056b3;
        }
        .footer {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 12px;
            color: #999;
        }
        .copy {
            cursor: pointer;
            color: #0056b3;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Nouveau Mot de Passe</h1>
        </div>
        <div class="content">
            <p>Bonjour {{ $data['email'] }},</p>
            
            <p>Votre mot de passe a été réinitialisé avec succès. Voici votre nouveau mot de passe :</p>
            
            <p><strong>Nouveau Mot de Passe:</strong> <span class="copy" onclick="copyPassword()">{{ $data['new_password'] }}</span></p>

            <p>Cliquez sur le mot de passe ci-dessus pour le copier dans votre presse-papiers.</p>
            
            <p>Veuillez conserver ces informations en sécurité et envisagez de changer régulièrement votre mot de passe.</p>
        </div>
        <div class="footer">
            <p>&copy; 2024 Votre Entreprise. Tous droits réservés.</p>
        </div>
    </div>

    <script>
        function copyPassword() {
            var tempInput = document.createElement("textarea");
            tempInput.value = "{{ $data['new_password'] }}";
            document.body.appendChild(tempInput);
            tempInput.select();
            document.execCommand("copy");
            document.body.removeChild(tempInput);
            alert("Mot de passe copié dans le presse-papiers !");
        }
    </script>
</body>
</html>
