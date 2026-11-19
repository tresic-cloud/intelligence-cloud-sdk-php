<?php

declare(strict_types=1);

namespace IntelligenceCloud\Webhook;

/**
 * A DedupStore in a database table, shared by every worker. The insert is the record: a row is
 * written before the delivery is acknowledged as new, so it survives restarts, and the table can
 * double as your work queue (read the ids your worker has not processed yet).
 *
 *     $pdo = new PDO('sqlite:/var/lib/ic/after-call.sqlite');  // or your own database connection
 *     $store = new PdoDedupStore($pdo, 'ic_after_call_queue');
 *     $store->createTable();                                   // once
 *     $dedup = new Deduplicator($store);
 *
 * The table has a primary key on `id`; a repeat insert is a duplicate.
 */
final class PdoDedupStore implements DedupStore
{
    public function __construct(private readonly \PDO $pdo, private readonly string $table = 'ic_webhook_ids')
    {
        if (preg_match('/^[A-Za-z_][A-Za-z0-9_]{0,62}$/', $table) !== 1) {
            throw new \InvalidArgumentException('table must be a plain identifier');
        }
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    public function createTable(): void
    {
        $this->pdo->exec(sprintf(
            'CREATE TABLE IF NOT EXISTS %s (id VARCHAR(64) PRIMARY KEY, received_at VARCHAR(32) NOT NULL, processed_at VARCHAR(32) NULL)',
            $this->table,
        ));
    }

    public function add(string $id): bool
    {
        try {
            $this->pdo->prepare(sprintf('INSERT INTO %s (id, received_at) VALUES (?, ?)', $this->table))
                ->execute([$id, gmdate('Y-m-d\TH:i:s\Z')]);
            return true;
        } catch (\PDOException $e) {
            if (in_array((string) $e->getCode(), ['23000', '23505'], true)) { // SQLSTATE unique violation
                return false; // unique violation: seen before
            }
            throw $e;
        }
    }
}
