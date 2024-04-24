<?php

namespace App\Http\Controllers;

use App\Mail\RegisterConfirmation;
use App\Models\Admin;
use App\Models\Conge;
use App\Models\Motif;
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

    // Validate the request data for updating user details
    $validator = Validator::make($request->all(), [
        'firstname' => 'nullable|string',
        'lastname' => 'nullable|string',
        'genre' => 'nullable|string',
        'password' => 'nullable|string|min:6', // Allow password to be nullable
        'cin' => 'nullable|string',
        'tel' => 'nullable|string',
        'adresse' => 'nullable|string',
    ]);

    if ($validator->fails()) {
        return response()->json(['error' => $validator->errors()], 400);
    }

    // Update user details
    if ($request->filled('firstname')) {
        $user->firstname = $request->input('firstname');
    }

    if ($request->filled('lastname')) {
        $user->lastname = $request->input('lastname');
    }

    if ($request->filled('genre')) {
        $user->genre = $request->input('genre');
    }

    if ($request->filled('cin')) {
        $user->cin = $request->input('cin');
    }

    if ($request->filled('tel')) {
        $user->tel = $request->input('tel');
    }

    if ($request->filled('adresse')) {
        $user->adresse = $request->input('adresse');
    }

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

    return response()->json(['message' => 'User details updated successfully', 'user' => $user]);
}


public function updateAvatar(Request $request)
{
    // Retrieve the authenticated user
    $user = auth()->user();

    // Validate the request data for updating the avatar
    $validator = Validator::make($request->all(), [
        'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // Allow only image files with max size of 2MB
    ]);

    if ($validator->fails()) {
        return response()->json(['error' => $validator->errors()], 400);
    }

    // Handle avatar upload
    if ($request->hasFile('avatar')) {
        // Delete previous avatar if exists
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        $avatar = $request->file('avatar');
        $imageName = Str::random(32) . '.' . $avatar->getClientOriginalExtension();
        $avatar->storeAs('avatars', $imageName, 'public'); // Store the avatar in the 'avatars' directory within the 'public' disk
        $user->avatar = $imageName;

        // Save the updated avatar
        $user->save();

        return response()->json(['message' => 'Avatar updated successfully', 'user' => $user]);
    } else {
        return response()->json(['error' => 'Avatar file not provided'], 400);
    }
}



public function displayEncryptedQRCode()
{
    // Data to be encoded in the QR code
    $user = auth()->user(); // Get current user's ID
    $data = 'testdata'; // Replace with your actual data

    // Encrypt the data along with user ID
    $encryptedData = Crypt::encrypt(['user_id' => $user, 'data' => $data]);

    // Generate QR code with the encrypted data
    $qrCode = QrCode::size(120)->generate($encryptedData);

    // Pass the base64-encoded QR code to the view
    return view('welcome', ['qrCode' => $qrCode]);

}

public function scanQRCodeAndDecryptData(Request $request)
{
    try {
        // Check if the user is authenticated
        if (!auth()->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access'], 403);
        }

        // Get the encrypted data from the request
        $encryptedData = $request->input('encryptedData');

        // Decrypt the encrypted data
        $decryptedData = Crypt::decrypt($encryptedData);
        
        // Get the authenticated user
        $user = auth()->user();

        // Retrieve user's availability status for today
        $availabilityStatus = $this->getUserAvailabilityTodayForUser($user);

        return response()->json(['success' => true, 'availability_status' => $availabilityStatus, 'data' => $decryptedData['data']], 200);

    } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
        // Error occurred during decryption
        return response()->json(['success' => false, 'message' => 'Error decrypting data'], 500);
    } catch (\Exception $e) {
        // General error handling
        return response()->json(['success' => false, 'message' => 'Error processing request'], 500);
    }
}

private function getUserAvailabilityTodayForUser($user)
{
    if (!$user) {
        return 'not_available'; // Assuming not available if user not found
    }

    $today = now()->toDateString();

    // Find today's pointing record for the authenticated user
    $pointing = Pointing::where('user_id', $user->id)
                        ->where('date', $today)
                        ->latest() // Get the latest record first
                        ->first();

    if ($pointing) {
        // Retrieve and return the availability status
        return $pointing->status_available;
    } else {
        // If no pointing record exists for today, default status to not_available
        return 'not_available';
    }
}

