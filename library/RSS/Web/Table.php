<?php

namespace Icinga\Module\RSS\Web;

use ipl\Html\BaseHtmlElement;
use ipl\Html\Attributes;
use ipl\Html\HtmlElement;

class Table extends BaseHtmlElement
{
    protected $tag = 'table';

    public function __construct(
        protected array $data,
    ) {
        $this->attributes = new Attributes([
            'class' => 'common-table'
        ]);
    }

    protected function assemble(): void
    {
        $columns = [];
        foreach ($this->data as $row) {
            foreach($row as $key => $value) {
                if (!in_array($key, $columns)) {
                    $columns[] = $key;
                }
            }
        }

        $headers = [];
        foreach ($columns as $column) {
            $headers[] = HtmlElement::create(
                'th',
                Attributes::create([]),
                $column
            );
        }

        $tableHead = HtmlElement::create(
            'thead',
            null,
            HtmlElement::create(
                'tr',
                null,
                $headers
            )
        );

        $this->addHtml($tableHead);

        $rows = [];
        foreach ($this->data as $row) {
            $rowElements = [];
            foreach ($columns as $column) {
                if (array_key_exists($column, $row)) {
                    $rowElements[] = HtmlElement::create('td', null, [$row[$column]]);
                } else {
                    $rowElements[] = HtmlElement::create('td', null, ['']);
                }
            }
            $rows[] = HtmlElement::create('tr', null, $rowElements);
        }

        $tableBody = HtmlElement::create('tbody', null, $rows);
        $this->addHtml($tableBody);
    }
}
