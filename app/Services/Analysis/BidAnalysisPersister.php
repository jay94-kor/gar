<?php

namespace App\Services\Analysis;

use App\Models\Bid;
use App\Models\BidAnalysis;
use App\Models\BidChecklist;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BidAnalysisPersister
{
    /**
     * @param  array<string, mixed>  $analysis
     */
    public function persist(Bid $bid, array $analysis, string $inputHash): BidAnalysis
    {
        return DB::transaction(function () use ($bid, $analysis, $inputHash): BidAnalysis {
            $version = ((int) $bid->analyses()->max('analysis_version')) + 1;

            $bid->analyses()->update(['is_current' => false]);

            $record = $bid->analyses()->create([
                'summary' => $this->formatSummary($analysis['summary'] ?? []),
                'special_conditions' => $analysis['special_conditions'] ?? [],
                'status' => $this->resolveStatus($analysis),
                'schema_version' => data_get($analysis, 'meta.schema_version', config('analysis.schema_version')),
                'prompt_version' => data_get($analysis, 'meta.prompt_version', config('analysis.prompt_version')),
                'model_name' => data_get($analysis, 'meta.model_name', 'openclaw'),
                'input_hash' => $inputHash,
                'analysis_version' => $version,
                'confidence' => data_get($analysis, 'meta.analysis_confidence'),
                'is_current' => true,
                'raw_payload' => $analysis,
            ]);

            $this->persistVehicles($bid, $analysis['vehicles'] ?? []);
            $this->persistContract($bid, $analysis);
            $this->persistInsurance($bid, $analysis['insurance'] ?? []);
            $this->persistQualification($bid, $analysis);
            $this->persistPerformance($bid, $analysis['performance_requirement'] ?? []);
            $this->persistChecklists($bid, $analysis['required_documents'] ?? []);

            $bid->update([
                'pipeline_status' => 'analyzed',
            ]);

            return $record;
        });
    }

    /**
     * @param  array<string, mixed>  $summary
     */
    protected function formatSummary(array $summary): ?string
    {
        $title = trim((string) ($summary['title'] ?? ''));
        $points = collect($summary['key_points'] ?? [])
            ->filter(fn ($point) => is_string($point) && trim($point) !== '')
            ->map(fn (string $point) => '- '.trim($point))
            ->implode("\n");

        $text = trim($title.($points !== '' ? "\n".$points : ''));

        return $text !== '' ? $text : null;
    }

    /**
     * @param  array<string, mixed>  $analysis
     */
    protected function resolveStatus(array $analysis): string
    {
        $confidence = (float) data_get($analysis, 'meta.analysis_confidence', 0);

        return $confidence < 0.6 ? 'needs_review' : 'validated';
    }

    /**
     * @param  array<int, array<string, mixed>>  $vehicles
     */
    protected function persistVehicles(Bid $bid, array $vehicles): void
    {
        $bid->vehicles()->delete();

        foreach (array_values($vehicles) as $index => $vehicle) {
            if (! is_array($vehicle)) {
                continue;
            }

            $bid->vehicles()->create([
                'seq' => $index + 1,
                'manufacturer' => $this->nullableString($vehicle['manufacturer'] ?? null),
                'model' => $this->nullableString($vehicle['model'] ?? null),
                'trim' => $this->nullableString($vehicle['trim'] ?? null),
                'fuel_type' => $this->nullableString($vehicle['fuel_type'] ?? null),
                'seats' => $this->toInteger($vehicle['seats'] ?? null),
                'quantity' => $this->toInteger($vehicle['quantity'] ?? 1) ?? 1,
                'year_condition' => $this->nullableString($vehicle['year_condition'] ?? null),
                'color_exterior' => $this->nullableString($vehicle['color_exterior'] ?? null),
                'color_interior' => $this->nullableString($vehicle['color_interior'] ?? null),
                'options' => $this->stringArray($vehicle['options'] ?? []),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $analysis
     */
    protected function persistContract(Bid $bid, array $analysis): void
    {
        $procurement = is_array($analysis['procurement'] ?? null) ? $analysis['procurement'] : [];
        $contract = is_array($analysis['contract'] ?? null) ? $analysis['contract'] : [];

        $bid->contract()->updateOrCreate(
            ['bid_id' => $bid->id],
            [
                'vehicle_condition' => $this->nullableString($procurement['vehicle_condition'] ?? null) ?? 'unspecified',
                'year_threshold' => $this->toInteger($procurement['year_threshold'] ?? null),
                'registration_requirement' => $this->toBoolean($procurement['registration_requirement'] ?? null),
                'funding_implication' => $this->nullableString($procurement['funding_implication'] ?? null) ?? 'unknown',
                'contract_months' => $this->toInteger($contract['period_months'] ?? null),
                'prepayment_rate' => $this->toDecimal($contract['prepayment_rate'] ?? null),
                'prepayment_amount' => $this->toInteger($contract['prepayment_amount'] ?? null),
                'deposit' => $this->toInteger($contract['deposit'] ?? null),
                'annual_mileage' => $this->toInteger($contract['annual_mileage_km'] ?? null),
                'residual_value_rate' => $this->toDecimal($contract['residual_value_rate'] ?? null),
                'opening_fee' => $this->toInteger($contract['opening_fee'] ?? null),
                'payment_method' => $this->nullableString($contract['payment_method'] ?? null),
                'delivery_deadline' => $this->nullableString($contract['delivery_deadline'] ?? null),
                'delivery_location' => $this->nullableString($contract['delivery_location'] ?? null),
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $insurance
     */
    protected function persistInsurance(Bid $bid, array $insurance): void
    {
        $bid->insurance()->updateOrCreate(
            ['bid_id' => $bid->id],
            [
                'liability_1' => $this->nullableString($insurance['liability_1'] ?? null),
                'liability_2' => $this->nullableString($insurance['liability_2'] ?? null),
                'property_damage' => $this->toInteger($insurance['property_damage'] ?? null),
                'own_vehicle' => $this->toBoolean($insurance['own_vehicle'] ?? null),
                'own_vehicle_deductible' => $this->toInteger($insurance['own_vehicle_deductible'] ?? null),
                'personal_injury' => $this->nullableString($insurance['personal_injury'] ?? null),
                'uninsured_motorist' => $this->nullableString($insurance['uninsured_motorist'] ?? null),
                'driver_age_min' => $this->toInteger($insurance['driver_age_min'] ?? null),
                'driver_scope' => $this->nullableString($insurance['driver_scope'] ?? null),
                'emergency_service' => $this->nullableString($insurance['emergency_service'] ?? null),
                'special_coverage' => $this->nullableString($insurance['special_coverage'] ?? null),
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $analysis
     */
    protected function persistQualification(Bid $bid, array $analysis): void
    {
        $qualification = is_array($analysis['qualification'] ?? null) ? $analysis['qualification'] : [];
        $evaluation = is_array($analysis['evaluation'] ?? null) ? $analysis['evaluation'] : [];
        $requiredDocuments = is_array($analysis['required_documents'] ?? null) ? $analysis['required_documents'] : [];

        $bizCode = $this->nullableString($qualification['biz_type_code'] ?? null);
        $bizName = $this->nullableString($qualification['biz_type_name'] ?? null);

        $bid->qualification()->updateOrCreate(
            ['bid_id' => $bid->id],
            [
                'biz_registration' => trim(implode(' ', array_filter([$bizCode, $bizName]))),
                'region_limit' => $this->normalizeRegionLimit($qualification['region_limit'] ?? null),
                'company_size_limit' => $this->nullableString($qualification['company_size_limit'] ?? null),
                'joint_contract' => $this->toBoolean($qualification['joint_contract_allowed'] ?? null),
                'subcontract' => $this->toBoolean($qualification['subcontract_allowed'] ?? null),
                'branch_requirement' => $this->nullableString($qualification['branch_requirement'] ?? null),
                'other_requirements' => $this->stringArray($qualification['other_requirements'] ?? []),
                'evaluation_method' => $this->nullableString($evaluation['method'] ?? null),
                'evaluation_standard' => $this->nullableString($evaluation['standard'] ?? null),
                'success_threshold' => $this->toDecimal($evaluation['success_threshold_rate'] ?? null),
                'passing_score' => $this->toInteger($evaluation['passing_score'] ?? null),
                'price_basis' => $this->nullableString($evaluation['price_basis'] ?? null),
                'preliminary_prices_count' => $this->toInteger($evaluation['preliminary_prices_count'] ?? null),
                'preliminary_prices_range' => $this->nullableString($evaluation['preliminary_prices_range'] ?? null),
                'required_docs' => $requiredDocuments,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $performance
     */
    protected function persistPerformance(Bid $bid, array $performance): void
    {
        $bid->performance()->updateOrCreate(
            ['bid_id' => $bid->id],
            [
                'performance_type' => $this->nullableString($performance['type'] ?? null),
                'performance_scope' => $this->nullableString($performance['scope'] ?? null),
                'performance_years' => $this->toInteger($performance['years'] ?? null),
                'min_amount' => $this->toInteger($performance['min_amount'] ?? null),
                'min_count' => $this->toInteger($performance['min_count'] ?? null),
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $requiredDocuments
     */
    protected function persistChecklists(Bid $bid, array $requiredDocuments): void
    {
        $map = [
            'bid_stage' => 'bid_stage',
            'screening_stage' => 'screening_stage',
            'contract_stage' => 'contract_stage',
        ];

        foreach ($map as $key => $stage) {
            $items = $this->stringArray($requiredDocuments[$key] ?? []);

            BidChecklist::query()->updateOrCreate(
                [
                    'bid_id' => $bid->id,
                    'stage' => $stage,
                ],
                [
                    'items' => $items,
                    'notes' => null,
                ],
            );
        }
    }

    protected function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    protected function toInteger(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $normalized = str_replace(',', '', (string) $value);

        return is_numeric($normalized) ? (int) round((float) $normalized) : null;
    }

    protected function toDecimal(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $normalized = str_replace(',', '', (string) $value);

        return is_numeric($normalized) ? round((float) $normalized, 3) : null;
    }

    protected function toBoolean(mixed $value): ?bool
    {
        return match ($value) {
            true, 1, '1', 'Y', 'y', 'true', 'TRUE' => true,
            false, 0, '0', 'N', 'n', 'false', 'FALSE' => false,
            default => null,
        };
    }

    /**
     * @return array<int, string>
     */
    protected function stringArray(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return collect($value)
            ->filter(fn ($item) => is_string($item) && trim($item) !== '')
            ->map(fn (string $item) => trim($item))
            ->values()
            ->all();
    }

    protected function normalizeRegionLimit(mixed $value): ?string
    {
        if (is_array($value)) {
            $value = collect($value)
                ->filter(fn ($item) => is_string($item) && trim($item) !== '')
                ->implode(', ');
        }

        return $this->nullableString($value);
    }
}
