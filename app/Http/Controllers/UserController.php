<?php

namespace App\Http\Controllers;

use App\Mail\NewLeaveRequestNotification;
use App\Mail\Newpassword;
use App\Mail\NewPasswordMail;
use App\Mail\OnlineWorkRequestNotification;
use App\Mail\ProfileUpdateNotification;
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
public function sendVerificationCode(Request $request)
    {
        // Validate request input
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        // Generate a 4-digit verification code
        $verificationCode = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

        // Update user's password field with the verification code (for demonstration purposes)
        $user = User::where('email', $request->email)->first();
        $user->password = bcrypt($verificationCode); // Store the verification code temporarily
        $user->save();

        // Send the verification code via email
        $data = [
            'email' => $request->email,
            'verification_code' => $verificationCode,
        ];

        Mail::to($request->email)->send(new Newpassword($data));

        return response()->json([
            'success' => true,
            'message' => 'Verification code sent to your email.',
            'verification_code' => $verificationCode, // For demonstration, you might not want to return this in production
        ]);
    }
    
    public function resetPassword(Request $request)
    {
        // Validate request input
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'verification_code' => 'required|digits:4',
        ]);
    
        // Retrieve user by email
        $user = User::where('email', $request->email)->first();
    
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }
    
        // Check if the verification code matches the stored password hash
        if (Hash::check($request->verification_code, $user->password)) {
            // Generate a new random password
            $newPassword = Str::random(8);
    
            // Update user's password in the database
            $user->password = Hash::make($newPassword);
            $user->save();
    
            // Send the new password via email
            $data = [
                'email' => $request->email,
                'new_password' => $newPassword,
            ];
    
            // Uncomment to send email with new password
            Mail::to($request->email)->send(new NewPasswordMail($data));
    
            return response()->json([
                'success' => true,
                'message' => 'Password reset successfully. Check your email for the new password.',
                'password' => $newPassword
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Invalid verification code.',
            ], 422);
        }
    }



// public function sendNewPassword(Request $request)
//     {
//         // Validate request input
//         $request->validate([
//             'email' => 'required|email|exists:users,email',
//         ]);

//         // Generate a new password
//         $newPassword = Str::random(8); // Generate a random password

//         // Update user's password in the database
//         $user = User::where('email', $request->email)->first();
//         $user->password = Hash::make($newPassword);
//         $user->save();

//         $data = [
//             'firstname' => $user->firstname,
//             'lastname' => $user->lastname,
//             'email' => $user->email,
//             'password' => $newPassword,
//             'new_password' => $newPassword, // Add this line
//         ];
        

//         Mail::to($user->email)->send(new Newpassword($data));


