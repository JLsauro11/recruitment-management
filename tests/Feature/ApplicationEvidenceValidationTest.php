<?php

namespace Tests\Feature;

use App\Models\FormField;
use App\Services\ApplicationEvidenceValidator;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ApplicationEvidenceValidationTest extends TestCase
{
    private function fields(array $keys)
    {
        return collect($keys)->map(function ($key, $index) {
            $field = new FormField(['field_key' => $key, 'field_type' => 'date', 'label' => $key]);
            $field->id = $index + 1;
            return $field;
        });
    }

    public function test_dynamic_date_pair_rejects_reverse_dates(): void
    {
        $this->expectException(ValidationException::class);
        app(ApplicationEvidenceValidator::class)->validate(
            Request::create('/', 'POST', ['answers' => [1 => '2026-09-09', 2 => '2026-09-08']]),
            $this->fields(['training_start_date', 'training_end_date']), false
        );
    }

    public function test_dynamic_date_pair_accepts_equal_dates(): void
    {
        app(ApplicationEvidenceValidator::class)->validate(
            Request::create('/', 'POST', ['answers' => [1 => '2026-09-09', 2 => '2026-09-09']]),
            $this->fields(['training_start_date', 'training_end_date']), false
        );
        $this->assertTrue(true);
    }

    public function test_unrelated_date_fields_are_not_assumed_to_be_a_pair(): void
    {
        app(ApplicationEvidenceValidator::class)->validate(
            Request::create('/', 'POST', ['answers' => [1 => '2026-09-09', 2 => '2020-01-01']]),
            $this->fields(['available_date', 'graduation_date']), false
        );
        $this->assertTrue(true);
    }
}
