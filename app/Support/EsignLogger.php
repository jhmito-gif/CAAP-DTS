<?php

namespace App\Support;

use App\Models\Attachment;
use App\Models\EsignLog;
use App\Models\Record;
use App\Models\Signature;
use App\Models\SignatureRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Writes the e-sign audit trail: a row in esign_logs (browsable by admins)
 * and the same entry in storage/logs/esign.log.
 *
 * Never pass secrets (passwords, authenticator codes, access tokens) in the
 * context. Logging must not break the action being logged, so a failure to
 * write is reported and swallowed.
 */
class EsignLogger
{
    /**
     * @param  array{user?: ?User, record?: ?Record, attachment?: ?Attachment, request?: ?SignatureRequest, signature?: ?Signature}  $subjects
     * @param  array<string, mixed>  $context
     */
    public static function log(string $event, string $outcome, array $subjects = [], array $context = []): ?EsignLog
    {
        $user = array_key_exists('user', $subjects) ? $subjects['user'] : Auth::user();
        $signature = $subjects['signature'] ?? null;
        $signatureRequest = $subjects['request'] ?? $signature?->signatureRequest;
        $attachment = $subjects['attachment'] ?? $signatureRequest?->attachment ?? $signature?->attachment;
        $record = $subjects['record'] ?? $signatureRequest?->record ?? $attachment?->record;

        $entry = [
            'event' => $event,
            'outcome' => $outcome,
            'user_id' => $user?->id,
            'user_name' => $user?->name,
            'user_office' => $user?->office,
            'record_id' => $record?->id,
            'record_reference' => $record?->reference,
            'attachment_id' => $attachment?->id,
            'signature_request_id' => $signatureRequest?->id,
            'signature_id' => $signature?->id,
            'ip_address' => request()->ip(),
            'user_agent' => Str::limit((string) request()->userAgent(), 500, '') ?: null,
            'context' => array_filter($context + ['document' => $attachment?->original_name], fn ($value) => $value !== null) ?: null,
        ];

        try {
            Log::channel('esign')->log($outcome === EsignLog::FAILURE ? 'warning' : 'info', $event, $entry);
        } catch (Throwable $exception) {
            report($exception);
        }

        try {
            return EsignLog::create($entry);
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }
    }
}
