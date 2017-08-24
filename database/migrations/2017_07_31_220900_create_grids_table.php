<?php

use App\Models\Grid;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGridsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('grids', function (Blueprint $table) {
            $table->uuid('id');
            $table->string('name');
            $table->integer('width'); // -1 for infinity
            $table->integer('height'); // -1 for infinity
            $table->json('data')->nullable();
            $table->integer('version')->unsigned()->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->primary('id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('grids');
    }
}
