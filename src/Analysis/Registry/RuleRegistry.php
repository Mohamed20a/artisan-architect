<?php

declare(strict_types=1);

namespace ArtisanArchitect\Analysis\Registry;

use ArtisanArchitect\Rules\Contracts\Rule;
use ArtisanArchitect\Rules\Controllers\ControllerBusinessLogicRule;
use ArtisanArchitect\Rules\Controllers\DirectDatabaseAccessRule;
use ArtisanArchitect\Rules\Controllers\FatControllerRule;
use ArtisanArchitect\Rules\Controllers\InlineValidationRule;
use ArtisanArchitect\Rules\Controllers\TooManyDependenciesRule;
use ArtisanArchitect\Rules\Models\FatModelRule;
use ArtisanArchitect\Rules\Models\ModelBusinessLogicRule;
use ArtisanArchitect\Rules\Requests\EmptyRulesRule;
use ArtisanArchitect\Rules\Requests\HardcodedAuthorizeRule;
use ArtisanArchitect\Rules\Requests\MissingRulesMethodRule;
use ArtisanArchitect\Rules\Resources\FatResourceRule;
use ArtisanArchitect\Rules\Routes\InvalidRouteActionRule;
use ArtisanArchitect\Rules\Routes\NoClosureInRoutesRule;

class RuleRegistry
{
    /** @var array<string, list<Rule>> */
    protected array $rules = [];

    public function register(string $type, Rule $rule): void
    {
        $this->rules[$type][] = $rule;
    }

    /**
     * @return list<Rule>
     */
    public function forType(string $type): array
    {
        return $this->rules[$type] ?? [];
    }

    /**
     * @return list<Rule>
     */
    public function getDefaultRules(): array
    {
        return [
            new FatControllerRule(),
            new DirectDatabaseAccessRule(),
            new InlineValidationRule(),
            new ControllerBusinessLogicRule(),
            new TooManyDependenciesRule(),

            new FatModelRule(),
            new ModelBusinessLogicRule(),

            new HardcodedAuthorizeRule(),
            new EmptyRulesRule(),
            new MissingRulesMethodRule(),

            new FatResourceRule(),

            new NoClosureInRoutesRule(),
            new InvalidRouteActionRule(),
        ];
    }

    /**
     * @return list<Rule>
     */
    public function all(): array
    {
        return array_merge(...array_values($this->rules));
    }

    public function has(string $type): bool
    {
        return ! empty($this->rules[$type]);
    }
}
