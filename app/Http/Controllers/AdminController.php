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




    public function getAllUsers()
    {
        try {
            // Check if the authenticated user is an admin
            $meResponse = $this->me();

            if (isset($meResponse['error'])) {
                return response()->json(['error' => 'Unauthorized. Only admins can view all users.'], 401);
            }

            if (!isset($meResponse['role']) || $meResponse['role'] !== 'admin') {
                return response()->json(['error' => 'Unauthorized. Only admins can view all users.'], 401);
            }

            // Fetch all users
            $users = User::all();

            // Return the users as a JSON response
            return response()->json(['users' => $users], 200);
        } catch (\Exception $e) {
            // Handle any exceptions and return an error response
            return response()->json(['error' => 'Failed to fetch users', 'message' => $e->getMessage()], 500);
        }
    }


    public function deleteUser($id)
    {
        try {
            // Check if the authenticated user is an admin
            $meResponse = $this->me();

            if (isset($meResponse['error'])) {
                return response()->json(['error' => 'Unauthorized. Only admins can delete users.'], 401);
            }

            if (!isset($meResponse['role']) || $meResponse['role'] !== 'admin') {
                return response()->json(['error' => 'Unauthorized. Only admins can delete users.'], 401);
            }

            // Find the user by ID
            $user = User::find($id);

            // Check if the user exists
            if (!$user) {
                return response()->json(['error' => 'User not found'], 404);
            }

            // Delete the user
            $user->delete();

            // Return a success message
            return response()->json(['message' => 'User deleted successfully'], 200);
        } catch (\Exception $e) {
            // Handle any exceptions and return an error response
            return response()->json(['error' => 'Failed to delete user', 'message' => $e->getMessage()], 500);
        }
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



    public function countUsers()
    {
        try {
            // Check if the authenticated user is an admin
            $meResponse = $this->me();
    
            if (isset($meResponse['error'])) {
                return response()->json(['error' => 'Unauthorized. Only admins can count users.'], 401);
            }
    
            if (!isset($meResponse['role']) || $meResponse['role'] !== 'admin') {
                return response()->json(['error' => 'Unauthorized. Only admins can count users.'], 401);
            }
    
            // Count the number of users
            $userCount = User::count();
    
            // Return the user count as a JSON response
            return response()->json(['usercount' => $userCount], 200);
        } catch (\Exception $e) {
            // Handle any exceptions and return an error response
            return response()->json(['error' => 'Failed to count users', 'message' => $e->getMessage()], 500);
        }
    }

    // Function to delete a user


    
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

        // Fetch all demandes (congé requests) with user information
        $demandes = Conge::with('user')->get();

        // Return the demandes as a JSON response
        return response()->json(['demandes' => $demandes], 200);
    } catch (\Exception $e) {
        // Handle any exceptions and return an error response
        return response()->json(['error' => 'Failed to fetch demandes', 'message' => $e->getMessage()], 500);
    }
}




public function getPointingsByDateAllUsers(Request $request)
{
    $date = $request->input('date', now()->toDateString()); // Default to today's date if not provided

    // Retrieve all pointing records for the specified date
    $pointings = Pointing::whereDate('date', $date)
                         ->get(['user_id', 'entre', 'sortie']);

    // Initialize an array to store entre and sortie times per user
    $userPointings = [];

    // Group pointing records by user_id
    foreach ($pointings as $pointing) {
        $userId = $pointing->user_id;

        // Initialize user's entre and sortie arrays if not exists
        if (!isset($userPointings[$userId])) {
            $userPointings[$userId] = [
                'user_id' => $userId,
                'entre' => [],
                'sortie' => []
            ];
        }

        // Collect entre and sortie times
        if ($pointing->entre) {
            $userPointings[$userId]['entre'][] = $pointing->entre;
        }
        if ($pointing->sortie) {
            $userPointings[$userId]['sortie'][] = $pointing->sortie;
        }
    }

    // Convert array values to list format
    $userPointings = array_values($userPointings);

    return response()->json(['user_pointings' => $userPointings], 200);
}



public function getUserStatusForToday()
{
    // Get all users
    $users = User::all();

    // Get today's date
    $today = Carbon::today()->toDateString();

    // Array to store user statuses
    $userStatuses = [];

    // Loop through each user
    foreach ($users as $user) {
        // Check if the user has a check-in record for today
        $pointing = Pointing::where('user_id', $user->id)
                            ->whereDate('date', $today)
                            ->latest()
                            ->first();

        // Determine user status based on check-in record
        $status = $pointing && $pointing->entre ? 'present' : 'absent';

        // Add user status to the array
        $userStatuses[] = [
            'user_id' => $user->id,
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'status' => $status,
        ];
    }

    // Return user statuses as JSON response
    return response()->json(['user_statuses' => $userStatuses]);
}



public function countUsersPresentToday()
{
    // Call the getUserStatusForToday() function to get the user statuses for today
    $response = $this->getUserStatusForToday();

    // Check if the response contains user statuses
    if ($response->getStatusCode() === 200) {
        // Decode the JSON response
        $data = json_decode($response->getContent(), true);
        
        // Initialize count of present users
        $presentUsersCount = 0;
        
        // Loop through user statuses
        foreach ($data['user_statuses'] as $userStatus) {
            // If the user is present, increment the count
            if ($userStatus['status'] === 'present') {
                $presentUsersCount++;
            }
        }

        // Return the count of present users as a JSON response
        return response()->json(['present_users_count' => $presentUsersCount]);
    } else {
        // Return an error response if failed to get user statuses
        return $response;
    }
}



