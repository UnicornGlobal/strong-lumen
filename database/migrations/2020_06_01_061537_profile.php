<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Profile extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('profile_pictures', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('_id')->unique();
            $table->unsignedBigInteger('user_id');

            $table->string('title');
            $table->string('mime');
            $table->string('file_url');
            $table->string('file_key');

            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('profile_picture_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('profile_pictures');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('profile_picture_id');
        });
    }
}
