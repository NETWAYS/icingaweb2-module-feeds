<?php

namespace Icinga\Module\RSS\Web;

use ipl\Html\BaseHtmlElement;
use ipl\Html\Attributes;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Web\Widget\Icon;

use Icinga\Module\RSS\RSSChannel;
use Icinga\Module\RSS\RSSItem;

class Item extends BaseHtmlElement
{
    protected $tag = 'li';

    public function __construct(
        protected RSSChannel $channel,
        protected RSSItem $item,
        protected bool $compact,
    ) {}

    protected function assemble(): void
    {
        $image = $this->item->image ?? $this->channel->image;
        $iconElement = null;
        if ($image) {
            $iconElement = HtmlElement::create('img',
                Attributes::create([
                    'class' => 'feed-item-icon',
                    'src' => $image,
                ])
            );
        } else {
            $iconElement = new Icon(
                'rss',
                Attributes::create([
                    'class' => 'feed-item-icon',
                ])
            );
        }

        $contentElement = null;
        if (!$this->compact && $this->item->description != null) {
            // FIXME: This is horribly insecure
            $description = new Text($this->item->description);
            $description->setEscaped(true);
            $contentElement = HtmlElement::create('div',
                Attributes::create([
                    'class' => 'feed-content-wrapper',
                ]),
                $description,
            );
        }

        $classes = ['feed-item'];
        if ($this->compact) {
            $classes[] = 'compact';
        }
        $this->addHtml(
            HtmlElement::create('div',
                Attributes::create([
                    'class' => join(' ', $classes),
                ]), [
                    HtmlElement::create('a',
                        Attributes::create([
                            'class' => 'feed-item-info',
                            'target' => '_blank',
                            'href' => $this->item->link,
                        ]), [
                            $iconElement,
                            $this->item->title,
                        ]
                    ),
                    $contentElement,
                ]
            )
        );
    }
}
