<?php

namespace Perevorot\Users\Traits;

/**
 * Class UserModelTrait
 * @package Perevorot\Users\Traits
 */
trait UserModelTrait
{
    /**
     * @var array
     */
    public $dynamicFields = [
        'is_search_access',
        'is_overdraft',
        'is_accelerator',
        'is_gov',
        'is_go',
        'is_test',
        'company_address',
        'company_index',
        'company_city',
        'company_region',
        'company_country',
        'director_position',
        'director_fio',
        'director_document',
        'payer',
        'payer_code',
        'supplier',
        'customer',
        'contact_fio',
        'contact_position',
        'contact_email',
        'contact_office_phone',
        'contact_mobile_phone',
    ];

    var $is_processed=false;

    /**
     * @return void
     */
    public function afterFetch()
    {
        if(!isset($this->attributes['data']))
        {
            return null;
        }

        $fields = (array) json_decode($this->attributes['data'], true);

        foreach ($fields as $field => $data) {
            if (!in_array($field, $this->dynamicFields)) {
                continue;
            }

            $this->attributes[$field] = $data;
        }

        $this->is_processed=true;
    }

    /**
     * @return void
     */
    public function beforeSave()
    {
        $processed=false;

        foreach($this->dynamicFields as $field){
            if(array_key_exists($field, $this->attributes))
                $processed=true;
        }

        $dynamicFields = [];

        foreach ($this->attributes as $field => $value) {
            if (!in_array($field, $this->dynamicFields)) {
                continue;
            }

            $dynamicFields[$field] = $value;
            unset($this->attributes[$field]);
        }

        if(empty($this->data)) {
            $this->data = json_encode([]);
        }

        $this->attributes['data'] = $processed ? json_encode($dynamicFields) : $this->data;
    }

    /**
     * @param $fields
     * @return void
     */
    public function processFields($fields)
    {
        foreach ($fields as $field => $value) {
            $this->attributes[$field] = $value;
        }

        $this->is_processed=true;
    }

    /**
     * @param $fields
     * @return void
     */
    public function exportFieldsMutators()
    {
        $data=json_decode($this->data);

        foreach ($this->dynamicFields as $field => $value) {
            if(!empty($data->{$value})){
                $this->attributes[$value] = $data->{$value};
            }
        }
    }
}