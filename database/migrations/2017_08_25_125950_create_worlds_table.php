<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWorldsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('worlds', function (Blueprint $table) {
            $table->uuid('id');
            $table->string('name');
            $table->json('data')->nullable();
            $table->timestamps();
            $table->primary('id');
        });

        Schema::table('grids', function (Blueprint $table) {
            $table->uuid('world_id')->nullable()->after('id');
            $table->foreign('world_id')->references('id')->on('world')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('grids', function (Blueprint $table) {
            $table->dropColumn('world_id');
        });

        Schema::dropIfExists('worlds');
    }
}
