<?php

namespace App\Policies;

use App\Models\JobApplication;
use App\Models\User;

class JobApplicationPolicy extends ModulePolicy
{
    protected function module(): string
    {
        return 'applications';
    }

    /** Applications arrive from the careers portal. */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Résumés are personal data held on the private disk — downloading is
     * gated separately from simply viewing the application record.
     */
    public function downloadResume(User $user, JobApplication $application): bool
    {
        return $user->can('applications.download');
    }
}
