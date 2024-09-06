<?php

namespace Ahmed3bead\LaraCrud\BaseClasses\traits;

use Ahmed3bead\LaraCrud\BaseClasses\BaseResponse;
use Ahmed3bead\LaraCrud\BaseClasses\HttpStatus;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

trait ServiceTrait
{

    private array $errors = []; // Define $errors property

    protected function response($statusCode = HttpStatus::HTTP_OK): BaseResponse|JsonResponse
    {
        return ( new BaseResponse($statusCode))
            ->setErrors($this->getErrors());
    }

    public function setResponse($data)
    {
        return $this->response()->setData($data)->setStatusCode(HttpStatus::HTTP_OK);
    }

    public function setErrorResponse($message = "", $status = HttpStatus::HTTP_ERROR): JsonResponse
    {
        return $this->response()->setErrors(['message' => $message])->setMessage($message)->setStatusCode($status)->json();
    }

    public function setSuccessResponse($message = "", $status = HttpStatus::HTTP_OK): JsonResponse
    {
        return $this->response()->setErrors(['message' => $message])->setMessage($message)->setStatusCode($status)->json();
    }

    public function setPaginateResponse(LengthAwarePaginator $paginator): BaseResponse|JsonResponse
    {
        return $this->response()
            ->setData($paginator->items())
            ->setMeta([
                'currentPage' => $paginator->currentPage(),
                'lastPage' => $paginator->lastPage(),
                'path' => $paginator->path(),
                'totalCount' => count($paginator->items()),
                'perPage' => $paginator->perPage(),
                'total' => $paginator->total(),
            ])
            ->setStatusCode(HttpStatus::HTTP_OK);
    }

    /**
     * @throws \Exception
     */
    public function tryAndResponse(callable $func): BaseResponse|JsonResponse
    {
        try {
            DB::beginTransaction();
            $result = $func();
            DB::commit();

            return $result;
        } catch (\Exception $exception) {
            DB::rollBack();
            throw $exception;
        }
    }

    public function setMessageResponse($message, $status = HttpStatus::HTTP_ERROR): JsonResponse
    {
        return $this->response()
            ->setData(['message' => $message])
            ->setMessage($message)
            ->setStatusCode($status)->json();
    }

    /**
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @param array $errors
     */
    public function setErrors(array $errors): void
    {
        $this->errors = $errors;
    }

    /**
     * @param string $error
     */
    public function setError(string $error): void
    {
        $this->errors[] = $error;
    }

    public function readApiResponse($data)
    {
        if ($data['status_code'] == HttpStatus::HTTP_OK) {
            return $this->setResponse($data['data'])->setMeta($data['meta'] ?? null);
        } elseif (isset($data['errors']) && $data['status_code'] == HttpStatus::HTTP_VALIDATION_ERROR) {
            return $this->response()->setErrors($data['errors'])->setStatusCode($data['status_code'])->json();
        } elseif (isset($data['errors']) && $data['status_code'] == HttpStatus::HTTP_ERROR) {
            return $this->response()->setErrors($data['errors'])->setStatusCode($data['status_code'])->json();
        } else {
            return $this->response()->setData($data)->setStatusCode($data['status_code'])->json();
        }

    }

}
