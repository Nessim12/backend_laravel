<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        // Routes de l'API utilisateur
        '/api/user/login',
        '/api/user/demande',
        '/api/user/showdemande',
        '/api/user/scan',
        '/api/user/entre',
        '/api/user/sortie',
        '/api/user/poitnagebydate',
        '/api/user/alluseravailble',
    
        // Routes de l'API administrateur
        '/api/admin/register',
        '/api/admin/login',
        '/api/admin/logout',
        '/api/admin/adduser',
        '/api/admin/updatedemande/*',
        '/api/admin/alluserpointage',
        '/api/admin/alluseravailble',
        // Ajoutez d'autres routes administratives ici...
    ];
};