public function getUserStatusForDate(Request $request)
{
    try {
        // Get the date from the request or default to today's date
        $date = $request->input('date', now()->toDateString());

        // Get all users
        $users = User::all();

        // Array to store user statuses
        $userStatuses = [];

        // Loop through each user
        foreach ($users as $user) {
            // Check if the user has a check-in record for the specified date
            $pointing = Pointing::where('user_id', $user->id)
                                ->whereDate('date', $date)
                                ->latest()
                                ->first();

            // Determine user status based on check-in record
            $status = $pointing && $pointing->entre ? 'present' : 'absent';

            // Add user status to the array
            $userStatuses[] = [
                'user_id' => $user->id,
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
                'status' => $status,
            ];
        }

        // Return user statuses as JSON response
        return response()->json(['user_statuses' => $userStatuses], 200);
    } catch (\Exception $e) {
        // Handle any exceptions and return an error response
        return response()->json(['error' => 'Failed to get user statuses', 'message' => $e->getMessage()], 500);
    }
}




public function getUsersAvailabilityToday()
{
    try {
        // Check if the user is authenticated
        if (!auth()->check()) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }
        
        // Get today's date
        $today = Carbon::today()->toDateString();
        
        // Get all users
        $users = User::all();
        
        // Array to store user availability status
        $availability = [];
        
        // Loop through each user
        foreach ($users as $user) {
            // Find today's pointing record for the user
            $pointing = Pointing::where('user_id', $user->id)
                                ->whereDate('date', $today)
                                ->latest() // Get the latest record first
                                ->first();
    
            // Determine user status based on pointing record
            $status = $pointing && $pointing->entre ? 'present' : 'absent';
            
            // Determine user availability based on status
            $availabilityStatus = $status === 'present' ? 'available' : 'not_available';

            // Add user availability status to the array
            $availability[] = [
                'user_id' => $user->id,
                'availability' => $availabilityStatus,
            ];
        }
        
        return response()->json(['availability' => $availability], 200);
    } catch (\Exception $e) {
        // Handle any exceptions and return an error response
        return response()->json(['error' => 'Failed to get users availability', 'message' => $e->getMessage()], 500);
    }
}


public function timeworks($userId, $date)
{
    // Find all pointing records for the specified user and date
    $pointings = Pointing::where('user_id', $userId)
                         ->whereDate('date', $date)
                         ->get();

    // Initialize total time worked in seconds
    $totalTimeWorkedSeconds = 0;

    // Process each pointing record for time calculations
    foreach ($pointings as $pointing) {
        if ($pointing->entre) {
            $checkInTime = Carbon::parse($pointing->entre);

            // Calculate time worked based on check-in and check-out times
            $checkOutTime = $pointing->sortie ? Carbon::parse($pointing->sortie) : Carbon::now();
            $timeWorkedSeconds = $checkOutTime->diffInSeconds($checkInTime);

            // Add to total time worked
            $totalTimeWorkedSeconds += $timeWorkedSeconds;
        }
    }

    // Format total time worked as HH:MM:SS
    $totalTimeWorked = gmdate('H:i', $totalTimeWorkedSeconds);

    return $totalTimeWorked;
}
public function getUserStatusesAndAvailabilityForDate(Request $request)
{
    try {
        // Get the date from the request or default to today's date
        $date = $request->input('date', now()->toDateString());

        // Get all users
        $users = User::all();

        // Array to store user statuses, availability, and time worked
        $userStatuses = [];

        // Loop through each user
        foreach ($users as $user) {
            // Find pointing record for the user on the specified date
            $pointing = Pointing::where('user_id', $user->id)
                                ->whereDate('date', $date)
                                ->orderBy('created_at', 'desc') // Order by creation time to get the latest record
                                ->first();

            // Determine user status based on pointing record
            $status = $pointing && $pointing->entre ? 'present' : 'absent';

            // Initialize availability and time worked
            $availability = null;
            $timeWorked = null;

            // Calculate availability and time worked if pointing record exists
            if ($pointing && $pointing->entre) {
                // Determine availability based on check-out status
                $availability = $pointing->sortie ? 'not_available' : 'available';

                // Calculate time worked using the timeworks function for the specific user and date
                $timeWorked = $this->timeworks($user->id, $date);
            } else {
                // No pointing record for the specified date
                $availability = 'not_available';
            }

            // Add user status, availability, and time worked to the array
            $userStatuses[] = [
                'user_id' => $user->id,
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
                'status' => $status,
                'availability' => $availability,
                'time_worked' => $timeWorked,
            ];
        }

        // Return user statuses, availability, and time worked as JSON response
        return response()->json(['user_statuses' => $userStatuses], 200);
    } catch (\Exception $e) {
        // Handle exceptions and return an error response
        return response()->json(['error' => 'Failed to get user statuses and availability', 'message' => $e->getMessage()], 500);
    }
}


}