<?php

namespace App\Http\Controllers;

use App\Mail\RegisterConfirmation;
use App\Models\Admin;
use App\Models\Conge;
use App\Models\Pointing;
use App\Models\User;
use Carbon\CarbonInterval;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;


class AdminController extends Controller
{

    
    public function addUser(Request $request)
    {
        // Check if the authenticated user is an admin
        $meResponse = $this->me();
        
        if (isset($meResponse['error'])) {
            return response()->json(['error' => 'Unauthorized. Only admins can add users.'], 401);
        }
        
        if (!isset($meResponse['role']) || $meResponse['role'] !== 'admin') {
            return response()->json(['error' => 'Unauthorized. Only admins can add users.'], 401);
        }
        
        // Validate the incoming request data
        $validator = Validator::make($request->all(), [
            'firstname' => 'required|string',
            'lastname' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'genre' => 'required|in:women,men',
        ]);
    
        // If validation fails, return error response
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }
    
        // Generate a random password
        $password = Str::random(8);
    
        // Set default avatar based on gender
        $defaultAvatar = $request->input('genre') === 'men' ? 'avatarmen.jpg' : 'womenavatar.png';
    
        // Move default avatar to public disk
        $avatarPath = "avatars/$defaultAvatar";
        Storage::disk('public')->put($avatarPath, Storage::disk('local')->get("public/$defaultAvatar"));
    
        // Create the user with the provided data and default soldecongée value of 20
        $user = User::create([
            'firstname' => $request->input('firstname'),
            'lastname' => $request->input('lastname'),
            'email' => $request->input('email'),
            'password' => bcrypt($password), // Generate and hash a random password
            'genre' => $request->input('genre'),
            'soldecongée' => 20, // Set the default value for soldecongée
            'avatar' => $avatarPath, // Set default avatar path
        ]);
    
        $data = [
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'email' => $user->email,
            'password' => $password,
        ];
    
        // Send an email with the user's login details
        Mail::to($user->email)->send(new RegisterConfirmation($data));
    
        // Return response with the newly created user's information
        return response()->json([
            'message' => 'User created successfully',
            'user' => $user,
            'password' => $password
        ]);
    }
    
public function me()
{
    $user = auth()->user();

    if (!$user) {
        return ['error' => 'Unauthorized'];
    }

    if ($user instanceof Admin) {
        return ['user' => $user, 'role' => 'admin'];
    } elseif ($user instanceof User) {
        return ['user' => $user, 'role' => 'user'];
    } else {
        return ['error' => 'Unauthorized'];
    }
}
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'firstname' => 'required|string',
            'lastname' => 'required|string',
            'email' => 'required|email|unique:admins,email',
            'password' => 'required|string|min:6',
            'genre' => 'required|in:women,men',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $admin = Admin::create([
            'firstname' => $request->input('firstname'),
            'lastname' => $request->input('lastname'),
            'email' => $request->input('email'),
            'password' => bcrypt($request->input('password')),
            'genre' => $request->input('genre'),
        ]);

        return response()->json(['message' => 'Admin registered successfully', 'admin' => $admin]);
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (Auth::guard('admin')->attempt($credentials)) {
            $admin = Auth::guard('admin')->user();
            $token = $admin->createToken('authToken')->plainTextToken;

            return response()->json([
                'access_token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => null, // No need to specify expiration for Sanctum
                'admin' => $admin,
            ]);
        }

        return response()->json(['error' => 'Unauthorized'], 401);
    }

    // Function to delete a user
    public function deleteUser($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }



    
    public function update_demande(Request $request, $id)
{
    try {
        // Check if the authenticated user is an admin
        $meResponse = $this->me();

        if (isset($meResponse['error'])) {
            return response()->json(['error' => 'Unauthorized. Only admins can update demande.'], 401);
        }

        if (!isset($meResponse['role']) || $meResponse['role'] !== 'admin') {
            return response()->json(['error' => 'Unauthorized. Only admins can update demande.'], 401);
        }

        // Find the congé request by ID
        $conge = Conge::findOrFail($id);

        // Validate the incoming request data
        $validatedData = $request->validate([
            'status' => 'required|in:en_cours,accepter,refuser',
        ]);

        // Update the status of the congé request
        $conge->update([
            'status' => $validatedData['status'],
        ]);

        $conge->save();
        
        if ($validatedData['status'] === 'accepter') {
            $user = $conge->user;
            $user->soldecongée -= $conge->solde;
            $user->save();
        }

        // Return a response indicating success
        return response()->json(['message' => 'Congé request status updated successfully', 'conge' => $conge], 200);
    } catch (\Exception $e) {
        // Handle any exceptions and return an error response
        return response()->json(['error' => 'Failed to update congé request status', 'message' => $e->getMessage()], 500);
    }
}
public function viewAllDemandes()
{
    try {
        // Check if the authenticated user is an admin
        $meResponse = $this->me();

        if (isset($meResponse['error'])) {
            return response()->json(['error' => 'Unauthorized. Only admins can view all demandes.'], 401);
        }

        if (!isset($meResponse['role']) || $meResponse['role'] !== 'admin') {
            return response()->json(['error' => 'Unauthorized. Only admins can view all demandes.'], 401);
        }

        // Fetch all demandes (congé requests)
        $demandes = Conge::all();

        // Return the demandes as a JSON response
        return response()->json(['demandes' => $demandes], 200);
    } catch (\Exception $e) {
        // Handle any exceptions and return an error response
        return response()->json(['error' => 'Failed to fetch demandes', 'message' => $e->getMessage()], 500);
    }
}

public function getPointingsByDatealluser(Request $request)
{
    $date = $request->input('date', now()->toDateString()); // Default to today's date if not provided
    
    // Retrieve all pointing records for the specified date
    $pointings = Pointing::whereDate('date', $date)
                         ->get(['user_id', 'entre', 'sortie']);

    // Initialize an array to store total working hours for each user
    $totalHoursPerUser = [];

    foreach ($pointings as $pointing) {
        $userId = $pointing->user_id;

        // Calculate the working hours for the current pointing record
        if ($pointing->entre && $pointing->sortie) {
            $entre = Carbon::parse($pointing->entre);
            $sortie = Carbon::parse($pointing->sortie);
            $duration = $sortie->diffInSeconds($entre);

            // Add the duration to the total hours for the user
            if (!isset($totalHoursPerUser[$userId])) {
                $totalHoursPerUser[$userId] = 0;
            }
            $totalHoursPerUser[$userId] += $duration;
        }
    }

    // Format the total hours for each user as HH:MM:SS
    $formattedTotalHoursPerUser = [];
    foreach ($totalHoursPerUser as $userId => $totalSeconds) {
        $formattedTotalHoursPerUser[$userId] = CarbonInterval::seconds($totalSeconds)->cascade()->forHumans();
    }

    return response()->json([
        'pointings' => $pointings,
        'total_hours_per_user' => $formattedTotalHoursPerUser
    ]);
}
}