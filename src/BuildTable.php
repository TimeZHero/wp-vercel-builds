<?php

namespace Builds;

use Illuminate\Support\Str;
use WP_List_Table;
use WP_Query;

class BuildTable extends WP_List_Table
{
    public static function make()
    {
        return new static;
    }

    public function render()
    {
        $perPage        = 10;
        $columns        = $this->get_columns();
        $hidden         = $this->get_hidden_columns();
        $sortable       = $this->get_sortable_columns();
        $currentPage    = $this->get_pagenum();
        $tableData      = $this->table_data($perPage, $currentPage);
        $data           = $tableData['data'];

        $this->set_pagination_args([
            'total_items' => $tableData['meta']['total'],
            'per_page'    => $perPage
        ]);

        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->items = $data;
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
            'version'       => $this->center('Version')
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
    private function table_data($per_page, $page = 1)
    {
        $deployments = new WP_Query([
            'post_type' => 'vercel_builds',
            'posts_per_page' => 10,
            'no_found_rows' => true,
            'orderby' => 'date',
        ]);
        
        $deployments = collect($deployments->get_posts())
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
                'duration'  => $this->formatDuration($deployment['start'] / 1000, $deployment['end']),
                'date'      => date('d/m/y H:i:s', $deployment['start'] / 1000),
                'version'   => $this->formatCommit($deployment['commit'])
            ]);

        return [
            'data' => $deployments,
            'meta' => [
                'total' => count($deployments),
            ],
        ];
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
    protected function handle_row_actions($item, $column_name, $primary)
    {
        return $this->row_actions([]);
    }

    private function formatStatus($status)
    {
        $statusMap = [
            'deployment.created' => [
                'text' => 'Building',
                'color' => 'blue',
            ],
            'deployment.canceled' => [
                'text' => 'Canceled',
                'color' => 'orange',
            ],
            'deployment.error' => [
                'text' => 'Error',
                'color' => 'red',
            ],
            'deployment.succeeded' => [
                'text' => 'Completed',
                'color' => 'green',
            ],
        ];

        return "<span style=\"color:{$statusMap[$status]['color']}\">{$statusMap[$status]['text']}</span>";
    }

    /**
     * Ensure the url has a schema and that it does not break the table width
     */
    private function formatUrl(string $url): string
    {
        $shortUrl = Str::limit($url, 37);
        $url = Str::start($url, 'https://');
        
        return sprintf('<a target="_blank" href="%1$s">%2$s</a>', $url, $shortUrl);
    }

    /**
     * Format the build duration in the same format Vercel uses
     */
    private function formatDuration(int $start, int|string $end): string
    {
        $duration = $end 
            ? ($end / 1000) - $start
            : time() - $start;
        
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

?>

<script>
function copyToClipboard(element) {
    navigator.clipboard.writeText(element.getAttribute('value'));
}
</script>
