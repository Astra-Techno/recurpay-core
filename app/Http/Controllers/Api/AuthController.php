<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AuthController extends Controller
{
	public function login(Request $request)
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
}
