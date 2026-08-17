# Inventory audit and reconciliation

These commands repair inventory through the outbound movement ledger. They never
set `warehouse_items.remaining_quantity` to an arbitrary value and never delete
orphan rows automatically.

## Safe operating sequence

1. Take and verify a database backup, then pause outbound/inbound writes for the
   reconciliation window.
2. Run `php artisan inventory:audit --format=json --output=/secure/path/audit-before.json`.
   CSV is available with `--format=csv`. Existing files are not replaced unless
   `--force` is passed.
3. Match every discrepancy to a signed inbound/outbound document and vehicle.
   Copy the example JSON, replace every example ID/value, and have it reviewed.
4. Run `php artisan inventory:reconcile /secure/path/reconciliation.json`. This is
   the default read-only dry-run. A successful dry-run writes no database rows.
5. Apply only the exact reviewed file with
   `php artisan inventory:reconcile /secure/path/reconciliation.json --apply --actor="operator-id"`.
   Apply rechecks all expected values under row locks and commits the whole file
   atomically. Any mismatch rolls everything back.
6. Run `inventory:audit` again and retain both reports, the reviewed JSON, its
   SHA-256 hash from the command result, and the signed supporting documents.

Do not use `--apply` until the backup and source-document mapping are confirmed.
The repository example deliberately contains fictional IDs and must never be
applied unchanged.

## Schema version 1

The template is [inventory-reconciliation.schema-v1.example.json](inventory-reconciliation.schema-v1.example.json).

- `reference` identifies the approved document set. `operation_id` must be
  unique within that reference. Together they form the idempotency key.
- `approved_by` names the person or committee that approved the mapping;
  `--actor` records the operator who ran it.
- Every `expected` block is an optimistic-lock snapshot. Nullable values must
  still be present. Existing ledger snapshots include their accounting fields,
  location, and status flag as well as IDs/quantities. If any current value
  differs, dry-run/apply fails.
- `after.ledger` contains the intended movement. Optional accounting fields
  (`custom_value`, `gross_weight`, `net_weight`, `cpm`, `cpm_capacity`) may be
  supplied from the official document; otherwise they are derived pro-rata from
  the warehouse item. `location` is optional.
- `outbound_updates` is required when a documented movement changes the outbound
  header total. Its expected value is checked before changing `quantity_packages`.
- The projected and final sum of movement quantities must equal each affected
  outbound's `quantity_packages`.

Supported operations are:

- `create`: `expected.ledger` is `null`; creates a missing movement.
- `update`: changes quantity fields while keeping warehouse item, outbound, and
  vehicle unchanged.
- `relink`: changes relationships while keeping both quantity fields unchanged.
- `split`: keeps the source relationships, reduces the source row, and creates a
  second row. Source plus new quantities must exactly equal the expected source.

For `split`, place the retained row in `after.source_ledger` and the new row in
`after.ledger`. Each existing ledger row may be the source of only one operation
per file. A final `(warehouse_item_id, outbound_car_id)` pair may occur only once.

## Inventory policy and audit output

Outbound status 3 is an editable warehouse-release draft and does not consume
availability. Statuses 4, 5, 6, and 7 consume stock; status 6 (needs revision)
does not restore it automatically.

`inventory:audit` is always read-only. Its JSON summary includes `by_code`,
`constraint_blocker_count`, and `constraints_ready`. Strict foreign keys and
unique constraints should only be enabled after `constraints_ready` is true.
The status-FK repair migration performs that preflight and stops with a clear
error when invalid status rows remain. The remaining ledger `NOT NULL`, unique,
and restrictive-delete constraints are intentionally deferred until the cleanup
audit passes; they must not be added as a silent or partial cleanup mechanism.
Balance mismatches remain visible, and over-allocation is reported as a negative
calculated balance rather than being silently clamped to zero.

The legacy `app:check-remaining-quantity` command and sidebar action now run only
the read-only audit; they no longer delete movements or rewrite balances.
