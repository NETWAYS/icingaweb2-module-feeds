<?php

namespace Icinga\Module\Feeds\Web;

use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\TimeAgo;

use Icinga\Module\Feeds\Parser\Result\FeedItem;

class Item extends BaseHtmlElement
{
    protected $tag = 'li';

    public function __construct(
        protected FeedItem $item,
        protected bool     $compact,
    ) {
    }

    protected function getTitleElement(): HtmlElement
    {
        $title = $this->item->title;

        if ($title === null || $title === '') {
            return HtmlElement::create(
                'span',
                Attributes::create([
                    'class' => 'text-dim',
                ]),
                [
                    'No title provided',
                ]
            );
        }

        return HtmlElement::create(
            'span',
            Attributes::create([]),
            [
                $this->item->title,
            ]
        );
    }

    protected function getIconElement(): BaseHtmlElement
    {
        $image = $this->item->image ?? $this->item->feed->image;

        if ($image) {
            return HtmlElement::create(
                'img',
                Attributes::create([
                    'class' => 'feed-item-icon',
                    'src' => $image,
                ])
            );
        }

        return new Icon(
            'rss',
            Attributes::create([
                'class' => 'feed-item-icon',
            ])
        );
    }

    protected function getCategoriesElement(): ?BaseHtmlElement
    {
        $elements = [];

        if ($this->item->creator !== null) {
            $elements[] = HtmlElement::create(
                'span',
                Attributes::create([
                    'class' => 'feed-item-creator',
                ]),
                $this->item->creator,
            );
        }

        foreach ($this->item->categories as $category) {
            $elements[] = HtmlElement::create(
                'span',
                Attributes::create([
                    'class' => 'feed-item-category',
                ]),
                $category,
            );
        }

        return HtmlElement::create(
            'div',
            Attributes::create([
                'class' => 'feed-item-categories',
            ]),
            $elements,
        );
    }

    protected function getContentElement(): ?BaseHtmlElement
    {
        $text = $this->item->description;
        $description = new FeedContent($text);

        return HtmlElement::create(
            'div',
            Attributes::create([
                'class' => 'feed-content-wrapper',
            ]),
            $description,
        );
    }

    protected function getLink(): ?string
    {
        return $this->item->link ?? $this->item->feed->link;
    }

    protected function getDate(): BaseHtmlElement
    {
        return (new TimeAgo($this->item->date->getTimestamp()));
    }

    protected function assembleHeader(): BaseHtmlElement
    {
        $header = HtmlElement::create('header');

        $title = HtmlElement::create(
            'div',
            Attributes::create(['class' => 'feed-item-title']),
        );

        $hasLink = $this->getLink() !== null;

        $title->add(HtmlElement::create(
            $hasLink ? 'a' : 'span',
            Attributes::create([
                'class' => 'feed-item-info',
                'target' => '_blank',
                'href' => $this->getLink(),
            ]),
            [
                $this->getIconElement(),
                $this->getTitleElement(),
            ]
        ));

        $header->add($title);

        // Once we migrate to IPL ItemRenderer this goes into assembleExtendedInfo
        $extendedInfo = HtmlElement::create(
            'div',
            Attributes::create(['class' => 'feed-item-extended-info text-dim']),
            [
                $this->getDate()
            ],
        );

        $header->add($extendedInfo);

        return $header;
    }

    protected function assembleContent(): BaseHtmlElement
    {
        $content = HtmlElement::create('section');

        $content->add($this->getContentElement());

        return $content;
    }

    protected function assemble(): void
    {
        $classes = ['feed-item'];

        if ($this->compact) {
            $classes[] = 'compact';
        }

        $container = HtmlElement::create('div', Attributes::create(['class' => join(' ', $classes),]));

        $container->addHtml($this->assembleHeader());

        if (!$this->compact) {
            $container->add($this->getCategoriesElement());
            $container->addHtml($this->assembleContent());
        }

        $this->addHtml($container);
    }
}
