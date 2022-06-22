<?php declare(strict_types=1);

namespace BenSampo\Enum;

use Doctrine\DBAL\Types\Type;
use BenSampo\Enum\Rules\Enum;
use BenSampo\Enum\Rules\EnumKey;
use BenSampo\Enum\Rules\EnumValue;
use Illuminate\Support\ServiceProvider;
use BenSampo\Enum\Commands\MakeEnumCommand;
use BenSampo\Enum\Commands\EnumAnnotateCommand;

class EnumServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     */
    public function boot(): void
    {
        $this->bootCommands();
        $this->bootValidationTranslation();
        $this->bootValidators();
        $this->bootDoctrineType();
    }

    /**
     * Boot the custom commands.
     */
    private function bootCommands(): void
    {
        $this->publishes([
            __DIR__.'/Commands/stubs' => $this->app->basePath('stubs')
        ], 'stubs');

        if ($this->app->runningInConsole()) {
            $this->commands([
                EnumAnnotateCommand::class,
                MakeEnumCommand::class,
            ]);
        }
    }

    /**
     * Boot the custom validators.
     */
    private function bootValidators(): void
    {
        $this->app['validator']->extend('enum_key', function ($attribute, $value, $parameters, $validator) {
            $enum = $parameters[0] ?? null;

            return (new EnumKey($enum))->passes($attribute, $value);
        }, __('laravelEnum::messages.enum_key'));

        $this->app['validator']->extend('enum_value', function ($attribute, $value, $parameters, $validator) {
            $enum = $parameters[0] ?? null;

            $strict = $parameters[1] ?? null;

            if (! $strict) {
                return (new EnumValue($enum))->passes($attribute, $value);
            }

            $strict = !! json_decode(strtolower($strict));

            return (new EnumValue($enum, $strict))->passes($attribute, $value);
        }, __('laravelEnum::messages.enum_value'));

        $this->app['validator']->extend('enum', function ($attribute, $value, $parameters, $validator) {
            $enum = $parameters[0] ?? null;

            return (new Enum($enum))->passes($attribute, $value);
        }, __('laravelEnum::messages.enum'));
    }

    /**
     * Boot the Doctrine type.
     */
    private function bootDoctrineType(): void
    {
        // Not included by default in Laravel
        if (class_exists('Doctrine\DBAL\Types\Type')) {
            if (! Type::hasType(EnumType::ENUM)) {
                Type::addType(EnumType::ENUM, EnumType::class);
            }
        }
    }

    private function bootValidationTranslation(): void
    {
        $this->publishes([
            __DIR__ . '/../lang' => lang_path('vendor/laravelEnum'),
        ], 'translations');

        $this->loadTranslationsFrom(__DIR__ . '/../lang/', 'laravelEnum');
    }
}
