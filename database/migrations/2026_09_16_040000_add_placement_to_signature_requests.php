<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Where the sending office wants the signature: the page and the box, in
     * PDF points with the origin at the bottom-left, as the signing screen and
     * the stamper use. Null means nobody marked it and the signature spot is
     * worked out from the document instead.
     */
    public function up(): void
    {
        Schema::table('signature_requests', function (Blueprint $table) {
            $table->unsignedSmallInteger('placement_page')->nullable()->after('signed_at');
            $table->float('placement_x')->nullable()->after('placement_page');
            $table->float('placement_y')->nullable()->after('placement_x');
            $table->float('placement_width')->nullable()->after('placement_y');
            $table->float('placement_height')->nullable()->after('placement_width');
            $table->string('placed_by')->nullable()->after('placement_height');
        });
    }

    public function down(): void
    {
        Schema::table('signature_requests', function (Blueprint $table) {
            $table->dropColumn([
                'placement_page',
                'placement_x',
                'placement_y',
                'placement_width',
                'placement_height',
                'placed_by',
            ]);
        });
    }
};
