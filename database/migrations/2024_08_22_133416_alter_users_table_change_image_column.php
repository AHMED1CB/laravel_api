<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterUsersTableChangeImageColumn extends Migration
{
    public function up()
    {
        DB::statement('ALTER TABLE users MODIFY image MEDIUMBLOB');
    }

    public function down()
    {
        DB::statement('ALTER TABLE users MODIFY image BLOB');
    }
}
