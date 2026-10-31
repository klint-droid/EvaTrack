<?php

namespace App\Domains\Authentication\Controllers;

use App\Domains\Authentication\Models\User;
use App\Domains\Notifications\Services\TextBeeService;
use App\Http\Controllers\Controller;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ForgotPasswordController extends Controller
{
    /**
     * Ensure the `email` column exists on `users` table in mysql_v2.
     */
    private function ensureEmailColumnExists(): void
    {
        try {
            if (!Schema::connection('mysql_v2')->hasColumn('users', 'email')) {
                Schema::connection('mysql_v2')->table('users', function (Blueprint $table) {
                    $table->string('email', 255)->nullable()->unique()->after('last_name');
                });
            }
        } catch (\Throwable $e) {
            Log::warning('[ForgotPassword] Could not check/add email column: ' . $e->getMessage());
        }
    }

    /**
     * Normalize Philippine phone number variants for flexible lookup.
     *
     * @return array<string>
     */
    private function phoneVariants(string $raw): array
    {
        $digits = preg_replace('/\D+/', '', $raw);
        if (!$digits) {
            return [$raw];
        }

        $variants = [$raw, $digits];

        // 09XXXXXXXXX -> +639XXXXXXXXX, 639XXXXXXXXX, 9XXXXXXXXX
        if (str_starts_with($digits, '09') && strlen($digits) === 11) {
            $core = substr($digits, 1); // 9XXXXXXXXX
            $variants[] = '+63' . $core;
            $variants[] = '63' . $core;
            $variants[] = $core;
        } elseif (str_starts_with($digits, '639') && strlen($digits) === 12) {
            $core = substr($digits, 2); // 9XXXXXXXXX
            $variants[] = '0' . $core;
            $variants[] = '+' . $digits;
            $variants[] = $core;
        } elseif (str_starts_with($digits, '9') && strlen($digits) === 10) {
            $variants[] = '0' . $digits;
            $variants[] = '+63' . $digits;
            $variants[] = '63' . $digits;
        }

        return array_values(array_unique($variants));
    }

    /**
     * Format phone number to E.164 (+639XXXXXXXXX) for TextBee SMS gateway.
     */
    private function toE164(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);
        if (str_starts_with($digits, '09') && strlen($digits) === 11) {
            return '+63' . substr($digits, 1);
        }
        if (str_starts_with($digits, '639') && strlen($digits) === 12) {
            return '+' . $digits;
        }
        if (str_starts_with($digits, '9') && strlen($digits) === 10) {
            return '+63' . $digits;
        }
        return str_starts_with(trim($phone), '+') ? trim($phone) : '+' . $digits;
    }

    /**
     * Mask mobile number for privacy (e.g. 0912 ••• •789).
     */
    private function maskMobile(?string $phone): ?string
    {
        if (!$phone || trim($phone) === '') {
            return null;
        }
        $clean = trim($phone);
        $len = strlen($clean);
        if ($len <= 6) {
            return substr($clean, 0, 2) . '•••';
        }
        return substr($clean, 0, 4) . ' •••• ' . substr($clean, -3);
    }

    /**
     * Mask email address for privacy (e.g. kl•••@gmail.com).
     */
    private function maskEmail(?string $email): ?string
    {
        if (!$email || !str_contains($email, '@')) {
            return null;
        }
        [$local, $domain] = explode('@', trim($email), 2);
        $visible = substr($local, 0, min(2, max(1, strlen($local) - 1)));
        return $visible . '••••@' . $domain;
    }

    /**
     * Find active user by User ID, Mobile Number, or Email.
     */
    private function findUserByIdentifier(string $identifier): ?User
    {
        $this->ensureEmailColumnExists();
        $trimmed = trim($identifier);

        $query = User::query()->where(function ($q) use ($trimmed) {
            $q->where('user_id', $trimmed)
              ->orWhereIn('contact_number', $this->phoneVariants($trimmed));

            if (Schema::connection('mysql_v2')->hasColumn('users', 'email')) {
                $q->orWhere('email', $trimmed);
            }
        });

        return $query->first();
    }

    /**
     * Step 1: Lookup user account and return available OTP channels (mobile / email).
     */
    public function lookup(Request $request): JsonResponse
    {
        $request->validate([
            'identifier' => 'required|string|max:255',
        ]);

        $user = $this->findUserByIdentifier($request->input('identifier'));

        if (!$user) {
            return response()->json([
                'message' => 'No active user account found matching that User ID, mobile number, or email.',
            ], 404);
        }

        $hasMobile = !empty(trim((string) $user->contact_number));
        $hasEmail  = !empty(trim((string) $user->email)) && filter_var($user->email, FILTER_VALIDATE_EMAIL);

        if (!$hasMobile && !$hasEmail) {
            return response()->json([
                'message' => 'This account does not have a registered mobile number or email address. Please contact your Super Admin to reset your password.',
            ], 422);
        }

        return response()->json([
            'user_id'       => $user->user_id,
            'name'          => $user->name ?: $user->user_id,
            'has_mobile'    => $hasMobile,
            'masked_mobile' => $hasMobile ? $this->maskMobile($user->contact_number) : null,
            'has_email'     => $hasEmail,
            'masked_email'  => $hasEmail ? $this->maskEmail($user->email) : null,
        ]);
    }

    /**
     * Step 2: Generate and send a 6-digit OTP via Mobile SMS or Email.
     */
    public function sendOtp(Request $request, TextBeeService $sms): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|string',
            'channel' => 'required|in:sms,email',
        ]);

        $this->ensureEmailColumnExists();
        $user = User::where('user_id', $request->input('user_id'))->first();

        if (!$user) {
            return response()->json(['message' => 'User account not found.'], 404);
        }

        $channel = $request->input('channel');

        if ($channel === 'sms' && empty(trim((string) $user->contact_number))) {
            return response()->json([
                'message' => 'No mobile number is registered for this account.',
            ], 422);
        }

        if ($channel === 'email' && (empty(trim((string) $user->email)) || !filter_var($user->email, FILTER_VALIDATE_EMAIL))) {
            return response()->json([
                'message' => 'No valid email address is registered for this account.',
            ], 422);
        }

        $otp = (string) random_int(100000, 999999);
        $cacheKey = "pwd_reset_otp:{$user->user_id}";

        Cache::store('file')->put($cacheKey, [
            'otp_hash' => Hash::make($otp),
            'channel'  => $channel,
            'attempts' => 0,
        ], now()->addMinutes(10));

        Log::info("[ForgotPassword] OTP generated for {$user->user_id} via {$channel}", [
            'user_id' => $user->user_id,
            'channel' => $channel,
            'otp'     => $otp,
        ]);

        if ($channel === 'sms') {
            $e164 = $this->toE164($user->contact_number);
            $smsMessage = "[EvaTrack] Your password reset OTP code is: {$otp}. It expires in 10 minutes. Do not share this code with anyone.";

            $result = $sms->send($e164, $smsMessage);
            if (!$result['success'] && $e164 !== $user->contact_number) {
                // Fallback attempt with raw contact number format
                $result = $sms->send($user->contact_number, $smsMessage);
            }

            if (!$result['success']) {
                return response()->json([
                    'message' => 'Unable to send SMS OTP at the moment (' . ($result['error'] ?? 'Gateway error') . '). Please try Email OTP if available or try again shortly.',
                ], 502);
            }

            return response()->json([
                'message'            => 'A 6-digit OTP code has been sent to your mobile number.',
                'channel'            => 'sms',
                'destination_masked' => $this->maskMobile($user->contact_number),
                'expires_in'         => 600,
            ]);
        }

        // Send via Email
        try {
            $name = e($user->name ?: $user->user_id);
            $html = "
                <div style=\"font-family: Inter, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; max-width: 480px; margin: 0 auto; padding: 28px; border: 1px solid #e2e8f0; border-radius: 16px; background: #ffffff; color: #0f172a;\">
                    <h2 style=\"margin: 0 0 8px; font-size: 20px; font-weight: 800; color: #1e3a8a;\">EvaTrack Password Reset</h2>
                    <p style=\"margin: 0 0 18px; font-size: 13px; color: #475569; line-height: 1.6;\">
                        Hello <strong>{$name}</strong> (<code>{$user->user_id}</code>),<br/>
                        Use the 6-digit verification code below to reset your EvaTrack account password:
                    </p>
                    <div style=\"background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 12px; padding: 18px; text-align: center; margin-bottom: 18px;\">
                        <span style=\"font-size: 28px; font-weight: 900; letter-spacing: 8px; color: #2563eb; font-family: monospace;\">{$otp}</span>
                    </div>
                    <p style=\"margin: 0; font-size: 12px; color: #64748b; line-height: 1.5;\">
                        This code is valid for <strong>10 minutes</strong>. If you did not request a password reset, you can safely ignore this email.
                    </p>
                </div>
            ";

            Mail::html($html, function ($message) use ($user) {
                $message->to($user->email, $user->name ?: $user->user_id)
                        ->subject('[EvaTrack] Your Password Reset OTP Code');
            });
        } catch (\Throwable $e) {
            Log::error('[ForgotPassword] Mail send failed', [
                'user_id' => $user->user_id,
                'error'   => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Unable to send Email OTP (' . $e->getMessage() . '). Please try Mobile SMS OTP if available or check mail configuration.',
            ], 502);
        }

        return response()->json([
            'message'            => 'A 6-digit OTP code has been sent to your email address.',
            'channel'            => 'email',
            'destination_masked' => $this->maskEmail($user->email),
            'expires_in'         => 600,
        ]);
    }

    /**
     * Step 3: Verify the 6-digit OTP and issue a short-lived password reset token.
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|string',
            'otp'     => 'required|digits:6',
        ]);

        $userId = $request->input('user_id');
        $cacheKey = "pwd_reset_otp:{$userId}";
        $cached = Cache::store('file')->get($cacheKey);

        if (!$cached) {
            return response()->json([
                'message' => 'OTP code has expired or was not found. Please request a new code.',
            ], 422);
        }

        if (($cached['attempts'] ?? 0) >= 5) {
            Cache::store('file')->forget($cacheKey);
            return response()->json([
                'message' => 'Too many invalid attempts. Please request a new OTP code.',
            ], 429);
        }

        if (!Hash::check($request->input('otp'), $cached['otp_hash'])) {
            $cached['attempts'] = ($cached['attempts'] ?? 0) + 1;
            Cache::store('file')->put($cacheKey, $cached, now()->addMinutes(10));

            return response()->json([
                'message' => 'Invalid OTP code. Please check the 6-digit code and try again.',
            ], 422);
        }

        // OTP verified! Clear OTP cache and issue a 10-minute reset token
        Cache::store('file')->forget($cacheKey);

        $resetToken = Str::random(48);
        Cache::store('file')->put("pwd_reset_token:{$userId}", Hash::make($resetToken), now()->addMinutes(10));

        return response()->json([
            'verified'    => true,
            'reset_token' => $resetToken,
            'message'     => 'OTP verified successfully. You may now set your new password.',
        ]);
    }

    /**
     * Step 4: Reset the user's password using the verified reset token.
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'user_id'     => 'required|string',
            'reset_token' => 'required|string',
            'password'    => 'required|string|min:8|confirmed',
        ]);

        $userId = $request->input('user_id');
        $tokenKey = "pwd_reset_token:{$userId}";
        $hashedToken = Cache::store('file')->get($tokenKey);

        if (!$hashedToken || !Hash::check($request->input('reset_token'), $hashedToken)) {
            return response()->json([
                'message' => 'Your password reset session has expired. Please start over.',
            ], 422);
        }

        $user = User::where('user_id', $userId)->first();
        if (!$user) {
            return response()->json(['message' => 'User account not found.'], 404);
        }

        $user->password = Hash::make($request->input('password'));
        $user->save();

        // Revoke existing active tokens for security
        $user->tokens()->delete();
        Cache::store('file')->forget($tokenKey);

        return response()->json([
            'message' => 'Your password has been reset successfully. You can now sign in with your new password.',
        ]);
    }
}

