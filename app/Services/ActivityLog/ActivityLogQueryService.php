<?php

namespace App\Services\ActivityLog;

use App\Models\StaffMember;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\Activitylog\Models\Activity;

class ActivityLogQueryService
{
    /**
     * @param  array{
     *     q?: ?string,
     *     subject_q?: ?string,
     *     causer_q?: ?string,
     *     event?: ?string,
     *     subject_type?: ?string,
     *     date_from?: ?string,
     *     date_to?: ?string
     * }  $filters
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = Activity::query()
            ->with(['causer'])
            ->latest();

        if (! empty($filters['q'])) {
            $term = $this->likeTerm($filters['q']);
            $query->where(function ($builder) use ($term) {
                $builder->where('description', 'like', $term)
                    ->orWhere('properties', 'like', $term);
            });
        }

        if (! empty($filters['subject_q'])) {
            $this->applySubjectNameFilter($query, $filters['subject_q']);
        }

        if (! empty($filters['causer_q'])) {
            $term = $this->likeTerm($filters['causer_q']);
            $query->whereHasMorph('causer', [User::class], function ($builder) use ($term) {
                $builder->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term);
            });
        }

        if (! empty($filters['event'])) {
            $query->where('event', $filters['event']);
        }

        if (! empty($filters['subject_type'])) {
            $query->where('subject_type', $filters['subject_type']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->paginate(40)->withQueryString();
    }

    private function applySubjectNameFilter($query, string $value): void
    {
        $term = $this->likeTerm($value);

        $userIds = User::withTrashed()
            ->where(function ($builder) use ($term) {
                $builder->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term);
            })
            ->pluck('id');

        $staffIds = StaffMember::withTrashed()
            ->where(function ($builder) use ($term) {
                $builder->where('full_name_en', 'like', $term)
                    ->orWhere('full_name_ku', 'like', $term)
                    ->orWhere('email', 'like', $term);
            })
            ->pluck('id');

        $query->where(function ($builder) use ($userIds, $staffIds) {
            $matched = false;

            if ($userIds->isNotEmpty()) {
                $matched = true;
                $builder->orWhere(function ($nested) use ($userIds) {
                    $nested->where('subject_type', User::class)
                        ->whereIn('subject_id', $userIds);
                });
            }

            if ($staffIds->isNotEmpty()) {
                $matched = true;
                $builder->orWhere(function ($nested) use ($staffIds) {
                    $nested->where('subject_type', StaffMember::class)
                        ->whereIn('subject_id', $staffIds);
                });
            }

            if (! $matched) {
                $builder->whereRaw('0 = 1');
            }
        });
    }

    private function likeTerm(string $value): string
    {
        $escaped = str_replace(['%', '_'], ['\\%', '\\_'], trim($value));

        return '%' . $escaped . '%';
    }
}
