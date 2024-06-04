<?php

namespace App\Http\Controllers;

use App\Mail\RegisterConfirmation;
use App\Models\Admin;
use App\Models\Conge;
use App\Models\Holiday;
use App\Models\Motif;
use App\Models\Pointing;
use App\Models\User;
use App\Models\Workremote;
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
        'cin' => 'required|string',
        'firstname' => 'required|string',
        'lastname' => 'required|string',
        'email' => 'required|email|unique:users,email',
        'tel' => 'required|string',
        'adresse' => 'required|string',
        'genre' => 'required|in:women,men',
    ]);

    // If validation fails, return error response
    if ($validator->fails()) {
        return response()->json(['error' => $validator->errors()], 400);
    }

    // Generate a random password
    $password = Str::random(8);

    // Set default avatar based on gender
    $defaultAvatar = $request->input('genre') === 'men' ? 'avatarmen1.jpg' : 'womenavatar.jpg';

    // Move default avatar to public disk
    $avatarPath = "avatars/$defaultAvatar";
    Storage::disk('public')->put($avatarPath, Storage::disk('local')->get("public/$defaultAvatar"));

    // Create the user with the provided data and default soldecongée value of 20
    $user = User::create([
        'cin' => $request->input('cin'),
        'firstname' => $request->input('firstname'),
        'lastname' => $request->input('lastname'),
        'email' => $request->input('email'),
        'password' => bcrypt($password), // Generate and hash a random password
        'tel' => $request->input('tel'),
        'adresse' => $request->input('adresse'),
        'genre' => $request->input('genre'),
        'work_mod' => 'presentiel',
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



public function updateonlinework(Request $request, $id)
{
    try {
        $meResponse = $this->me();

        if (isset($meResponse['error'])) {
            return response()->json(['error' => 'Unauthorized. Only admins can update work mode change requests.'], 401);
        }

        if (!isset($meResponse['role']) || $meResponse['role'] !== 'admin') {
            return response()->json(['error' => 'Unauthorized. Only admins can update work mode change requests.'], 401);
        }

        // Find the work mode change request by ID
        $workonline = Workremote::find($id);

        // If work mode change request not found, return error response
        if (!$workonline) {
            return response()->json(['error' => 'Work mode change request not found'], 404);
        }

        // Validate the incoming request data
        $validatedData = $request->validate([
            'status' => 'required|in:accepted,refused',
        ]);

        // Update the status of the work mode change request
        $workonline->status = $validatedData['status'];
        $workonline->save();

        return response()->json(['message' => 'Work mode change request updated successfully'], 200);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Failed to update work mode change request', 'message' => $e->getMessage()], 500);
    }
}



public function updateUser(Request $request, $id)
{
    // Check if the authenticated user is an admin
    $meResponse = $this->me();

    if (isset($meResponse['error'])) {
        return response()->json(['error' => 'Unauthorized. Only admins can update users.'], 401);
    }

    if (!isset($meResponse['role']) || $meResponse['role'] !== 'admin') {
        return response()->json(['error' => 'Unauthorized. Only admins can update users.'], 401);
    }

    // Find the user by ID
    $user = User::find($id);

    // If user not found, return error response
    if (!$user) {
        return response()->json(['error' => 'User not found'], 404);
    }

    // Validate the incoming request data
    $validator = Validator::make($request->all(), [
        'cin' => 'required|string',
        'firstname' => 'required|string',
        'lastname' => 'required|string',
        'email' => 'required|email|unique:users,email,' . $user->id, // Ignore unique check for the current user
        'tel' => 'required|string',
        'adresse' => 'required|string',
        'genre' => 'required|in:women,men',
    ]);

    // If validation fails, return error response
    if ($validator->fails()) {
        return response()->json(['error' => $validator->errors()], 400);
    }

    // Update user with the provided data
    $user->cin = $request->input('cin');
    $user->firstname = $request->input('firstname');
    $user->lastname = $request->input('lastname');
    $user->email = $request->input('email');
    $user->tel = $request->input('tel');
    $user->adresse = $request->input('adresse');
    $user->genre = $request->input('genre');

    // Save the updated user
    $user->save();

    // Return response with the updated user's information
    return response()->json([
        'message' => 'User updated successfully',
        'user' => $user
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
    public function getUserDetails($id)
    {
        try {
            // Check if the authenticated user is an admin
            $meResponse = $this->me();
    
            if (isset($meResponse['error'])) {
                return response()->json(['error' => 'Unauthorized. Only admins can view user details.'], 401);
            }
    
            if (!isset($meResponse['role']) || $meResponse['role'] !== 'admin') {
                return response()->json(['error' => 'Unauthorized. Only admins can view user details.'], 401);
            }
    
            // Fetch the user with their conge and work online requests
            $user = User::find($id);
    
            if (!$user) {
                return response()->json(['error' => 'User not found'], 404);
            }
    
            // Return the detailed data as a JSON response
            return response()->json(['user' => $user], 200);
        } catch (\Exception $e) {
            // Handle any exceptions and return an error response
            return response()->json(['error' => 'Failed to fetch user details', 'message' => $e->getMessage()], 500);
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
    
            // Call the updateWorkModeAutomatically method
            $this->updateWorkModeAutomatically();
    
            return response()->json([
                'access_token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => null, // No need to specify expiration for Sanctum
                'admin' => $admin,
            ]);
        }
    
        return response()->json(['error' => 'Unauthorized'], 401);
    }
    
    public function updateWorkModeAutomatically()
    {
        try {
            // Get today's date
            $today = Carbon::today();
    
            // Get all users
            $users = User::all();
    
            // Loop through each user
            foreach ($users as $user) {
                // Check if the user has an online work request for today with an accepted status
                $onlineWorkRequest = Workremote::where('user_id', $user->id)
                    ->whereDate('date', $today)
                    ->where('status', 'accepted')
                    ->first();
    
                // If an online work request exists for today and it's accepted, set work_mod to 'accepter'
                // Otherwise, set work_mod to 'presentiel'
                if ($onlineWorkRequest) {
                    $user->work_mod = 'accepter';
                } else {
                    $user->work_mod = 'presentiel';
                }
    
                // Save the changes
                $user->save();
            }
    
            return response()->json(['message' => 'Work mode updated successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update work mode', 'message' => $e->getMessage()], 500);
        }
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
                'refuse_reason' => 'required_if:status,refuser|string', // Require refuse_reason when status is "refuser"
            ]);
    
            // Update the status and refuse_reason (if applicable) of the congé request
            $conge->status = $validatedData['status'];
            if ($validatedData['status'] === 'refuser') {
                $conge->refuse_reason = $validatedData['refuse_reason'];
            }
            $conge->save();
    
            // Deduct soldecongée from user's balance if the request is accepted
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
    
            // Fetch all demandes (congé requests) with user and motif information
            $demandes = Conge::with(['user', 'motif'])->get();
    
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
            // Determine user status
            $status = 'absent'; // Default status is absent

            // Initialize availability and time worked
            $availability = null;
            $timeWorked = null;

            // Check if today is a holiday
            $isHoliday = Holiday::whereDate('holiday_date', $date)->exists();

            // Check if the user has a leave (congé) request for today
            $hasLeave = conge::where('user_id', $user->id)
                                    ->where('status', 'accepter')
                                    ->where(function($query) use ($date) {
                                        $query->whereDate('date_d', '<=', $date)
                                              ->whereDate('date_f', '>=', $date);
                                    })
                                    ->exists();

            if ($isHoliday) {
                // Today is a holiday, set status to 'Holiday'
                $status = 'Holiday';
            } elseif ($hasLeave) {
                // User has an accepted leave request for today, set status to 'Conge'
                $status = 'Conge';
            } else {
                // Find pointing record for the user on the specified date
                $pointing = Pointing::where('user_id', $user->id)
                                    ->whereDate('date', $date)
                                    ->orderBy('created_at', 'desc') // Order by creation time to get the latest record
                                    ->first();

                // Determine user status based on pointing record
                $status = $pointing && $pointing->entre ? 'present' : 'absent';

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
            }

            // Check if the user has an online work request for today with an accepted status
            $onlineWorkRequest = Workremote::where('user_id', $user->id)
                ->whereDate('date', $date)
                ->where('status', 'accepted')
                ->exists();

            // If an online work request exists for today and it's accepted, set work_mod to 'accepter'
            // Otherwise, set work_mod to 'presentiel'
            $work_mod = $onlineWorkRequest ? 'accepter' : 'presentiel';

            // Add user status, availability, and time worked to the array
            $userStatuses[] = [
                'user_id' => $user->id,
                'cin' => $user->cin,
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
                'tel' => $user->tel,
                'work_mod' => $work_mod, // Update work_mod based on online work request
                'email' => $user->email,
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

// public function getUserStatusesAndAvailabilityForDate(Request $request)
// {
//     try {
//         // Get the date from the request or default to today's date
//         $date = $request->input('date', now()->toDateString());

//         // Get all users
//         $users = User::all();

//         // Array to store user statuses, availability, and time worked
//         $userStatuses = [];

//         // Loop through each user
//         foreach ($users as $user) {
//             // Determine user status
//             $status = 'absent'; // Default status is absent

//             // Initialize availability and time worked
//             $availability = null;
//             $timeWorked = null;

//             // Check if today is a holiday
//             $isHoliday = Holiday::whereDate('holiday_date', $date)->exists();

//             // If today is not a holiday, check user's pointing record
//             if (!$isHoliday) {
//                 // Find pointing record for the user on the specified date
//                 $pointing = Pointing::where('user_id', $user->id)
//                                     ->whereDate('date', $date)
//                                     ->orderBy('created_at', 'desc') // Order by creation time to get the latest record
//                                     ->first();

//                 // Determine user status based on pointing record
//                 $status = $pointing && $pointing->entre ? 'present' : 'absent';

//                 // Calculate availability and time worked if pointing record exists
//                 if ($pointing && $pointing->entre) {
//                     // Determine availability based on check-out status
//                     $availability = $pointing->sortie ? 'not_available' : 'available';

//                     // Calculate time worked using the timeworks function for the specific user and date
//                     $timeWorked = $this->timeworks($user->id, $date);
//                 } else {
//                     // No pointing record for the specified date
//                     $availability = 'not_available';
//                 }
//             } else {
//                 // Today is a holiday, set status to 'Holiday'
//                 $status = 'Holiday';
//             }

//             // Check if the user has an online work request for today with an accepted status
//             $onlineWorkRequest = Workremote::where('user_id', $user->id)
//                 ->whereDate('date', $date)
//                 ->where('status', 'accepted')
//                 ->exists();

//             // If an online work request exists for today and it's accepted, set work_mod to 'accepter'
//             // Otherwise, set work_mod to 'presentiel'
//             $work_mod = $onlineWorkRequest ? 'accepter' : 'presentiel';

//             // Add user status, availability, and time worked to the array
//             $userStatuses[] = [
//                 'user_id' => $user->id,
//                 'cin' => $user->cin,
//                 'firstname' => $user->firstname,
//                 'lastname' => $user->lastname,
//                 'tel' => $user->tel,
//                 'work_mod' => $work_mod, // Update work_mod based on online work request
//                 'email' => $user->email,
//                 'status' => $status,
//                 'availability' => $availability,
//                 'time_worked' => $timeWorked,
//             ];
//         }

//         // Return user statuses, availability, and time worked as JSON response
//         return response()->json(['user_statuses' => $userStatuses], 200);
//     } catch (\Exception $e) {
//         // Handle exceptions and return an error response
//         return response()->json(['error' => 'Failed to get user statuses and availability', 'message' => $e->getMessage()], 500);
//     }
// }

public function getUserDailyWorkTime($id, Request $request)
{
    try {
        $startDate = Carbon::parse($request->input('start_date', now()->startOfMonth()));
        $endDate = Carbon::parse($request->input('end_date', now()->endOfMonth()));

        // Validate if the user exists
        $user = User::find($id);
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $dailyWorkTimes = [];

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $dateString = $date->toDateString();
            $timeWorked = $this->timeworks($user->id, $dateString);
            $dailyWorkTimes[] = [
                'date' => $dateString,
                'time_worked' => $timeWorked,
            ];
        }

        $userWorkTimes = [
            'user_id' => $user->id,
            'cin' => $user->cin,
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'tel' => $user->tel,
            'email' => $user->email,
            'daily_work_times' => $dailyWorkTimes,
        ];

        return response()->json(['user_work_times' => $userWorkTimes], 200);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Failed to get user daily work times', 'message' => $e->getMessage()], 500);
    }
}

public function getUserMonthlyWorkTimes($id, Request $request)
{
    try {
        // Get the selected year and month from the request
        $year = $request->input('year', now()->year); // Default to the current year if not provided
        $month = $request->input('month'); // Get the selected month from the request

        // Validate if the user exists
        $user = User::find($id);
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $monthlyWorkTimes = [];

        // Determine the range of months to process
        $months = $month ? [$month] : range(1, 12);

        // Loop through each specified month to calculate the total work time and presence/absence
        foreach ($months as $month) {
            $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
            $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();

            // Get all pointing records for the user within the specified date range
            $pointingRecords = Pointing::where('user_id', $id)
                                        ->whereBetween('date', [$startDate, $endDate])
                                        ->get();

            $totalTimeWorked = 0; // Initialize total time worked
            $presentDays = collect(); // Use a collection to store present days

            // Calculate total time worked for the month and count present days
            foreach ($pointingRecords as $record) {
                if ($record->entre && $record->sortie) {
                    // Calculate time difference between check-in and check-out times
                    $checkIn = Carbon::parse($record->entre);
                    $checkOut = Carbon::parse($record->sortie);
                    $timeWorked = $checkOut->diffInMinutes($checkIn); // Calculate time difference in minutes
                    $totalTimeWorked += $timeWorked;
                    // Add the date to the collection of present days
                    $presentDays->put($record->date, true);
                }
            }

            // Count unique present days
            $uniquePresentDaysCount = $presentDays->count();
            // Calculate total days in the month
            $totalDaysInMonth = $startDate->daysInMonth;
            // Calculate absent days
            $absentDays = $totalDaysInMonth - $uniquePresentDaysCount;

            // Convert total time worked from minutes to hours and minutes format
            $hours = floor($totalTimeWorked / 60);
            $minutes = $totalTimeWorked % 60;

            // Format the total time worked
            $formattedTotalTimeWorked = sprintf('%02d:%02d', $hours, $minutes);

            // Store the result for the current month
            $monthlyWorkTimes[] = [
                'month' => $month,
                'total_work_time' => $formattedTotalTimeWorked,
                'present_days' => $uniquePresentDaysCount,
                'absent_days' => $absentDays
            ];
        }

        // Prepare the response data
        $userWorkTimes = [
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'year' => $year,
            'monthly_work_times' => $monthlyWorkTimes
        ];

        // Return the user work times for the specified months
        return response()->json($userWorkTimes, 200);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Failed to get user monthly work times', 'message' => $e->getMessage()], 500);
    }
}



public function addMotif(Request $request)
{
    $validator = Validator::make($request->all(), [
        'motif_name' => 'required|string|unique:motifs,motif_name',
    ]);

    if ($validator->fails()) {
        return response()->json(['error' => $validator->errors()], 400);
    }

    $motif = Motif::create([
        'motif_name' => $request->input('motif_name'),
    ]);

    return response()->json(['message' => 'Motif added successfully', 'motif' => $motif], 201);
}

// Method to update an existing motif
public function updateMotif(Request $request, $motifId)
{
    $motif = Motif::findOrFail($motifId);

    $validator = Validator::make($request->all(), [
        'motif_name' => 'required|string|unique:motifs,motif_name,' . $motif->id,
    ]);

    if ($validator->fails()) {
        return response()->json(['error' => $validator->errors()], 400);
    }

    $motif->motif_name = $request->input('motif_name');
    $motif->save();

    return response()->json(['message' => 'Motif updated successfully', 'motif' => $motif], 200);
}

// Method to delete an existing motif
public function deleteMotif($motifId)
{
    $motif = Motif::findOrFail($motifId);
    $motif->delete();

    return response()->json(['message' => 'Motif deleted successfully'], 200);
}
public function getAllMotifs()
{
    try {
        // Fetch all motifs
        $motifs = Motif::all();

        // Return motifs as JSON response
        return response()->json(['motifs' => $motifs], 200);
    } catch (\Exception $e) {
        // Handle any exceptions and return an error response
        return response()->json(['error' => 'Failed to fetch motifs', 'message' => $e->getMessage()], 500);
    }
}
public function getAllOnlineWork()
{
    try {
        // Retrieve all online work change requests with the associated user's firstname and lastname
        $onlineWorkRequests = Workremote::with('user:id,firstname,lastname')->get();

        // Return the list of online work change requests as JSON response
        return response()->json(['workonline' => $onlineWorkRequests], 200);
    } catch (\Exception $e) {
        // Handle any exceptions and return an error response
        return response()->json(['error' => 'Failed to fetch online work change requests', 'message' => $e->getMessage()], 500);
    }
}
public function addholiday(Request $request)
    {
        $request->validate([
            'holiday_date' => 'required|date',
            'holiday_name' => 'required|string',
        ]);

        $holiday = Holiday::create([
            'holiday_date' => $request->input('holiday_date'),
            'holiday_name' => $request->input('holiday_name'),
        ]);

        return response()->json(['message' => 'Holiday created successfully', 'holiday' => $holiday], 201);
    }

    public function updateHoliday(Request $request, $id)
    {
        $request->validate([
            'holiday_date' => 'required|date',
            'holiday_name' => 'required|string',
        ]);
    
        $holiday = Holiday::findOrFail($id);
    
        $holiday->update([
            'holiday_date' => $request->input('holiday_date'),
            'holiday_name' => $request->input('holiday_name'),
        ]);
    
        return response()->json(['message' => 'Holiday updated successfully', 'holiday' => $holiday], 200);
    }

    public function deleteHoliday($id)
{
    $holiday = Holiday::findOrFail($id);

    $holiday->delete();

    return response()->json(['message' => 'Holiday deleted successfully'], 200);
}
public function getAllHolidays()
{
    try {
        $holidays = Holiday::all();
        return response()->json($holidays, 200);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Failed to fetch holidays', 'message' => $e->getMessage()], 500);
    }
}



}