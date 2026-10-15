<?php

namespace App\Support\Video;

/**
 * Reads the running time of an MP4 file without ffmpeg.
 *
 * An MP4 is a tree of "boxes", each a 4-byte size, a 4-byte type and a body.
 * The length of the film lives in one of them — `moov` → `mvhd` — as a
 * duration counted in ticks plus the number of ticks per second. Walking the
 * boxes to that header takes a few dozen reads however large the file is,
 * because `mdat` (the actual video data) is skipped over rather than read.
 *
 * Covers MP4, M4V and QuickTime .mov, which share the format. Returns null
 * for anything it cannot make sense of, and callers treat that as "could not
 * be checked" rather than guessing.
 */
final class Mp4Duration
{
    /** Boxes that hold other boxes, which the search descends into. */
    private const CONTAINERS = ['moov'];

    public static function seconds(string $path): ?float
    {
        $handle = @fopen($path, 'rb');

        if ($handle === false) {
            return null;
        }

        try {
            $size = filesize($path) ?: 0;

            return self::search($handle, 0, $size);
        } finally {
            fclose($handle);
        }
    }

    /** @param resource $handle */
    private static function search($handle, int $start, int $end): ?float
    {
        $offset = $start;

        // A real file has a handful of top-level boxes; the cap stops a
        // corrupt one from looping for ever.
        for ($guard = 0; $offset + 8 <= $end && $guard < 10_000; $guard++) {
            fseek($handle, $offset);
            $header = fread($handle, 8);

            if ($header === false || strlen($header) < 8) {
                return null;
            }

            $boxSize = unpack('N', substr($header, 0, 4))[1];
            $type = substr($header, 4, 4);
            $headerLength = 8;

            if ($boxSize === 1) {
                // 64-bit size follows the type.
                $large = fread($handle, 8);

                if ($large === false || strlen($large) < 8) {
                    return null;
                }

                $boxSize = unpack('J', $large)[1];
                $headerLength = 16;
            } elseif ($boxSize === 0) {
                // Runs to the end of the file.
                $boxSize = $end - $offset;
            }

            if ($boxSize < $headerLength) {
                return null;
            }

            if ($type === 'mvhd') {
                return self::readMovieHeader($handle, $offset + $headerLength);
            }

            if (in_array($type, self::CONTAINERS, true)) {
                $found = self::search($handle, $offset + $headerLength, min($end, $offset + $boxSize));

                if ($found !== null) {
                    return $found;
                }
            }

            $offset += $boxSize;
        }

        return null;
    }

    /** @param resource $handle */
    private static function readMovieHeader($handle, int $bodyOffset): ?float
    {
        fseek($handle, $bodyOffset);
        $body = fread($handle, 32);

        if ($body === false || strlen($body) < 20) {
            return null;
        }

        $version = ord($body[0]);

        if ($version === 1) {
            // version/flags(4) created(8) modified(8) timescale(4) duration(8)
            if (strlen($body) < 32) {
                return null;
            }

            $timescale = unpack('N', substr($body, 20, 4))[1];
            $duration = unpack('J', substr($body, 24, 8))[1];
        } else {
            // version/flags(4) created(4) modified(4) timescale(4) duration(4)
            $timescale = unpack('N', substr($body, 12, 4))[1];
            $duration = unpack('N', substr($body, 16, 4))[1];
        }

        if ($timescale <= 0) {
            return null;
        }

        return $duration / $timescale;
    }
}
