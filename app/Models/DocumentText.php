<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * What one document says, kept so it can be searched by its contents.
 */
class DocumentText extends Model
{
    protected $fillable = [
        'source_type',
        'source_id',
        'status',
        'method',
        'pages',
        'ocr_pages',
        'characters',
        'text',
        'sha256',
        'failure',
        'attempts',
        'read_at',
    ];

    protected $casts = [
        'pages' => 'integer',
        'ocr_pages' => 'integer',
        'characters' => 'integer',
        'attempts' => 'integer',
        'read_at' => 'datetime',
    ];

    /** The file this text came from. */
    public function source(): Attachment|Document|null
    {
        return $this->source_type === 'attachment'
            ? Attachment::find($this->source_id)
            : Document::find($this->source_id);
    }

    /**
     * Queue a file to be read, or leave the row alone if it is already read
     * and the file has not changed since.
     */
    public static function queue(string $type, int $id, ?string $sha256 = null): self
    {
        $row = static::firstOrNew(['source_type' => $type, 'source_id' => $id]);

        if ($row->exists && $row->status === 'done' && $sha256 && $row->sha256 === $sha256) {
            return $row;
        }

        $row->fill(['status' => 'pending', 'failure' => null])->save();

        return $row;
    }

    /**
     * A short piece of the text around the first match, for the search results.
     */
    public function snippet(string $needle, int $length = 180): string
    {
        $text = (string) $this->text;
        $needle = trim($needle);

        if ($needle === '' || $text === '') {
            return Str::limit($text, $length);
        }

        $at = mb_stripos($text, $needle);

        if ($at === false) {
            // Not a whole-phrase match: try the longest word in the search.
            $words = collect(preg_split('/\s+/', $needle))->sortByDesc(fn ($word) => mb_strlen($word));

            foreach ($words as $word) {
                $at = mb_stripos($text, $word);

                if ($at !== false) {
                    break;
                }
            }
        }

        if ($at === false) {
            return Str::limit($text, $length);
        }

        $start = max(0, $at - (int) ($length / 3));
        $piece = mb_substr($text, $start, $length);

        return ($start > 0 ? '…' : '') . trim($piece) . (mb_strlen($text) > $start + $length ? '…' : '');
    }
}
