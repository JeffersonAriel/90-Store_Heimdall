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
        try {
            \App\Models\ApiConfiguracao::updateOrCreate(
                ['slug' => 'superfrete'],
                [
                    'nome' => 'SuperFrete',
                    'tipo' => 'frete',
                    'ativo' => true,
                    'sandbox' => false,
                    'credenciais_json' => [
                        'token' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpYXQiOjE3ODM4MjEwMDUsInN1YiI6IjdSZEU4N1lCMU9WNkxJN3Y3N09KSk93UnFaTDIifQ.PTTy4dTooL-zFq_YbhBhqX5TCWl-YLSuiRJfQugUIQk'
                    ]
                ]
            );
        } catch (\Exception $e) {
            // Ignora se der erro durante migrações concorrentes
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
