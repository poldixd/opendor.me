<?php

namespace App\Http\Controllers\Auth;

use App\Jobs\LoadUserRepositories;
use App\Jobs\SyncUserOrganizations;
use App\Jobs\UpdateUserDetails;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Two\GithubProvider;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class GithubController
{
    public function __invoke(): Response
    {
        $githubUser = $this->socialite()->user();

        $data = [
            'email' => $githubUser->getEmail(),
            'name' => $githubUser->getNickname(),
            'full_name' => $githubUser->getName(),
            'github_access_token' => $githubUser->token,
        ];

        $user = User::updateOrCreate(['id' => $githubUser->getId()], $data);

        abort_if($user->isBlocked(), Response::HTTP_FORBIDDEN);

        if ($user->wasRecentlyCreated) {
            event(new Registered($user));
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        UpdateUserDetails::dispatch($user);
        SyncUserOrganizations::dispatch($user);
        LoadUserRepositories::dispatch($user);

        Auth::login($user);

        return redirect()->intended(
            route('app.contributions')
        );
    }

    public function redirect(): RedirectResponse
    {
        return $this->socialite()
            ->redirectUrl(route('auth.github.callback'))
            ->redirect();
    }

    protected function socialite(): GithubProvider
    {
        return Socialite::driver('github');
    }
}
