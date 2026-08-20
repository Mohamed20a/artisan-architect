<?php

declare(strict_types=1);

namespace ArtisanArchitect\Rules\Models;

use ArtisanArchitect\Analysis\AnalysisResult;
use ArtisanArchitect\Rules\Contracts\Rule;

class MissingFillableRule implements Rule
{
    public function name(): string
    {
        return 'missing-fillable';
    }

    public function description(): string
    {
        return 'Detect Eloquent models that have neither $fillable nor $guarded defined — mass-assignment protection may be missing.';
    }

    public function appliesToType(): ?string
    {
        return 'model';
    }

    public function check(AnalysisResult $result): array
    {
        if ($result->type !== 'model') {
            return [];
        }

        if (! (bool) config('artisan-architect.rules.models.require_fillable', true)) {
            return [];
        }

        /** @var bool $hasFillable */
        $hasFillable = $result->data['has_fillable'] ?? false;

        /** @var bool $hasGuarded */
        $hasGuarded = $result->data['has_guarded'] ?? false;

        if ($hasFillable || $hasGuarded) {
            return [];
        }

        return [
            [
                'rule'     => $this->name(),
                'severity' => 'error',
                'message'  => sprintf(
                    'Model %s has no $fillable or $guarded property. Define mass-assignment protection.',
                    $result->class,
                ),
                'file'     => $result->file,
                'line'     => 1,
            ],
        ];
    }
}
