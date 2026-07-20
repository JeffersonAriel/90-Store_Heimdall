<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Recalcula as métricas de CRM para todos os clientes cadastrados
        $clientes = \App\Models\Cliente::all();
        foreach ($clientes as $c) {
            \App\Services\Crm\CrmKpiService::recalcularCliente($c->id);
        }
    }

    public function down()
    {
        // Nenhuma ação necessária no rollback
    }
};
