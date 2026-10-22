<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalogo_produto_estados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalogo_produto_id')
                ->unique()
                ->constrained('catalogo_produtos')
                ->cascadeOnDelete();
            $table->decimal('custo_observado', 14, 4)->nullable();
            $table->decimal('preco_venda_observado', 14, 4)->nullable();
            $table->decimal('custo_base_seguro', 14, 4)->nullable();
            $table->boolean('promocao_detectada')->default(false);
            $table->integer('estoque_observado')->nullable();
            $table->integer('quantidade_promocional_disponivel')->nullable();
            $table->string('promocao_inicio', 100)->nullable();
            $table->string('promocao_fim', 100)->nullable();
            $table->text('promocao_texto')->nullable();
            $table->timestamp('primeira_coleta_em')->nullable();
            $table->timestamp('ultima_coleta_em')->nullable();
            $table->timestamps();
        });

        Schema::create('catalogo_produto_precos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalogo_produto_id')
                ->constrained('catalogo_produtos')
                ->cascadeOnDelete();
            $table->string('chave_historico', 64);
            $table->decimal('custo_observado', 14, 4)->nullable();
            $table->decimal('preco_venda_observado', 14, 4)->nullable();
            $table->decimal('custo_base_seguro', 14, 4)->nullable();
            $table->boolean('promocao_detectada')->default(false);
            $table->integer('estoque_no_momento')->nullable();
            $table->integer('quantidade_promocional_disponivel')->nullable();
            $table->string('promocao_inicio', 100)->nullable();
            $table->string('promocao_fim', 100)->nullable();
            $table->text('promocao_texto')->nullable();
            $table->timestamp('coletado_em')->nullable();
            $table->timestamps();

            $table->unique(
                ['catalogo_produto_id', 'chave_historico'],
                'uq_catalogo_produto_precos_chave'
            );
        });

        Schema::create('catalogo_produto_aplicacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalogo_produto_id')
                ->constrained('catalogo_produtos')
                ->cascadeOnDelete();
            $table->foreignId('catalogo_montadora_id')
                ->nullable()
                ->constrained('catalogo_montadoras')
                ->nullOnDelete();
            $table->foreignId('catalogo_veiculo_id')
                ->nullable()
                ->constrained('catalogo_veiculos')
                ->nullOnDelete();
            $table->string('chave_aplicacao', 64);
            $table->string('montadora_raw', 191)->nullable();
            $table->string('veiculo_raw', 191)->nullable();
            $table->string('mes_ano', 100)->nullable();
            $table->string('modelo', 191)->nullable();
            $table->string('motor', 191)->nullable();
            $table->string('comp', 100)->nullable();
            $table->string('valvulas', 100)->nullable();
            $table->string('cilindros', 100)->nullable();
            $table->string('combustivel', 191)->nullable();
            $table->text('complemento')->nullable();
            $table->boolean('ativa')->default(true);
            $table->timestamp('primeira_coleta_em')->nullable();
            $table->timestamp('ultima_confirmacao_em')->nullable();
            $table->timestamps();

            $table->unique(
                ['catalogo_produto_id', 'chave_aplicacao'],
                'uq_catalogo_produto_aplicacoes_chave'
            );
            $table->index(['montadora_raw', 'veiculo_raw']);
            $table->index(
                ['catalogo_veiculo_id', 'catalogo_produto_id'],
                'idx_cat_aplic_veiculo_produto'
            );
        });

        Schema::create('catalogo_produto_atributos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalogo_produto_id')
                ->constrained('catalogo_produtos')
                ->cascadeOnDelete();
            $table->string('chave', 191);
            $table->string('chave_raw', 191)->nullable();
            $table->text('valor')->nullable();
            $table->timestamp('primeira_coleta_em')->nullable();
            $table->timestamp('ultima_confirmacao_em')->nullable();
            $table->timestamps();

            $table->unique(
                ['catalogo_produto_id', 'chave'],
                'uq_catalogo_produto_atributos_chave'
            );
        });

        Schema::create('catalogo_produto_imagens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalogo_produto_id')
                ->constrained('catalogo_produtos')
                ->cascadeOnDelete();
            $table->string('chave_imagem', 64);
            $table->text('url_original')->nullable();
            $table->text('url_thumb')->nullable();
            $table->text('arquivo_origem')->nullable();
            $table->string('hash_sha256', 64)->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('tamanho_bytes')->nullable();
            $table->boolean('principal')->default(false);
            $table->string('status', 50)->nullable();
            $table->timestamp('primeira_coleta_em')->nullable();
            $table->timestamp('ultima_confirmacao_em')->nullable();
            $table->timestamps();

            $table->unique(
                ['catalogo_produto_id', 'chave_imagem'],
                'uq_catalogo_produto_imagens_chave'
            );
            $table->index('hash_sha256');
        });

        Schema::create('catalogo_produto_similares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalogo_produto_id')
                ->constrained('catalogo_produtos')
                ->cascadeOnDelete();
            $table->foreignId('similar_catalogo_produto_id')
                ->constrained('catalogo_produtos')
                ->cascadeOnDelete();
            $table->timestamp('primeira_coleta_em')->nullable();
            $table->timestamp('ultima_confirmacao_em')->nullable();
            $table->timestamps();

            $table->unique(
                ['catalogo_produto_id', 'similar_catalogo_produto_id'],
                'uq_catalogo_produto_similares_par'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalogo_produto_similares');
        Schema::dropIfExists('catalogo_produto_imagens');
        Schema::dropIfExists('catalogo_produto_atributos');
        Schema::dropIfExists('catalogo_produto_aplicacoes');
        Schema::dropIfExists('catalogo_produto_precos');
        Schema::dropIfExists('catalogo_produto_estados');
    }
};
