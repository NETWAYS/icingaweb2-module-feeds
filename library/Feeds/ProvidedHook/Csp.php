<?php

namespace Icinga\Module\Feeds\ProvidedHook;

use Icinga\Application\Hook\CspHook;
use Icinga\Authentication\Auth;
use Icinga\Module\Feeds\Storage\StorageFactory;
use Icinga\User;
use ipl\Web\Common\Csp as CspInstance;
use ipl\Web\Url;

/**
 * Csp hook to add the feed images to the content security policy
 *
 * All users with the permission 'feeds/view' will get the CSP for all feeds.
 * This hook assumes that images are sourced from the same domain as the feed.
 */
class Csp extends CspHook
{
    public function getCspForAllUsers(): CspInstance
    {
        $csp = new CspInstance();

        $storage = StorageFactory::getStorage();
        foreach ($storage->getFeeds() as $feed) {
            $url = Url::fromPath($feed->url);
            $csp->add('img-src', $url->getScheme() . '://' . $url->getHost());
        }

        return $csp;
    }

    public function getCspForUser(User $user): CspInstance
    {
        if (Auth::getInstance()->hasPermission('feeds/view')) {
            return $this->getCspForAllUsers();
        }

        return new CspInstance();
    }
}
