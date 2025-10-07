<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            // Evitar recrear si ya existe
            if (Schema::hasTable('nodes')) {
                Log::warning('Migration skipped: table "nodes" already exists.');
                return;
            }

            Schema::create('nodes', function (Blueprint $table) {
                // id autoincrement BIGINT PRIMARY KEY
                $table->id();
                // Relación padre-hijo (puede ser null)
                $table->unsignedBigInteger('parent')->nullable();

                $table->string('title')->nullable();

                // timestamps estándar de Laravel (created_at / updated_at)
                $table->timestampsTz(0); // UTC por defecto

                // Foreign key auto referencial
                $table->foreign('parent')
                      ->references('id')
                      ->on('nodes')
                      ->onDelete('restrict'); // No permite borrar si tiene hijos

                // Índices recomendados
                $table->index('parent');
                $table->index('title');
            });

            Log::info('Migration "create_nodes_table" executed successfully.');
        } catch (\Throwable $e) {
            Log::error('Error executing migration create_nodes_table: ' . $e->getMessage());
            // Rethrow para detener el proceso de migración
            throw $e;
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            if (Schema::hasTable('nodes')) {
                Schema::table('nodes', function (Blueprint $table) {
                    // Eliminar foreign key si existe
                    if (Schema::hasColumn('nodes', 'parent')) {
                        try {
                            $table->dropForeign(['parent']);
                        } catch (\Throwable $e) {
                            Log::warning('Foreign key drop failed: ' . $e->getMessage());
                        }
                    }
                });

                Schema::dropIfExists('nodes');
                Log::info('Table "nodes" dropped successfully.');
            } else {
                Log::warning('Down migration skipped: table "nodes" does not exist.');
            }
        } catch (\Throwable $e) {
            Log::error('Error reverting migration create_nodes_table: ' . $e->getMessage());
            throw $e;
        }
    }
};
