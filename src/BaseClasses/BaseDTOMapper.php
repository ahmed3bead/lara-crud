<?php

namespace Ahmed3bead\LaraCrud\BaseClasses;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class BaseDTOMapper
{
    private mixed $model;
    private static mixed $DTO;

    public function __construct($dto,$model)
    {
        $this->setModel($model);
        $this->setDTO($dto);
    }

    public static function getDTO(): mixed
    {
        return self::$DTO;
    }

    public static function setDTO(mixed $DTO): void
    {
        self::$DTO = $DTO;
    }





    /**
     * @return mixed
     */
    public function getModel(): mixed
    {
        return $this->model;
    }

    /**
     * @param mixed $model
     */
    public function setModel(mixed $model): void
    {
        $this->model = $model;
    }



    public static function toModel($data)
    {
        return $data;
    }

    public static function mapFromRequest(BaseRequest $request)
    {
        $mapper = get_called_class();
        $mapper = new $mapper;
        return $mapper::prepareData(self::getDTO(), $request);
    }

    public static function mapFromDB($Ae)
    {
        $mapper = get_called_class();
        $mapper = new $mapper;
        return $mapper::prepareData(self::getDTO(), $Ae);
    }

    public static function fromArray(array $data): array
    {
        return self::fromCollection(collect($data));
    }

    public static function fromCollection(Collection $data, $listing = false): array
    {
        return $data->map(fn($item) => self::fromModel($item, $listing))->toArray();
    }

    public static function fromPaginator(
        LengthAwarePaginator $paginator
    ): array {
        $mapper = get_called_class();
        $mapper = new $mapper;

        return [
            'items' => $mapper::fromArray($paginator->items()),
            'meta'  => [
                'currentPage' => $paginator->currentPage(),
                'lastPage'    => $paginator->lastPage(),
                'path'        => $paginator->path(),
                'perPage'     => $paginator->perPage(),
                'links'     => $paginator->links(),
                'total'       => $paginator->total(),
            ],
        ];
    }
}
