<?php

namespace Icinga\Module\RSS\Web;

use ipl\Html\BaseHtmlElement;
use ipl\Html\Attributes;
use ipl\Html\HtmlElement;
use ipl\Web\Widget\Link;

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
                if (str_starts_with($key, '_')) {
                    continue;
                }

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
            $link = $row['_link'] ?? null;
            foreach ($columns as $column) {
                $text = '';
                if (array_key_exists($column, $row)) {
                    $text = $row[$column];
                }

                if ($link !== null) {
                    $text = new Link($text, $link, Attributes::create([
                        'data-base-target' => '_next',
                    ]));
                }
                $rowElements[] = HtmlElement::create('td', null, $text);
            }
            $rows[] = HtmlElement::create('tr', null, $rowElements);
        }

        $tableBody = HtmlElement::create('tbody', null, $rows);
        $this->addHtml($tableBody);
    }
}
