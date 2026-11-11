<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const FK_CASCADE = 'notas_cliente_id_foreign';
    private const FK_RESTRICT = 'notas_cliente_id_restrict_foreign';

    public function up(): void
    {
        $this->alterarRegra(
            'CASCADE', 'RESTRICT',
            self::FK_CASCADE, self::FK_RESTRICT
        );
    }

    public function down(): void
    {
        // Reversao explicita: reintroduz o CASCADE original.
        $this->alterarRegra(
            'RESTRICT', 'CASCADE',
            self::FK_RESTRICT, self::FK_CASCADE
        );
    }

    private function alterarRegra(
        string $esperada,
        string $nova,
        string $fkAtual,
        string $fkNova
    ): void {
        $conexao = DB::connection();

        if ($conexao->getDriverName() !== 'mysql') {
            throw new RuntimeException(
                'Esta migration exige MySQL.'
            );
        }

        $versao = (string) $conexao
            ->selectOne('SELECT VERSION() AS versao')
            ->versao;

        if (
            stripos($versao, 'mariadb') !== false
            || ! preg_match('/^([0-9]+)\./', $versao, $m)
            || (int) $m[1] < 8
        ) {
            throw new RuntimeException(
                'Esta migration exige MySQL 8 ou superior.'
            );
        }

        $tabelas = $conexao->select(
            "SELECT TABLE_NAME, ENGINE
             FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME IN ('notas', 'clientes')"
        );

        if (
            count($tabelas) !== 2
            || collect($tabelas)->contains(
                fn ($tabela) => strtoupper(
                    (string) $tabela->ENGINE
                ) !== 'INNODB'
            )
        ) {
            throw new RuntimeException(
                'Tabelas ausentes ou fora do InnoDB.'
            );
        }

        $this->verificarRegra($esperada, $fkAtual);

        $checks = (int) $conexao->selectOne(
            'SELECT @@SESSION.foreign_key_checks AS ativo'
        )->ativo;

        if ($checks !== 1) {
            throw new RuntimeException(
                'FOREIGN_KEY_CHECKS deve estar ativo.'
            );
        }

        // Evita recriar a FK sobre dados previamente inconsistentes.
        $orfa = $conexao->selectOne(
            "SELECT n.id
             FROM notas n
             LEFT JOIN clientes c ON c.id = n.cliente_id
             WHERE n.cliente_id IS NOT NULL
               AND c.id IS NULL
             LIMIT 1"
        );

        if ($orfa !== null) {
            throw new RuntimeException(
                'Existem notas com cliente inexistente.'
            );
        }

        // Um unico ALTER TABLE evita intervalo entre DROP e ADD.
        // FOREIGN_KEY_CHECKS e alterado somente nesta conexao.
        // Nenhuma gravacao de dados ocorre durante esse intervalo.
        $conexao->statement(
            'SET SESSION foreign_key_checks = 0'
        );

        try {
            $conexao->statement(
                'ALTER TABLE `notas` '
                .'DROP FOREIGN KEY `'.$fkAtual.'`, '
                .'ADD CONSTRAINT `'.$fkNova.'` '
                .'FOREIGN KEY (`cliente_id`) '
                .'REFERENCES `clientes` (`id`) '
                .'ON DELETE '.$nova.' '
                .'ON UPDATE NO ACTION, '
                .'ALGORITHM=INPLACE'
            );
        } finally {
            $conexao->statement(
                'SET SESSION foreign_key_checks = 1'
            );
        }

        $this->verificarRegra($nova, $fkNova);
    }

    private function verificarRegra(
        string $esperada,
        string $nomeEsperado
    ): void {
        $restricoes = DB::select(
            "SELECT
                k.CONSTRAINT_NAME AS nome,
                k.REFERENCED_TABLE_NAME AS tabela,
                k.REFERENCED_COLUMN_NAME AS coluna,
                rc.DELETE_RULE AS regra_exclusao,
                rc.UPDATE_RULE AS regra_atualizacao
             FROM information_schema.KEY_COLUMN_USAGE k
             INNER JOIN information_schema.REFERENTIAL_CONSTRAINTS rc
                ON rc.CONSTRAINT_SCHEMA = k.CONSTRAINT_SCHEMA
               AND rc.TABLE_NAME = k.TABLE_NAME
               AND rc.CONSTRAINT_NAME = k.CONSTRAINT_NAME
             WHERE k.TABLE_SCHEMA = DATABASE()
               AND k.TABLE_NAME = 'notas'
               AND k.COLUMN_NAME = 'cliente_id'
               AND k.REFERENCED_TABLE_NAME IS NOT NULL"
        );

        if (count($restricoes) !== 1) {
            throw new RuntimeException(
                'Quantidade inesperada de FKs em notas.cliente_id.'
            );
        }

        $fk = $restricoes[0];

        if (
            $fk->nome !== $nomeEsperado
            || $fk->tabela !== 'clientes'
            || $fk->coluna !== 'id'
            || $fk->regra_exclusao !== $esperada
            || $fk->regra_atualizacao !== 'NO ACTION'
        ) {
            throw new RuntimeException(
                'FK de notas diferente da estrutura esperada.'
            );
        }
    }
};
