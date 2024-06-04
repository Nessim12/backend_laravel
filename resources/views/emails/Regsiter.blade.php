<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmation d'inscription</title>
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
        .content ul {
            list-style: none;
            padding: 0;
        }
        .content ul li {
            padding: 5px 0;
        }
        .content ul li strong {
            color: #0056b3;
        }
        .footer {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 12px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Confirmation d'inscription</h1>
        </div>
        <div class="content">
            <p>Bonjour {{ $data['firstname'] }} {{ $data['lastname'] }},</p>
            
            <p>Merci de vous être inscrit sur notre site. Votre inscription a été réussie !</p>
            
            <p>Voici les détails de votre inscription :</p>
            
            <ul>
                <li><strong>Prénom :</strong> {{ $data['firstname'] }}</li>
                <li><strong>Nom :</strong> {{ $data['lastname'] }}</li>
                <li><strong>Email :</strong> {{ $data['email'] }}</li>
                <li><strong>Mot de passe :</strong> {{ $data['password'] }}</li>
            </ul>
            
            <p>Veuillez garder ces informations en sécurité et ne partagez votre mot de passe avec personne.</p>
        </div>
        <div class="footer">
            <p>&copy; 2024 Votre Entreprise. Tous droits réservés.</p>
        </div>
    </div>
</body>
</html>
