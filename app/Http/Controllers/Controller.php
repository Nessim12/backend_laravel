<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
//     public function handleScannedData(Request $request)
// {
//     // Validate incoming request data if necessary
    
//     // Process the scanned data and time
//     $userId = auth()->id();
//     $scannedData = $request->input('scannedData');
//     $scanType = $request->input('scanType');
//     $scanTime = $request->input('scanTime');
    
//     // Check if there is an existing entry for the same user, scan type, and date
//     $existingEntry = Pointing::where('user_id', $userId)
//                               ->where('scan_type', $scanType)
//                             //   ->whereDate('scan_time', now()->toDateString())
//                               ->first();
    
//     if ($existingEntry) {
//         // If an entry already exists for the same user, scan type, and date, return an error response
//         return response()->json([
//             'error' => 'You have already scanned this type today.',
//         ], 400);
//     } else {
//         // Create a new entry in the database
//         Pointing::create([
//             'user_id' => $userId,
//             'scanned_data' => $scannedData,
//             'scan_type' => $scanType,
//             'scan_time' => $scanTime,
//         ]);
        
//         return response()->json(['message' => 'Scanned data processed successfully']);
//     }
// }

// public function handleScannedData(Request $request)
// {
//     // Validate incoming request data if necessary
    
//     // Process the scanned data and time
//     $userId = auth()->id();
//     $scannedData = $request->input('scannedData');
//     $scanType = $request->input('scanType');
//     $scanTime = $request->input('scanTime');
    
//     // Check if the user has already scanned "departure" for the current day
//     $hasDeparture = Pointing::where('user_id', $userId)
//                              ->where('scan_type', 'departure')
//                             //  ->whereDate('scan_time', now()->toDateString())
//                              ->exists();
    
//     // If the user has scanned "departure", allow them to scan "arrival" again
//     if ($hasDeparture && $scanType === 'arrival') {
//         // Create a new entry in the database
//         Pointing::create([
//             'user_id' => $userId,
//             'scanned_data' => $scannedData,
//             'scan_type' => $scanType,
//             'scan_time' => $scanTime,
//         ]);
        
//         return response()->json(['message' => 'Scanned data processed successfully']);
//     }
    
//     // If the user hasn't scanned "departure" yet, or if the scan type is not "arrival" after scanning "departure", check for existing entries
//     $existingEntry = Pointing::where('user_id', $userId)
//                               ->where('scan_type', $scanType)
//                             //   ->whereDate('scan_time', now()->toDateString())
//                               ->exists();
    
//     if ($existingEntry) {
//         // If an entry already exists for the same user, scan type, and date, return an error response
//         return response()->json([
//             'error' => 'You have already scanned this type today.',
//         ], 400);
//     }
    
//     // Create a new entry in the database
//     Pointing::create([
//         'user_id' => $userId,
//         'scanned_data' => $scannedData,
//         'scan_type' => $scanType,
//         'scan_time' => $scanTime,
//     ]);
    
//     return response()->json(['message' => 'Scanned data processed successfully']);
// }

// public function register(Request $request)
// {
//     $validator = Validator::make($request->all(), [
//         'firstname' => 'required|string',
//         'lastname' => 'required|string',
//         'email' => 'required|email|unique:users,email',
//         'genre' => 'required|in:women,men',
//     ]);

//     if ($validator->fails()) {
//         return response()->json(['error' => $validator->errors()], 400);
//     }

//     // Generate a random password
//     $password = Str::random(8);

//     // Create the user
//     $user = User::create([
//         'firstname' => $request->input('firstname'),
//         'lastname' => $request->input('lastname'),
//         'email' => $request->input('email'),
//         'password' => bcrypt($password),
//         'genre' => $request->input('genre'),
//     ]);

//     $data = [
//         'firstname' => $user->firstname,
//         'lastname' => $user->lastname,
//         'email' => $user->email,
//         'password' => $password,
//     ];

//     Mail::to($user->email)->send(new RegisterConfirmation($data));

//     // Return response with user and password
//     return response()->json([
//         'message' => 'User registered successfully',
//         'user' => $user,
//         'password' => $password, // Include password in the response
//     ]);
// }
//     // public function register(Request $request)
//     // {
//     //     $validator = Validator::make($request->all(), [
//     //         'firstname' => 'required|string',
//     //         'lastname' => 'required|string',
//     //         'email' => 'required|email|unique:users,email',
//     //         'password' => 'required|string|min:6',
//     //         'genre' => 'required|in:women,men',
//     //     ]);

//     //     if ($validator->fails()) {
//     //         return response()->json(['error' => $validator->errors()], 400);
//     //     }

//     //     $user = User::create([
//     //         'firstname' => $request->input('firstname'),
//     //         'lastname' => $request->input('lastname'),
//     //         'email' => $request->input('email'),
//     //         'password' => bcrypt($request->input('password')),
//     //         'genre' => $request->input('genre'),
//     //     ]);