public function getUserAvailabilityToday()
{
    // Check if the user is authenticated
    if (!auth()->check()) {
        return response()->json(['error' => 'Unauthenticated'], 401);
    }

    $user = auth()->user();
    $today = Carbon::today()->toDateString();

    // Find today's pointing record for the authenticated user
    $pointing = Pointing::where('user_id', $user->id)
                        ->where('date', $today)
                        ->latest() // Get the latest record first
                        ->first();

    if ($pointing) {
        // Retrieve and return only the availability status
        $availabilityStatus = $pointing->status_available;
    } else {
        // If no pointing record exists for today, default status to not_available
        $availabilityStatus = 'not_available';
    }

    return response()->json(['availability_status' => $availabilityStatus], 200);
}
 

// public function scanQRCodeAndDecryptData(Request $request)
// {
//     try {
//         // Get the encrypted data from the request
//         $encryptedData = $request->input('encryptedData');

//         // Decrypt the encrypted data
//         $decryptedData = Crypt::decrypt($encryptedData);
        
//         // Check if the user ID in the decrypted data matches the current user's ID
//         $user = auth()->user();
// if ($user instanceof User) {
//     // User authenticated, return the decrypted data
//     return response()->json(['success' => true, 'data' => $decryptedData['data']]);
// } else {
//     // User not authorized, deny access
//     return response()->json(['success' => false, 'message' => 'Unauthorized access'], 403);
// }
//     } catch (\Exception $e) {
//         // Error occurred during decryption, handle it appropriately
//         return response()->json(['success' => false, 'message' => 'Error decrypting data'], 500);
//     }
// }
public function create_demande(Request $request)
{
    try {
        $user = Auth::user();

        // Check if the user already has a pending or accepted congé request
        $existingConge = Conge::where('user_id', $user->id)
            ->whereIn('status', ['en_cours']) // Check for 'en_cours' and 'accepter' statuses
            ->exists();

        if ($existingConge) {
            return response()->json(['error' => 'You already have a pending '], 400);
        }

        // Validate the incoming request data
        $validator = Validator::make($request->all(), [
            'date_d' => 'required|date',
            'date_f' => 'required|date|after:date_d',
            'motif_id' => 'required|exists:motifs,id',
            'description' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 400);
        }

        // Calculate the number of days between date_d and date_f
        $dateDebut = Carbon::parse($request->date_d);
        $dateFin = Carbon::parse($request->date_f);
        $nbrJours = $dateFin->diffInDays($dateDebut);

        // Find the last accepted leave request
        $lastAcceptedConge = Conge::where('user_id', $user->id)
            ->where('status', 'accepter') // Only consider 'accepter' status
            ->orderBy('date_f', 'desc') // Order by date_f descending
            ->first();

        if ($lastAcceptedConge) {
            // Get the end date of the last accepted leave request
            $lastEndDate = Carbon::parse($lastAcceptedConge->date_f);

            // Check if the requested start date is within or before the last accepted leave request end date
            if ($dateDebut->lessThanOrEqualTo($lastEndDate)) {
                return response()->json(['error' => 'You must wait until after your last accepted leave request ends'], 400);
            }
        }

        // Check if the remaining soldecongée is sufficient for the requested duration
        if ($user->soldecongée < $nbrJours) {
            return response()->json(['error' => 'Insufficient leave balance for this request'], 400);
        }

        // Create the leave request (conge)
        $demande = Conge::create([
            'user_id' => $user->id,
            'date_d' => $request->date_d,
            'date_f' => $request->date_f,
            'motif_id' => $request->motif_id,
            'description' => $request->description,
            'solde' => $nbrJours,
            'status' => 'en_cours', // Status is initially set to 'en_cours'
        ]);

        return response()->json(['message' => 'Leave request created successfully', 'demande' => $demande], 201);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Failed to create leave request', 'message' => $e->getMessage()], 500);
    }
}


// public function create_demande(Request $request)
// {
//     try {
//         $user = auth()->user();

//         // Check if the user already has a congé request with status 'en_cours' or 'accepter'
//         $existingConge = Conge::where('user_id', $user->id)
//             ->whereIn('status', ['en_cours', 'accepter'])
//             ->exists();

//         // If the user already has a pending or accepted congé request, return an error response
//         if ($existingConge) {
//             return response()->json(['error' => 'You already have a pending or accepted congé request'], 400);
//         }

//         // Validate the incoming request data
//         $validatedData = $request->validate([
//             'date_d' => 'required|date',
//             'date_f' => 'required|date|after:date_d', // Ensure date_f is after date_d
//             'motif_id' => 'required|exists:motifs,id', // Validate motif ID
//             'description' => 'required|string',
//         ]);

//         // Calculate the number of days between date_d and date_f
//         $dateDebut = Carbon::parse($validatedData['date_d']);
//         $dateFin = Carbon::parse($validatedData['date_f']);
//         $nbrJours = $dateFin->diffInDays($dateDebut);

