<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Util\Player;

use srag\Plugins\Opencast\API\OpencastAPI;
use srag\Plugins\Opencast\Model\Config\PluginConfig;

/**
 * Class LivePlayerDataBuilder
 * @package srag\Plugins\Opencast\Util\Player
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class LivePlayerDataBuilder extends PlayerDataBuilder
{
    /**
     * @return array{streams: array<int, array{content: string, sources: array{hls: array<int, array{src: mixed, mimetype: mixed}>}}>, metadata: array{title: string}}
     */
    public function buildStreamingData(): array
    {
        $episode_data = $this->api->routes()->search->getEpisodes(
            [
                'id' => $this->event->getIdentifier()
            ],
            OpencastAPI::RETURN_ARRAY
        );

        //Temporary fix until this issue is fixed in the opencast-php-library:
        //https://github.com/elan-ev/opencast-php-library/issues/33
        if (array_key_exists('search-results', $episode_data)) {
            $media_package = $episode_data['search-results']['result']['mediapackage'];
        } else {
            $media_package = $episode_data['result'][0]['mediapackage'];
        }

        $source_format = PluginConfig::getConfig(PluginConfig::F_LIVESTREAM_BUFFERED) ? 'hls' : 'hlsLive';
        $streams = [];
        if (isset($media_package['media']['track'][0])) {  // multi stream
            foreach ($media_package['media']['track'] as $track) {
                $role = strpos($track['type'], self::ROLE_MASTER) !== false ? self::ROLE_MASTER : self::ROLE_SLAVE;
                $streams[$role] = [
                    "content" => $role,
                    "sources" => [
                        $source_format => [
                            [
                                "src" => $track['url'],
                                "mimetype" => $track['mimetype']
                            ]
                        ]
                    ]
                ];
                if (isset($track['video']['resolution'])) {
                    $streams[$role]['sources'][$source_format][0]['res'] = $this->getConsumableResolution(
                        $track['video']['resolution']
                    );
                }
            }
        } else {    // single stream
            $track = $media_package['media']['track'];
            $streams[] = [
                "content" => self::ROLE_MASTER,
                "sources" => [
                    $source_format => [
                        [
                            "src" => $track['url'],
                            "mimetype" => $track['mimetype']
                        ]
                    ]
                ]
            ];
            if (isset($track['video']['resolution'])) {
                $streams[0]['sources'][$source_format][0]['res'] = $this->getConsumableResolution(
                    $track['video']['resolution']
                );
            }
        }

        return [
            "streams" => array_values($streams),
            "metadata" => [
                "title" => $this->event->getTitle(),
                "preview" => ILIAS_HTTP_PATH . ltrim($this->event->publications()->getThumbnailUrl(), '.'),
                "videoid" => $this->event->getIdentifier() ?? '',
                "seriesid" => $this->event->getSeriesIdentifier() ?? ''
            ],
        ];
    }

    private function getConsumableResolution($resolution): array
    {
        $video_res = [
            "w" => '1920',
            "h" => '1080'
        ];
        $resolution_arr = explode('x', $resolution);
        if (count($resolution_arr) == 2) {
            $video_res['w'] = $resolution_arr[0];
            $video_res['h'] = $resolution_arr[1];
        }
        return $video_res;
    }
}
