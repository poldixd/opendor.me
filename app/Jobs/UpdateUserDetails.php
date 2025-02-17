<?php

namespace App\Jobs;

use App\Models\User;
use Carbon\CarbonInterval;

class UpdateUserDetails extends GithubJob
{
    public function __construct(protected User $user)
    {
        parent::__construct();
        $this->timeout = CarbonInterval::minutes(5)->totalSeconds;
    }

    public function run(): void
    {
        $data = $this->user->github()->get("/users/{$this->user->name}")->json();

        $this->user->update([
            'full_name' => $data['name'],
            'description' => $data['bio'],
            'twitter' => $data['twitter_username'],
            'website' => $data['blog'],
            'location' => $data['location'],
        ]);
    }
}
