<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ApplicationEvidenceValidator
{
public function validate(Request $request, Collection $fields, bool $enforceRecordCount = true): void
{
    $byKey = $fields->keyBy('field_key');
    $value = function (string $key) use ($request, $byKey) {
        $field = $byKey->get($key);
        return $field ? $request->input('answers.' . $field->id) : null;
    };
    $errorKey = function (string $key) use ($byKey): ?string {
        $field = $byKey->get($key);
        return $field ? 'answers.' . $field->id : null;
    };
    $errors = [];
    $add = function (string $key, string $message) use (&$errors, $errorKey): void {
        if ($path = $errorKey($key)) {
            $errors[$path][] = $message;
        }
    };

    if (trim((string) $value('applied_through')) === 'Employee Referral' && trim((string) $value('referred_by')) === '') {
        $add('referred_by', 'Please enter the employee who referred you.');
    }

    $employmentStatus = trim((string) $value('current_employment_status'));
    $notice = trim((string) $value('notice_period'));
    if (in_array($employmentStatus, ['Employed','Self-Employed','Freelance / Project-Based'], true) && $notice === '') {
        $add('notice_period', 'Please select your notice period because you indicated that you are currently working.');
    }

    $workDeclaration = trim((string) $value('work_experience_declaration'));
    $requestedCount = (int) $request->input('employment_record_count', $workDeclaration === 'Yes' ? 1 : 0);
    $requestedCount = max(0, min(5, $requestedCount));

    $employmentKeysFor = fn (int $i) => [
        "company_{$i}", "company_address_{$i}", "position_held_{$i}",
        "employment_dates_{$i}", "employment_end_{$i}", "currently_employed_{$i}",
    ];

    if ($workDeclaration === 'Yes' && $requestedCount < 1) {
        $add('work_experience_declaration', 'Please complete at least one employment record because you selected Yes.');
    }

    for ($i = 1; $i <= 5; $i++) {
        $keys = $employmentKeysFor($i);
        $hasAny = collect($keys)->contains(fn ($key) => trim((string) $value($key)) !== '');
        $active = $enforceRecordCount
            ? ($workDeclaration === 'Yes' && $i <= max(1, $requestedCount))
            : ($hasAny || ($workDeclaration === 'Yes' && $i === 1));

        if ($workDeclaration === 'No' && $hasAny) {
            $add('work_experience_declaration', 'You selected No work experience, but an employment record contains information. Clear the record or select Yes.');
            continue;
        }

        if (!$active) {
            if ($hasAny) {
                $add('work_experience_declaration', 'An employment record contains information but is not active. Re-add the record or clear its fields.');
            }
            continue;
        }

        foreach ([
            "company_{$i}" => 'company name',
            "company_address_{$i}" => 'company address',
            "position_held_{$i}" => 'position held',
            "employment_dates_{$i}" => 'employment start date',
            "currently_employed_{$i}" => 'current-employment status',
        ] as $key => $label) {
            if (trim((string) $value($key)) === '') {
                $add($key, 'Please provide the ' . $label . ' for this employment record.');
            }
        }

        $current = trim((string) $value("currently_employed_{$i}"));
        $start = trim((string) $value("employment_dates_{$i}"));
        $endValue = trim((string) $value("employment_end_{$i}"));

        if ($current === 'No' && $endValue === '') {
            $add("employment_end_{$i}", 'Please provide the employment end date for a previous employer.');
        }
        if ($current === 'Yes' && $endValue !== '') {
            $add("employment_end_{$i}", 'Leave the end date blank when you are currently employed here.');
        }
        if ($start !== '' && $endValue !== '') {
            try {
                if (\Illuminate\Support\Carbon::parse($endValue)->lt(\Illuminate\Support\Carbon::parse($start))) {
                    $add("employment_end_{$i}", 'Employment end date cannot be earlier than the start date.');
                }
            } catch (\Throwable $exception) {
                // Base date validation reports malformed dates.
            }
        }
    }

    // Apply chronology to current and future date pairs by their stable field keys.
    // Restrict to date fields so arbitrary text questions are never parsed as dates.
    foreach ($fields->where('field_type', 'date') as $endField) {
        $key = (string) $endField->field_key;
        $candidates = [];
        if (str_contains($key, 'end')) {
            $candidates[] = str_replace('end', 'start', $key);
            $candidates[] = str_replace('employment_end_', 'employment_dates_', $key);
        }
        if (str_contains($key, 'to_date')) {
            $candidates[] = str_replace('to_date', 'from_date', $key);
        }
        foreach (array_unique($candidates) as $startKey) {
            $startField = $byKey->get($startKey);
            if ($startKey === $key || !$startField || $startField->field_type !== 'date') {
                continue;
            }
            $startValue = $value($startKey);
            $endValue = $value($key);
            if (filled($startValue) && filled($endValue) && strcmp((string) $endValue, (string) $startValue) < 0) {
                $add($key, $endField->label . ' cannot be earlier than ' . $startField->label . '.');
            }
        }
    }

    if ($errors !== []) {
        throw ValidationException::withMessages($errors);
    }
}

}
