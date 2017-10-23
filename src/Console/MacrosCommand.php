<?php

namespace Tutorigo\LaravelMacroHelper\Console;

use Illuminate\Console\Command;

class MacrosCommand extends Command
{
    /** @var string The name and signature of the console command */
    protected $signature = 'ide-helper:macros';

    /** @var string The console command description */
    protected $description = 'Generate an IDE helper file for Laravel macros';

    /** @var array Laravel classes with Macroable support */
    protected $classes = [
        \Illuminate\Database\Schema\Blueprint::class,
        \Illuminate\Support\Arr::class,
        \Illuminate\Support\Carbon::class,
        \Illuminate\Support\Collection::class,
        \Illuminate\Console\Scheduling\Event::class,
        \Illuminate\Database\Eloquent\FactoryBuilder::class,
        \Illuminate\Filesystem\Filesystem::class,
        \Illuminate\Mail\Mailer::class,
        \Illuminate\Foundation\Console\PresetCommand::class,
        \Illuminate\Routing\Redirector::class,
        \Illuminate\Database\Eloquent\Relations\Relation::class,
        \Illuminate\Cache\Repository::class,
        \Illuminate\Routing\ResponseFactory::class,
        \Illuminate\Routing\Route::class,
        \Illuminate\Routing\Router::class,
        \Illuminate\Validation\Rule::class,
        \Illuminate\Support\Str::class,
        \Illuminate\Foundation\Testing\TestResponse::class,
        \Illuminate\Translation\Translator::class,
        \Illuminate\Routing\UrlGenerator::class,
        \Illuminate\Database\Query\Builder::class,
        \Illuminate\Http\JsonResponse::class,
        \Illuminate\Http\RedirectResponse::class,
        \Illuminate\Auth\RequestGuard::class,
        \Illuminate\Http\Response::class,
        \Illuminate\Auth\SessionGuard::class,
        \Illuminate\Http\UploadedFile::class,
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $classes = array_merge($this->classes, config('ide-macros.classes', []));

        $fileName = config('ide-macros.filename');
        $file = fopen(base_path($fileName), 'w');
        fwrite($file, "<?php" . PHP_EOL);

        foreach ($classes as $class) {
            $reflection = new \ReflectionClass($class);
            if ($reflection->hasProperty('macros')) {
                $property = $reflection->getProperty('macros');
            } else {
                continue;
            }
            $property->setAccessible(true);
            $macros = $property->getValue();

            if (!$macros) {
                continue;
            }

            fwrite($file, "namespace " . $reflection->getNamespaceName() . " {" . PHP_EOL);
            fwrite($file, "    class " . $reflection->getShortName() . " {" . PHP_EOL);

            foreach ($macros as $name => $macro) {
                $reflection = new \ReflectionFunction($macro);
                if ($comment = $reflection->getDocComment()) {
                    fwrite($file, "        $comment" . PHP_EOL);
                }

                fwrite($file, "        public static function " . $name . "(");

                $index = 0;
                foreach ($reflection->getParameters() as $parameter) {
                    if ($index) {
                        fwrite($file, ", ");
                    }

                    fwrite($file, "$" . $parameter->getName());
                    if ($parameter->isOptional()) {
                        fwrite($file, " = " . var_export($parameter->getDefaultValue(), true));
                    }

                    $index++;
                }

                fwrite($file, ") { }" . PHP_EOL);
            }

            fwrite($file, "    }" . PHP_EOL . "}" . PHP_EOL);
        }

        fclose($file);

        $this->line($fileName . ' has been successfully generated.', 'info');
    }
}
