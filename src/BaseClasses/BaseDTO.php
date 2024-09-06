<?php

namespace Ahmed3bead\LaraCrud\BaseClasses;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

abstract class BaseDTO implements JsonSerializable, Arrayable
{
    protected $created_at;

    protected $updated_at;

    /**
     * @return mixed
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * @param mixed $created_at
     * @return BaseDTO
     */
    public function setCreatedAt($created_at)
    {
        $this->created_at = $created_at;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getUpdatedAt()
    {
        return $this->updated_at;
    }

    /**
     * @param mixed $updated_at
     * @return BaseDTO
     */
    public function setUpdatedAt($updated_at)
    {
        $this->updated_at = $updated_at;

        return $this;
    }

    public function toArray()
    {
        return get_object_vars($this);
    }
}