//         // Create the congé request
//         $conge = Conge::create([
//             'date_d' => $validatedData['date_d'],
//             'date_f' => $validatedData['date_f'],
//             'motif_id' => $validatedData['motif_id'], // Store motif ID instead of name
//             'description' => $validatedData['description'],
//             'solde' => $nbrJours, // Update solde by subtracting the number of days
//             'status' => 'en_cours', // Set default status as 'en_attente'
//             'user_id' => $user->id, // Associate the request with the authenticated user
//         ]);

//         // Return a response indicating success
//         return response()->json(['message' => 'Congé request created successfully', 'conge' => $conge], 201);
//     } catch (\Exception $e) {
//         // Handle any exceptions and return an error response
//         return response()->json(['error' => 'Failed to create congé request', 'message' => $e->getMessage()], 500);
//     }
// }

public function show_demandes(Request $request)
{
    try {
        $user = auth()->user();

        // Retrieve congé requests of the authenticated user with motif details
        $demandes = Conge::where('user_id', $user->id)
            ->with('motif') // Eager load the motif relationship
            ->get();

        // Calculate solde for each demande
        $demandes->each(function ($demande) {
            $demande->solde = \Carbon\Carbon::parse($demande->date_f)->diffInDays(\Carbon\Carbon::parse($demande->date_d));
        });

        // Transform motif_id to motif_name in each demande
        $demandes->transform(function ($demande) {
            $demande->motif_name = $demande->motif->motif_name; // Access the motif name via the relationship
            unset($demande->motif); // Remove the motif object after extracting the motif_name
            return $demande;
        });

        // Return response with the congé requests
        return response()->json(['demandes' => $demandes]);
    } catch (\Exception $e) {
        // Handle any exceptions and return an error response
        return response()->json(['error' => 'Failed to retrieve congé requests', 'message' => $e->getMessage()], 500);
    }
}



public function checkIn(Request $request)
{
    $user = auth()->user();
    $today = Carbon::today()->toDateString();
    
    // Find today's pointing record for the user
    $pointing = Pointing::where('user_id', $user->id)
                        ->where('date', $today)
                        ->latest() // Get the latest record first
                        ->first();

    // Check if there's already a pointing record for today
    if (!$pointing || $pointing->sortie) {
        // Create a new pointing record if no record exists for today or if the last action was 'sortie'
        $pointing = new Pointing();
        $pointing->user_id = $user->id;
        $pointing->date = $today;
    } elseif ($pointing->entre) {
        // If the last action was 'entre', return an error
        return response()->json(['error' => 'Already checked in'], 400);
    }

    // Update entre time, set status to present, and change status_available to available
    $pointing->entre = Carbon::now();
    $pointing->statusjour = 'present';
    $pointing->status_available = 'available'; // Change status to available
    $pointing->save();

    return response()->json(['message' => 'Check-in successful'], 200);
}



public function checkOut(Request $request)
{
    $user = auth()->user();
    $today = Carbon::today()->toDateString();
    
    // Find today's pointing record for the user
    $pointing = Pointing::where('user_id', $user->id)
                        ->where('date', $today)
                        ->latest() // Get the latest record first
                        ->first();

    // If there's no record, return an error
    if (!$pointing) {
        return response()->json(['error' => 'No check-in found for today'], 400);
    }

    // Check if the last action was 'sortie' or there's no 'entre' action yet
    if ($pointing->sortie || !$pointing->entre) {
        return response()->json(['error' => 'Invalid check-out action'], 400);
    }

    // Update sortie time and change status_available to not_available
    $pointing->sortie = Carbon::now();
    $pointing->status_available = 'not_available'; // Change status to not_available
    $pointing->save();

    return response()->json(['message' => 'Check-out successful'], 200);
}


