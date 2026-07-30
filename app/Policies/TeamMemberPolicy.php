<?php

namespace App\Policies;

/**
 * Leadership profiles are part of the company-profile module, alongside
 * milestones, certificates, CSR initiatives and FAQs.
 */
class TeamMemberPolicy extends ModulePolicy
{
    protected function module(): string
    {
        return 'company';
    }
}
