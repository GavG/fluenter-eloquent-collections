<?php

declare(strict_types=1);

namespace GavG\FluenterEloquentCollections;

use Illuminate\Database\Eloquent\Model;

final class BulkUpdateQuery
{
    /**
     * @param array<int, array{model: Model, key: mixed, attributes: array<string, mixed>}> $updates
     */
    public static function execute(Model $model, array $updates, array $columns): void
    {
        $connection = $model->getConnection();
        $grammar = $connection->getQueryGrammar();
        $wrappedKey = $grammar->wrap($model->getKeyName());
        $wrappedTable = $grammar->wrapTable($model->getTable());

        $sets = [];
        $bindings = [];

        foreach (array_keys($columns) as $column) {
            $wrappedColumn = $grammar->wrap($column);
            $cases = [];

            foreach ($updates as $update) {
                if (! array_key_exists($column, $update['attributes'])) {
                    continue;
                }

                $cases[] = sprintf('WHEN %s = ? THEN ?', $wrappedKey);
                $bindings[] = $update['key'];
                $bindings[] = $update['attributes'][$column];
            }

            $sets[] = sprintf(
                '%s = CASE %s ELSE %s END',
                $wrappedColumn,
                implode(' ', $cases),
                $wrappedColumn,
            );
        }

        $keys = array_column($updates, 'key');
        $placeholders = implode(', ', array_fill(0, count($keys), '?'));
        $bindings = array_merge($bindings, $keys);

        $sql = sprintf(
            'UPDATE %s SET %s WHERE %s IN (%s)',
            $wrappedTable,
            implode(', ', $sets),
            $wrappedKey,
            $placeholders,
        );

        $connection->update($sql, $bindings);
    }
}