<?php

namespace LeadMax\TrackYourStats\Report\Filters;


use Carbon\Carbon;
use LeadMax\TrackYourStats\Table\Assignments;

class ClickLink implements Filter
{

    public $clicksArrayKey;

    public $offerIdArrayKey;

    public $assign;

    public $pathTemplate;

    public $extraQuery;

    public function __construct(
        $assign,
        $clicksArrayKey = "Clicks",
        $offerIdArrayKey = "idoffer",
        $pathTemplate = "/offer/{id}/clicks",
        array $extraQuery = ['filter' => 'affiliate']
    )
    {
        $this->assign = $assign;

        $this->clicksArrayKey = $clicksArrayKey;

        $this->offerIdArrayKey = $offerIdArrayKey;

        $this->pathTemplate = $pathTemplate;

        $this->extraQuery = $extraQuery;
    }

    public function filter($data)
    {
        $i = 0;
        $count = count($data);
        foreach ($data as $key => $row) {
            $i++;
            if (isset($row[$this->clicksArrayKey], $row[$this->offerIdArrayKey]) && $i !== $count) {
                $query = array_merge([
                    'd_from' => $this->assign->get("d_from", Carbon::today()->format('Y-m-d')),
                    'd_to' => $this->assign->get("d_to", Carbon::today()->format('Y-m-d')),
                    'dateSelect' => $this->assign->get("dateSelect", 0),
                ], $this->extraQuery);
                foreach (['role', 'adminLogin'] as $contextKey) {
                    if ($this->assign->get($contextKey) !== null) {
                        $query[$contextKey] = $this->assign->get($contextKey);
                    }
                }
                $path = str_replace('{id}', rawurlencode((string) $row[$this->offerIdArrayKey]), $this->pathTemplate);
                $replaced = "<a class='load_click' href=\"{$path}?".http_build_query($query)."\">{$row[$this->clicksArrayKey]}</a>";
                $data[$key][$this->clicksArrayKey] = $replaced;
            }
        }

        return $data;
    }


}
