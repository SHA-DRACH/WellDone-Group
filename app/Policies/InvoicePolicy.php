<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Database\Eloquent\Model;

/**
 * Invoices. Once money has been received against one, its lines are fixed —
 * changing them would leave a payment matched to a bill that no longer
 * says what the client was charged. Cancel and re-issue instead.
 */
class InvoicePolicy extends ModulePolicy
{
    protected function module(): string
    {
        return 'invoices';
    }

    public function update(User $user, Model $model): bool
    {
        /** @var Invoice $model */
        return parent::update($user, $model) && $model->isEditable();
    }

    public function delete(User $user, Model $model): bool
    {
        /** @var Invoice $model */
        return parent::delete($user, $model) && $model->status === 'draft';
    }

    public function recordPayment(User $user, Invoice $invoice): bool
    {
        return $user->can(Permissions::INVOICES_PAYMENTS)
            && in_array($invoice->status, Invoice::OPEN, true)
            && $this->writableAcrossDivisions($user, $invoice);
    }

    public function changeStatus(User $user, Invoice $invoice): bool
    {
        return $user->can(Permissions::INVOICES_UPDATE)
            && $invoice->status !== 'paid'
            && $this->writableAcrossDivisions($user, $invoice);
    }
}
