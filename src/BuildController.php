<?php

namespace Builds;

use WP_Error;
use WP_Query;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;

class BuildController extends WP_REST_Controller
{
    /**
     * Add or update a build
     */
    public static function update(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $body = json_decode($request->get_body());

        // update current build status
        update_option('vercel_current_build', $body->type);

        $builds = new WP_Query([
            'post_type'         => 'vercel_builds',
            'post_name'         => $body->payload->deployment->id,
            'no_found_rows'     => true,
            'posts_per_page'    => 1,
            'meta_key'          => 'url',
            'meta_value'        => $body->payload->url,
        ]);

        $builds = $builds->get_posts();

        // if the build already exists, update it
        if (count($builds)) {
            return rest_ensure_response(wp_update_post([
                'ID' => $builds[0]->ID,
                'meta_input' => [
                    'end'       => static::millisecondsToSeconds($body->createdAt),
                    'status'    => $body->type
                ]
            ]));
        }

        // add a new build
        return rest_ensure_response(wp_insert_post([
            'post_title' => 'Vercel Deployment',
            'post_type' => 'vercel_builds',
            'post_name' => $body->payload->deployment->id,
            'post_status' => 'publish',
            'meta_input' => [
                'url'       => $body->payload->url,
                'commit'    => $body->payload->deployment->meta->gitlabCommitSha,
                'start'     => static::millisecondsToSeconds($body->createdAt),
                'status'    => $body->type
            ]
        ]));
    }

    /**
     * Answer with the current build status
     */
    public static function poll(): WP_REST_Response
    {
        // defaults
        $code = 404;
        $data = [];
        
        if ($status = get_option('vercel_current_build')) {
            $data = [
                'status' => $status,
                'badge' => Build::status($status)->badge,
            ];
            
            $code = 200;
        }
        
        return new WP_REST_Response((object) $data, $code, ['Content-type' => 'application/json']);
    }

    /**
     * Converts milliseconds to seconds
     */
    private static function millisecondsToSeconds(int $milliseconds): int
    {
        return (int) round($milliseconds, -3) / 1000;
    }
}