//     //     return response()->json(['message' => 'User registered successfully', 'user' => $user]);
//     // }
//     public function login(Request $request)
// {
//     $credentials = $request->only('email', 'password');

//     if (auth()->attempt($credentials)) {
//         $user = auth()->user();
//         $token = $user->createToken('authToken')->plainTextToken;

//         return response()->json([
//             'access_token' => $token,
//             'token_type' => 'Bearer',
//             'expires_in' => null, // No need to specify expiration for Sanctum
//             'user' => $user,
//         ]);
//     }

//     return response()->json(['error' => 'Unauthorized'], 401);
// }


// public function me()
// {
//     $user = auth()->user();// obligatoire the user have auth

//     if (!$user) {
//         return response()->json(['error' => 'Unauthorized'], 401);
//     }

//     return response()->json(['user' => $user]);
// }

// public function logout()
// {
//     // Revoke the user's current token
//     auth()->user()->currentAccessToken()->delete();

//     // Log the user out
//     Auth::guard('web')->logout();

//     return response()->json(['message' => 'Successfully logged out']);
// }

// public function update(Request $request)
// {
//     // Retrieve the authenticated user
//     $user = auth()->user();

//     // Validate the request data
//     $validator = Validator::make($request->all(), [
//         'firstname' => 'required|string',
//         'lastname' => 'required|string',
//         'genre' => 'required|string',
//         'password' => 'nullable|string|min:6', // Allow password to be nullable
//     ]);

//     if ($validator->fails()) {
//         return response()->json(['error' => $validator->errors()], 400);
//     }

//     // Update user details
//     $user->firstname = $request->input('firstname');
//     $user->lastname = $request->input('lastname');
//     $user->genre = $request->input('genre');

//     // Check if a new password is provided
//     if ($request->filled('password')) {
//         // Validate the new password
//         $validator = Validator::make($request->all(), [
//             'password' => 'required|string|min:6',
//         ]);

//         if ($validator->fails()) {
//             return response()->json(['error' => $validator->errors()], 400);
//         }

//         // Update the user's password
//         $user->password = bcrypt($request->input('password'));
//     }

//     // Save the updated user details
//     $user->save();

//     return response()->json(['message' => 'User updated successfully', 'user' => $user]);
// }
// // public function displayQRCode()
// // {
// //     // Generate encrypted data with a unique identifier for the QR code
// //     $uniqueIdentifier = Str::random(10); // Generate a random string as the identifier
// //     $encryptedData = $this->encryptData('azerty' . '|' . $uniqueIdentifier, env('APP_KEY'));

// //     // Generate QR code with the encrypted data
// //     $qrCode = QrCode::size(120)->generate($encryptedData);

// //     // Pass the base64-encoded QR code and unique identifier to the view
// //     return view('welcome', ['qrCode' => $qrCode, 'identifier' => $uniqueIdentifier]);
// // }
// public function displayQRCode()
// {
//     // Data to be encoded in the QR code
//     $data = 'Your data here'; // Replace 'Your data here' with the actual data to be encoded

//     // Generate QR code with the data
//     $qrCode = QrCode::size(120)->generate($data);

//     // Pass the base64-encoded QR code to the view
//     return view('welcome', ['qrCode' => $qrCode]);
// }




// public function handleScannedData(Request $request)
// {
//     // Check if the user is authenticated
//     if (!$request->user()) {
//         return response()->json(['error' => 'Unauthorized'], 401);
//     }

//     $encryptedData = $request->input('scannedData');
//     $decryptedData = $this->decryptData($encryptedData, env('APP_KEY'));

//     // Extract data and expiration timestamp
//     list($data, $expirationTime) = explode('|', $decryptedData);

//     // Check if the QR code is still valid
//     if (now()->lessThanOrEqualTo(Carbon::parse($expirationTime))) {
//         // Process the valid scanned data
//         // ...
//         return response()->json(['message' => 'Scanned data processed successfully']);
//     }

//     // QR code has expired
//     return response()->json(['error' => 'QR code has expired'], 400);
// }

// private function decryptData($encryptedData, $key)
// {
//     return Crypt::decryptString($encryptedData);
// }


//     // Handle scanned data function removed

//     // Encryption function moved to the top for easy access
//     private function encryptData($data, $key)
//     {
//         // Encrypt the data using the provided key
//         return Crypt::encryptString($data);
//     }

// */use App\Http\Controllers\UserController;

// Route::post('/register', [UserController::class, 'register']);
// Route::post('/login', [UserController::class, 'login']);
// Route::post('/admin/register', [AdminController::class, 'register']);
// Route::post('/admin/login', [AdminController::class, 'login']);

// Route::middleware('auth:sanctum,api')->group(function () {
//     Route::post('/logout', [UserController::class, 'logout']);
//     Route::get('/user', [UserController::class, 'me']);
//     Route::put('/update', [UserController::class, 'update']);
//     Route::post('/adduser',[AdminController::class ,'addUser']);
//     Route::post('/handle_scanned_data', [UserController::class, 'handleScannedData']);
    
// });

}
