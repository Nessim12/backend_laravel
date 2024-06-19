<?php

use App\Http\Controllers\AdminController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/use App\Http\Controllers\UserController;
Route::prefix('user')->group(function () {
    Route::post('/login', [UserController::class, 'login']);
    Route::post('/newpassword', [UserController::class, 'sendVerificationCode']);
    Route::post('/resetnewpassword', [UserController::class, 'resetPassword']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [UserController::class, 'logout']);
        Route::get('/user', [UserController::class, 'me']);
        Route::get('/isHolidayToday', [UserController::class, 'isHolidayToday']);
        Route::post('/update', [UserController::class, 'update']);
        Route::post('/updateavatar', [UserController::class, 'updateAvatar']);
        Route::post('/demande', [UserController::class, 'create_demande']);
        Route::put('/update_demande/{id}', [UserController::class, 'update_demande']);
        Route::delete('/delete_demande/{id}', [UserController::class, 'delete_demande']);
        Route::get('/allmotifs', [UserController::class, 'getAllMotifs']);
        Route::get('/showdemande', [UserController::class, 'show_demandes']);
        Route::post('/scan', [UserController::class, 'scanQRCodeAndDecryptData']);
        Route::post('/entre', [UserController::class, 'checkIn']);
        Route::post('/sortie', [UserController::class, 'checkOut']);
        Route::post('/poitnagebydate', [UserController::class, 'getPointingsByDate']);
        Route::get('/alluseravailble', [UserController::class, 'getUsersAvailabilityToday']);
        Route::get('/useravailble', [UserController::class, 'getUserAvailabilityToday']);
        Route::get('/Countertime', [UserController::class, 'getTimeWorked']);
        Route::get('/timeworks', [UserController::class, 'timeworks']);
        Route::get('/calendrie', [UserController::class, 'getAttendanceStatusAndTimeWorked']);
        Route::get('/datecreate', [UserController::class, 'getUserCreationDateFromToken']);
        Route::post('/onlinwork', [UserController::class, 'onlinwork']);
        Route::delete('/deleteWorkRequest/{id}', [UserController::class, 'deleteWorkRequest']);
        Route::get('/showonlinwork', [UserController::class, 'getAllWorkOnlineRequests']);
        Route::get('/showholidays', [UserController::class, 'showholidays']);
        // Add more user-specific routes here...
    });
});

// Routes for Admin Authentication
Route::prefix('admin')->group(function () {
    Route::post('/register', [AdminController::class, 'register']);
    Route::post('/login', [AdminController::class, 'login']);
    Route::get('/update-work-mode', [AdminController::class, 'updateWorkModeAutomatically']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user', [AdminController::class, 'me']);
        Route::get('/countUsers', [AdminController::class, 'countUsers']);
        Route::get('/users', [AdminController::class, 'getAllUsers']);
        Route::get('/userdetails/{id}', [AdminController::class, 'getUserDetails']);
        Route::post('/adduser', [AdminController::class, 'addUser']);
        Route::put('/updateuser/{id}', [AdminController::class, 'updateUser']);
        Route::post('/addmotif', [AdminController::class, 'addMotif']);
        Route::put('/updatemotif/{id}', [AdminController::class, 'updateMotif']);
        Route::delete('/deletemotif/{id}', [AdminController::class, 'deleteMotif']);
        Route::put('/updatedemande/{id}', [AdminController::class, 'update_demande']);
        Route::get('/allmotifs', [AdminController::class, 'getAllMotifs']);
        Route::delete('/deleteuser/{id}', [AdminController::class, 'deleteUser']);
        Route::get('/alldemande', [AdminController::class, 'viewAllDemandes']);
        Route::post('/alluserpointage', [AdminController::class, 'getPointingsByDateAllUsers']);
        Route::get('/alluseretat', [AdminController::class, 'getUserStatusForToday']);
        Route::get('/alluserpresent', [AdminController::class, 'countUsersPresentToday']);
        Route::post('/alluseretatwithdate', [AdminController::class, 'getUserStatusesAndAvailabilityForDate']);
        Route::post('/getUserDailyWorkTime/{id}', [AdminController::class, 'getUserMonthlyWorkTimes']);
        Route::get('/alluseravaibleadmin', [AdminController::class, 'getUsersAvailabilityToday']);
        Route::post('/timeworks', [AdminController::class, 'timeworks']);
        Route::get('/allonlinework', [AdminController::class, 'getAllOnlineWork']);
        Route::put('/updateonline/{id}', [AdminController::class, 'updateonlinework']);
        Route::post('/addholiday', [AdminController::class, 'addholiday']);
        Route::put('/updateholiday/{id}', [AdminController::class, 'updateHoliday']);
        Route::delete('/deleteholiday/{id}', [AdminController::class, 'deleteHoliday']);
        Route::get('/holidays', [AdminController::class, 'getAllHolidays']);  
        // Add more admin-specific routes here...
    });
});
