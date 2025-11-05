<?php
declare(strict_types=1);

if (!function_exists('app_log')) {
    /**
     * Write a structured line to the application error log.
     *
     * @param array<string, mixed> $context
     */
    function app_log(string $message, array $context = []): void
    {
        $entry = [
            'timestamp' => (new DateTimeImmutable())->format(DateTimeInterface::ATOM),
            'message' => $message,
        ];

        if ($context !== []) {
            $entry['context'] = $context;
        }

        $encoded = json_encode($entry, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($encoded === false) {
            $encoded = $message . ' ' . var_export($context, true);
        }

        error_log($encoded);
    }
}

if (!function_exists('app_log_exception')) {
    /**
     * Log an exception with its stack trace and optional context.
     *
     * @param array<string, mixed> $context
     */
    function app_log_exception(Throwable $exception, array $context = []): void
    {
        $context['exception'] = [
            'type' => get_class($exception),
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => explode("\n", $exception->getTraceAsString()),
        ];

        app_log('Unhandled exception', $context);
    }
}
