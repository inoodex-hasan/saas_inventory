<?php

namespace App\Actions\Fortify;

use App\Models\Team;
use App\Models\User;
use App\Services\SmsOtpService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Jetstream\Jetstream;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        $smsOtpService = app(SmsOtpService::class);
        $normalizedPhone = isset($input['phone'])
            ? $smsOtpService->normalizePhone((string) $input['phone'])
            : '';

        $validationData = array_merge($input, [
            'phone' => $normalizedPhone,
        ]);

        Validator::make($validationData, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone' => ['required', 'string', 'regex:/^(8801[3-9]\d{8}|[1-9]\d{7,14})$/', 'unique:users,phone'],
            'password' => $this->passwordRules(),
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature() ? ['accepted', 'required'] : '',
        ], [
            'phone.regex' => 'Please enter a valid mobile number (e.g. 017XXXXXXXX).',
            'phone.unique' => 'This phone number is already registered with another account.',
        ])->validate();

        $user = DB::transaction(function () use ($input, $normalizedPhone) {
            return tap(User::create([
                'name' => $input['name'],
                'email' => $input['email'],
                'phone' => $normalizedPhone,
                'password' => Hash::make($input['password']),
            ]), function (User $user) {
                $this->createTeam($user);
            });
        });

        $smsOtpService->sendOtp($user, true);

        return $user;
    }

    /**
     * Create a personal team for the user.
     */
    protected function createTeam(User $user): void
    {
        $team = $user->ownedTeams()->save(Team::forceCreate([
            'user_id' => $user->id,
            'name' => explode(' ', $user->name, 2)[0]."'s Team",
            'personal_team' => true,
        ]));

        $user->switchTeam($team);

        \Database\Seeders\RolesAndPermissionsSeeder::seedRolesForTeam($team->id);
        setPermissionsTeamId($team->id);
        $user->assignRole('owner');
    }
}
