<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UserTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id'); // New
            $table->uuid('_id')->unique(); // New
            $table->uuid('api_key')->unique()->nullable(); // New
            $table->string('username')->unique();
            $table->string('password'); // At least they has it :D
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->unique();
            $table->boolean('is_banned')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->uuid('confirm_code');
            $table->dateTime('confirmed_at')->nullable();

            $table->unsignedBigInteger('created_by'); // fk to users
            $table->unsignedBigInteger('updated_by'); // fk to users, new

            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes(); // New
        });

        Schema::create('password_resets', function (Blueprint $table) {
            $table->string('email')->index();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_resets');
    }
}
