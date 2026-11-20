<?php

namespace App\Policies;

use App\Models\DocumentReview;
use App\Models\User;
use App\Support\Permissions;

/**
 * Who sees a document sent for review, and who decides on it.
 *
 * Reviewers (the CEO and Managing Director) see every submission. A division
 * sees only its own division's — the same confinement as everything else in
 * the panel — and the person who submitted a document can always open it.
 */
class DocumentReviewPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::DOCUMENT_REVIEWS_REVIEW)
            || $user->can(Permissions::DOCUMENT_REVIEWS_SUBMIT);
    }

    public function view(User $user, DocumentReview $review): bool
    {
        if ($user->can(Permissions::DOCUMENT_REVIEWS_REVIEW)) {
            return true;
        }

        if ($review->submitted_by === $user->id) {
            return true;
        }

        return $user->can(Permissions::DOCUMENT_REVIEWS_SUBMIT)
            && $review->division_id !== null
            && $review->isVisibleTo($user);
    }

    public function create(User $user): bool
    {
        return $user->can(Permissions::DOCUMENT_REVIEWS_SUBMIT);
    }

    /** Record a decision. Reviewers only, and never on their own submission. */
    public function review(User $user, DocumentReview $review): bool
    {
        return $user->can(Permissions::DOCUMENT_REVIEWS_REVIEW)
            && $review->submitted_by !== $user->id;
    }
}
