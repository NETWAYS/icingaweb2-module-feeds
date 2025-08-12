<?php

namespace Icinga\Module\RSS\Web;

use ipl\Html\BaseHtmlElement;
use ipl\Html\Attributes;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Web\Widget\Icon;

use Icinga\Module\RSS\Parser\Result\RSSItem;

class Item extends BaseHtmlElement
{
    protected $tag = 'li';

    public function __construct(
        protected RSSItem $item,
        protected bool $compact,
    ) {}

    protected function getTitleElement(): HtmlElement
    {
        $title = $this->item->title;
        if ($title === null || $title === '') {
            return HtmlElement::create(
                'span',
                Attributes::create([
                    'class' => 'text-dim',
                ]), [
                    'No title provided',
                ]);
        } else {
            return HtmlElement::create(
                'span',
                Attributes::create([]), [
                    $this->item->title,
                ]);
        }
    }

    protected function getIconElement(): BaseHtmlElement
    {
        $image = $this->item->image ?? $this->item->channel->image;
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

    protected function getContentElement(): ?BaseHtmlElement
    {
        // FIXME: This is horribly insecure
        $description = new Text($this->item->description);
        $description->setEscaped(true);
        return HtmlElement::create('div',
            Attributes::create([
                'class' => 'feed-content-wrapper',
            ]),
            $description,
        );
    }

    protected function getLink(): ?string
    {
        return $this->item->link ?? $this->item->channel->link;
    }

    protected function assemble(): void
    {
        $classes = ['feed-item'];
        if ($this->compact) {
            $classes[] = 'compact';
        }
        $displayContent = !$this->compact && $this->item->description != null;
        $hasLink = $this->getLink() !== null;
        $this->addHtml(
            HtmlElement::create(
                'div',
                Attributes::create([
                    'class' => join(' ', $classes),
                ]), [
                    HtmlElement::create(
                        $hasLink ? 'a' : 'span',
                        Attributes::create([
                            'class' => 'feed-item-info',
                            'target' => '_blank',
                            'href' => $this->getLink(),
                        ]), [
                            $this->getIconElement(),
                            $this->getTitleElement(),
                        ]
                    ),
                    $displayContent ? $this->getContentElement() : null,
                ]
            )
        );
    }
}
