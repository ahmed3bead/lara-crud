<?php

namespace Ahmed3bead\LaraCrud\BaseClasses;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use JsonSerializable;
use stdClass;
use function Ahmed3bead\LaraCrud\BaseClasses\traits\response;

class BaseResponse implements JsonSerializable
{
    /**
     * @var int
     */
    private $statusCode = 200;

    /**
     * @var mixed
     */
    private mixed $errors = null;

    /**
     * @var mixed
     */
    private $data = null;

    private $extraData = null;

    /**
     * @var mixed
     */
    private $meta = null;

    /**
     * @var mixed
     */
    private $debug = null;

    /**
     * @var array
     */
    private $headers = [];

    /**
     * @var string
     */
    private $message = "";

    /**
     * @var string
     */
    private string $source = 'OPs';

    public function __construct($statusCode = 200, $data = null)
    {
        $this->statusCode = $statusCode;
        $this->data = $data ?? new stdClass();
        $this->errors = new stdClass();
    }

    /**
     * build and return the json response.
     *
     * @return JsonResponse
     */
    public function json()
    {
        return response()->json(
            [
                'status_code' => $this->getStatusCode(),
                'errors' => $this->getErrors() ?? (new stdClass()),
                'data' => $this->getData() ?? (new stdClass()),
                'extra_data' => $this->getExtraData() ?? (new stdClass()),
                'message' => $this->getMessage(),
                'source' => $this->getSource(),
            ],
            $this->getStatusCode(),
            $this->getHeaders()
        );
    }

    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @param int $statusCode
     *
     * @return BaseResponse
     */
    public function setStatusCode(int $statusCode): self
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    public function getErrors(): mixed
    {
        if (is_array($this->errors) && empty($this->errors)) {
            $this->errors = new stdClass();
        }

        return is_string($this->errors) ? (object)['error' => $this->errors] : $this->errors;
    }

    public function setErrors(mixed $errors): self
    {
        $this->errors = $errors;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getData(): mixed
    {
        return $this->data;
    }

    /**
     * @param mixed $data
     *
     * @return BaseResponse
     */
    public function setData(mixed $data): self
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @return null
     */
    public function getExtraData()
    {
        return $this->extraData;
    }

    /**
     * @param null $extraData
     */
    public function setExtraData($extraData): void
    {
        $this->extraData = $extraData;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @param string $message
     *
     * @return BaseResponse
     */
    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    /**
     * @return string
     */
    public function getSource(): string
    {
        return $this->source;
    }

    /**
     * @param string $source
     *
     * @return BaseResponse
     */
    public function setSource(string $source): self
    {
        $this->source = $source;

        return $this;
    }

    /**
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @param array $headers
     *
     * @return BaseResponse
     */
    public function setHeaders(array $headers): self
    {
        $this->headers = $headers;

        return $this;
    }

    /**
     * return the data that can be serialized as json.
     */
    public function jsonSerialize(): array
    {
        $return_data = [
            'status_code' => $this->getStatusCode(),
            'errors' => $this->getErrors() ?? (new stdClass()),
            'data' => $this->getData() ?? (new stdClass()),
            'extra_data' => $this->getExtraData() ?? (new stdClass()),
            'meta' => $this->getMeta() ?? (new stdClass()),
            'message' => $this->getMessage(),
            'source' => $this->getSource(),
        ];
        if (env('APP_DEBUG', false)) {
            $return_data += ['debug' => $this->getDebug()];
        }

        return $return_data;
    }

    /**
     * @return mixed
     */
    public function getMeta(): mixed
    {
        return $this->meta;
    }

    /**
     * @param mixed $data
     *
     * @return BaseResponse
     */
    public function setMeta(mixed $meta): self
    {
        $this->meta = $meta;

        return $this;
    }

    /**
     * @return array
     */
    public function getDebug(): array
    {
        $collect = collect(DB::getQueryLog());

        return [
            'count' => $collect->count(),
            'total_time' => $collect->sum('time'),
            'biggest_time' => $collect->max('time'),
            'queries' => $collect->toArray(),
        ];
    }
}
