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
            // Remove o registro anterior para evitar conflito de chave e criptografia antiga
            \App\Models\ApiConfiguracao::where('slug', 'superfrete')->delete();

            // Cria o registro novo com as credenciais limpas e sandbox desativado
            \App\Models\ApiConfiguracao::create([
                'slug' => 'superfrete',
                'nome' => 'SuperFrete',
                'tipo' => 'frete',
                'ativo' => true,
                'sandbox' => false,
                'fallback_ordem' => 1,
                'template_campos_json' => [
                    ['campo' => 'token', 'label' => 'Token de API', 'obrigatorio' => true, 'tipo' => 'password']
                ],
                'credenciais_json' => [
                    'token' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpYXQiOjE3ODM4MjEwMDUsInN1YiI6IjdSZEU4N1lDTU9WNkxJN3Y3N09KSk93UnFaTDIifQ.PTTy4dTooL-zFq_YbhBhqX5TCWl-YLSuiRJfQugUIQk'
                ]
            ]);
        } catch (\Exception $e) {
            //
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
