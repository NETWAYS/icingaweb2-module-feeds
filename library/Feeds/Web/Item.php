<?php

namespace Icinga\Module\Feeds\Web;

use Icinga\Web\Helper\HtmlPurifier;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Attributes;
use ipl\Html\HtmlElement;
use ipl\Html\HtmlString;
use ipl\Html\Text;
use ipl\Web\Widget\Icon;

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
        } else {
            return HtmlElement::create(
                'span',
                Attributes::create([]),
                [
                    $this->item->title,
                ]
            );
        }
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
        } else {
            return new Icon(
                'rss',
                Attributes::create([
                    'class' => 'feed-item-icon',
                ])
            );
        }
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
        $text = HtmlPurifier::process($text);
        $description = new HtmlString($text);

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

    protected function assemble(): void
    {
        $classes = ['feed-item'];
        if ($this->compact) {
            $classes[] = 'compact';
        }
        $hasLink = $this->getLink() !== null;
        $this->addHtml(
            HtmlElement::create(
                'div',
                Attributes::create([
                    'class' => join(' ', $classes),
                ]),
                [
                    HtmlElement::create(
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
                    ),
                    $this->compact ? null : [
                        $this->getCategoriesElement(),
                        $this->getContentElement(),
                    ],
                ]
            )
        );
    }
}
