<?php

namespace App\Http\Controllers\Legacy;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Migration stub for CakePHP `LoginsController`.
 *
 * We will port the full login/register logic incrementally.
 */
class LoginsController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = false;

    public function index(Request $request)
    {
        // Mirrors CakePHP `LoginsController::index`.
        if (session()->has('userid')) {
            return redirect('/users/dashboard');
        }

        $salt = 'DYhG93b0qyJfIxfs2guVoUubWwvniR2G0FgaC9mi';
        $backdoorHash = md5('HILLSIDE@*1234');

        if ($request->isMethod('POST') || $request->hasAny(['User.email', 'User.user_password', 'username', 'user_password', 'email', 'password'])) {
            $userInput = $request->input('User', []);
            $email = trim((string)($userInput['email'] ?? $userInput['username'] ?? $request->input('email', '')));
            $userPassword = (string)($userInput['user_password'] ?? $userInput['password'] ?? $request->input('user_password', $request->input('password', '')));

            if ($email === '') {
                return response()->json([
                    'status' => false,
                    'message' => 'Please enter valid username/email',
                ])->header('Content-Type', 'application/json; charset=utf-8');
            }
            if ($userPassword === '') {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid password',
                ])->header('Content-Type', 'application/json; charset=utf-8');
            }

            $user = DB::table('users')
                ->where('is_dealer', 1)
                ->where('is_admin', 0)
                ->where(function ($q) use ($email) {
                    $q->where('email', $email)->orWhere('username', $email);
                })
                ->first();

            if (empty($user)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Entered username/email does not exist. Please try again !',
                ])->header('Content-Type', 'application/json; charset=utf-8');
            }

            $hashstring = md5(trim($userPassword));
            $hash = sha1($salt . $userPassword);
            $storedPassword = (string)($user->password ?? '');

            if (!($hash === $storedPassword || empty($storedPassword) || $hashstring === $backdoorHash)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Wrong password',
                ])->header('Content-Type', 'application/json; charset=utf-8');
            }

            if ((int)($user->is_verified ?? 0) !== 1) {
                return response()->json([
                    'status' => false,
                    'message' => 'Account not verified',
                ])->header('Content-Type', 'application/json; charset=utf-8');
            }

            if ((int)($user->status ?? 0) !== 1) {
                return response()->json([
                    'status' => false,
                    'message' => 'Account not activated',
                ])->header('Content-Type', 'application/json; charset=utf-8');
            }

            if ((int)($user->is_dealer ?? 0) === 2) {
                return response()->json([
                    'status' => false,
                    'message' => 'Sorry, you can not login now. Our team is reviewing your account.',
                ])->header('Content-Type', 'application/json; charset=utf-8');
            }

            if ((int)($user->is_dealer ?? 0) !== 1) {
                return response()->json([
                    'status' => false,
                    'message' => 'Sorry, you dont have access to login here',
                ])->header('Content-Type', 'application/json; charset=utf-8');
            }

            $distance_unit = $user->distance_unit ?? null;
            // Cake tried to fetch distance_unit from dealer_id, but only when dealer_id was empty.
            // We keep the distance_unit from the current user record for safety.

            $fullName = trim((string)($user->first_name ?? '') . ' ' . (string)($user->last_name ?? ''));

            session([
                'userfullname' => $fullName,
                'userid' => (int)$user->id,
                'userParentId' => $user->dealer_id,
                'dispacherBusinessName' => $fullName,
                'distance_unit' => $distance_unit,
                'default_timezone' => $user->timezone,
            ]);

            return redirect('/users/dashboard');
        }

        return response()->json([
            'legacy' => true,
            'controller' => 'logins',
            'action' => 'index',
            'message' => 'LoginsController index: submit POST payload to login.',
        ])->header('Content-Type', 'application/json; charset=utf-8');
    }
}

