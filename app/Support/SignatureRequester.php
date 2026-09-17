<?php

namespace App\Support;

use App\Models\Attachment;
use App\Models\EsignLog;
use App\Models\RecordTagging;
use App\Models\SignatureRequest;
use App\Models\User;
use App\Notifications\SignatureRequested;

/**
 * Assigns a signatory to a document: creates the request, tags the signer so
 * they can open the record (including a confidential one), notifies them and
 * writes the e-sign log. Callers decide who may request signatures.
 */
class SignatureRequester
{
    /**
     * @param  string  $source  where the request was made, for the e-sign log
     * @return SignatureRequest|null  null when the signer was already assigned
     */
    public function request(Attachment $attachment, User $signer, User $requester, string $source, ?array $placements = null): ?SignatureRequest
    {
        // One box or several -- a signatory may have to sign more than one page.
        $boxes = $placements
            ? array_values(array_is_list($placements) ? $placements : [$placements])
            : [];

        $marked = $boxes ? [
            'placements' => $boxes,
            'placed_by' => $requester->name,
        ] : [];

        $signatureRequest = SignatureRequest::firstOrCreate(
            ['attachment_id' => $attachment->id, 'signer_id' => $signer->id],
            $marked + ['record_id' => $attachment->record_id, 'requested_by' => $requester->id],
        );

        if (! $signatureRequest->wasRecentlyCreated) {
            return null;
        }

        RecordTagging::firstOrCreate(
            ['record_id' => $attachment->record_id, 'user_id' => $signer->id],
            ['office' => $signer->office, 'tagged_by' => $requester->name],
        );

        $signer->notify(new SignatureRequested($signatureRequest, $requester->name));

        EsignLogger::log('request.created', EsignLog::SUCCESS, ['user' => $requester, 'request' => $signatureRequest], [
            'signer_id' => $signer->id,
            'signer_name' => $signer->name,
            'signer_office' => $signer->office,
            'source' => $source,
            'pages_marked' => $boxes ? collect($boxes)->pluck('page')->unique()->sort()->values()->all() : 'not marked',
        ]);

        return $signatureRequest;
    }
}
