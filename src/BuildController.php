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
                    'end'       => $body->createdAt,
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
                'start'     => $body->createdAt,
                'status'    => $body->type
            ]
        ]));
    }

    /**
     * Answer with the current build status
     */
    public static function poll()
    {
        // TODO:
    }
}
