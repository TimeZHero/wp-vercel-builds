<?php

namespace Builds;

class Build
{
    /**
     * The text displayed in place of the status
     */
    public string $text;

    /**
     * The color of the status text
     */
    public string $color;

    /**
     * The badge image displayed
     */
    public string $badge;

    public static function status(string $status): static
    {
        return new static($status);
    }

    public function __construct(public string $status)
    {
        switch($status) {
            case 'deployment.created':
                $this->text  = 'Building';
                $this->color = 'blue';
                $this->badge = $this->badgeUrl('created');
                break;
            case 'deployment.canceled':
                $this->text  = 'Canceled';
                $this->color = 'orange';
                $this->badge = $this->badgeUrl('canceled');
                break;
            case 'deployment.error':
                $this->text  = 'Error';
                $this->color = 'red';
                $this->badge = $this->badgeUrl('error');
                break;
            case 'deployment.succeeded':
                $this->text  = 'Completed';
                $this->color = 'green';
                $this->badge = $this->badgeUrl('succeeded');
                break;
            default:
                $this->text  = 'Unknown';
                $this->color = 'grey';
                $this->badge = $this->badgeUrl('default');
                break;
        }
    }

    /**
     * Whether or not it is a new build event
     */
    public function isNewBuild(): bool
    {
        return $this->status === 'deployment.created';
    }

    /**
     * Whether or not the build was not executed correctly
     */
    public function hasIssues(): bool
    {
        return $this->status === 'deployment.error';
    }

    /**
     * Return the url of the requested badge
     */
    private function badgeUrl(string $slug): string
    {
        return plugins_url('vercel-builds')."/src/assets/img/{$slug}.svg";
    }
}
