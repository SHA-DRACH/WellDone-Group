<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Records that hang off an employee — training, documents.
 *
 * The module permission says whether this role deals with such records at
 * all; the employee says whose. A department manager with training.view
 * reads their own department's training and nobody else's, because they
 * cannot view the other employees in the first place.
 */
abstract class EmployeeRecordPolicy extends ModulePolicy
{
    public function view(User $user, Model $model): bool
    {
        return $user->can($this->permission('view')) && $this->canSeeEmployee($user, $model);
    }

    public function update(User $user, Model $model): bool
    {
        return $user->can($this->permission('update')) && $this->canSeeEmployee($user, $model);
    }

    public function delete(User $user, Model $model): bool
    {
        return $user->can($this->permission('delete')) && $this->canSeeEmployee($user, $model);
    }

    protected function canSeeEmployee(User $user, Model $model): bool
    {
        $employee = $model->employee;

        return $employee !== null && $user->can('view', $employee);
    }
}
