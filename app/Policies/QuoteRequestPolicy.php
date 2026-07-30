<?php

namespace App\Policies;

use App\Models\QuoteRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class QuoteRequestPolicy extends ModulePolicy
{
    protected function module(): string
    {
        return 'quotes';
    }

    /** Quote requests arrive from the public site; nobody creates them here. */
    public function create(User $user): bool
    {
        return false;
    }

    public function assign(User $user, ?QuoteRequest $quote = null): bool
    {
        return $user->can('quotes.assign');
    }

    /** Downloading a requester's attached documents. */
    public function downloadFiles(User $user, QuoteRequest $quote): bool
    {
        return $user->can('quotes.download');
    }

    public function update(User $user, Model $model): bool
    {
        if (! $user->can('quotes.update')) {
            return false;
        }

        /** @var QuoteRequest $model */
        // A sales officer without the assign right may only work the leads
        // routed to them; anyone who can reassign can work any lead.
        if ($user->can('quotes.assign')) {
            return true;
        }

        return $model->assigned_to === null || $model->assigned_to === $user->id;
    }
}
