<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const TABELA = 'marketing_leads';
    private const ORIGINAL = 'marketing_leads_cliente_id_foreign';
    private const PROTEGIDA = 'marketing_leads_cliente_restrict_fk';
    private const REGRA_ORIGINAL = 'SET NULL';

    public function up(): void
    {
        $this->alterar(
            self::ORIGINAL,
            self::REGRA_ORIGINAL,
            self::PROTEGIDA,
            'RESTRICT'
        );
    }

    public function down(): void
    {
        // Reversao somente sob procedimento controlado.
        $this->alterar(
            self::PROTEGIDA,
            'RESTRICT',
            self::ORIGINAL,
            self::REGRA_ORIGINAL
        );
    }

    private function alterar(
        string $atual,
        string $regraAtual,
        string $nova,
        string $regraNova
    ): void {
        $db = DB::connection();

        if ($db->getDriverName() !== 'mysql') {
            throw new RuntimeException('MySQL obrigatorio.');
        }

        $versao = (string) $db
            ->selectOne('SELECT VERSION() AS versao')->versao;

        if (
            stripos($versao, 'mariadb') !== false
            || ! preg_match('/^([0-9]+)\./', $versao, $m)
            || (int) $m[1] < 8
        ) {
            throw new RuntimeException('MySQL 8+ obrigatorio.');
        }

        $tabelas = $db->select(
            "SELECT TABLE_NAME, ENGINE
             FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME IN (?, 'clientes')",
            [self::TABELA]
        );

        if (
            count($tabelas) !== 2
            || collect($tabelas)->contains(
                fn ($t) => strtoupper((string) $t->ENGINE) !== 'INNODB'
            )
        ) {
            throw new RuntimeException('Estrutura InnoDB inesperada.');
        }

        $this->verificar($atual, $regraAtual);

        $checks = (int) $db->selectOne(
            'SELECT @@SESSION.foreign_key_checks AS ativo'
        )->ativo;

        if ($checks !== 1) {
            throw new RuntimeException('FOREIGN_KEY_CHECKS deve estar ativo.');
        }

        $orfa = $db->selectOne(
            'SELECT filho.cliente_id
             FROM `'.self::TABELA.'` filho
             LEFT JOIN `clientes` pai ON pai.id = filho.cliente_id
             WHERE filho.cliente_id IS NOT NULL
               AND pai.id IS NULL
             LIMIT 1'
        );

        if ($orfa !== null) {
            throw new RuntimeException('Vinculos orfaos encontrados.');
        }

        $db->statement('SET SESSION foreign_key_checks = 0');

        try {
            $db->statement(
                'ALTER TABLE `'.self::TABELA.'` '
                .'DROP FOREIGN KEY `'.$atual.'`, '
                .'ADD CONSTRAINT `'.$nova.'` '
                .'FOREIGN KEY (`cliente_id`) '
                .'REFERENCES `clientes` (`id`) '
                .'ON DELETE '.$regraNova.' '
                .'ON UPDATE NO ACTION, '
                .'ALGORITHM=INPLACE'
            );
        } finally {
            $db->statement('SET SESSION foreign_key_checks = 1');
        }

        $this->verificar($nova, $regraNova);
    }

    private function verificar(string $nome, string $regra): void
    {
        $fks = DB::select(
            "SELECT
                k.CONSTRAINT_NAME AS nome,
                k.REFERENCED_TABLE_NAME AS tabela,
                k.REFERENCED_COLUMN_NAME AS coluna,
                rc.DELETE_RULE AS regra_exclusao,
                rc.UPDATE_RULE AS regra_atualizacao
             FROM information_schema.KEY_COLUMN_USAGE k
             JOIN information_schema.REFERENTIAL_CONSTRAINTS rc
               ON rc.CONSTRAINT_SCHEMA = k.CONSTRAINT_SCHEMA
              AND rc.TABLE_NAME = k.TABLE_NAME
              AND rc.CONSTRAINT_NAME = k.CONSTRAINT_NAME
             WHERE k.TABLE_SCHEMA = DATABASE()
               AND k.TABLE_NAME = ?
               AND k.COLUMN_NAME = 'cliente_id'
               AND k.REFERENCED_TABLE_NAME IS NOT NULL",
            [self::TABELA]
        );

        if (count($fks) !== 1) {
            throw new RuntimeException('Quantidade inesperada de FKs.');
        }

        $fk = $fks[0];

        if (
            $fk->nome !== $nome
            || $fk->tabela !== 'clientes'
            || $fk->coluna !== 'id'
            || $fk->regra_exclusao !== $regra
            || $fk->regra_atualizacao !== 'NO ACTION'
        ) {
            throw new RuntimeException('FK diferente da esperada.');
        }
    }
};
