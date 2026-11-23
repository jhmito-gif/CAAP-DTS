<?php

namespace App\Livewire\Concerns;

use App\Models\DocumentCategory;
use Illuminate\Validation\Rule;

/**
 * Each file attached to a record is given its document type (Memorandum,
 * Letter, Report…) as it is uploaded, so the Documents page can list every
 * file of one type. The types are the ones admins keep under Document
 * Categories.
 *
 * Types sit beside the uploads by position. Picking files replaces the whole
 * list, so the types are cleared with it; removing one file shifts the rest.
 */
trait PicksDocumentTypes
{
    /** Document type id for each pending upload, by its position in the list. */
    public array $uploadTypes = [];

    /**
     * A type for every file -- required whenever there are types to pick
     * from, so nobody is stuck if an admin has not set any up.
     *
     * @param  array<int, mixed>  $files
     */
    protected function uploadTypeRules(array $files): array
    {
        $rule = [
            DocumentCategory::exists() ? 'required' : 'nullable',
            'integer',
            Rule::exists('document_categories', 'id'),
        ];

        $rules = [];

        foreach (array_keys($files) as $index) {
            $rules["uploadTypes.{$index}"] = $rule;
        }

        return $rules;
    }

    /**
     * "document type for memo.pdf", so an error names the file it is about.
     *
     * @param  array<int, mixed>  $files
     */
    protected function uploadTypeAttributes(array $files): array
    {
        $attributes = [];

        foreach ($files as $index => $file) {
            $name = is_object($file) && method_exists($file, 'getClientOriginalName')
                ? $file->getClientOriginalName()
                : 'the file';

            $attributes["uploadTypes.{$index}"] = "document type for {$name}";
        }

        return $attributes;
    }

    protected function uploadTypeFor(int $index): ?int
    {
        $id = $this->uploadTypes[$index] ?? null;

        return filled($id) ? (int) $id : null;
    }

    /** Keep the types lined up with the files when one is taken out. */
    protected function forgetUploadType(int $index): void
    {
        $types = $this->uploadTypes;
        unset($types[$index]);

        // Re-index by position: anything after the removed file moves up one.
        ksort($types);
        $shifted = [];

        foreach ($types as $position => $type) {
            $shifted[$position > $index ? $position - 1 : $position] = $type;
        }

        $this->uploadTypes = $shifted;
    }
}