public function getPointingsByDate(Request $request)
    {
        $user = auth()->user();
        $date = $request->input('date', now()->toDateString()); // Default to today's date if not provided
        
        // Retrieve all pointing records for the user on the specified date
        $pointings = Pointing::where('user_id', $user->id)
                            ->whereDate('date', $date)
                            ->get(['entre', 'sortie']);

        // Calculate total working hours for the day
        $totalSeconds = 0;
        foreach ($pointings as $pointing) {
            if ($pointing->entre && $pointing->sortie) {
                $entre = Carbon::parse($pointing->entre);
                $sortie = Carbon::parse($pointing->sortie);
                $duration = $sortie->diffInSeconds($entre);
                $totalSeconds += $duration;
            }
        }

        // Format the total hours as HH:MM:SS
        $formattedTotalHours = CarbonInterval::seconds($totalSeconds)->cascade()->forHumans();

        return response()->json([
            'pointings' => $pointings,
            'total_hours' => $formattedTotalHours
        ]);
    }

    public function getUsersAvailabilityToday()
    {
        // Check if the user is authenticated
        if (!auth()->check()) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }
        
        $user = auth()->user();
        
        $today = Carbon::today()->toDateString();
        
        // Get all users
        $users = User::all();
        
        // Create an array to store user availability status
        $availability = [];
        
        // Loop through each user
        foreach ($users as $user) {
            // Find today's pointing record for the user
            $pointing = Pointing::where('user_id', $user->id)
                                ->where('date', $today)
                                ->latest() // Get the latest record first
                                ->first();
    
            // Check if there's a pointing record for today
            if ($pointing) {
                // Check if the user is available or not based on the status_available field
                $availability[] = [
                    'name' => $user->firstname,
                    'email' => $user->email,
                    'status_available' => $pointing->status_available,
                ];
            } else {
                // If no pointing record exists, default status to not_available
                $availability[] = [
                    'name' => $user->firstname,
                    'email' => $user->email,
                    'status_available' => 'not_available',
                ];
            }
        }
        
        return response()->json(['availability' => $availability], 200);
    }

    

    

// public function getPointingsByDate(Request $request)
// {
//     $user = auth()->user();
//     $date = $request->input('date', now()->toDateString()); // Default to today's date if not provided
    
//     // Retrieve all pointing records for the user on the specified date
//     $pointings = Pointing::where('user_id', $user->id)
//                         ->whereDate('date', $date)
//                         ->get(['entre', 'sortie']);

//     return response()->json(['pointings' => $pointings]);
// }
public function getTimeWorked(Request $request)
{
    $user = auth()->user();
    $today = Carbon::today()->toDateString();
    
    // Find today's pointing records for the user
    $pointings = Pointing::where('user_id', $user->id)
                        ->where('date', $today)
                        ->get();

    // Initialize variable to store total time worked
    $totalTimeWorkedSeconds = 0;

    // Calculate total time worked for the day
    foreach ($pointings as $pointing) {
        if ($pointing->entre && !$pointing->sortie) {
            // If 'entre' is present but 'sortie' is not, calculate time worked until now
            $checkInTime = Carbon::parse($pointing->entre);
            $currentTime = Carbon::now();
            $timeWorkedSeconds = $currentTime->diffInSeconds($checkInTime);
            $totalTimeWorkedSeconds += $timeWorkedSeconds;
        } elseif ($pointing->entre && $pointing->sortie) {
            // If both 'entre' and 'sortie' are present, calculate time worked normally
            $checkInTime = Carbon::parse($pointing->entre);
            $checkOutTime = Carbon::parse($pointing->sortie);
            $timeWorkedSeconds = $checkOutTime->diffInSeconds($checkInTime);
            $totalTimeWorkedSeconds += $timeWorkedSeconds;
        }
    }

    // Format total time worked as HH:MM:SS
    $totalTimeWorked = gmdate('H:i', $totalTimeWorkedSeconds);

    return response()->json(['time_worked' => $totalTimeWorked]);
}
public function timeworks(Request $request)
{
    $user = auth()->user();
    $today = Carbon::today()->toDateString();

    // Find all pointing records for the user today
    $pointings = Pointing::where('user_id', $user->id)
                         ->where('date', $today)
                         ->get();

    // Initialize total time worked in seconds
    $totalTimeWorkedSeconds = 0;

    // Process each pointing record for time calculations
    foreach ($pointings as $pointing) {
        if ($pointing->entre) {
            $checkInTime = Carbon::parse($pointing->entre);

            if (!$pointing->sortie) {
                // If there's a check-in but no check-out, calculate time worked until now
                $currentTime = Carbon::now();
                $timeWorkedSeconds = $currentTime->diffInSeconds($checkInTime);
            } else {
                // If there's both check-in and check-out, calculate normal time worked
                $checkOutTime = Carbon::parse($pointing->sortie);
                $timeWorkedSeconds = $checkOutTime->diffInSeconds($checkInTime);
            }

            // Add to total time worked
            $totalTimeWorkedSeconds += $timeWorkedSeconds;
        }
    }

    // Format total time worked as HH:MM:SS
    $totalTimeWorked = gmdate('H:i:s', $totalTimeWorkedSeconds);

    return response()->json(['time_worked' => $totalTimeWorked]);
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


}
