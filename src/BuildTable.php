<?php

namespace Builds;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use WP_List_Table;
use WP_Query;

class BuildTable extends WP_List_Table
{
    /**
     * Querystring arguments for the modal url
     */
    const MODAL_ARGS = [
        'modal_window' => true,
        'page' => 'build_logs',
        'TB_iframe' => true,
        'width' => 1200,
        'height' => 600,
    ];
    
    /**
     * Returns a static instance
     */
    public static function make(): static
    {
        return new static;
    }

    public function render()
    {
        $perPage        = 20;
        $columns        = $this->get_columns();
        $hidden         = $this->get_hidden_columns();
        $sortable       = $this->get_sortable_columns();
        $currentPage    = $this->get_pagenum();
        $tableData      = $this->table_data($perPage, $currentPage, $_REQUEST['s'] ?? null);

        $this->set_pagination_args([
            'total_items' => $tableData['meta']['total'],
            'per_page'    => $perPage
        ]);

        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->items = $tableData['data'];
    }

    /**
     * Override the parent columns method. Defines the columns to use in your listing table
     *
     * @return array
     */
    public function get_columns(): array
    {
        return [
            'url'           => 'Deployment URL',
            'status'        => $this->center('Status'),
            'duration'      => $this->center('Duration'),
            'date'          => $this->center('Date'),
            'version'       => $this->center('Version'),
            'actions'       => $this->center('Actions'),
        ];
    }

    /**
     * Define which columns are hidden
     *
     * @return array
     */
    public function get_hidden_columns(): array
    {
        return [];
    }

    /**
     * Define the sortable columns
     *
     * @return array
     */
    public function get_sortable_columns(): array
    {
		return [];
    }

    /**
     * Get the table data
     *
     * @return array
     */
    private function table_data($perPage, $page = 1, $search = null)
    {
        $args = [
            'post_type'         => 'vercel_builds',
            'posts_per_page'    => $perPage,
            'paged'             => $page,
            'orderby'           => 'date',
        ];

        if (! empty($_REQUEST['s'])) {
            $args['meta_query'] = [
                'relation' => 'OR',
                [
                    'key'     => 'url',
                    'value'   => $search,
                    'compare' => 'LIKE'
                ],
                [
                    'key'     => 'commit',
                    'value'   => $search,
                    'compare' => 'LIKE'
                ]
            ];
        }

        $query = new WP_Query($args);
        
        $deployments = collect($query->get_posts())
            // ensure deployment is an array and enrich with additional metadata
            ->map(function ($deployment) {
                $deployment = (array) $deployment;
                foreach(['url', 'status', 'start', 'end', 'commit'] as $field) {
                    $deployment[$field] = get_post_meta($deployment['ID'], $field, true);
                }

                return $deployment;
            })
            // table friendly format
            ->map(fn ($deployment) => [
                'url'       => $this->formatUrl($deployment['url']),
                'status'    => $this->formatStatus($deployment['status']),
                'duration'  => $this->formatDuration($deployment['start'], $deployment['end']),
                'date'      => date('d/m/y H:i:s', $deployment['start']),
                'version'   => $this->formatCommit($deployment['commit']),
                'statusCode'=> $deployment['status'],
                'post_id'   => $deployment['ID'],
                'actions'   => $this->getActions($deployment)
            ]);

        return [
            'data' => $deployments,
            'meta' => [
                'total' => $query->found_posts,
            ],
        ];
    }

    /**
     * Retrieve deployment available actions
     */
    protected function getActions(array $deployment): string
    {
        if ($deployment['status'] === 'deployment.error') {
            // View Logs
            $querystring = Arr::query(static::MODAL_ARGS);
            $deploymentId = sprintf('deployment_id=%1$s', $deployment['ID']);
            $url = admin_url("admin.php?{$deploymentId}&{$querystring}");
            
            return sprintf('<a href="%1$s" style="width:36px;display:flex;align-items:center;justify-content:center;" class="thickbox button"><span class="dashicons dashicons-search"></span></a>', $url);
        }

        return '';
    }

    /**
     * Define what data to show on each column of the table
     *
     * @param  array $item        Data
     * @param  string $columnName - Current column name
     *
     * @return mixed
     */
    public function column_default($item, $columnName)
    {
        switch ($columnName) {
            case 'url':
                return $item[$columnName] ?? '-';
            default:
                return $this->center($item[$columnName] ?? '-');
        }
    }

    /**
     * Generates admin actions for each row
     *
     * @return mixed
     */
    protected function handle_row_actions($item, $columnName, $primary)
    {
        $actions = [];
        
        return $this->row_actions($actions);
    }

    /**
     * Formats the status with the approriate style
     */
    private function formatStatus(string $status): string
    {
        $build = Build::status($status);

        return "<span style=\"color:{$build->color}\">{$build->text}</span>";
    }

    /**
     * Ensure the url has a schema and that it does not break the table width
     */
    private function formatUrl(string $url): string
    {
        return sprintf(
            '<a target="_blank" href="%1$s">%2$s</a>', 
            "https://{$url}",
            Str::limit($url, 32)
        );
    }

    /**
     * Format the build duration in the same format Vercel uses
     */
    private function formatDuration(int $start, int|string $end): string
    {
        $duration = ($end ?: time()) - $start;
        
        $format = '';
        $values = [];
        
        $hours = $duration / 3600;
        $minutes = $duration / 60 % 60;
        $seconds = $duration % 60;

        if ((int) $hours) {
            $format.='%dh ';
            $values[] = $hours;
        }

        if ((int) $minutes) {
            $format.='%dm ';
            $values[] = $minutes;
        }

        if ((int) $seconds) {
            $format.='%ds ';
            $values[] = $seconds;
        }

        return sprintf($format, ...$values);
    }

    /**
     * Make the commit a comfortable button to copy to clipboard
     */
    private function formatCommit(string $commit): string
    {
        $text = Str::limit($commit, 8, '');
        
        return "
        <button type=\"button\" onclick=\"copyToClipboard(this)\" value=\"{$commit}\">
            {$text}
        </button>
        ";
    }

    /**
     * Add center tags
     */
    private function center(string $string): string
    {
        return "<center>{$string}</center>";
    }
}
