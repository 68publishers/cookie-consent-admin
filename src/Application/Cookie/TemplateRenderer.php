<?php

declare(strict_types=1);

namespace App\Application\Cookie;

use ErrorException;
use Latte\Loaders\StringLoader;
use Latte\Sandbox\SecurityPolicy;
use Nette\Bridges\ApplicationLatte\LatteFactory;
use stdClass;
use Throwable;

final readonly class TemplateRenderer implements TemplateRendererInterface
{
    public function __construct(
        private LatteFactory $latteFactory,
    ) {}

    public function render(Template $template): string
    {
        $latte = $this->latteFactory->create();

        $latte->setLoader(new StringLoader([
            $template->projectId() => $template->template(),
        ]));

        if (true === $template->option(Template::OPTION_NO_CACHE, false)) {
            $latte->setTempDirectory(null);
        }

        $policy = SecurityPolicy::createSafePolicy();

        $policy->allowProperties(stdClass::class, SecurityPolicy::ALL);
        $latte->setPolicy($policy);
        $latte->setSandboxMode();

        return $this->catchErrors(function () use ($latte, $template) {
            return $latte->renderToString($template->projectId(), [
                'providers' => $template->arguments()->providers(),
                'cookies' => $template->arguments()->cookies(),
                'environment' => $template->arguments()->environment(),
            ]);
        });
    }

    /**
     * @throws CompileException
     */
    private function catchErrors(callable $cb): string
    {
        $previousErrorHandler = set_error_handler(static function (int $severity, string $message, string $file, int $line) {
            $errors = [
                E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
                E_USER_ERROR => 'E_USER_ERROR',
                E_NOTICE => 'E_NOTICE',
            ];

            if (isset($errors[$severity])) {
                throw CompileException::fromPrevious(new ErrorException($message, 0, $severity, $file, $line));
            }

            return false;
        });

        try {
            $result = $cb();
        } catch (CompileException $e) {
        } catch (Throwable $e) {
            $e = CompileException::fromPrevious($e);
        } finally {
            if (is_callable($previousErrorHandler)) {
                set_error_handler($previousErrorHandler);
            }

            if (isset($e)) {
                throw $e;
            }

            return $result ?? '';
        }
    }
}
