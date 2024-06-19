<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mise à Jour de la Demande de Congé</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #e0e0e0;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
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
            <h1>Mise à Jour de la Demande de Congé</h1>
        </div>
        <div class="content">
            <p>Bonjour {{ $data['user']->firstname }} {{ $data['user']->lastname }},</p>
            
            <p>Votre demande de congé a été mise à jour. Voici les détails :</p>
            
            <p><strong>Status:</strong> {{ $data['conge']->status }}</p>
            <p><strong>Date de Début:</strong> {{ $data['conge']->date_d }}</p>
            <p><strong>Date de Fin:</strong> {{ $data['conge']->date_f }}</p>
            <p><strong>Nombre de Jours:</strong> {{ $data['conge']->solde }}</p>
            
            @if($data['conge']->status === 'refuser')
            <p><strong>Raison du Refus:</strong> {{ $data['conge']->refuse_reason }}</p>
            @endif

            <p>Veuillez consulter votre solde de congés mis à jour dans votre profil.</p>
            
            <p>Merci,</p>
            <p>L'équipe de Ressources Humaines</p>
        </div>
        <div class="footer">
            <p>&copy; 2024 Votre Entreprise. Tous droits réservés.</p>
        </div>
    </div>
</body>
</html>
