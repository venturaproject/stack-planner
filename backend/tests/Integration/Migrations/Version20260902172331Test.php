<?php

declare(strict_types=1);

namespace App\Tests\Integration\Migrations;

use App\Migrations\Version20260902172331;
use App\Tests\Support\DatabaseKernelTestCase;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Psr\Log\NullLogger;

/**
 * Version20260902172331 backfills chain_sequence for any audit_logs rows that
 * already existed before it ran (a fresh install has none). This proves the
 * backfill follows the hash chain's own previous_hash/hash links — the only
 * unambiguous record of real order — rather than physical/insertion order,
 * which a bare `ADD COLUMN ... DEFAULT nextval(...)` would have silently
 * relied on instead.
 */
final class Version20260902172331Test extends DatabaseKernelTestCase
{
    private const GENESIS_HASH = 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855';

    public function testBackfillsChainSequenceByHashChainOrderNotInsertionOrder(): void
    {
        $connection = $this->em->getConnection();

        // Revert this test's view of the schema to how it looked immediately
        // before this migration ran: no chain_sequence column (dropping it also
        // drops the sequence, since the real migration marks it OWNED BY the
        // column) and no pre-existing audit_logs rows from other tests/fixtures,
        // so this test's own rows are the entire history the migration sees.
        $connection->executeStatement('DROP INDEX IF EXISTS UNIQ_D62F2858B8F8E0FA');
        $connection->executeStatement('ALTER TABLE audit_logs DROP COLUMN IF EXISTS chain_sequence');
        $connection->executeStatement('DROP SEQUENCE IF EXISTS audit_logs_chain_sequence_seq');
        $connection->executeStatement('DELETE FROM audit_logs');

        $hashOfFirst = 'genesis-child-'.str_repeat('a', 50);
        $hashOfSecond = 'chain-tip-'.str_repeat('b', 54);

        // Logically SECOND in the chain but inserted first, physically before
        // the genesis-linked row below — the opposite of what real insertion
        // order would have produced, so a backfill that trusted physical/heap
        // order instead of the hash links would get this exactly backwards.
        $connection->insert('audit_logs', [
            'id' => '00000000-0000-7000-8000-000000000001',
            'action' => 'test.first-inserted-but-second-in-chain',
            'resource_type' => 'Test',
            'metadata' => '{}',
            'previous_hash' => $hashOfFirst,
            'hash' => $hashOfSecond,
            'created_at' => '2026-01-01 11:00:00+00',
        ]);
        $connection->insert('audit_logs', [
            'id' => '00000000-0000-7000-8000-000000000002',
            'action' => 'test.second-inserted-but-first-in-chain',
            'resource_type' => 'Test',
            'metadata' => '{}',
            'previous_hash' => self::GENESIS_HASH,
            'hash' => $hashOfFirst,
            'created_at' => '2026-01-01 12:00:00+00',
        ]);

        $this->runMigration($connection);

        $sequenceByHash = $connection->fetchAllKeyValue(
            'SELECT hash, chain_sequence FROM audit_logs WHERE hash IN (:a, :b)',
            ['a' => $hashOfFirst, 'b' => $hashOfSecond],
        );

        self::assertLessThan(
            $sequenceByHash[$hashOfSecond],
            $sequenceByHash[$hashOfFirst],
            'The genesis-linked row must get the smaller chain_sequence regardless of insertion order.',
        );

        // The sequence must continue from the backfill, not restart at 1 and
        // collide with real history.
        $nextValue = (int) $connection->fetchOne("SELECT nextval('audit_logs_chain_sequence_seq')");
        self::assertGreaterThan((int) $sequenceByHash[$hashOfSecond], $nextValue);
    }

    public function testAbortsRatherThanBackfillingARowUnreachableFromGenesis(): void
    {
        $connection = $this->em->getConnection();

        $connection->executeStatement('DROP INDEX IF EXISTS UNIQ_D62F2858B8F8E0FA');
        $connection->executeStatement('ALTER TABLE audit_logs DROP COLUMN IF EXISTS chain_sequence');
        $connection->executeStatement('DROP SEQUENCE IF EXISTS audit_logs_chain_sequence_seq');
        $connection->executeStatement('DELETE FROM audit_logs');

        // previous_hash points at neither genesis nor any other row's hash —
        // an impossible state for the real chain-recording code path to ever
        // produce, but exactly the kind of corruption a migration must not
        // silently paper over by leaving chain_sequence unordered/arbitrary.
        $connection->insert('audit_logs', [
            'id' => '00000000-0000-7000-8000-000000000099',
            'action' => 'test.orphaned',
            'resource_type' => 'Test',
            'metadata' => '{}',
            'previous_hash' => str_repeat('f', 64),
            'hash' => 'unreachable-'.str_repeat('c', 52),
            'created_at' => '2026-01-01 12:00:00+00',
        ]);

        $this->expectException(\Doctrine\DBAL\Exception::class);

        $this->runMigration($connection);
    }

    private function runMigration(Connection $connection): void
    {
        $migration = new Version20260902172331($connection, new NullLogger());
        $migration->up(new Schema());

        foreach ($migration->getSql() as $query) {
            $connection->executeStatement($query->getStatement(), $query->getParameters(), $query->getTypes());
        }
    }
}
