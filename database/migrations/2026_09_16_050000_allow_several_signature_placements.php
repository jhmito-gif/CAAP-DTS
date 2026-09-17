<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A signatory may have to sign several pages of the same document, so the
     * single marked box becomes a list of boxes. Signatures keep the same list,
     * recording every spot that one signing act stamped.
     */
    public function up(): void
    {
        Schema::table('signature_requests', function (Blueprint $table) {
            $table->json('placements')->nullable()->after('signed_at');
        });

        Schema::table('signatures', function (Blueprint $table) {
            $table->json('placements')->nullable()->after('height');
        });

        // Carry across anything already marked as a single box.
        DB::table('signature_requests')
            ->whereNotNull('placement_page')
            ->orderBy('id')
            ->each(function ($request) {
                DB::table('signature_requests')->where('id', $request->id)->update([
                    'placements' => json_encode([[
                        'page' => (int) $request->placement_page,
                        'x' => (float) $request->placement_x,
                        'y' => (float) $request->placement_y,
                        'width' => (float) $request->placement_width,
                        'height' => (float) $request->placement_height,
                    ]]),
                ]);
            });

        Schema::table('signature_requests', function (Blueprint $table) {
            $table->dropColumn([
                'placement_page',
                'placement_x',
                'placement_y',
                'placement_width',
                'placement_height',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('signature_requests', function (Blueprint $table) {
            $table->unsignedSmallInteger('placement_page')->nullable();
            $table->float('placement_x')->nullable();
            $table->float('placement_y')->nullable();
            $table->float('placement_width')->nullable();
            $table->float('placement_height')->nullable();
        });

        DB::table('signature_requests')
            ->whereNotNull('placements')
            ->orderBy('id')
            ->each(function ($request) {
                $first = json_decode((string) $request->placements, true)[0] ?? null;

                if ($first) {
                    DB::table('signature_requests')->where('id', $request->id)->update([
                        'placement_page' => $first['page'],
                        'placement_x' => $first['x'],
                        'placement_y' => $first['y'],
                        'placement_width' => $first['width'],
                        'placement_height' => $first['height'],
                    ]);
                }
            });

        Schema::table('signature_requests', function (Blueprint $table) {
            $table->dropColumn('placements');
        });

        Schema::table('signatures', function (Blueprint $table) {
            $table->dropColumn('placements');
        });
    }
};
