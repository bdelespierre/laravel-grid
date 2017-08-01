<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCellsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cells', function (Blueprint $table) {
            $table->uuid('id');
            $table->integer('map_id')->unsigned();
            $table->integer('x')->unsigned();
            $table->integer('y')->unsigned();
            $table->json('data')->nullable();
            $table->integer('version')->unsigned()->default(0);
            $table->timestamps();
            $table->primary('id');
            $table->foreign('map_id')->references('id')->on('map')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cells');
    }
}
