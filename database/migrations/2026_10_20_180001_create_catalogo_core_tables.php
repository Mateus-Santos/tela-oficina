<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalogo_fontes', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('nome', 150);
            $table->string('tipo', 50)->default('catalogo');
            $table->boolean('ativo')->default(true);
            $table->json('configuracao')->nullable();
            $table->timestamp('ultima_importacao_em')->nullable();
            $table->timestamps();
        });

        Schema::create('catalogo_montadoras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalogo_fonte_id')
                ->constrained('catalogo_fontes')
                ->cascadeOnDelete();
            $table->string('chave_externa', 191);
            $table->string('nome', 150);
            $table->boolean('ativo_fonte')->default(true);
            $table->timestamp('primeira_coleta_em')->nullable();
            $table->timestamp('ultima_coleta_em')->nullable();
            $table->timestamps();

            $table->unique(
                ['catalogo_fonte_id', 'chave_externa'],
                'uq_catalogo_montadoras_fonte_chave'
            );
            $table->index(['catalogo_fonte_id', 'nome']);
        });

        Schema::create('catalogo_veiculos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalogo_fonte_id')
                ->constrained('catalogo_fontes')
                ->cascadeOnDelete();
            $table->foreignId('catalogo_montadora_id')
                ->constrained('catalogo_montadoras')
                ->cascadeOnDelete();
            $table->string('chave_externa', 191);
            $table->string('nome', 191);
            $table->boolean('ativo_fonte')->default(true);
            $table->timestamp('primeira_coleta_em')->nullable();
            $table->timestamp('ultima_coleta_em')->nullable();
            $table->timestamps();

            $table->unique(
                ['catalogo_fonte_id', 'chave_externa'],
                'uq_catalogo_veiculos_fonte_chave'
            );
            $table->index(['catalogo_montadora_id', 'nome']);
        });

        Schema::create('catalogo_departamentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalogo_fonte_id')
                ->constrained('catalogo_fontes')
                ->cascadeOnDelete();
            $table->string('chave_externa', 191);
            $table->string('nome', 191);
            $table->timestamps();

            $table->unique(
                ['catalogo_fonte_id', 'chave_externa'],
                'uq_catalogo_departamentos_fonte_chave'
            );
        });

        Schema::create('catalogo_tipos_produto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalogo_fonte_id')
                ->constrained('catalogo_fontes')
                ->cascadeOnDelete();
            $table->string('chave_externa', 191);
            $table->string('nome', 191);
            $table->timestamps();

            $table->unique(
                ['catalogo_fonte_id', 'chave_externa'],
                'uq_catalogo_tipos_fonte_chave'
            );
        });

        Schema::create('catalogo_produtos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalogo_fonte_id')
                ->constrained('catalogo_fontes')
                ->cascadeOnDelete();
            $table->foreignId('produto_id')
                ->nullable()
                ->constrained('produtos')
                ->nullOnDelete();

            $table->string('chave_externa', 191);
            $table->string('codigo_fabricante', 191)->nullable();
            $table->string('codigo_auxiliar', 191)->nullable();
            $table->text('descricao')->nullable();
            $table->string('marca_raw', 191)->nullable();
            $table->string('unidade', 30)->nullable();
            $table->string('codigo_barras', 191)->nullable();
            $table->string('digito_verificador', 50)->nullable();
            $table->string('ncm', 20)->nullable();
            $table->string('quantidade_jogo', 50)->nullable();
            $table->string('quantidade_caixa', 50)->nullable();
            $table->string('origem_raw', 191)->nullable();
            $table->text('imagem_thumb_url')->nullable();
            $table->text('imagem_url')->nullable();
            $table->string('aplicacao_status', 50)->nullable();

            $table->string('vinculo_status', 30)->default('nao_vinculado');
            $table->decimal('vinculo_confianca', 5, 2)->nullable();
            $table->string('vinculo_motivo', 191)->nullable();

            $table->timestamp('primeira_coleta_em')->nullable();
            $table->timestamp('ultima_coleta_em')->nullable();
            $table->timestamps();

            $table->unique(
                ['catalogo_fonte_id', 'chave_externa'],
                'uq_catalogo_produtos_fonte_chave'
            );
            $table->index('produto_id');
            $table->index('codigo_fabricante');
            $table->index('codigo_barras');
            $table->index(['catalogo_fonte_id', 'marca_raw']);
            $table->index(['catalogo_fonte_id', 'vinculo_status']);
        });

        Schema::create('catalogo_produto_departamentos', function (Blueprint $table) {
            $table->foreignId('catalogo_produto_id')
                ->constrained('catalogo_produtos')
                ->cascadeOnDelete();
            $table->foreignId('catalogo_departamento_id')
                ->constrained('catalogo_departamentos')
                ->cascadeOnDelete();
            $table->timestamp('primeira_coleta_em')->nullable();
            $table->timestamp('ultima_coleta_em')->nullable();
            $table->timestamps();

            $table->primary(
                ['catalogo_produto_id', 'catalogo_departamento_id'],
                'pk_catalogo_produto_departamentos'
            );
        });

        Schema::create('catalogo_produto_tipos', function (Blueprint $table) {
            $table->foreignId('catalogo_produto_id')
                ->constrained('catalogo_produtos')
                ->cascadeOnDelete();
            $table->foreignId('catalogo_tipo_produto_id')
                ->constrained('catalogo_tipos_produto')
                ->cascadeOnDelete();
            $table->timestamp('primeira_coleta_em')->nullable();
            $table->timestamp('ultima_coleta_em')->nullable();
            $table->timestamps();

            $table->primary(
                ['catalogo_produto_id', 'catalogo_tipo_produto_id'],
                'pk_catalogo_produto_tipos'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalogo_produto_tipos');
        Schema::dropIfExists('catalogo_produto_departamentos');
        Schema::dropIfExists('catalogo_produtos');
        Schema::dropIfExists('catalogo_tipos_produto');
        Schema::dropIfExists('catalogo_departamentos');
        Schema::dropIfExists('catalogo_veiculos');
        Schema::dropIfExists('catalogo_montadoras');
        Schema::dropIfExists('catalogo_fontes');
    }
};
