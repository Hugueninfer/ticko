<?php

declare(strict_types=1);

namespace App\Http\Requests\Reports;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;

final class MetricsRequest extends FormRequest
{
    private const int DEFAULT_DAYS = 30;

    private const int MAX_DAYS = 366;

    /**
     * Authorized before validation, so validation errors never leak data to callers without access.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('viewMetrics') ?? false;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'from' => ['sometimes', 'date_format:Y-m-d'],
            'to' => ['sometimes', 'date_format:Y-m-d', 'after_or_equal:from', 'before_or_equal:today'],
        ];
    }

    public function to(): CarbonImmutable
    {
        return $this->filled('to') ? CarbonImmutable::parse($this->string('to')->toString()) : CarbonImmutable::today();
    }

    /**
     * The start of the period, clamped so a single request never spans more than a year.
     */
    public function from(): CarbonImmutable
    {
        $to = $this->to();
        $from = $this->filled('from')
            ? CarbonImmutable::parse($this->string('from')->toString())
            : $to->subDays(self::DEFAULT_DAYS - 1);

        return $from->max($to->subDays(self::MAX_DAYS - 1));
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function queryParameters(): array
    {
        return [
            'from' => ['description' => 'First day (Y-m-d). Defaults to 30 days before `to`; at most one year before it.', 'example' => '2026-09-13'],
            'to' => ['description' => 'Last day (Y-m-d), not in the future. Defaults to today.', 'example' => '2026-10-12'],
        ];
    }
}
