<?php

namespace App\Http\Controllers;

use App\Mail\RegisterConfirmation;
use App\Models\Admin;
use App\Models\Conge;
use App\Models\Pointing;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;


class UserController extends Controller
{
    public function login(Request $request)
{
    $credentials = $request->only('email', 'password');

    if (auth()->attempt($credentials)) {
        $user = auth()->user();
        $token = $user->createToken('authToken')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => null, // No need to specify expiration for Sanctum
            'user' => $user,
        ]);
    }

    return response()->json(['error' => 'Unauthorized'], 401);
}


public function me()
{
    $user = auth()->user();

    if (!$user) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    if ($user instanceof Admin) {
        return response()->json(['user' => $user, 'role' => 'admin']);
    } elseif ($user instanceof User) {
        return response()->json(['user' => $user, 'role' => 'user']);
    } else {
        return response()->json(['error' => 'Unauthorized'], 401);
    }
}


public function logout()
{
    // Revoke the user's current token
    auth()->user()->currentAccessToken()->delete();

    // Log the user out
    Auth::guard('web')->logout();

    return response()->json(['message' => 'Successfully logged out']);
}

public function update(Request $request)
{
    // Retrieve the authenticated user
    $user = auth()->user();

    // Validate the request data
    $validator = Validator::make($request->all(), [
        'firstname' => 'required|string',
        'lastname' => 'required|string',
        'genre' => 'required|string',
        'password' => 'nullable|string|min:6', // Allow password to be nullable
    ]);

    if ($validator->fails()) {
        return response()->json(['error' => $validator->errors()], 400);
    }

    // Update user details
    $user->firstname = $request->input('firstname');
    $user->lastname = $request->input('lastname');
    $user->genre = $request->input('genre');

    // Check if a new password is provided
    if ($request->filled('password')) {
        // Validate the new password
        $validator = Validator::make($request->all(), [
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        // Update the user's password
        $user->password = bcrypt($request->input('password'));
    }

    // Save the updated user details
    $user->save();

    return response()->json(['message' => 'User updated successfully', 'user' => $user]);
}
// public function displayQRCode()
// {
//     // Generate encrypted data with a unique identifier for the QR code
//     $uniqueIdentifier = Str::random(10); // Generate a random string as the identifier
//     $encryptedData = $this->encryptData('azerty' . '|' . $uniqueIdentifier, env('APP_KEY'));

//     // Generate QR code with the encrypted data
//     $qrCode = QrCode::size(120)->generate($encryptedData);

//     // Pass the base64-encoded QR code and unique identifier to the view
//     return view('welcome', ['qrCode' => $qrCode, 'identifier' => $uniqueIdentifier]);
// }
public function displayQRCode()
{
    // Data to be encoded in the QR code
    $data = 'ya fatma wino mo5ek'; // Replace 'Your data here' with the actual data to be encoded

    // Generate QR code with the data
    $qrCode = QrCode::size(120)->generate($data);

    // Pass the base64-encoded QR code to the view
    return view('welcome', ['qrCode' => $qrCode]);
}




public function handleScannedData(Request $request)
{
    // Check if the user is authenticated
    if (!$request->user()) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    $encryptedData = $request->input('scannedData');
    $decryptedData = $this->decryptData($encryptedData, env('APP_KEY'));

    // Extract data and expiration timestamp
    list($data, $expirationTime) = explode('|', $decryptedData);

    // Check if the QR code is still valid
    if (now()->lessThanOrEqualTo(Carbon::parse($expirationTime))) {
        // Process the valid scanned data
        // ...
        return response()->json(['message' => 'Scanned data processed successfully']);
    }

    // QR code has expired
    return response()->json(['error' => 'QR code has expired'], 400);
}

private function decryptData($encryptedData, $key)
{
    return Crypt::decryptString($encryptedData);
}


    // Handle scanned data function removed

    // Encryption function moved to the top for easy access
    private function encryptData($data, $key)
    {
        // Encrypt the data using the provided key
        return Crypt::encryptString($data);
    }

    public function create_demande(Request $request)
{
    try {
        $user = auth()->user();

        // Check if the user already has a congé request with status 'en_cours' or 'accepter'
        $existingConge = Conge::where('user_id', $user->id)
            ->whereIn('status', ['en_cours', 'accepter'])
            ->exists();

        // If the user already has a pending or accepted congé request, return an error response
        if ($existingConge) {
            return response()->json(['error' => 'You already have a pending or accepted congé request'], 400);
        }

        // Validate the incoming request data
        $validatedData = $request->validate([
            'date_d' => 'required|date',
            'date_f' => 'required|date',
            'motif' => 'required|string',
            'desciprtion' => 'required|string',
        ]);

        // Calculate the number of days between date_d and date_f
        $dateDebut = Carbon::parse($validatedData['date_d']);
        $dateFin = Carbon::parse($validatedData['date_f']);
        $nbrJours = $dateFin->diffInDays($dateDebut);

        // Create the congé request
        $conge = Conge::create([
            'date_d' => $validatedData['date_d'],
            'date_f' => $validatedData['date_f'],
            'motif' => $validatedData['motif'],
            'desciprtion' => $validatedData['desciprtion'],
            'solde' => $nbrJours, // Update solde by subtracting the number of days
            'status' => 'en_cours', // Set default status as 'en_cours'
            'user_id' => $user->id, // Associate the request with the authenticated user
        ]);

        // Return a response indicating success
        return response()->json(['message' => 'Congé request created successfully', 'conge' => $conge], 201);
    } catch (\Exception $e) {
        // Handle any exceptions and return an error response
        return response()->json(['error' => 'Failed to create congé request', 'message' => $e->getMessage()], 500);
    }
}
public function show_demandes(Request $request)
{
    try {
        $user = auth()->user();

        // Retrieve congé requests of the authenticated user
        $demandes = Conge::where('user_id', $user->id)->get();

        // Calculate solde for each demande
        $demandes->each(function ($demande) {
            $demande->solde = \Carbon\Carbon::parse($demande->date_f)->diffInDays(\Carbon\Carbon::parse($demande->date_d));
        });

        // Return response with the congé requests
        return response()->json(['demandes' => $demandes]);
    } catch (\Exception $e) {
        // Handle any exceptions and return an error response
        return response()->json(['error' => 'Failed to retrieve congé requests', 'message' => $e->getMessage()], 500);
    }
}


}