//         return response()->json([
//             'success' => true,
//             'message' => 'New password sent to your email.',
//             'password' => $newPassword,
//         ]);
//     }

    public function me()
    {
        $user = auth()->user();
    
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    
        $response = ['user' => $user];
    
        if ($user instanceof Admin) {
            $response['role'] = 'admin';
        } elseif ($user instanceof User) {
            $response['role'] = 'user';
    
            // Get today's date
            $date = now()->toDateString();
    
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
                if ($pointing && $pointing->entre) {
                    $status = 'present';
                } else {
                    $status = 'absent';
                }
            }
    
            // Add the status to the response
            $response['status'] = $status;
        } else {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    
        return response()->json($response);
    }
    

    public function isHolidayToday()
    {
        $today = Carbon::today();
        $isHoliday = Holiday::where('holiday_date', $today->toDateString())->exists();
        $isSunday = $today->isSunday();
    
        $isHolidayToday = $isHoliday || $isSunday;
    
        return response()->json(['is_holiday_today' => $isHolidayToday]);
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

    // Send email notification to the user about the profile update
    Mail::to($user->email)->send(new ProfileUpdateNotification($user));

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
 

public function create_demande(Request $request)
{
    try {
        $user = Auth::user();

        $existingConge = Conge::where('user_id', $user->id)
            ->where('status', 'en_cours') // Check for 'en_cours' status
            ->exists();

        if ($existingConge) {
            return response()->json(['error' => 'Vous avez déjà une demande en attente '], 400);
        }

        // Custom validation logic for date_d to ensure it is today or in the future and not a Sunday
        $validator = Validator::make($request->all(), [
            'date_d' => ['required', 'date', function ($attribute, $value, $fail) {
                $dateDebut = Carbon::parse($value)->startOfDay();
                $today = Carbon::today();
                if ($dateDebut->lt($today)) {
                    $fail('La date de début ne peut pas être une date passée.');
                }
                if ($dateDebut->dayOfWeek === Carbon::SUNDAY) {
                    $fail('La date de début ne peut pas être un dimanche.');
                }
            }],
            'date_f' => 'required|date|after_or_equal:date_d', // Allow same day end date
            'motif_id' => 'required|exists:motifs,id',
            'description' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 400);
        }

        // Calculate the number of days between date_d and date_f, including the start date
        $dateDebut = Carbon::parse($request->date_d);
        $dateFin = Carbon::parse($request->date_f);
        $nbrJours = $dateFin->diffInDays($dateDebut) + 1; // Include the start date in the count

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
                return response()->json(['error' => 'Vous devez attendre la fin de votre dernière demande de congé acceptée'], 400);
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

        // Prepare the email data
        $data = [
            'user' => $user,
            'demande' => $demande,
        ];

        // Send email notification to the user
        Mail::to($user->email)->send(new NewLeaveRequestNotification($data)); // Replace with the actual user's email

        return response()->json(['message' => 'Leave request created successfully', 'demande' => $demande], 201);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Failed to create leave request', 'message' => $e->getMessage()], 500);
    }
}


public function delete_demande(Request $request, $id)
{
    try {
        $user = Auth::user();
        $demande = Conge::findOrFail($id);

        // Check if the leave request belongs to the authenticated user
        if ($demande->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Check if the leave request status is 'en_cours'
        if ($demande->status !== 'en_cours') {
            return response()->json(['error' => 'Cannot delete leave request with status ' . $demande->status], 400);
        }

        // Delete the leave request
        $demande->delete();

        return response()->json(['message' => 'Leave request deleted successfully'], 200);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Failed to delete leave request', 'message' => $e->getMessage()], 500);
    }
}



public function show_demandes(Request $request)
{
    try {
        $user = auth()->user();

        // Retrieve congé requests of the authenticated user with motif details
        $demandes = Conge::where('user_id', $user->id)
            ->with('motif') // Eager load the motif relationship
            ->get();

        // Calculate solde for each demande
        // $demandes->each(function ($demande) {
        //     $demande->solde = \Carbon\Carbon::parse($demande->date_f)->diffInDays(\Carbon\Carbon::parse($demande->date_d));
        // });

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

public function showholidays()
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            $holidays = Holiday::all();
            return response()->json(['holidays' => $holidays], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Something went wrong'], 500);
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
    
    // Get all users except the currently logged-in user
    $users = User::where('id', '!=', $user->id)->get();
    
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

public function getAttendanceStatusAndTimeWorked(Request $request)
{
    try {
        // Get the user ID from the request or default to the authenticated user's ID
        $userId = $request->input('user_id', auth()->id());

        // Get the user's creation date
        $creationDateResponse = $this->getUserCreationDateFromToken($request);
        $creationDate = $creationDateResponse->getData()->creation_date;

        // Initialize an array to store attendance data for each date
        $attendanceData = [];

        // Start from the user's creation date up to today
        $startDate = Carbon::parse($creationDate);
        $endDate = Carbon::today();

        // Fetch all holidays and convert to an array of strings
        $holidays = Holiday::pluck('holiday_date')->map(function ($date) {
            return Carbon::parse($date)->toDateString();
        })->toArray();

        // Loop through each date from the creation date up to today
        while ($startDate <= $endDate) {
            // Format the date
            $date = $startDate->toDateString();

            // Check if the current date is a Sunday or a holiday
            if ($startDate->dayOfWeek === Carbon::SUNDAY || in_array($date, $holidays)) {
                // Mark as holiday
                $attendanceStatus = 'holiday';
                $totalTimeWorked = null; // No need to calculate total time worked if it's a holiday
            } else {
                // Check if the user has a "conge" on the current date
                $hasConge = Conge::where('user_id', $userId)
                    ->where('status', 'accepter') // Check if the congé request is accepted
                    ->whereDate('date_d', '<=', $date) // Check if the congé starts before or on the current date
                    ->whereDate('date_f', '>=', $date) // Check if the congé ends after or on the current date
                    ->exists();

                // If the user has a congé on the current date, mark as "conge"
                if ($hasConge) {
                    $attendanceStatus = 'conge';
                    $totalTimeWorked = null; // No need to calculate total time worked if on congé
                } else {
                    // Find pointing records for the current date
                    $pointings = Pointing::where('user_id', $userId)
                        ->whereDate('date', $date)
                        ->get();

                    // Determine attendance status based on pointing records
                    $attendanceStatus = $pointings->isNotEmpty() ? 'present' : 'absent';

                    // Initialize total hours worked as null since there are no pointing records
                    $totalTimeWorked = null;

                    // If there are pointing records, calculate total time worked
                    if ($pointings->isNotEmpty()) {
                        // Initialize total time worked seconds
                        $totalTimeWorkedSeconds = 0;

                        // Calculate total time worked for the current date
                        foreach ($pointings as $pointing) {
                            if ($pointing->entre) {
                                // Calculate time worked based on check-in and check-out times
                                $checkInTime = Carbon::parse($pointing->entre);
                                $checkOutTime = $pointing->sortie ? Carbon::parse($pointing->sortie) : Carbon::now();
                                $timeWorkedSeconds = $checkOutTime->diffInSeconds($checkInTime);

                                // Add to total time worked
                                $totalTimeWorkedSeconds += $timeWorkedSeconds;
                            }
                        }

                        // Format total time worked as HH:MM
                        $totalTimeWorked = gmdate('H:i', $totalTimeWorkedSeconds);
                    }
                }
            }

            // Add attendance data for the current date to the array
            $attendanceData[] = [
                'date' => $date,
                'attendance_status' => $attendanceStatus,
                'total_hours_worked' => $totalTimeWorked,
            ];

            // Move to the next date
            $startDate->addDay();
        }

        // Return response with attendance status, total hours worked, and conge information for all dates
        return response()->json($attendanceData, 200);
    } catch (\Exception $e) {
        // Handle any exceptions and return an error response
        return response()->json(['error' => 'Failed to fetch attendance status and time worked', 'message' => $e->getMessage()], 500);
    }
}



public function getUserCreationDateFromToken(Request $request)
{
    try {
        // Get the authenticated user using the token
        $user = auth()->user();

        // Get the user's creation date
        $creationDate = $user->created_at->toDateString();

        // Return the creation date
        return response()->json(['creation_date' => $creationDate], 200);
    } catch (\Exception $e) {
        // Handle any exceptions and return an error response
        return response()->json(['error' => 'Failed to fetch user creation date', 'message' => $e->getMessage()], 500);
    }
}
public function onlinwork(Request $request)
{
    try {
        // Check if the user is authenticated
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $user = Auth::user();

        // Check if the user already has a request with status "en_cours"
        $existingRequest = Workremote::where('user_id', $user->id)
            ->where('status', 'en_cours')
            ->first();

        if ($existingRequest) {
            return response()->json(['error' => 'Vous avez déjà une demande avec le statut "en_cours".'], 400);
        }

        // Validate the incoming request data
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string',
            'date' => [
                'required',
                'date',
                'after_or_equal:tomorrow', // Date must be after or equal to tomorrow
                function ($attribute, $value, $fail) {
                    // Check if the date is a Sunday
                    if (Carbon::parse($value)->dayOfWeek === Carbon::SUNDAY) {
                        $fail('Vous ne pouvez pas soumettre une demande pour un dimanche.');
                    }

                    // Check if the date is a holiday
                    $holiday = Holiday::where('holiday_date', $value)->first();
                    if ($holiday) {
                        $fail('Vous ne pouvez pas soumettre une demande pour un jour férié (' . $holiday->holiday_name . ').');
                    }
                },
            ],
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 400);
        }

        // Create the work mode change request
        $workonline = Workremote::create([
            'user_id' => $user->id,
            'reason' => $request->input('reason'),
            'date' => $request->input('date'), // Store the date in the database
            'status' => 'en_cours', // Status is initially set to 'en_cours'
        ]);

        // Prepare the email data
        $data = [
            'user' => $user,
            'workonline' => $workonline,
        ];

        // Send email notification to the user
        Mail::to($user->email)->send(new OnlineWorkRequestNotification($data));

        return response()->json(['message' => 'Work mode change request created successfully', 'workonline' => $workonline], 201);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Failed to create work mode change request', 'message' => $e->getMessage()], 500);
    }
}


public function deleteWorkRequest(Request $request, $id)
{
    try {
        // Check if the user is authenticated
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $user = Auth::user();

        // Find the work mode change request by ID
        $workRequest = Workremote::find($id);

        // Check if the work mode change request exists
        if (!$workRequest) {
            return response()->json(['error' => 'Work mode change request not found.'], 404);
        }

        // Check if the authenticated user is the owner of the work mode change request
        if ($workRequest->user_id !== $user->id) {
            return response()->json(['error' => 'You are not authorized to delete this work mode change request.'], 403);
        }

        // Delete the work mode change request
        $workRequest->delete();

        return response()->json(['message' => 'Work mode change request deleted successfully'], 200);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Failed to delete work mode change request', 'message' => $e->getMessage()], 500);
    }
}


public function getAllWorkOnlineRequests(Request $request)
{
    try {
        // Check if the user is authenticated
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $user = Auth::user();

        // Fetch all work mode change requests for the authenticated user
        $workOnlineRequests = Workremote::where('user_id', $user->id)->get();

        return response()->json(['workonline' => $workOnlineRequests], 200);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Failed to fetch work mode change requests', 'message' => $e->getMessage()], 500);
    }
}


}
