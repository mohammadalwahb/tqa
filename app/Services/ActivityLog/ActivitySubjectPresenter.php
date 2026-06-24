<?php

namespace App\Services\ActivityLog;

use App\Models\College;
use App\Models\Committee;
use App\Models\Department;
use App\Models\Evaluation;
use App\Models\StaffMember;
use App\Models\User;
use App\Support\LocaleHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;

class ActivitySubjectPresenter
{
    /**
     * @return array<string, string>
     */
    public static function subjectTypeOptions(): array
    {
        return [
            User::class         => __('activity_log.subject_user'),
            StaffMember::class  => __('activity_log.subject_staff'),
            College::class      => __('activity_log.subject_college'),
            Department::class   => __('activity_log.subject_department'),
            Committee::class    => __('activity_log.subject_committee'),
            Evaluation::class   => __('activity_log.subject_evaluation'),
        ];
    }

    /**
     * @param  Collection<int, Activity>  $activities
     */
    public function enrich(Collection $activities): void
    {
        $loaded = $this->loadSubjectsByType($activities);

        $activities->each(function (Activity $activity) use ($loaded) {
            $activity->setAttribute('subject_label', $this->label($activity, $loaded));
        });
    }

    /**
     * @param  array<string, Collection<int, Model>>  $loaded
     */
    public function label(Activity $activity, array $loaded = []): string
    {
        if (! $activity->subject_type || ! $activity->subject_id) {
            return '—';
        }

        $subject = $loaded[$activity->subject_type][$activity->subject_id] ?? null;
        if ($subject instanceof Model) {
            return $this->labelForModel($subject);
        }

        $fromProperties = $this->labelFromProperties($activity);
        if ($fromProperties !== null && $fromProperties !== '') {
            return $fromProperties;
        }

        $type = class_basename($activity->subject_type);

        return "{$type} #{$activity->subject_id}";
    }

    /**
     * @param  Collection<int, Activity>  $activities
     * @return array<string, Collection<int, Model>>
     */
    private function loadSubjectsByType(Collection $activities): array
    {
        $loaded = [];

        foreach ($activities->groupBy('subject_type') as $type => $items) {
            if (! is_string($type) || ! class_exists($type)) {
                continue;
            }

            $ids = $items->pluck('subject_id')->filter()->unique()->values();
            if ($ids->isEmpty()) {
                continue;
            }

            $query = $type::query();
            if (in_array(SoftDeletes::class, class_uses_recursive($type), true)) {
                $query->withTrashed();
            }

            if ($type === Evaluation::class) {
                $query->with(['evaluatee' => fn ($q) => $q->withTrashed()]);
            } elseif ($type === Committee::class) {
                $query->with(['department', 'college']);
            }

            $loaded[$type] = $query->whereIn('id', $ids)->get()->keyBy('id');
        }

        return $loaded;
    }

    private function labelForModel(Model $model): string
    {
        return match (true) {
            $model instanceof User => $model->name ?: ($model->email ?: "User #{$model->id}"),
            $model instanceof StaffMember => LocaleHelper::staffDisplayName($model) ?: ($model->email ?: "Staff #{$model->id}"),
            $model instanceof College => LocaleHelper::collegeDisplayName($model) ?: "College #{$model->id}",
            $model instanceof Department => LocaleHelper::departmentDisplayName($model) ?: "Department #{$model->id}",
            $model instanceof Committee => $this->committeeLabel($model),
            $model instanceof Evaluation => $this->evaluationLabel($model),
            default => class_basename($model) . ' #' . $model->getKey(),
        };
    }

    private function committeeLabel(Committee $committee): string
    {
        if ($committee->name) {
            return $committee->name;
        }

        $department = LocaleHelper::departmentDisplayName($committee->department);
        if ($department !== '') {
            return $department;
        }

        $college = LocaleHelper::collegeDisplayName($committee->college);

        return $college !== '' ? $college : "Committee #{$committee->id}";
    }

    private function evaluationLabel(Evaluation $evaluation): string
    {
        if ($evaluation->evaluatee) {
            $name = LocaleHelper::staffDisplayName($evaluation->evaluatee);

            return $name !== '' ? $name : "Evaluation #{$evaluation->id}";
        }

        return "Evaluation #{$evaluation->id}";
    }

    private function labelFromProperties(Activity $activity): ?string
    {
        $properties = $activity->properties instanceof Collection
            ? $activity->properties->all()
            : (array) $activity->properties;

        $attributes = (array) ($properties['attributes'] ?? []);
        $old = (array) ($properties['old'] ?? []);
        $merged = array_merge($old, $attributes);

        return match (class_basename((string) $activity->subject_type)) {
            'User' => $merged['name'] ?? $merged['email'] ?? null,
            'StaffMember' => $merged['full_name_en'] ?? $merged['full_name_ku'] ?? $merged['email'] ?? null,
            'College' => $merged['name_en'] ?? $merged['name_ku'] ?? null,
            'Department' => $merged['name_en'] ?? $merged['name_ku'] ?? null,
            default => null,
        };
    }
}
