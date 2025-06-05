<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('subscribers', function (Blueprint $table) {
            $table->foreignId('managed_contact_id')
                ->nullable()
                ->after('email_list_id') // email_list_id の後に追加 (適切な位置に調整してください)
                ->constrained('managed_contacts') // managed_contacts テーブルの id を参照
                ->onUpdate('cascade') // ManagedContactのidが更新されたらこちらも更新
                ->onDelete('cascade'); // ManagedContactが削除されたらNULLにする (Subscriberレコードは残す)
            // もしManagedContact削除時にSubscriberも一緒に削除したい場合は 'cascade' を指定

            $table->dropColumn([
                'name',
                'company_name',
                'postal_code',
                'address',
                'phone_number',
                'fax_number',
                'url',
                'representative_name',
                'establishment_date',
                'industry',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscribers', function (Blueprint $table) {
            // 外部キー制約を削除する際は、カラム名ではなく、Laravelが生成する規約に則った制約名を指定するか、
            // カラム名を配列で渡す必要があります。
            // 例: $table->dropForeign(['managed_contact_id']);
            // もしくは、より安全な方法として制約名を明示的に指定します。
            // $foreignKeyName = Schema::getConnection()->getDoctrineSchemaManager()
            //     ->listTableForeignKeys('subscribers')['managed_contacts_managed_contact_id_foreign'] ?? null; // 環境によって名前が異なる可能性
            // if ($foreignKeyName) {
            //    $table->dropForeign($foreignKeyName);
            // }
            // 簡潔にするため、カラム名での削除を試みます。
            $table->dropForeign(['managed_contact_id']);
            $table->dropColumn('managed_contact_id');

            Schema::table('subscribers', function (Blueprint $table) {
                // ロールバック時にカラムを再追加
                $table->string('name')->nullable()->after('email');
                $table->string('company_name')->nullable()->after('name');
                $table->string('postal_code', 20)->nullable()->after('company_name');
                $table->string('address', 1000)->nullable()->after('postal_code');
                $table->string('phone_number', 30)->nullable()->after('address');
                $table->string('fax_number', 30)->nullable()->after('phone_number');
                $table->string('url')->nullable()->after('fax_number');
                $table->string('representative_name')->nullable()->after('url');
                $table->date('establishment_date')->nullable()->after('representative_name');
                $table->string('industry')->nullable()->after('establishment_date');
            });
        });
    }
};
