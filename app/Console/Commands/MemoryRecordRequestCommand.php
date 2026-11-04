<?php

namespace App\Console\Commands;

use App\Models\ProjectRequest;
use App\Services\ContentSanitizer;
use App\Services\JsonInputReader;
use App\Services\WorkflowRecorder;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

final class MemoryRecordRequestCommand extends Command
{
    protected $signature = 'memory:record-request {--stdin-json : Read a JSON object from standard input}';

    protected $description = 'Save a project request and its source, client, and emphasis details';

    public function handle(ContentSanitizer $sanitizer, WorkflowRecorder $recorder, JsonInputReader $inputReader): int
    {
        if (! $this->option('stdin-json')) {
            $this->error('Pass --stdin-json and provide title/body as JSON on standard input.');

            return self::INVALID;
        }

        $input = $inputReader->decode();
        if (! is_array($input)
            || ! is_string($input['title'] ?? null) || trim($input['title']) === ''
            || ! is_string($input['body'] ?? null) || trim($input['body']) === '') {
            $this->error('JSON must include non-empty title and body fields.');

            return self::INVALID;
        }

        $status = $input['status'] ?? 'draft';
        $budgetMin = $input['budget_min_amount'] ?? null;
        $budgetMax = $input['budget_max_amount'] ?? null;
        $currency = isset($input['currency']) && is_string($input['currency'])
            ? strtoupper(trim($input['currency']))
            : null;
        $deadlineDays = $input['client_deadline_days'] ?? null;
        $isFullTime = $input['is_full_time'] ?? null;
        $isFeatured = $input['is_featured'] ?? null;
        $clientIsNew = $input['client_is_new'] ?? null;
        $clientPaymentVerified = $input['client_payment_verified'] ?? null;
        $clientReviewsCount = $input['client_reviews_count'] ?? null;
        $applicantsCount = $input['applicants_count'] ?? null;
        $clientRating = $input['client_rating'] ?? null;
        $clientRatingScale = $input['client_rating_scale'] ?? null;
        $emphases = $input['emphases'] ?? [];
        $optionalStrings = [
            'platform' => [80, false],
            'source_url' => [2048, true],
            'client_location_text' => [160, true],
            'client_city' => [100, true],
            'client_region' => [100, true],
            'client_country' => [100, true],
            'client_deadline_text' => [160, true],
            'posted_time_text' => [120, true],
            'source_timezone' => [64, true],
        ];
        $validStatuses = ['draft', 'negotiating', 'agreed', 'submitted', 'closed', 'cancelled'];
        $validOptionalAmounts = static fn (mixed $value): bool => $value === null
            || ((is_int($value) || is_float($value) || is_string($value))
                && preg_match('/^\d+(?:\.\d{1,4})?$/D', (string) $value) === 1);
        $validOptionalIntegers = static fn (mixed $value): bool => $value === null
            || ((is_int($value) || is_string($value))
                && filter_var($value, FILTER_VALIDATE_INT) !== false
                && (int) $value >= 0);
        $validOptionalBooleans = static fn (mixed $value): bool => $value === null
            || in_array($value, [true, false, 0, 1, '0', '1'], true);
        $validOptionalRating = static fn (mixed $value): bool => $value === null
            || ((is_int($value) || is_float($value) || is_string($value))
                && preg_match('/^\d+(?:\.\d{1,3})?$/D', (string) $value) === 1
                && (float) $value <= 100);
        $validOptionalText = static function (mixed $value, int $max, bool $nullable): bool {
            return ($nullable && $value === null)
                || (is_string($value) && mb_strlen(trim($value)) <= $max);
        };
        $timezone = $input['source_timezone'] ?? config('app.timezone', 'UTC');
        $capturedAtInput = $input['captured_at'] ?? null;
        $postedAtInput = $input['posted_at'] ?? null;
        $capturedAt = $this->parseDateTime($capturedAtInput, is_string($timezone) ? $timezone : 'UTC');
        $postedAt = $this->parseDateTime($postedAtInput, is_string($timezone) ? $timezone : 'UTC');
        $postedTimeText = $input['posted_time_text'] ?? null;
        $hasValidTimezone = is_string($timezone) && $this->isValidTimezone($timezone);
        $capturedAtIsValid = $this->isTimestampInput($capturedAtInput, is_string($timezone) ? $timezone : 'UTC');
        $postedAtIsValid = $this->isTimestampInput($postedAtInput, is_string($timezone) ? $timezone : 'UTC');
        $validStrings = true;
        foreach ($optionalStrings as $key => [$max, $nullable]) {
            $value = $key === 'platform' ? ($input[$key] ?? 'other') : ($input[$key] ?? null);
            if (! $validOptionalText($value, $max, $nullable)
                || ($key === 'platform' && trim($value) === '')) {
                $validStrings = false;
                break;
            }
        }
        $sourceUrl = $input['source_url'] ?? null;
        $validSourceUrl = $sourceUrl === null
            || (is_string($sourceUrl)
                && filter_var($sourceUrl, FILTER_VALIDATE_URL) !== false
                && in_array(strtolower((string) parse_url($sourceUrl, PHP_URL_SCHEME)), ['http', 'https'], true));
        $validEmphases = is_array($emphases) && array_is_list($emphases) && count($emphases) <= 50;
        if ($validEmphases) {
            foreach ($emphases as $emphasis) {
                if (! is_array($emphasis)
                    || ! is_string($emphasis['title'] ?? null)
                    || trim($emphasis['title']) === ''
                    || mb_strlen(trim($emphasis['title'])) > 255
                    || ! is_string($emphasis['interpretation'] ?? null)
                    || trim($emphasis['interpretation']) === ''
                    || mb_strlen(trim($emphasis['interpretation'])) > 12000
                    || (isset($emphasis['source_excerpt'])
                        && (! is_string($emphasis['source_excerpt']) || mb_strlen($emphasis['source_excerpt']) > 12000))) {
                    $validEmphases = false;
                    break;
                }
            }
        }

        if (! in_array($status, $validStatuses, true)
            || ! $validOptionalAmounts($budgetMin)
            || ! $validOptionalAmounts($budgetMax)
            || ! $validOptionalIntegers($deadlineDays)
            || ! $validOptionalIntegers($clientReviewsCount)
            || ! $validOptionalIntegers($applicantsCount)
            || ! $validOptionalBooleans($isFullTime)
            || ! $validOptionalBooleans($isFeatured)
            || ! $validOptionalBooleans($clientIsNew)
            || ! $validOptionalBooleans($clientPaymentVerified)
            || ! $validOptionalRating($clientRating)
            || ! $validOptionalRating($clientRatingScale)
            || ($deadlineDays !== null && (int) $deadlineDays < 1)
            || ($budgetMin !== null && $budgetMax !== null && (float) $budgetMin > (float) $budgetMax)
            || ($clientRatingScale !== null && (float) $clientRatingScale <= 0)
            || ($clientRating !== null && $clientRatingScale !== null && (float) $clientRating > (float) $clientRatingScale)
            || ($currency !== null && ! preg_match('/^[A-Z]{3}$/D', $currency))
            || (($budgetMin !== null || $budgetMax !== null) && ! preg_match('/^[A-Z]{3}$/D', $currency ?? ''))
            || ! $validStrings || ! $validSourceUrl || ! $hasValidTimezone
            || ! $capturedAtIsValid || ! $postedAtIsValid || ! $validEmphases) {
            $this->error('Request details contain an invalid value. Use ISO-8601 dates, valid source URLs and time zones, nullable booleans, non-negative counts, and complete emphasis entries.');

            return self::INVALID;
        }

        $capturedAt ??= CarbonImmutable::now('UTC');
        $postedAtBasis = 'unknown';
        if ($postedAt !== null) {
            $postedAtBasis = 'source_absolute';
        } elseif (is_string($postedTimeText) && trim($postedTimeText) !== '') {
            $postedAt = $this->derivePostedAt($postedTimeText, $capturedAt);
            if ($postedAt !== null) {
                $postedAtBasis = 'derived_relative';
            }
        }

        $metadata = $sanitizer->redactMetadata(is_array($input['metadata'] ?? null) ? $input['metadata'] : []);
        $sanitizedEmphases = array_map(fn (array $emphasis): array => [
            'title' => $sanitizer->redact(trim($emphasis['title'])),
            'source_excerpt' => isset($emphasis['source_excerpt'])
                ? $sanitizer->redact(trim($emphasis['source_excerpt']))
                : null,
            'interpretation' => $sanitizer->redact(trim($emphasis['interpretation'])),
        ], $emphases);

        $request = DB::transaction(function () use ($input, $metadata, $status, $budgetMin, $budgetMax, $currency, $deadlineDays, $isFullTime, $isFeatured, $clientIsNew, $clientPaymentVerified, $clientReviewsCount, $applicantsCount, $clientRating, $clientRatingScale, $postedAt, $postedAtBasis, $capturedAt, $timezone, $sanitizedEmphases, $sanitizer, $recorder): ProjectRequest {
            $request = ProjectRequest::query()->create([
                'platform' => $sanitizer->redact(trim($input['platform'] ?? 'other')),
                'external_id' => isset($input['external_id']) ? $sanitizer->redact((string) $input['external_id']) : null,
                'title' => $sanitizer->redact(trim($input['title'])),
                'request_body' => $sanitizer->redact(trim($input['body'])),
                'status' => $status,
                'budget_min_amount' => $budgetMin,
                'budget_max_amount' => $budgetMax,
                'currency' => $currency,
                'client_deadline_days' => $deadlineDays,
                'source_url' => isset($input['source_url']) ? $sanitizer->redact(trim($input['source_url'])) : null,
                'client_location_text' => $this->sanitizedOptional($input, 'client_location_text', $sanitizer),
                'client_city' => $this->sanitizedOptional($input, 'client_city', $sanitizer),
                'client_region' => $this->sanitizedOptional($input, 'client_region', $sanitizer),
                'client_country' => $this->sanitizedOptional($input, 'client_country', $sanitizer),
                'client_deadline_text' => $this->sanitizedOptional($input, 'client_deadline_text', $sanitizer),
                'posted_at' => $postedAt,
                'posted_time_text' => $this->sanitizedOptional($input, 'posted_time_text', $sanitizer),
                'posted_at_basis' => $postedAtBasis,
                'captured_at' => $capturedAt,
                'source_timezone' => $timezone,
                'client_is_new' => $clientIsNew,
                'client_reviews_count' => $clientReviewsCount,
                'client_rating' => $clientRating,
                'client_rating_scale' => $clientRatingScale,
                'client_payment_verified' => $clientPaymentVerified,
                'applicants_count' => $applicantsCount,
                'is_featured' => $isFeatured,
                'is_full_time_known' => $isFullTime !== null,
                'is_full_time' => $isFullTime,
                'metadata' => $metadata !== [] ? $metadata : null,
            ]);

            foreach ($sanitizedEmphases as $index => $emphasis) {
                $request->emphases()->create([
                    ...$emphasis,
                    'position' => $index + 1,
                ]);
            }

            $recorder->event(
                null,
                $request,
                'request.recorded',
                'completed',
                'Project request and source details saved.',
                ['emphasis_count' => count($sanitizedEmphases), 'posted_at_basis' => $postedAtBasis],
                actor: 'agent',
            );

            return $request;
        });

        $this->info(json_encode([
            'id' => $request->id,
            'title' => $request->title,
            'posted_at' => $request->posted_at,
            'posted_at_basis' => $request->posted_at_basis,
            'emphasis_count' => count($sanitizedEmphases),
        ], JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    }

    private function parseDateTime(mixed $value, string $timezone): ?CarbonImmutable
    {
        if (! $this->isTimestampShape($value)) {
            return null;
        }

        try {
            return CarbonImmutable::parse($value, $timezone)->setTimezone('UTC');
        } catch (Throwable) {
            return null;
        }
    }

    private function isTimestampInput(mixed $value, string $timezone): bool
    {
        return $value === null || $this->parseDateTime($value, $timezone) !== null;
    }

    private function isTimestampShape(mixed $value): bool
    {
        return is_string($value)
            && preg_match('/^\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}(?::\d{2}(?:\.\d{1,6})?)?(?:Z|[+-]\d{2}:\d{2})?$/D', trim($value)) === 1;
    }

    private function isValidTimezone(string $timezone): bool
    {
        try {
            new \DateTimeZone($timezone);

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    private function derivePostedAt(string $sourceText, CarbonImmutable $capturedAt): ?CarbonImmutable
    {
        $sourceText = strtr($sourceText, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);

        if (preg_match('/(\d+)\s*(seconds?|secs?|ثانیه|minutes?|mins?|دقیقه|hours?|hrs?|ساعت|days?|روز|weeks?|هفته|months?|ماه|years?|سال)\s*(?:ago|before|پیش|قبل)/iu', $sourceText, $matches) !== 1) {
            return null;
        }

        $amount = (int) $matches[1];
        $unit = mb_strtolower($matches[2]);

        return match (true) {
            in_array($unit, ['second', 'seconds', 'sec', 'secs', 'ثانیه'], true) => $capturedAt->subSeconds($amount),
            in_array($unit, ['minute', 'minutes', 'min', 'mins', 'دقیقه'], true) => $capturedAt->subMinutes($amount),
            in_array($unit, ['hour', 'hours', 'hr', 'hrs', 'ساعت'], true) => $capturedAt->subHours($amount),
            in_array($unit, ['day', 'days', 'روز'], true) => $capturedAt->subDays($amount),
            in_array($unit, ['week', 'weeks', 'هفته'], true) => $capturedAt->subWeeks($amount),
            in_array($unit, ['month', 'months', 'ماه'], true) => $capturedAt->subMonths($amount),
            in_array($unit, ['year', 'years', 'سال'], true) => $capturedAt->subYears($amount),
            default => null,
        };
    }

    /** @param array<string, mixed> $input */
    private function sanitizedOptional(array $input, string $key, ContentSanitizer $sanitizer): ?string
    {
        return isset($input[$key]) ? $sanitizer->redact(trim((string) $input[$key])) : null;
    }
}
