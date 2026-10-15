<?php

namespace App\Support\Video;

/**
 * Turns a YouTube or Vimeo page link into the address of its embeddable
 * player.
 *
 * Staff paste whatever is in their address bar — a watch page, a youtu.be
 * share link, a Short — so every common shape is accepted. YouTube is served
 * from its no-cookie domain: the video plays the same, and visitors are not
 * tracked by YouTube until they press play.
 */
final class VideoEmbed
{
    /**
     * @return array{provider: string, id: string, embed: string}|null
     */
    public static function from(?string $url): ?array
    {
        $url = trim((string) $url);

        if ($url === '') {
            return null;
        }

        $patterns = [
            // youtube.com/watch?v=ID, youtube.com/embed/ID, /shorts/ID, /live/ID, youtu.be/ID
            'youtube' => '~^(?:https?://)?(?:www\.|m\.)?(?:youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/)|youtu\.be/|youtube-nocookie\.com/embed/)([A-Za-z0-9_-]{11})~i',
            // vimeo.com/123456, vimeo.com/channels/x/123456, player.vimeo.com/video/123456
            'vimeo' => '~^(?:https?://)?(?:www\.|player\.)?vimeo\.com/(?:video/|channels/[^/]+/|groups/[^/]+/videos/)?(\d{6,12})~i',
        ];

        foreach ($patterns as $provider => $pattern) {
            if (preg_match($pattern, $url, $match)) {
                $id = $match[1];

                return [
                    'provider' => $provider,
                    'id' => $id,
                    'embed' => $provider === 'youtube'
                        ? 'https://www.youtube-nocookie.com/embed/'.$id.'?rel=0&modestbranding=1&playsinline=1'
                        : 'https://player.vimeo.com/video/'.$id.'?dnt=1',
                ];
            }
        }

        return null;
    }
}
