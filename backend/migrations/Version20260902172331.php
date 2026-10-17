<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260902172331 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add audit_logs.chain_sequence — a real Postgres sequence for deterministic hash-chain ordering, replacing createdAt/id (a UUIDv7\'s monotonicity is only guaranteed within one PHP process, not across the concurrent outbox workers withHashChainLock() serializes)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SEQUENCE audit_logs_chain_sequence_seq');
        // Added nullable first, deliberately without the DEFAULT yet — see below
        // for why the column is populated by hand instead of a bare
        // `DEFAULT nextval(...)` on this ADD COLUMN itself.
        $this->addSql('ALTER TABLE audit_logs ADD chain_sequence BIGINT');

        // A fresh install has no rows yet, so this backfill is a no-op — but an
        // install that's been running before this migration does have real
        // history to preserve correctly. A bare `ADD COLUMN ... DEFAULT
        // nextval(...)` would evaluate that volatile default once per row during
        // Postgres's table-rewrite, in whatever order it happens to scan the
        // heap in — an implementation detail of the rewrite, not something
        // Postgres documents as matching original insertion order, let alone the
        // hash chain's own order. previous_hash/hash links are the only
        // unambiguous record of real chain order that exists, so walk those
        // instead of trusting physical storage order to have preserved it.
        $this->addSql(<<<'SQL'
            WITH RECURSIVE chain AS (
                SELECT id, hash, 1::bigint AS seq
                FROM audit_logs
                WHERE previous_hash = 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855'
                UNION ALL
                SELECT a.id, a.hash, chain.seq + 1
                FROM audit_logs a
                INNER JOIN chain ON a.previous_hash = chain.hash
            )
            UPDATE audit_logs
            SET chain_sequence = chain.seq
            FROM chain
            WHERE audit_logs.id = chain.id
            SQL);

        // Fails the whole migration (rather than silently leaving gaps that
        // NOT NULL would otherwise catch) if any row's previous_hash didn't
        // resolve back to genesis through the recursive walk above — a broken
        // hash chain link is exactly the kind of thing a migration should stop
        // on, not paper over with an unordered fallback.
        $this->addSql('ALTER TABLE audit_logs ALTER COLUMN chain_sequence SET NOT NULL');
        $this->addSql('ALTER TABLE audit_logs ALTER COLUMN chain_sequence SET DEFAULT nextval(\'audit_logs_chain_sequence_seq\')');
        $this->addSql('ALTER SEQUENCE audit_logs_chain_sequence_seq OWNED BY audit_logs.chain_sequence');
        // Lands the sequence one past the highest value actually backfilled above
        // (or at 1, its already-default starting point, when there was nothing to
        // backfill), so the very next INSERT continues the chain instead of
        // colliding with real history.
        $this->addSql("SELECT setval('audit_logs_chain_sequence_seq', COALESCE((SELECT MAX(chain_sequence) FROM audit_logs), 0) + 1, false)");
        $this->addSql('CREATE UNIQUE INDEX UNIQ_AUDIT_LOGS_CHAIN_SEQUENCE ON audit_logs (chain_sequence)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_AUDIT_LOGS_CHAIN_SEQUENCE');
        $this->addSql('ALTER TABLE audit_logs DROP chain_sequence');
        $this->addSql('DROP SEQUENCE IF EXISTS audit_logs_chain_sequence_seq');
    }
}
