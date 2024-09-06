<?php

namespace Ahmed3bead\LaraCrud\BaseClasses;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

class KeywordSearchFilterInTranslationsWithRelation implements Filter
{
    public $fields = [];
    public $relationName;

    public function __construct(array $fields = ['name'], string $relationName = null)
    {
        $this->fields = $fields;
        $this->relationName = $relationName;
    }

    public function __invoke(Builder $query, $value, string $property): Builder
    {
        $value = strtolower($value);

        if ($this->relationName) {
            return $query->whereHas($this->relationName, function (Builder $query) use ($value) {
                $query->where(function (Builder $query) use ($value) {
                    foreach ($this->fields as $field) {
                        $query->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(`$field`, '$.en')) LIKE ?", ['%' . $value . '%'])
                            ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(`$field`, '$.ar')) LIKE ?", ['%' . $value . '%']);
                    }
                });
            });
        } else {
            return $query->where(function (Builder $query) use ($value) {
                foreach ($this->fields as $field) {
                    $query->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(`$field`, '$.en')) LIKE ?", ['%' . $value . '%'])
                        ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(`$field`, '$.ar')) LIKE ?", ['%' . $value . '%']);
                }
            });
        }
    }
}
