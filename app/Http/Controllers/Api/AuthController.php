<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules;
use libphonenumber\PhoneNumberUtil;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\NumberParseException;

class AuthController extends Controller
{
	public function login1(Request $request)
	{
		$credentials = $request->validate([
			'email'=> ['required', 'email'],
			'password' => 'required',
			'remember' => 'boolean'
		]);

		$remember = $credentials['remember'] ?? false;
		unset($credentials['remember']);
		if (!Auth::attempt($credentials, $remember)) {
			return response([
				'message' => 'Email or password is incorrect'
			], 422);
		}

		/** @var \App\Models\User $user */
		$user = Auth::user();
		//$row = \DataForge::getUser($user->id);
		if (!$user) {
			Auth::logout();
			return response([
				'message' => 'User not found!'
			], 422);
		}

		// Allowed user group valiation.
		//$allowedGroups = DataForge::siteAccess($request->header('X-Site-Origin'));
		//if (!$allowedGroups || !in_array($row->group, $allowedGroups)) {
		    /*Auth::logout();
			return response([
				'message' => 'You don\'t have permission to authenticate as '.$request->header('X-Site-Origin')
			], 403);*/
		//}

		if ($user->block) {
			Auth::logout();
			return response([
				'message' => 'Your email address is not verified'
			], 403);
		}

		$token = $user->createToken('main')->plainTextToken;
		return response([
			'user' => $user->toArray(),
			'token' => $token
		]);
	}

	public function login(Request $request)
	{
		$request->validate([
			'email' => 'required|string',
			'otp' => 'nullable|string',
			'password' => 'nullable|string',
			'remember' => 'boolean',
		]);

		$remember = $request->boolean('remember', false);

		$normalized = $this->normalizePhone($request->email);
		if ($normalized)
			$user = User::where('phone', ltrim($normalized['e164'], '+'))->first();
		else
			$user = User::where('email', $request->email)->first();

		if (!$user) {
			return response(['message' => 'User not found!'], 422);
		}

		// OTP login path
		if ($request->filled('otp')) {
			if ($request->otp !== '1234') { // Dummy OTP for now
				return response(['message' => 'Invalid OTP'], 422);
			}
		}
		// Password login path
		elseif (!Hash::check($request->password, $user->password)) {
			return response(['message' => 'Email or password is incorrect'], 422);
		}

		if ($user->block) {
			return response(['message' => 'Your email address is not verified'], 403);
		}

		Auth::login($user, $remember);
		$token = $user->createToken('main')->plainTextToken;

		return response([
			'user' => $user->toArray(),
			'token' => $token
		]);
	}


	public function logout()
	{
		/** @var \App\Models\User $user */
		$user = Auth::user();
		$user->currentAccessToken()->delete();

		return response('', 204);
	}

	public function guestToken()
	{
		//$guestUser = User::factory()->create(['is_guest' => true]);

		// Generate a token with limited permissions or scope
		//$guestToken = $guestUser->createToken('guest_token', ['guest'])->plainTextToken;
		
		return response([
			'user' => Str::uuid(),
			'token' => Str::uuid()
		]);
	}

	public function register(Request $request)
    {
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'first_name' => ['required', 'string', 'max:255'],
			'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            //'password' => ['required', 'confirmed', Rules\Password::defaults()],
			'password' => ['required', Rules\Password::min(5)
        							//->letters()
        							//->mixedCase()
        							//->numbers()
					//->symbols() // Uncomment if you want to require symbols
					//->uncompromised() // Uncomment to check against compromised passwords
			],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {
			$site = $request->header('X-Site-Origin');

            // Create the user
            $user = User::create([
                'first_name' => $request->first_name,
				'last_name' => $request->last_name,
				'name' => $request->first_name.' '.$request->last_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
				'site' => $site
            ]);

            // You can generate a token here if you want immediate login
            // $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'error' => false,
                'message' => 'Registration successful! Please login.',
                'data' => [
                    'user' => $user,
                    // 'token' => $token // Include if using immediate login
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Registration failed!',
                'details' => $e->getMessage()
            ], 500);
        }
    }

	function normalizePhone($input, $defaultCountry = 'IN') 
	{
		$phoneUtil = PhoneNumberUtil::getInstance();

		try {
			$numberProto = $phoneUtil->parse($input, $defaultCountry);

			if (!$phoneUtil->isValidNumber($numberProto)) {
				return null;
			}

			return [
				'e164' => $phoneUtil->format($numberProto, PhoneNumberFormat::E164), // +919894839888
				'region' => $phoneUtil->getRegionCodeForNumber($numberProto),        // IN
				'national' => $phoneUtil->format($numberProto, PhoneNumberFormat::NATIONAL), // 09894 839888
			];
		} catch (NumberParseException $e) {
			return null;
		}
	}

}
